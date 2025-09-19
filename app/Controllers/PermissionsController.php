<?php
// app/Controllers/PermissionsController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\PermissionRepository;

/**
 * Контроллер для управления правами
 */
class PermissionsController
{
    private PermissionRepository $permRepo;

    public function __construct()
    {
        // Проверка авторизации должна быть на уровне маршрутизатора/middleware
        $this->permRepo = new PermissionRepository();
    }

    /**
     * Отобразить список всех прав
     */
    public function index(): void
    {
        if (!Auth::can('permission_view')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // --- Обработка параметров пагинации и фильтрации ---
        $filters = [
            'display_name' => trim($_POST['display_name'] ?? $_GET['display_name'] ?? ''),
            'module' => trim($_POST['module'] ?? $_GET['module'] ?? ''),
        ];
        $filters = array_filter($filters); // Убираем пустые

        // --- Получение данных ---
        $permissions = $this->permRepo->getAll($filters);

        // Получаем список уникальных категорий для фильтра
        $modules = $this->permRepo->getModules();

        View::render('permissions/index', [
            'permissions' => $permissions,
            'filters' => $filters,
            'modules' => $modules
        ]);
    }

    /**
     * Показать форму создания нового права
     */
    public function create(): void
    {
        if (!Auth::can('permission_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Получаем список категорий для выпадающего списка
        $modules = $this->permRepo->getModules();

        $standardShortNames = ['Просмотр', 'Создание', 'Редактирование', 'Удаление'];

        View::render('permissions/create', [
            'modules' => $modules,
            'standardShortNames' => $standardShortNames
        ]);
    }

    /**
     * Обработать отправку формы создания нового права
     */
    public function store(): void
    {
        if (!Auth::can('permission_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $displayName = trim($_POST['display_name'] ?? '');
        $displayNameShort = trim($_POST['display_name_short'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $module = trim($_POST['module'] ?? '');
        $isSystem = isset($_POST['is_system']) && $_POST['is_system'] == '1';

        $errors = [];
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
        } else if ($this->permRepo->findByName($name)) {
            $errors['name'] = 'Право с таким типом уже существует.';
        }
        if (empty($module)) {
             $errors['module'] = 'Модуль обязателен для заполнения.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/permissions/create');
            exit;
        }

        $permId = $this->permRepo->create([
            'name' => $name,
            'display_name' => $displayName,
            'display_name_short' => $displayNameShort,
            'description' => $description,
            'module' => $module,
            'is_system' => $isSystem ? 1 : 0
        ]);

        if ($permId === false) {
            $_SESSION['error'] = 'Не удалось создать право.';
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/permissions/create');
            exit;
        }

        $_SESSION['success'] = 'Право успешно создано.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/permissions');
        exit;
    }

    /**
     * Показать форму редактирования права
     */
    public function edit(int $id): void
    {
        if (!Auth::can('permission_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $permission = $this->permRepo->findById($id);
        if (!$permission) {
            $_SESSION['error'] = 'Право не найдено.';
            header('Location: /admin/permissions');
            exit;
        }

        // Получаем список категорий
        $modules = $this->permRepo->getModules();

        $standardShortNames = ['Просмотр', 'Создание', 'Редактирование', 'Удаление'];

        View::render('permissions/edit', [
            'permission' => $permission,
            'modules' => $modules,
            'standardShortNames' => $standardShortNames
        ]);
    }

    /**
     * Обработать отправку формы редактирования права
     */
    public function update(int $id): void
    {
        if (!Auth::can('permission_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $permission = $this->permRepo->findById($id);
        if (!$permission) {
            $_SESSION['error'] = 'Право не найдено.';
            header('Location: /admin/permissions');
            exit;
        }

        // Проверяем, является ли текущий пользователь суперадмином.
        $currentUser = \App\Core\Auth::user();
        $isCurrentUserSuperAdmin = $currentUser && !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;
        
        // Если право системное и пользователь НЕ суперадмин, запрещаем редактирование
        if ($permission['is_system'] && !$isCurrentUserSuperAdmin) {
             $_SESSION['error'] = 'Невозможно редактировать системное право.';
             header("Location: /admin/permissions/{$id}/edit");
             exit;
        }

        $displayName = trim($_POST['display_name'] ?? '');
        $displayNameShort = trim($_POST['display_name_short'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $module = trim($_POST['module'] ?? '');
        // is_system менять может ТОЛЬКО суперадмин
        $isSystem = null; // По умолчанию не меняем
        if ($isCurrentUserSuperAdmin) {
            // Только суперадмин может изменить флаг is_system
            $isSystem = isset($_POST['is_system']) && $_POST['is_system'] == '1';
        }

        $errors = [];
        if (empty($displayName)) {
            $errors['display_name'] = 'Название права обязательно для заполнения.';
        }
        if (empty($displayNameShort)) {
            $errors['display_name_short'] = 'Короткое название права обязательно для заполнения.';
        }
        if (empty($name)) {
            $errors['name'] = 'Тип права обязательно для заполнения.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Тип может содержать только латинские буквы, цифры и подчеркивание.';
        } else if (($existingPerm = $this->permRepo->findByName($name)) && $existingPerm['id'] != $id) {
            $errors['name'] = 'Право с таким типом уже существует.';
        }
        if (empty($module)) {
             $errors['module'] = 'Модуль обязателен для заполнения.';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/permissions/{$id}/edit");
            exit;
        }

        $dataToUpdate = [
            'name' => $name,
            'display_name' => $displayName,
            'display_name_short' => $displayNameShort,
            'description' => $description,
            'module' => $module,
            'is_system' => $isSystem ? 1 : 0
        ];

        $result = $this->permRepo->update($id, $dataToUpdate);

        if (!$result) {
            $_SESSION['error'] = 'Не удалось обновить право.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/permissions/{$id}/edit");
            exit;
        }

        $_SESSION['success'] = 'Право успешно обновлено.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/permissions');
        exit;
    }

    /**
     * Удалить право (soft delete)
     */
    public function delete(int $id): void
    {
        if (!Auth::can('permission_delete')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $permission = $this->permRepo->findById($id);
        if (!$permission) {
            $_SESSION['error'] = 'Право не найдено.';
            header('Location: /admin/permissions');
            exit;
        }

        if ($permission['is_system']) {
            $_SESSION['error'] = 'Невозможно удалить системное право.';
            header('Location: /admin/permissions');
            exit;
        }

        $result = $this->permRepo->delete($id);

        if (!$result) {
            $_SESSION['error'] = 'Не удалось удалить право. Возможно, оно используется.';
        } else {
            $_SESSION['success'] = 'Право успешно удалено.';
        }

        header('Location: /admin/permissions');
        exit;
    }
}