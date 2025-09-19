<?php
// app/Models/EmployeeRepository.php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;
use App\Core\QueryBuilder;

/**
 * Репозиторий для работы с сотрудниками (employees)
 */
class EmployeeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Найти сотрудника по логину
     * @param string $login Логин сотрудника
     * @return array|null Ассоциативный массив с данными сотрудника или null
     */
    public function findByLogin(string $login): ?array
    {
        // Включаем информацию о роли
        $stmt = $this->pdo->prepare(
            "SELECT e.*, r.name as role_name, r.id as role_id 
             FROM employees e 
             LEFT JOIN roles r ON e.role_id = r.id 
             WHERE e.login = ? AND e.deleted_at IS NULL 
             LIMIT 1"
        );
        $stmt->execute([$login]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Найти сотрудника по ID
     * @param int $id ID сотрудника
     * @return array|null Ассоциативный массив с данными сотрудника или null
     */
    public function findById(int $id): ?array
    {
        // Включаем информацию о роли
        $stmt = $this->pdo->prepare(
            "SELECT e.*, r.name as role_name, r.id as role_id 
             FROM employees e 
             LEFT JOIN roles r ON e.role_id = r.id 
             WHERE e.id = ? AND e.deleted_at IS NULL 
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Обновить время последнего входа
     * @param int $id ID сотрудника
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function updateLastLogin(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE employees SET last_login = NOW() WHERE id = ? AND deleted_at IS NULL");
        return $stmt->execute([$id]);
    }

    /**
     * Получить все права, назначенные роли сотрудника
     * @param int $roleId ID роли
     * @return array Массив имен прав (например, ['user_view', 'user_create'])
     */
    public function getPermissionsForRole(int $roleId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.name 
                 FROM permissions p
                 JOIN role_permissions rp ON p.id = rp.permission_id
                 WHERE rp.role_id = ? AND rp.is_allowed = 1 AND p.deleted_at IS NULL"
            );
            $stmt->execute([$roleId]);
            // Извлекаем только столбец 'name' из всех строк
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            error_log("EmployeeRepository::getPermissionsForRole($roleId) ошибка БД: " . $e->getMessage());
            return [];
        }
    }
    
    // --- НОВЫЕ МЕТОДЫ ДЛЯ ПОЛНОГО CRUD ---
    
    /**
     * Получить список сотрудников с фильтрацией и пагинацией
     * @param array $filters Ассоциативный массив фильтров (fio, login, phone, is_active, role_id)
     * @param int $limit Количество записей на страницу
     * @param int $offset Смещение
     * @param string|null $orderBy Поле для сортировки
     * @param string $orderDir Направление сортировки (ASC или DESC)
     * @return array Массив сотрудников
     */
    public function getAll(array $filters = [], ?string $orderBy = null, string $orderDir = 'ASC'): array
    {
        try {
            $qb = new QueryBuilder();
            $qb->select('e.*, r.display_name as role_display_name')
               ->from('employees', 'e')
               ->leftJoin('roles r', 'e.role_id = r.id')
               ->where('e.deleted_at IS NULL');
            
            // Применение фильтров
            if (!empty($filters['fio'])) {
                $qb->where('e.fio LIKE ?', '%' . $filters['fio'] . '%');
            }

            if (!empty($filters['login'])) {
                $qb->where('e.login LIKE ?', '%' . $filters['login'] . '%');
            }

            if (!empty($filters['phone'])) {
                $phoneDigits = preg_replace('/\D/', '', $filters['phone']);
                if ($phoneDigits) {
                    $qb->where("REPLACE(REPLACE(REPLACE(REPLACE(e.phone, '+', ''), ' ', ''), '-', ''), '(', '') LIKE ?", '%' . $phoneDigits . '%');
                }
            }

            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $qb->where('e.is_active = ?', (int)$filters['is_active']);
            }

            if (!empty($filters['role_id'])) {
                $qb->where('e.role_id = ?', (int)$filters['role_id']);
            }

            // Сортировка
            if ($orderBy && in_array($orderBy, ['id', 'fio', 'login', 'phone', 'is_active', 'created_at'])) {
                $qb->orderBy("e.$orderBy", $orderDir);
            } else {
                $qb->orderBy('e.id', 'ASC');
            }

            // Привязываем параметры фильтров
            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }

            $sql = $qb->toSql();
            $params = $qb->getParams();

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EmployeeRepository::getAll() ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Подсчитать общее количество сотрудников с учетом фильтров
     * @param array $filters Ассоциативный массив фильтров
     * @return int Общее количество записей
     */
    public function countAll(array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM employees e WHERE e.deleted_at IS NULL";
            $params = [];
            
            if (!empty($filters['fio'])) {
                $sql .= " AND e.fio LIKE ?";
                $params[] = '%' . $filters['fio'] . '%';
            }
            if (!empty($filters['login'])) {
                $sql .= " AND e.login LIKE ?";
                $params[] = '%' . $filters['login'] . '%';
            }
            if (!empty($filters['phone'])) {
                $phoneDigits = preg_replace('/\D/', '', $filters['phone']);
                if ($phoneDigits) {
                     $sql .= " AND REPLACE(REPLACE(REPLACE(REPLACE(e.phone, '+', ''), ' ', ''), '-', ''), '(', '') LIKE ?";
                     $params[] = '%' . $phoneDigits . '%';
                }
            }
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $sql .= " AND e.is_active = ?";
                $params[] = (int)$filters['is_active'];
            }
            if (!empty($filters['role_id'])) {
                $sql .= " AND e.role_id = ?";
                $params[] = (int)$filters['role_id'];
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("EmployeeRepository::countAll() ошибка БД: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Найти сотрудника по UUID
     * @param string $uuid UUID сотрудника
     * @return array|null Ассоциативный массив с данными сотрудника или null
     */
    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT e.*, r.display_name as role_display_name FROM employees e LEFT JOIN roles r ON e.role_id = r.id WHERE e.uuid = ? AND e.deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$uuid]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            return $employee ?: null;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::findByUuid($uuid) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти сотрудника по телефону
     * @param string $phone Телефон сотрудника
     * @return array|null Ассоциативный массив с данными сотрудника или null
     */
    public function findByPhone(string $phone): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM employees WHERE phone = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$phone]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            return $employee ?: null;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::findByPhone($phone) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Проверить, существует ли сотрудник с таким логином (исключая ID)
     * @param string $login Логин
     * @param int|null $excludeId ID сотрудника для исключения из проверки (при обновлении)
     * @return bool True если логин занят, false если свободен
     */
    public function isLoginExists(string $login, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM employees WHERE login = ? AND deleted_at IS NULL";
            $params = [$login];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::isLoginExists($login) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Проверить, существует ли сотрудник с таким телефоном (исключая ID)
     * @param string $phone Телефон
     * @param int|null $excludeId ID сотрудника для исключения из проверки (при обновлении)
     * @return bool True если телефон занят, false если свободен
     */
    public function isPhoneExists(string $phone, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM employees WHERE phone = ? AND deleted_at IS NULL";
            $params = [$phone];
            
            if ($excludeId !== null) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return ((int)$stmt->fetchColumn()) > 0;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::isPhoneExists($phone) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Создать нового сотрудника
     * @param array $data Данные сотрудника
     *                    Ожидается: ['fio', 'login', 'phone', 'password_hash', 'is_active', 'role_id', 'is_superadmin']
     * @return int|false ID нового сотрудника или false в случае ошибки
     */
    public function create(array $data)
    {
        try {
            // Генерируем UUID
            $uuid = $this->generateUuid();

            $sql = "INSERT INTO employees (uuid, fio, login, phone, password_hash, is_active, role_id, is_superadmin, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $uuid,
                $data['fio'] ?? '',
                $data['login'] ?? '',
                $data['phone'] ?? '',
                $data['password_hash'] ?? '',
                (int)($data['is_active'] ?? 1),
                $data['role_id'] ?? null, // Может быть NULL
                (int)($data['is_superadmin'] ?? 0)
            ]);

            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::create(...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить данные сотрудника
     * @param int $id ID сотрудника
     * @param array $data Данные для обновления
     *                    Ожидается: ['fio', 'login', 'phone', 'is_active', 'role_id']
     *                    password_hash обновляется отдельно
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE employees 
                    SET fio = ?, login = ?, phone = ?, is_active = ?, role_id = ?, updated_at = NOW() 
                    WHERE id = ? AND deleted_at IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['fio'] ?? '',
                $data['login'] ?? '',
                $data['phone'] ?? '',
                (int)($data['is_active'] ?? 1),
                $data['role_id'] ?? null, // Может быть NULL
                $id
            ]);

            return $result !== false;
        } catch (PDOException $e) {
            error_log("EmployeeRepository::update($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить хэш пароля сотрудника
     * @param int $id ID сотрудника
     * @param string $passwordHash Новый хэш пароля
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function updatePassword(int $id, string $passwordHash): bool
    {
        try {
            $sql = "UPDATE employees SET password_hash = ?, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$passwordHash, $id]);
        } catch (PDOException $e) {
            error_log("EmployeeRepository::updatePassword($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * "Удалить" сотрудника (soft delete)
     * @param int $id ID сотрудника
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function delete(int $id): bool
    {
        try {
            // Нельзя удалить суперадмина обычным способом
            // Эту проверку можно также продублировать на уровне контроллера/бизнес-логики
            $employee = $this->findById($id);
            if ($employee && $employee['is_superadmin']) {
                error_log("EmployeeRepository::delete($id): Попытка удаления суперадмина.");
                return false;
            }

            $sql = "UPDATE employees SET deleted_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("EmployeeRepository::delete($id) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить список ролей для выпадающего списка в формах
     * @return array Массив ['id' => ..., 'display_name' => ...]
     */
    public function getRoleOptions(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, display_name FROM roles WHERE deleted_at IS NULL ORDER BY display_name ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("EmployeeRepository::getRoleOptions() ошибка БД: " . $e->getMessage());
            return [];
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