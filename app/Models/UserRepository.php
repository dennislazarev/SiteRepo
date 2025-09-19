<?php
// app/Models/UserRepository.php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\QueryBuilder; // Добавлен импорт QueryBuilder
use PDO;
use PDOException;

/**
 * Репозиторий для работы с пользователями фронтенда (users)
 */
class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Получить список пользователей с фильтрацией и пагинацией
     * @param array $filters Ассоциативный массив фильтров (fio, login, phone, role_id, is_active)
     * @param int $limit Количество записей на страницу
     * @param int $offset Смещение
     * @param string|null $orderBy Поле для сортировки
     * @param string $orderDir Направление сортировки (ASC или DESC)
     * @return array Массив пользователей
     */
    public function getAll(array $filters = [], int $limit = 20, int $offset = 0, ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        try {
            // Использование QueryBuilder для построения запроса
            $qb = new QueryBuilder();
            $qb->select('u.*, ur.display_name as role_display_name')
               ->from('users', 'u')
               ->leftJoin('user_roles ur', 'u.role_id = ur.id')
               ->where('u.deleted_at IS NULL');

            // Применение фильтров
            if (!empty($filters['fio'])) {
                $qb->where('u.fio LIKE ?', '%' . $filters['fio'] . '%');
            }
            if (!empty($filters['login'])) {
                $qb->where('u.login LIKE ?', '%' . $filters['login'] . '%');
            }
            if (!empty($filters['phone'])) {
                // Для поиска по телефону уберем маску и сравним только цифры
                $phoneDigits = preg_replace('/\D/', '', $filters['phone']);
                if ($phoneDigits) {
                     $qb->where("REPLACE(REPLACE(REPLACE(REPLACE(u.phone, '+', ''), ' ', ''), '-', ''), '(', '') LIKE ?", '%' . $phoneDigits . '%');
                }
            }
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $qb->where('u.is_active = ?', (int)$filters['is_active']);
            }
            if (!empty($filters['role_id'])) {
                $qb->where('u.role_id = ?', (int)$filters['role_id']);
            }

            // Сортировка
            if ($orderBy && in_array($orderBy, ['id', 'fio', 'login', 'phone', 'is_active', 'created_at', 'subscription_ends_at'])) {
                // Добавляем role_display_name в список разрешенных полей для сортировки
                if ($orderBy === 'role_display_name') {
                     $qb->orderBy('ur.display_name', $orderDir);
                } else {
                     $qb->orderBy("u.$orderBy", $orderDir);
                }
            } else {
                $qb->orderBy('u.id', 'ASC');
            }

            // Пагинация
            $qb->limit($limit)->offset($offset);

            $sql = $qb->toSql();
            $params = $qb->getParams();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserRepository::getAll() ошибка БД: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Найти пользователя по ID
     * @param int $id ID пользователя
     * @return array|null Ассоциативный массив с данными пользователя или null
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.*, ur.display_name as role_display_name 
                 FROM users u 
                 LEFT JOIN user_roles ur ON u.role_id = ur.id 
                 WHERE u.id = ? AND u.deleted_at IS NULL 
                 LIMIT 1"
            );
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserRepository::findById($id) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти пользователя по UUID
     * @param string $uuid UUID пользователя
     * @return array|null Ассоциативный массив с данными пользователя или null
     */
    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.*, ur.display_name as role_display_name 
                 FROM users u 
                 LEFT JOIN user_roles ur ON u.role_id = ur.id 
                 WHERE u.uuid = ? AND u.deleted_at IS NULL 
                 LIMIT 1"
            );
            $stmt->execute([$uuid]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserRepository::findByUuid($uuid) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти пользователя по логину
     * @param string $login Логин пользователя
     * @return array|null Ассоциативный массив с данными пользователя или null
     */
    public function findByLogin(string $login): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM users WHERE login = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserRepository::findByLogin($login) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти пользователя по телефону
     * @param string $phone Телефон пользователя
     * @return array|null Ассоциативный массив с данными пользователя или null
     */
    public function findByPhone(string $phone): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM users WHERE phone = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserRepository::findByPhone($phone) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверить, существует ли пользователь с таким логином (исключая ID)
     * @param string $login Логин
     * @param int|null $excludeId ID пользователя для исключения из проверки (при обновлении)
     * @return bool True если логин занят, false если свободен
     */
    public function isLoginExists(string $login, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE login = ? AND deleted_at IS NULL";
            $params = [$login];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("UserRepository::isLoginExists($login) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверить, существует ли пользователь с таким телефоном (исключая ID)
     * @param string $phone Телефон
     * @param int|null $excludeId ID пользователя для исключения из проверки (при обновлении)
     * @return bool True если телефон занят, false если свободен
     */
    public function isPhoneExists(string $phone, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE phone = ? AND deleted_at IS NULL";
            $params = [$phone];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("UserRepository::isPhoneExists($phone) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создать нового пользователя
     * @param array $data Данные пользователя
     *                    Ожидается: ['fio', 'login', 'phone', 'password_hash', 'is_active', 'role_id', 'telegram_id', 'whatsapp_id', 'viber_id', 'vk_id', 'ok_id', 'subscription_ends_at']
     * @return int|false ID нового пользователя или false в случае ошибки
     */
    public function create(array $data)
    {
        try {
            // Генерируем UUID
            $uuid = $this->generateUuid();

            $sql = "INSERT INTO users (
                        uuid, fio, login, phone, password_hash, is_active, role_id,
                        telegram_id, whatsapp_id, viber_id, vk_id, ok_id,
                        subscription_ends_at, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, NOW(), NOW()
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $uuid,
                $data['fio'] ?? '',
                $data['login'] ?? '',
                $data['phone'] ?? '',
                $data['password_hash'] ?? '',
                (int)($data['is_active'] ?? 1),
                $data['role_id'] ?? null, // Может быть NULL
                $data['telegram_id'] ?? null, // Может быть NULL
                $data['whatsapp_id'] ?? null, // Может быть NULL
                $data['viber_id'] ?? null, // Может быть NULL
                $data['vk_id'] ?? null, // Может быть NULL
                $data['ok_id'] ?? null, // Может быть NULL
                $data['subscription_ends_at'] ?? null, // Может быть NULL
            ]);

            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("UserRepository::create(...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить данные пользователя
     * @param int $id ID пользователя
     * @param array $data Данные для обновления
     *                    Ожидается: ['fio', 'login', 'phone', 'is_active', 'role_id', 'telegram_id', 'whatsapp_id', 'viber_id', 'vk_id', 'ok_id', 'subscription_ends_at']
     *                    password_hash обновляется отдельно
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE users 
                    SET fio = ?, login = ?, phone = ?, is_active = ?, role_id = ?,
                        telegram_id = ?, whatsapp_id = ?, viber_id = ?, vk_id = ?, ok_id = ?,
                        subscription_ends_at = ?, updated_at = NOW() 
                    WHERE id = ? AND deleted_at IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['fio'] ?? '',
                $data['login'] ?? '',
                $data['phone'] ?? '',
                (int)($data['is_active'] ?? 1),
                $data['role_id'] ?? null, // Может быть NULL
                $data['telegram_id'] ?? null, // Может быть NULL
                $data['whatsapp_id'] ?? null, // Может быть NULL
                $data['viber_id'] ?? null, // Может быть NULL
                $data['vk_id'] ?? null, // Может быть NULL
                $data['ok_id'] ?? null, // Может быть NULL
                $data['subscription_ends_at'] ?? null, // Может быть NULL
                $id
            ]);

            return $result !== false;
        } catch (PDOException $e) {
            error_log("UserRepository::update($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить хэш пароля пользователя
     * @param int $id ID пользователя
     * @param string $passwordHash Новый хэш пароля
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function updatePassword(int $id, string $passwordHash): bool
    {
        try {
            $sql = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$passwordHash, $id]);
        } catch (PDOException $e) {
            error_log("UserRepository::updatePassword($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * "Удалить" пользователя (soft delete)
     * @param int $id ID пользователя
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "UPDATE users SET deleted_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("UserRepository::delete($id) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить список ролей пользователей для выпадающего списка в формах
     * @return array Массив ['id' => ..., 'display_name' => ...]
     */
    public function getUserRoleOptions(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, display_name FROM user_roles WHERE deleted_at IS NULL ORDER BY level ASC, display_name ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserRepository::getUserRoleOptions() ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Получить роль пользователя по его role_id
     * @param int|null $roleId ID роли
     * @return array|null Ассоциативный массив с данными роли или null
     */
    public function getUserRoleById(?int $roleId): ?array
    {
        if ($roleId === null) {
            return null;
        }
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_roles WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$roleId]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            error_log("UserRepository::getUserRoleById($roleId) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Генерация UUID v4
     * @return string
     */
    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>
