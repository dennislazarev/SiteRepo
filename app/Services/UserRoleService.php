<?php
// app/Services/UserRoleService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserRoleRepository;

/**
 * Сервис для работы с логикой ролей пользователей фронтенда
 * Цель: Вынести бизнес-логику из контроллера UserRolesController
 */
class UserRoleService
{
    private UserRoleRepository $userRoleRepo;

    public function __construct()
    {
        $this->userRoleRepo = new UserRoleRepository();
    }

    /**
     * Валидация данных для создания/обновления роли пользователя
     * @param array $data Данные из $_POST
     * @param int|null $existingId ID существующей роли при обновлении
     * @param array $existingRoles Список всех существующих ролей (для проверки is_default)
     * @return array Массив ошибок, пустой если валидация успешна
     */
    public function validate(array $data, ?int $existingId = null, array $existingRoles = []): array
    {
        $errors = [];
        $displayName = trim($data['display_name'] ?? '');
        $name = trim($data['name'] ?? '');
        $level = (int)($data['level'] ?? 0);

        if (empty($displayName)) {
            $errors['display_name'] = 'Название обязательно.';
        }
        if (empty($name)) {
            $errors['name'] = 'Тип обязательно.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Тип может содержать только латинские буквы, цифры и подчеркивание.';
        } else {
            $existingRole = $this->userRoleRepo->findByName($name);
            if ($existingRole && ($existingId === null || $existingRole['id'] != $existingId)) {
                $errors['name'] = 'Роль с таким типом уже существует.';
            }
        }
        
        return $errors;
    }

    /**
     * Создание новой роли пользователя
     * @param array $data Данные роли
     * @param array $existingRoles Список всех существующих ролей (для проверки is_default)
     * @return int|false ID новой роли или false в случае ошибки
     */
    public function create(array $data, array $existingRoles = [])
    {
        // Предполагается, что валидация уже пройдена

        $displayName = trim($data['display_name'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $level = (int)($data['level'] ?? 0);
        
        // Логика для is_default
        $hasDefaultRole = false;
        foreach ($existingRoles as $role) {
            if (!empty($role['is_default']) && (int)$role['is_default'] === 1) {
                $hasDefaultRole = true;
                break;
            }
        }
        $isDefault = false;
        if (!$hasDefaultRole) {
            $isDefault = isset($data['is_default']) && $data['is_default'] == '1';
        }

        // Подготовка данных для создания
        $roleData = [
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'level' => $level,
            'is_default' => $isDefault
        ];

        // Создание роли в БД
        $roleId = $this->userRoleRepo->create($roleData);
        
        // Назначение прав, если они были переданы
        if ($roleId && isset($data['permissions']) && is_array($data['permissions'])) {
            $selectedPermissions = array_filter(array_map('intval', $data['permissions']));
            foreach ($selectedPermissions as $permId) {
                $this->userRoleRepo->setPermissionForRole($roleId, $permId, true);
            }
        }

        return $roleId;
    }

    /**
     * Обновление данных роли пользователя
     * @param int $id ID роли
     * @param array $data Новые данные
     * @param array $existingRoles Список всех существующих ролей (для проверки is_default)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data, array $existingRoles = []): bool
    {
        // Предполагается, что валидация уже пройдена

        $displayName = trim($data['display_name'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $level = (int)($data['level'] ?? 0);
        
        // Логика обновления is_default
        $role = $this->userRoleRepo->findById($id); // Получаем текущую роль
        $hasOtherDefaultRole = false;
        foreach ($existingRoles as $r) {
            if ((int)$r['id'] !== (int)$id && !empty($r['is_default']) && (int)$r['is_default'] === 1) {
                $hasOtherDefaultRole = true;
                break;
            }
        }
        
        $isDefault = false;
        if ($role && $role['is_default']) {
            $isDefault = true;
        } else if (!$hasOtherDefaultRole) {
            $isDefault = isset($data['is_default']) && $data['is_default'] == '1';
        }

        // Подготовка данных для обновления
        $updateData = [
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'level' => $level,
            'is_default' => $isDefault
        ];

        // Обновление данных роли
        $result = $this->userRoleRepo->update($id, $updateData);
        
        if (!$result) {
            return false;
        }
        
        // Обновление прав: сначала удаляем все, потом добавляем выбранные
        $this->userRoleRepo->removeAllPermissionsFromRole($id);
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $selectedPermissions = array_filter(array_map('intval', $data['permissions']));
            foreach ($selectedPermissions as $permId) {
                $this->userRoleRepo->setPermissionForRole($id, $permId, true);
            }
        }
        
        return true;
    }

    // Другие методы сервиса (delete, findById, и т.д.) могут быть добавлены по мере необходимости
}
?>