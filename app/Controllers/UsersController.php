<?php
// app/Controllers/UsersController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\UserService;
// use DateTime; // Не используется напрямую в контроллере, логика валидации дат в сервисе

/**
 * Контроллер для управления пользователями фронтенда
 */
class UsersController
{
    private UserService $userService;

    public function __construct()
    {
        // Проверка авторизации должна быть на уровне маршрутизатора/middleware
        // Здесь мы считаем, что пользователь уже авторизован и имеет доступ

        $this->userService = new UserService();
    }

    /**
     * Отобразить список всех пользователей с пагинацией и фильтрами
     */
    public function index(): void
    {
        // --- Проверка прав доступа ---
        // Предполагается, что Auth::can() учитывает is_superadmin или другие критерии
        if (!Auth::can('user_view')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // --- Обработка параметров пагинации и фильтрации ---
        // Получение параметров фильтрации из GET-запроса
        $filters = [
            'fio' => trim($_GET['fio'] ?? ''),
            'login' => trim($_GET['login'] ?? ''),
            'phone' => trim($_GET['phone'] ?? ''),
            'is_active' => $_GET['is_active'] ?? '', // Может быть '', '0', '1'
            'role_id' => (int)($_GET['role_id'] ?? 0) ?: null
        ];
        
        // Убираем пустые фильтры, чтобы не мешали запросу
        // Лучше оставить фильтрацию на уровне сервиса/репозитория
        // $filters = array_filter($filters, function($value) {
        //      return $value !== '' && $value !== null;
        // });

        // --- Получение данных через сервис ---
        $users = $this->userService->getAll($filters);
        
        // Получаем список ролей для фильтра и формы
        $roles = $this->userService->getUserRoleOptions(); // Используем метод сервиса

        // --- Передача данных в представление ---
        View::render('users/index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => $roles, // Передаем список ролей для фильтра в представлении
            'currentUser' => Auth::user()
        ]);
    }

