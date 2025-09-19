<?php
// app/Controllers/UserRolesController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\UserRoleRepository;
use App\Models\UserPermissionRepository;

/**
 * Контроллер для управления ролями пользователей фронтенда
 */
class UserRolesController
{
    private UserRoleRepository $userRoleRepo;
    private UserPermissionRepository $userPermRepo;

    public function __construct()
    {
        // Проверка авторизации должна быть на уровне маршрутизатора/middleware
        $this->userRoleRepo = new UserRoleRepository();
        $this->userPermRepo = new UserPermissionRepository();
    }

    /**
     * Отобразить список всех ролей пользователей
     */
    public function index(): void
    {
        if (!Auth::can('user_role_view')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // --- Обработка параметров пагинации и фильтрации ---
        $filters = [
            'display_name' => trim($_GET['display_name'] ?? ''),
            // Можно добавить фильтр по уровню и т.д.
        ];
        $filters = array_filter($filters); // Убираем пустые

        // --- Получение данных ---
        $roles = $this->userRoleRepo->getAll($filters); // Убираем пагинацию
        $totalRoles = count($roles);

        View::render('user-roles/index', [
            'roles' => $roles,
            'filters' => $filters
        ]);
    }

    /**
     * Показать форму создания новой роли пользователя
     */
    public function create(): void
    {
        if (!Auth::can('user_role_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Получаем список всех ролей для проверки наличия роли по умолчанию
        $existingRoles = $this->userRoleRepo->getAll();
        
        // Проверяем, есть ли уже роль по умолчанию
        $hasDefaultRole = false;
        foreach ($existingRoles as $role) {
            if (!empty($role['is_default']) && (int)$role['is_default'] === 1) {
                $hasDefaultRole = true;
                break;
            }
        }
        
        // Получаем список всех прав пользователей для чекбоксов
        $permissions = $this->userPermRepo->getAll();

        View::render('user-roles/create', [
            'permissions' => $permissions,
            'hasDefaultRole' => $hasDefaultRole // <-- Передаем флаг в представление
        ]);
    }

    /**
     * Обработать отправку формы создания новой роли пользователя
     */
    public function store(): void
    {
        if (!Auth::can('user_role_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $displayName = trim($_POST['display_name'] ?? '');
        $name = trim($_POST['name'] ?? ''); // machine name
        $description = trim($_POST['description'] ?? '');
        $level = (int)($_POST['level'] ?? 0);
        // --- ИЗМЕНЕНО: Логика для is_default ---
        // Получаем список всех ролей для проверки
        $existingRoles = $this->userRoleRepo->getAll();
        $hasDefaultRole = false;
        foreach ($existingRoles as $role) {
            if (!empty($role['is_default']) && (int)$role['is_default'] === 1) {
                $hasDefaultRole = true;
                break;
            }
        }
        
        // Если уже есть роль по умолчанию, игнорируем значение из POST
        $isDefault = false; // По умолчанию false
        if (!$hasDefaultRole) {
            // Только если роли по умолчанию еще нет, можно установить
            $isDefault = isset($_POST['is_default']) && $_POST['is_default'] == '1';
        }
        // --- КОНЕЦ ИЗМЕНЕНИЙ ---
        $selectedPermissions = $_POST['permissions'] ?? [];

        $errors = [];
        if (empty($displayName)) {
            $errors['display_name'] = 'Название обязательно.';
        }
        if (empty($name)) {
            $errors['name'] = 'Тип обязательно.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Тип может содержать только латинские буквы, цифры и подчеркивание.';
        } else if ($this->userRoleRepo->findByName($name)) {
            $errors['name'] = 'Роль с таким типом уже существует.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/user-roles/create');
            exit;
        }

        $roleId = $this->userRoleRepo->create([
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'level' => $level,
            'is_default' => $isDefault
        ]);

        if ($roleId === false) {
            $_SESSION['error'] = 'Не удалось создать роль.';
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/user-roles/create');
            exit;
        }

        // Назначаем права
        foreach ($selectedPermissions as $permId) {
            $this->userRoleRepo->setPermissionForRole($roleId, (int)$permId, true);
        }

        $_SESSION['success'] = 'Роль успешно создана.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/user-roles');
        exit;
    }

    /**
     * Показать форму редактирования роли пользователя
     */
    public function edit(int $id): void
    {
        if (!Auth::can('user_role_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $role = $this->userRoleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/user-roles');
            exit;
        }

        // Получаем список всех ролей для проверки наличия другой роли по умолчанию
        $existingRoles = $this->userRoleRepo->getAll();
        
        // Проверяем, есть ли уже другая роль по умолчанию (не эта)
        $hasOtherDefaultRole = false;
        foreach ($existingRoles as $r) {
            if ((int)$r['id'] !== (int)$id && !empty($r['is_default']) && (int)$r['is_default'] === 1) {
                $hasOtherDefaultRole = true;
                break;
            }
        }

        // Получаем все права и права, уже назначенные этой роли
        $allPermissions = $this->userPermRepo->getAll();
        $rolePermissions = $this->userRoleRepo->getPermissionsForRole($id);
        $assignedPermissionIds = array_column($rolePermissions, 'id');

        // Группируем права по категориям для удобства отображения
        $groupedPermissions = [];
        foreach ($allPermissions as $perm) {
            $groupedPermissions[$perm['module'] ?? 'Без модуля'][] = $perm;
        }

        View::render('user-roles/edit', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'assignedPermissionIds' => $assignedPermissionIds,
            'hasOtherDefaultRole' => $hasOtherDefaultRole // <-- Передаем флаг в представление
        ]);
    }

    /**
     * Обработать отправку формы редактирования роли пользователя
     */
    public function update(int $id): void
    {
        if (!Auth::can('user_role_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $role = $this->userRoleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/user-roles');
            exit;
        }

        $displayName = trim($_POST['display_name'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $level = (int)($_POST['level'] ?? 0);
        // --- ИЗМЕНЕНО: Логика для is_default ---
        // Проверяем, есть ли уже другая роль по умолчанию (не эта)
        $existingRoles = $this->userRoleRepo->getAll();
        $hasOtherDefaultRole = false;
        foreach ($existingRoles as $r) {
            if ((int)$r['id'] !== (int)$id && !empty($r['is_default']) && (int)$r['is_default'] === 1) {
                $hasOtherDefaultRole = true;
                break;
            }
        }
        
        // Логика обновления is_default
        $isDefault = false; // По умолчанию false
        if ($role['is_default']) {
            // Если роль уже по умолчанию, сохраняем это значение
            $isDefault = true;
        } else if (!$hasOtherDefaultRole) {
            // Если роль не по умолчанию и другой роли по умолчанию нет, можно установить
            $isDefault = isset($_POST['is_default']) && $_POST['is_default'] == '1';
        }
        // --- КОНЕЦ ИЗМЕНЕНИЙ ---
        $selectedPermissions = $_POST['permissions'] ?? [];

        $errors = [];
        if (empty($displayName)) {
            $errors['display_name'] = 'Название обязательно.';
        }
        if (empty($name)) {
            $errors['name'] = 'Тип обязательно.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Тип может содержать только латинские буквы, цифры и подчеркивание.';
        } else if (($existingRole = $this->userRoleRepo->findByName($name)) && $existingRole['id'] != $id) {
            $errors['name'] = 'Роль с таким типом уже существует.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/user-roles/{$id}/edit");
            exit;
        }

        $result = $this->userRoleRepo->update($id, [
            'name' => $name,
            'display_name' => $displayName,
            'description' => $description,
            'level' => $level,
            'is_default' => $isDefault
        ]);

        if (!$result) {
            $_SESSION['error'] = 'Не удалось обновить роль.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/user-roles/{$id}/edit");
            exit;
        }

        // Обновляем права: сначала удаляем все, потом добавляем выбранные
        $this->userRoleRepo->removeAllPermissionsFromRole($id);
        foreach ($selectedPermissions as $permId) {
            $this->userRoleRepo->setPermissionForRole($id, (int)$permId, true);
        }

        $_SESSION['success'] = 'Роль успешно обновлена.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/user-roles');
        exit;
    }

    /**
     * Удалить роль пользователя (soft delete)
     */
    public function delete(int $id): void
    {
        if (!Auth::can('user_role_delete')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $role = $this->userRoleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/user-roles');
            exit;
        }

        // Проверка на роль по умолчанию
        if ($role['is_default']) {
            $_SESSION['error'] = 'Невозможно удалить роль по умолчанию.';
            header('Location: /admin/user-roles');
            exit;
        }

        $result = $this->userRoleRepo->delete($id);

        if (!$result) {
            $_SESSION['error'] = 'Не удалось удалить роль.';
        } else {
            $_SESSION['success'] = 'Роль успешно удалена.';
        }

        header('Location: /admin/user-roles');
        exit;
    }
}
?>