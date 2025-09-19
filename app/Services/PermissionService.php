<?php
// app/Services/PermissionService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\PermissionRepository;

/**
 * Сервис для работы с логикой прав сотрудников
 * Цель: Вынести бизнес-логику из контроллера PermissionsController
 */
class PermissionService
{
    private PermissionRepository $permRepo;

    public function __construct()
    {
        $this->permRepo = new PermissionRepository();
    }

    /**
     * Валидация данных для создания/обновления права
     * @param array $data Данные из $_POST
     * @param int|null $existingId ID существующего права при обновлении
     * @param bool $isCurrentUserSuperAdmin Является ли текущий пользователь суперадмином
     * @return array Массив ошибок, пустой если валидация успешна
     */
    public function validate(array $data, ?int $existingId = null, bool $isCurrentUserSuperAdmin = false): array
    {
        $errors = [];
        $displayName = trim($data['display_name'] ?? '');
        $displayNameShort = trim($data['display_name_short'] ?? '');
        $name = trim($data['name'] ?? '');
        $module = trim($data['module'] ?? '');

        if (empty($displayName)) {
            $errors['display_name'] = 'Название права обязательно для заполнения.';
        }
        if (empty($displayNameShort)) {
            $errors['display_name_short'] = 'Короткое название права обязательно для заполнения.';
        }
        if (empty($name)) {
            $errors['name'] = 'Тип права обязателен для заполнения.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Тип может содержать только латинские буквы, цифры и подчеркивание.';
        } else {
            $existingPerm = $this->permRepo->findByName($name);
            if ($existingPerm && ($existingId === null || $existingPerm['id'] != $existingId)) {
                $errors['name'] = 'Право с таким типом уже существует.';
            }
        }
        if (empty($module)) {
             $errors['module'] = 'Модуль обязателен для заполнения.';
        }
        
        return $errors;
    }

    /**
     * Создание нового права
     * @param array $data Данные права
     * @return int|false ID нового права или false в случае ошибки
     */
    public function create(array $data)
    {
        // Предполагается, что валидация уже пройдена

        $displayName = trim($data['display_name'] ?? '');
        $displayNameShort = trim($data['display_name_short'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $module = trim($data['module'] ?? '');
        $isSystem = isset($data['is_system']) && $data['is_system'] == '1';

        // Подготовка данных для создания
        $permData = [
            'name' => $name,
            'display_name' => $displayName,
            'display_name_short' => $displayNameShort,
            'description' => $description,
            'module' => $module,
            'is_system' => $isSystem ? 1 : 0
        ];

        // Создание права в БД
        $permId = $this->permRepo->create($permData);
        
        return $permId;
    }

    /**
     * Обновление данных права
     * @param int $id ID права
     * @param array $data Новые данные
     * @param bool $isCurrentUserSuperAdmin Является ли текущий пользователь суперадмином
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data, bool $isCurrentUserSuperAdmin = false): bool
    {
        // Предполагается, что валидация уже пройдена

        $displayName = trim($data['display_name'] ?? '');
        $displayNameShort = trim($data['display_name_short'] ?? '');
        $name = trim($data['name'] ?? '');
        $description = trim($data['description'] ?? '');
        $module = trim($data['module'] ?? '');
        
        // Подготовка данных для обновления
        $updateData = [
            'name' => $name,
            'display_name' => $displayName,
            'display_name_short' => $displayNameShort,
            'description' => $description,
            'module' => $module,
        ];

        // is_system обновляется только суперадмином
        if ($isCurrentUserSuperAdmin && array_key_exists('is_system', $data)) {
            $updateData['is_system'] = (int)($data['is_system'] == '1');
        }

        // Обновление данных права
        $result = $this->permRepo->update($id, $updateData);
        
        return $result !== false;
    }

    // Другие методы сервиса (delete, findById, и т.д.) могут быть добавлены по мере необходимости
}
?>