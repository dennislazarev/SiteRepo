<?php
// app/Controllers/RolesController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\RoleRepository;
use App\Models\PermissionRepository;

/**
 * Контроллер для управления ролями
 */
class RolesController
{
    private RoleRepository $roleRepo;
    private PermissionRepository $permRepo;

    public function __construct()
    {
        // Проверка авторизации должна быть на уровне маршрутизатора/middleware
        // Здесь мы считаем, что пользователь уже авторизован и имеет доступ

        $this->roleRepo = new RoleRepository();
        $this->permRepo = new PermissionRepository();
    }

    /**
     * Отобразить список всех ролей
     */
    public function index(): void
    {

        if (!Auth::can('role_view')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $filters = [
            'display_name' => trim($_POST['display_name'] ?? $_GET['display_name'] ?? ''),
            'name' => trim($_POST['name'] ?? $_GET['name'] ?? ''),
        ];
        $filters = array_filter($filters);

        $roles = $this->roleRepo->getAll($filters); // Убираем пагинацию
        $totalRoles = count($roles);

        View::render('roles/index', [
            'roles' => $roles,
            'filters' => $filters
        ]);
    }

    /**
     * Показать форму создания новой роли
     */
    public function create(): void
    {
        if (!Auth::can('role_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // 1. Получаем информацию о текущем пользователе
        $currentUser = Auth::user();
        if (!$currentUser) {
            // Не должно произойти, так как проверка 'auth' уже была
            http_response_code(401);
            echo "Не авторизован.";
            return;
        }

        // 2. Определяем, является ли он суперадмином
        $isSuperAdmin = !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;

        // 3. Получаем список модулей для группировки прав в форме
        $modules = $this->permRepo->getModules();

        // 4. Получаем список всех прав для отображения в форме создания (новое)
        $allPermissions = $this->permRepo->getAll(); // <-- Исправлено: определение переменной

        // 5. Передаем данные в представление
        View::render('roles/create', [
            'modules' => $modules,
            'allPermissions' => $allPermissions, // <-- Передаем переменную
            'isSuperAdmin' => $isSuperAdmin
        ]);
    }

    /**
     * Обработать отправку формы создания новой роли
     */
    public function store(): void
    {
        if (!Auth::can('role_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        // 1. Получение и валидация данных из POST-запроса
        $name = trim($_POST['name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $isSystem = isset($_POST['is_system']) && $_POST['is_system'] == '1';

        // 2.1. Получение выбранных прав (новое)
        $selectedPermissionIds = $_POST['permissions'] ?? [];
        // Убедимся, что это массив и содержит только целые числа
        if (!is_array($selectedPermissionIds)) {
            $selectedPermissionIds = [];
        }
        $selectedPermissionIds = array_filter(array_map('intval', $selectedPermissionIds));

        // --- Простая валидация на стороне сервера ---
        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'Имя роли обязательно для заполнения.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Имя роли может содержать только латинские буквы, цифры и подчеркивание.';
        } else {
            // Проверяем уникальность
            if ($this->roleRepo->findByName($name)) {
                $errors['name_unique'] = 'Роль с таким именем уже существует.';
            }
        }

        if (empty($displayName)) {
            $errors['display_name'] = 'Отображаемое имя роли обязательно для заполнения.';
        }

        // --- Конец валидации ---

        if (!empty($errors)) {
            // Если есть ошибки, возвращаем пользователя на форму с ошибками
            $_SESSION['errors'] = $errors; // <-- Используем массив ошибок
            // Сохраняем введенные данные для повторного отображения в форме
            $_SESSION['old_input'] = [
                'name' => $name,
                'display_name' => $displayName,
                'is_system' => $isSystem ? '1' : '0',
                'permissions' => $selectedPermissionIds // <-- Сохраняем выбранные права
            ];
            header('Location: /admin/roles/create');
            exit;
        }

        // 2. Создание роли в БД
        // ИСПРАВЛЕНО: Передаем аргументы как строки, соответствующие сигнатуре RoleRepository::create
        $roleId = $this->roleRepo->create($name, $displayName, $isSystem);
        
        if ($roleId === false) {
            $_SESSION['error'] = 'Не удалось создать роль. Попробуйте позже.';
            // Сохраняем введенные данные для повторного отображения в форме
            $_SESSION['old_input'] = [
                'name' => $name,
                'display_name' => $displayName,
                'is_system' => $isSystem ? '1' : '0',
                'permissions' => $selectedPermissionIds // <-- Сохраняем выбранные права
            ];
            header('Location: /admin/roles/create');
            exit;
        }

        // 3. Назначение прав роли (если они были выбраны)
        // Используем исправленную логику из метода update
        foreach ($selectedPermissionIds as $permId) {
            if ($permId > 0) {
                $this->roleRepo->setPermissionForRole($roleId, $permId, true);
            }
        }

        $_SESSION['success'] = 'Роль успешно создана.';
        // Очищаем сохраненные данные после успешного создания
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/roles');
        exit;
    }

    /**
     * Показать форму редактирования роли
     */
    public function edit(int $id): void
    {
        if (!Auth::can('role_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $role = $this->roleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/roles');
            exit;
        }

        // Нельзя редактировать системные роли, кроме как суперадмин
        if ($role['is_system'] && !Auth::isSuperAdmin()) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Получаем все права и права, уже назначенные этой роли
        $allPermissions = $this->permRepo->getAll();
        $rolePermissions = $this->roleRepo->getPermissionsForRole($id);

        // Создаем массив ID назначенных прав для удобства проверки в форме
        $assignedPermissionIds = array_column($rolePermissions, 'id');

        // Группируем все права по модулям для отображения в форме
        $modules = $this->permRepo->getModules();
        $groupedPermissions = [];
        foreach ($allPermissions as $perm) {
            $groupedPermissions[$perm['module']][] = $perm;
        }

        View::render('roles/edit', [
            'role' => $role,
            'groupedPermissions' => $groupedPermissions,
            'assignedPermissionIds' => $assignedPermissionIds,
            'modules' => $modules
        ]);
    }

    /**
     * Обработать отправку формы редактирования роли
     */
    public function update(int $id): void
    {
        if (!Auth::can('role_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        $role = $this->roleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/roles');
            exit;
        }
        
        if ($role['is_system'] && !Auth::isSuperAdmin()) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        // 1. Получение и валидация данных
        $name = trim($_POST['name'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        // is_system менять нельзя
        
        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'Имя роли обязательно для заполнения.';
        } else if (preg_match('/[^a-z0-9_]/i', $name)) {
            $errors['name'] = 'Имя роли может содержать только латинские буквы, цифры и подчеркивание.';
        } else {
            // Проверяем уникальность (исключая текущую роль)
            $existingRole = $this->roleRepo->findByName($name);
            if ($existingRole && $existingRole['id'] != $id) {
                $errors['name_unique'] = 'Роль с таким именем уже существует.';
            }
        }
        
        if (empty($displayName)) {
            $errors['display_name'] = 'Отображаемое имя роли обязательно для заполнения.';
        }
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors; // <-- Используем массив ошибок
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/roles/{$id}/edit");
            exit;
        }
        
        // 2. Обновление данных роли
        // ИСПРАВЛЕНО: Передаем данные как ассоциативный массив
        $result = $this->roleRepo->update($id, [
            'name' => $name,
            'display_name' => $displayName
            // is_system не обновляется
        ]);
        
        if (!$result) {
            $_SESSION['error'] = 'Не удалось обновить роль. Попробуйте позже.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/roles/{$id}/edit");
            exit;
        }
        
        // 3. Обновление прав роли
        // Простая логика: сначала удаляем все права, потом добавляем выбранные
        // В реальном приложении лучше использовать дельты
        $this->roleRepo->removeAllPermissionsFromRole($id);
        
        $selectedPermissions = $_POST['permissions'] ?? [];
        // Убедимся, что это массив и содержит только целые числа
        if (!is_array($selectedPermissions)) {
        $selectedPermissions = [];
        }
        $selectedPermissionIds = array_filter(array_map('intval', $selectedPermissions));
        
        foreach ($selectedPermissionIds as $permId) {
            if ($permId > 0) {
                $this->roleRepo->setPermissionForRole($id, $permId, true);
            }
        }
        
        $_SESSION['success'] = 'Роль успешно обновлена.';
        // Очищаем сохраненные данные после успешного обновления
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/roles');
        exit;
    }

    /**
     * Удалить роль
     */
    public function delete(int $id): void
    {
        if (!Auth::can('role_delete')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $role = $this->roleRepo->findById($id);
        if (!$role) {
            $_SESSION['error'] = 'Роль не найдена.';
            header('Location: /admin/roles');
            exit;
        }

        if ($role['is_system']) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        $result = $this->roleRepo->delete($id);

        if (!$result) {
            $_SESSION['error'] = 'Не удалось удалить роль. Возможно, это системная роль.';
        } else {
            $_SESSION['success'] = 'Роль успешно удалена.';
        }

        header('Location: /admin/roles');
        exit;
    }
}
?>