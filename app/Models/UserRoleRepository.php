<?php
// app/Models/UserRoleRepository.php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Репозиторий для работы с ролями пользователей фронтенда (user_roles)
 */
class UserRoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function getAll(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM user_roles WHERE deleted_at IS NULL";
            $params = [];
            
            if (!empty($filters['display_name'])) {
                $sql .= " AND display_name LIKE ?";
                $params[] = '%' . $filters['display_name'] . '%';
            }

            $sql .= " ORDER BY level ASC, display_name ASC";

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key + 1, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserRoleRepository::getAll() ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_roles WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            error_log("UserRoleRepository::findById($id) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    public function findByName(string $name): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_roles WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$name]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            error_log("UserRoleRepository::findByName($name) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    public function create(array $data)
    {
        try {
            $uuid = $this->generateUuid();
            $sql = "INSERT INTO user_roles (uuid, name, display_name, description, level, is_default, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $uuid,
                $data['name'] ?? '',
                $data['display_name'] ?? '',
                $data['description'] ?? null,
                (int)($data['level'] ?? 0),
                (int)($data['is_default'] ?? 0)
            ]);

            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("UserRoleRepository::create(...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            $sql = "UPDATE user_roles 
                    SET name = ?, display_name = ?, description = ?, level = ?, is_default = ?, updated_at = NOW() 
                    WHERE id = ? AND deleted_at IS NULL";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['name'] ?? '',
                $data['display_name'] ?? '',
                $data['description'] ?? null,
                (int)($data['level'] ?? 0),
                (int)($data['is_default'] ?? 0),
                $id
            ]);

            return $result !== false;
        } catch (PDOException $e) {
            error_log("UserRoleRepository::update($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    public function delete(int $id): bool
    {
        try {
            // Проверяем, не является ли роль по умолчанию
            $role = $this->findById($id);
            if ($role && $role['is_default']) {
                error_log("UserRoleRepository::delete($id): Попытка удаления роли по умолчанию.");
                return false;
            }

            $sql = "UPDATE user_roles SET deleted_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("UserRoleRepository::delete($id) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    // --- Методы для работы с правами роли ---

    public function getPermissionsForRole(int $roleId): array
    {
        try {
            $sql = "SELECT p.* 
                    FROM user_permissions p
                    JOIN user_role_permissions rp ON p.id = rp.permission_id
                    WHERE rp.role_id = ? AND rp.granted = 1 AND p.deleted_at IS NULL";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("UserRoleRepository::getPermissionsForRole($roleId) ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    public function setPermissionForRole(int $roleId, int $permissionId, bool $granted = true): bool
    {
        try {
            // Сначала пробуем обновить существующую запись
            $sql = "INSERT INTO user_role_permissions (role_id, permission_id, granted) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE granted = VALUES(granted)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$roleId, $permissionId, (int)$granted]);
        } catch (PDOException $e) {
            error_log("UserRoleRepository::setPermissionForRole($roleId, $permissionId, $granted) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    public function removeAllPermissionsFromRole(int $roleId): bool
    {
        try {
            $sql = "DELETE FROM user_role_permissions WHERE role_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$roleId]);
        } catch (PDOException $e) {
            error_log("UserRoleRepository::removeAllPermissionsFromRole($roleId) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    // --- Вспомогательные методы ---

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