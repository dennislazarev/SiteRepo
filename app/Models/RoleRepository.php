<?php
// app/Models/RoleRepository.php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

/**
 * Репозиторий для работы с ролями (roles)
 */
class RoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    /**
     * Получить список всех ролей (активных, не удаленных)
     * @return array Массив ассоциативных массивов с данными ролей
     */
    public function getAll(): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM roles WHERE deleted_at IS NULL ORDER BY name ASC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("RoleRepository::getAll() ошибка БД: " . $e->getMessage());
            return []; // Возвращаем пустой массив в случае ошибки
        }
    }

    /**
     * Найти роль по ID
     * @param int $id ID роли
     * @return array|null Ассоциативный массив с данными роли или null, если не найдена
     */
    public function findById(int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM roles WHERE id = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            error_log("RoleRepository::findById($id) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Найти роль по имени (name)
     * @param string $name Имя роли
     * @return array|null Ассоциативный массив с данными роли или null, если не найдена
     */
    public function findByName(string $name): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM roles WHERE name = ? AND deleted_at IS NULL LIMIT 1"
            );
            $stmt->execute([$name]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            return $role ?: null;
        } catch (PDOException $e) {
            error_log("RoleRepository::findByName($name) ошибка БД: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Создать новую роль
     * @param string $name Уникальное имя роли
     * @param string $displayName Отображаемое имя
     * @param bool $isSystem Является ли роль системной
     * @return int|false ID новой роли или false в случае ошибки
     */
    public function create(string $name, string $displayName, bool $isSystem = false): int|false
    {
        try {
            // Генерируем UUID
            $uuid = $this->generateUuid();

            $stmt = $this->pdo->prepare(
                "INSERT INTO roles (uuid, name, display_name, is_system, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())"
            );
            $result = $stmt->execute([$uuid, $name, $displayName, (int)$isSystem]);

            if ($result) {
                return (int)$this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("RoleRepository::create($name, $displayName) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
    * Обновить данные роли
    * @param int $id ID роли
    * @param array $data Ассоциативный массив с новыми данными роли
    *                        Ожидается: ['name', 'display_name']
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function update(int $id, array $data): bool
    {
        // 1. Извлекаем и валидируем данные
        $name = $data['name'] ?? '';
        $displayName = $data['display_name'] ?? '';

        if (empty($name) || empty($displayName)) {
            error_log("RoleRepository::update - Неверные данные: name или display_name пусты.");
            return false;
        }

        try {
            // 2. Проверяем, не пытаемся ли мы изменить имя на уже существующее (кроме текущего)
            $existingRole = $this->findByName($name);
            if ($existingRole && $existingRole['id'] != $id) {
                error_log("RoleRepository::update($id): Роль с именем '$name' уже существует.");
                return false; // Имя занято другой ролью
            }

            // 3. Подготавливаем SQL-запрос
            $sql = "UPDATE `roles` 
                    SET `name` = ?, `display_name` = ?, `updated_at` = NOW() 
                    WHERE `id` = ? AND `deleted_at` IS NULL";

            // 4. Выполняем запрос
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $name,
                $displayName,
                $id
            ]);

            return $result !== false;
        } catch (PDOException $e) {
            error_log("RoleRepository::update($id, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * "Удалить" роль (soft delete)
     * @param int $id ID роли
     * @return bool True в случае успеха, false в случае ошибки или попытки удалить системную роль
     */
    public function delete(int $id): bool
    {
        try {
            // Нельзя удалить системную роль
            $role = $this->findById($id);
            if (!$role) {
                error_log("RoleRepository::delete($id): Роль не найдена.");
                return false;
            }
            if ($role['is_system']) {
                error_log("RoleRepository::delete($id): Попытка удаления системной роли.");
                return false;
            }

            $stmt = $this->pdo->prepare(
                "UPDATE roles SET deleted_at = NOW() WHERE id = ?"
            );
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("RoleRepository::delete($id) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить все права, связанные с ролью
     * @param int $roleId ID роли
     * @return array Массив ассоциативных массивов с данными прав
     */
    public function getPermissionsForRole(int $roleId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT p.*, rp.is_allowed 
                 FROM permissions p
                 JOIN role_permissions rp ON p.id = rp.permission_id
                 WHERE rp.role_id = ? AND p.deleted_at IS NULL
                 ORDER BY p.module, p.name"
            );
            $stmt->execute([$roleId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("RoleRepository::getPermissionsForRole($roleId) ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Назначить или обновить право для роли
     * @param int $roleId ID роли
     * @param int $permissionId ID права
     * @param bool $isAllowed Разрешено (1) или запрещено (0)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function setPermissionForRole(int $roleId, int $permissionId, bool $isAllowed): bool
    {
        try {
            // Проверяем, существует ли уже связь
            $stmt = $this->pdo->prepare(
                "SELECT 1 FROM role_permissions WHERE role_id = ? AND permission_id = ? LIMIT 1"
            );
            $stmt->execute([$roleId, $permissionId]);
            $exists = $stmt->fetch();

            if ($exists) {
                // Обновляем существующую связь
                $stmt = $this->pdo->prepare(
                    "UPDATE role_permissions SET is_allowed = ? WHERE role_id = ? AND permission_id = ?"
                );
                return $stmt->execute([(int)$isAllowed, $roleId, $permissionId]);
            } else {
                // Создаем новую связь
                $stmt = $this->pdo->prepare(
                    "INSERT INTO role_permissions (role_id, permission_id, is_allowed, created_at) 
                     VALUES (?, ?, ?, NOW())"
                );
                return $stmt->execute([$roleId, $permissionId, (int)$isAllowed]);
            }
        } catch (PDOException $e) {
            error_log("RoleRepository::setPermissionForRole($roleId, $permissionId, $isAllowed) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить все права у роли (например, при удалении роли)
     * @param int $roleId ID роли
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function removeAllPermissionsFromRole(int $roleId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            return $stmt->execute([$roleId]);
        } catch (PDOException $e) {
            error_log("RoleRepository::removeAllPermissionsFromRole($roleId) ошибка БД: " . $e->getMessage());
            return false;
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
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
?>