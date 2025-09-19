<?php
// app/Services/RoleService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\RoleRepository;

/**
 * Сервис для работы с логикой ролей сотрудников
 * Цель: Вынести бизнес-логику из контроллера RolesController
 */
class RoleService
{
    private RoleRepository $roleRepo;

    public function __construct()
    {
        $this->roleRepo = new RoleRepository();
    }

    /**
     * Валидация данных для создания/обновления роли
     * @param array $data Данные из $_POST
     * @param int|null $existingId ID существующей роли при обновлении
     * @return array Массив ошибок, пустой если валидация успешна
     */
    public function validate(array $data, ?int $existingId = null): array
    {
        $errors = [];
        $name = trim($data['name'] ?? '');
        $displayName = trim($data['display_name'] ?? '');

        if (empty($name)) {
            $errors['name'] = 'Имя роли обязательно для заполнения.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Имя роли может содержать только латинские буквы, цифры и подчеркивание.';
        } else {
            $existingRole = $this->roleRepo->findByName($name);
            if ($existingRole && ($existingId === null || $existingRole['id'] != $existingId)) {
                $errors['name_unique'] = 'Роль с таким именем уже существует.';
            }
        }

        if (empty($displayName)) {
            $errors['display_name'] = 'Отображаемое имя роли обязательно для заполнения.';
        }
        
        return $errors;
    }

    /**
     * Создание новой роли
     * @param array $data Данные роли
     * @return int|false ID новой роли или false в случае ошибки
     */
    public function create(array $data)
    {
        // Предполагается, что валидация уже пройдена

        $name = trim($data['name'] ?? '');
        $displayName = trim($data['display_name'] ?? '');
        $isSystem = isset($data['is_system']) && $data['is_system'] == '1';

        // Создание роли в БД
        $roleId = $this->roleRepo->create($name, $displayName, $isSystem);
        
        // Назначение прав, если они были переданы
        if ($roleId && isset($data['permissions']) && is_array($data['permissions'])) {
            $selectedPermissionIds = array_filter(array_map('intval', $data['permissions']));
            foreach ($selectedPermissionIds as $permId) {
                if ($permId > 0) {
                    $this->roleRepo->setPermissionForRole($roleId, $permId, true);
                }
            }
        }
        
        return $roleId;
    }

    /**
     * Обновление данных роли
     * @param int $id ID роли
     * @param array $data Новые данные
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
        // Предполагается, что валидация уже пройдена

        $name = trim($data['name'] ?? '');
        $displayName = trim($data['display_name'] ?? '');
        // is_system не обновляется через этот сервис/контроллер

        // Подготовка данных для обновления
        $updateData = [
            'name' => $name,
            'display_name' => $displayName
        ];
        
        // Обновление данных роли
        $result = $this->roleRepo->update($id, $updateData);
        
        if (!$result) {
            return false;
        }
        
        // Обновление прав роли
        $this->roleRepo->removeAllPermissionsFromRole($id);
        
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            $selectedPermissionIds = array_filter(array_map('intval', $data['permissions']));
            foreach ($selectedPermissionIds as $permId) {
                if ($permId > 0) {
                    $this->roleRepo->setPermissionForRole($id, $permId, true);
                }
            }
        }
        
        return true;
    }

    // Другие методы сервиса (delete, findById, и т.д.) могут быть добавлены по мере необходимости
}
?>