    /**
     * Показать форму создания нового пользователя
     */
    public function create(): void
    {
        // --- Проверка прав доступа ---
        if (!Auth::can('user_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Получаем список ролей через сервис
        $roles = $this->userService->getUserRoleOptions(); 

        // Определяем роль по умолчанию
        $defaultRoleId = null;
        foreach ($roles as $role) {
            if (!empty($role['is_default']) && (int)$role['is_default'] === 1) {
                $defaultRoleId = (int)$role['id'];
                break;
            }
        }

        // Получаем текущего пользователя из уже существующей сессии
        $currentUser = Auth::user(); 
        if (!$currentUser) {
            // Не должно произойти, так как проверка 'auth' уже была
            http_response_code(401);
            echo "Не авторизован.";
            return;
        }

        $isSuperAdmin = !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;
        
        // Передаем данные в представление
        View::render('users/create', [
            'roles' => $roles,
            'currentUser' => $currentUser, 
            'isSuperAdmin' => $isSuperAdmin,
            'defaultRoleId' => $defaultRoleId 
        ]);
    }

    /**
     * Обработать отправку формы создания нового пользователя
     */
    public function store(): void
    {
        // --- Проверка прав доступа ---
        if (!Auth::can('user_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // 1. Получение и валидация данных из POST-запроса
        $fio = trim($_POST['fio'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] == '1';
        $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
        // Поля для социальных сетей
        $telegramId = !empty($_POST['telegram_id']) ? trim($_POST['telegram_id']) : null;
        $whatsappId = !empty($_POST['whatsapp_id']) ? trim($_POST['whatsapp_id']) : null;
        $viberId = !empty($_POST['viber_id']) ? trim($_POST['viber_id']) : null;
        $vkId = !empty($_POST['vk_id']) ? (int)trim($_POST['vk_id']) : null;
        $okId = !empty($_POST['ok_id']) ? (int)trim($_POST['ok_id']) : null;
        // Поле подписки
        $subscriptionEndsAt = !empty($_POST['subscription_ends_at']) ? trim($_POST['subscription_ends_at']) : null;

        // --- Валидация через сервис ---
        $errors = $this->userService->validate([
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
            // is_active передаем как строку, так как в валидации ожидается строка '0' или '1'
            'is_active' => $isActive ? '1' : '0', 
            'role_id' => $roleId,
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            'subscription_ends_at' => $subscriptionEndsAt
        ]);
        // --- Конец валидации ---
        
        if (!empty($errors)) {
            // Если есть ошибки, возвращаем пользователя на форму с ошибками
            $_SESSION['errors'] = $errors;
            // Сохраняем введенные данные для повторного отображения в форме
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/users/create');
            exit;
        }
        
        // 2. Создание пользователя через сервис
        $userId = $this->userService->create([
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password' => $password, // Передаем сырой пароль, сервис сам его хэширует
            'is_active' => $isActive, // Передаем как boolean/int
            'role_id' => $roleId,
            // Поля социальных сетей
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            // Поле подписки
            'subscription_ends_at' => $subscriptionEndsAt
        ]);
        
        if ($userId === false) {
            $_SESSION['error'] = 'Не удалось создать пользователя. Попробуйте позже.';
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/users/create');
            exit;
        }
        
        $_SESSION['success'] = 'Пользователь успешно создан.';
        unset($_SESSION['old_input']); // Очищаем сохраненные данные
        unset($_SESSION['errors']);
        header('Location: /admin/users');
        exit;
    }

    /**
     * Показать форму редактирования пользователя
     */
    public function edit(int $id): void
    {
        // --- Проверка прав доступа ---
        if (!Auth::can('user_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        // Также можно добавить проверку, что пользователь не пытается редактировать самого себя, если это нужно

        // Предполагаем, что в UserService есть метод findById
        $user = $this->userService->findById($id); 
        if (!$user) {
            $_SESSION['error'] = 'Пользователь не найден.';
            header('Location: /admin/users');
            exit;
        }
        
        // Получаем список ролей для выпадающего списка через сервис
        $roles = $this->userService->getUserRoleOptions(); 

        // Исправлено: получаем currentUser и isSuperAdmin внутри метода
        $currentUser = Auth::user(); 
        if (!$currentUser) {
             // Не должно произойти, но на всякий случай проверим
             http_response_code(401);
             echo "Не авторизован.";
             return;
        }
        $isSuperAdmin = !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;

        View::render('users/edit', [
            'user' => $user,
            'roles' => $roles,
            'currentUser' => $currentUser, // Передаем переменную currentUser
            'isSuperAdmin' => $isSuperAdmin // Передаем переменную isSuperAdmin
        ]);
    }

    /**
     * Обработать отправку формы редактирования пользователя
     */
    public function update(int $id): void
    {
        // --- Проверка прав доступа ---
        if (!Auth::can('user_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Предполагаем, что в UserService есть метод findById
        $user = $this->userService->findById($id); 
        if (!$user) {
            $_SESSION['error'] = 'Пользователь не найден.';
            header('Location: /admin/users');
            exit;
        }
        
        // 1. Получение и валидация данных
        $fio = trim($_POST['fio'] ?? '');
        $login = trim($_POST['login'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] == '1';
        $roleId = !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null;
        // Поля для социальных сетей
        $telegramId = !empty($_POST['telegram_id']) ? trim($_POST['telegram_id']) : null;
        $whatsappId = !empty($_POST['whatsapp_id']) ? trim($_POST['whatsapp_id']) : null;
        $viberId = !empty($_POST['viber_id']) ? trim($_POST['viber_id']) : null;
        $vkId = !empty($_POST['vk_id']) ? (int)trim($_POST['vk_id']) : null;
        $okId = !empty($_POST['ok_id']) ? (int)trim($_POST['ok_id']) : null;
        // Поле подписки
        $subscriptionEndsAt = !empty($_POST['subscription_ends_at']) ? trim($_POST['subscription_ends_at']) : null;

        // --- Валидация через сервис ---
        // Предполагаем, что в UserService::validate есть параметр $existingId
        $errors = $this->userService->validate([
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password' => $password,
            'password_confirm' => $passwordConfirm,
            // is_active передаем как строку
            'is_active' => $isActive ? '1' : '0', 
            'role_id' => $roleId,
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            'subscription_ends_at' => $subscriptionEndsAt
        ], $id); // <-- Передаем ID для проверки уникальности при обновлении
        // --- Конец валидации ---
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/users/{$id}/edit");
            exit;
        }
        
        // 2. Обновление данных пользователя через сервис
        $result = $this->userService->update($id, [
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password' => $password, // Передаем пароль, сервис сам проверит, нужно ли его обновлять
            'is_active' => $isActive, // Передаем как boolean/int
            'role_id' => $roleId,
            // Поля социальных сетей
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            // Поле подписки
            'subscription_ends_at' => $subscriptionEndsAt
        ]);
        
        if (!$result) {
            $_SESSION['error'] = 'Не удалось обновить данные пользователя. Попробуйте позже.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/users/{$id}/edit");
            exit;
        }
        
        $_SESSION['success'] = 'Данные пользователя успешно обновлены.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/users');
        exit;
    }

    /**
     * "Удалить" пользователя (soft delete)
     */
    public function delete(int $id): void
    {
        // --- Проверка прав доступа ---
        if (!Auth::can('user_delete')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }

        // Предполагаем, что в UserService есть метод findById
        $user = $this->userService->findById($id); 
        if (!$user) {
            $_SESSION['error'] = 'Пользователь не найден.';
            header('Location: /admin/users');
            exit;
        }
        
        // Примечание: В отличие от сотрудников, у пользователей фронтенда
        // нет концепции "суперадмина" на уровне таблицы users.
        // Логика блокировки удаления "особых" пользователей должна быть
        // реализована отдельно, если потребуется (например, по ID или роли).
        
        // Используем сервис для удаления
        $result = $this->userService->delete($id); 
        
        if (!$result) {
            $_SESSION['error'] = 'Не удалось удалить пользователя.';
        } else {
            $_SESSION['success'] = 'Пользователь успешно удален (помечен как удаленный).';
        }
        
        header('Location: /admin/users');
        exit;
    }
}
?>