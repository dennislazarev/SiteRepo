<?php
// app/Models/UserPermissionRepository.php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Репозиторий для работы с правами пользователей фронтенда (user_permissions)
 */
class UserPermissionRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Получить список всех прав пользователей с пагинацией и фильтрацией
     * @param array $filters Массив фильтров
     * @param int $limit Лимит записей
     * @param int $offset Смещение
     * @return array Массив ассоциативных массивов с данными прав
     */
    public function getAll(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM user_permissions WHERE deleted_at IS NULL";
            $params = [];
            
            if (!empty($filters['display_name'])) {
                $sql .= " AND display_name LIKE ?";
                $params[] = '%' . $filters['display_name'] . '%';
            }
            if (!empty($filters['module'])) {
                $sql .= " AND module = ?";
                $params[] = $filters['module'];
            }

            $sql .= " ORDER BY module ASC, display_name ASC";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::getAll() ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Найти право пользователя по ID
     * @param int $id ID права
     * @return array|null Ассоциативный массив с данными права или null, если не найдено
     */
    public function findById(int $id): ?array
    {
        try {								
            $stmt = $this->pdo->prepare("SELECT * FROM user_permissions WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$id]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            return $permission ?: null;
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::findById($id) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти право пользователя по имени (name)
     * @param string $name Имя права
     * @return array|null Ассоциативный массив с данными права или null, если не найдено
     */
    public function findByName(string $name): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_permissions WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$name]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            return $permission ?: null;
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::findByName($name) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создать новое право пользователя
     * @param array $data Ассоциативный массив с данными права
     *                    Ожидается: ['name', 'display_name', 'display_name_short', 'description', 'module', 'is_system']
     * @return int|false ID созданного права или false в случае ошибки
     */
    public function create(array $data)
    {
        try {
            $uuid = $this->generateUuid();
            $sql = "INSERT INTO `user_permissions` (
                    `uuid`, `name`, `display_name`, `display_name_short`, `description`, `module`, `is_system`, `created_at`, `updated_at`
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                )";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $uuid,
                $data['name'] ?? '',
                $data['display_name'] ?? '',
                $data['display_name_short'] ?? null,
                $data['description'] ?? null,
                $data['module'] ?? null,
                (int)($data['is_system'] ?? 0)
            ]);

            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::create(...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Обновить данные права пользователя
     * @param int $id ID права
     * @param array $data Ассоциативный массив с новыми данными права
     *                    Ожидается: ['name', 'display_name', 'display_name_short', 'description', 'module', 'is_system']
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
        try {
            // Проверяем, существует ли запись
            $existingPermission = $this->findById($id);
            if (!$existingPermission) {
                error_log("UserPermissionRepository::update - Право с ID $id не найдено.");
                return false;
            }

            $sql = "UPDATE `user_permissions` 
                SET `name` = ?, `display_name` = ?, `display_name_short` = ?, 
                    `description` = ?, `module` = ?, `updated_at` = NOW()";

            $params = [
                $data['name'] ?? '',
                $data['display_name'] ?? '',
                $data['display_name_short'] ?? null,
                $data['description'] ?? null,
                $data['module'] ?? null,
                $id
            ];

            // Добавляем обновление is_system только если оно передано
            if (array_key_exists('is_system', $data)) {
                $sql .= ", `is_system` = ?";
                $params = [
                    $data['name'] ?? '',
                    $data['display_name'] ?? '',
                    $data['display_name_short'] ?? null,
                    $data['description'] ?? null,
                    $data['module'] ?? null,
                    (int)$data['is_system'],
                    $id
                ];
            }

            $sql .= " WHERE `id` = ? AND `deleted_at` IS NULL";

            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);

            return $result !== false;
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::update($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * "Удалить" право пользователя (soft delete)
     * @param int $id ID права
     * @return bool True в случае успеха, false в случае ошибки или попытки удалить системное право
     */
    public function delete(int $id): bool
    {
        try {
            // Проверяем, не является ли право системным
            $permission = $this->findById($id);
            if (!$permission) {
                error_log("UserPermissionRepository::delete($id): Право не найдено.");
                return false;
            }
            if ($permission['is_system']) {
                error_log("UserPermissionRepository::delete($id): Попытка удаления системного права.");
                return false;
            }

            $stmt = $this->pdo->prepare(
                "UPDATE user_permissions SET deleted_at = NOW() WHERE id = ?"
            );
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::delete($id) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить список модулей, присутствующих в таблице user_permissions
     * @return array Массив строк с названиями модулей
     */
    public function getModules(): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT DISTINCT module FROM user_permissions WHERE module IS NOT NULL AND module != '' AND deleted_at IS NULL ORDER BY module ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (PDOException $e) {
            error_log("UserPermissionRepository::getModules() ошибка БД: " . $e->getMessage());
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