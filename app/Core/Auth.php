<?php
// app/Core/Auth.php

declare(strict_types=1);

namespace App\Core;

use App\Models\EmployeeRepository;
use App\Services\RateLimitService; 

class Auth
{
    /**
     * Проверить, обладает ли текущий пользователь указанным правом
     * @param string $permissionName Имя права (например, 'user_view')
     * @return bool True, если есть право или пользователь - суперадмин, иначе False
     */
    public static function can(string $permissionName): bool
    {
        // 1. Проверка авторизации
        if (!self::check()) {
            return false;
        }

        // 2. Получение данных пользователя
        $user = self::user();
        if (!$user) {
            return false;
        }

        // 3. Суперадмин имеет все права
        if (!empty($user['is_superadmin']) && (int)$user['is_superadmin'] === 1) {
            return true;
        }

        // 4. Проверка прав обычного пользователя
        // Получаем список прав, разрешенных для роли пользователя
        // Предполагаем, что в $user есть ключ 'role_id'
        $roleId = $user['role_id'] ?? null;
        if (!$roleId) {
            // У пользователя нет роли, доступ запрещен
            return false;
        }

        // Используем EmployeeRepository для получения прав роли
        $repo = new EmployeeRepository();
        $rolePermissions = $repo->getPermissionsForRole((int)$roleId);

        // Проверяем, есть ли нужное право в списке разрешенных
        return in_array($permissionName, $rolePermissions);
    }

    public static function check(): bool
    {
        // Проверяем, существует ли сессия и не истекло ли время
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || self::isSessionExpired()) {
            return false;
        }
        // Обновляем время последней активности
        $_SESSION['last_activity'] = time();
        return true;
    }

    /**
     * Проверить, истекла ли сессия
     * @return bool
     */
    public static function isSessionExpired(): bool
    {
        $lifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 15); // минуты
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        if ($lastActivity > 0 && (time() - $lastActivity) > ($lifetime * 60)) {
            return true;
        }
        return false;
    }

    /**
     * Получить данные текущего пользователя
     * @return array|null
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        $userId = $_SESSION['user_id'];
        $repo   = new EmployeeRepository();
        return $repo->findById($userId);
    }

    /**
     * Попытка входа
     * @param string $login Логин пользователя
     * @param string $password Пароль пользователя
     * @return true|string true при успехе, строка с ошибкой при неудаче
     */
    public static function attempt(string $login, string $password): bool|string
    {
        $rateLimit = new RateLimitService();
        $ip        = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($rateLimit->isBlocked($ip)) {
            return 'Слишком много неудачных попыток.';
        }

        $repo = new EmployeeRepository();
        $user = $repo->findByLogin($login); // Предполагаем, что login = name

        if (!$user) {
            //$rateLimit->logAttempt($ip, $login);
            return 'Неверный логин или пароль.';
        }

        // Проверка активности
        if (!$user['is_active']) {
            //$rateLimit->logAttempt($ip, $login); // Считаем неудачной попыткой
            return 'Аккаунт заблокирован.';
        }

        // Проверка пароля
        if (!password_verify($password, $user['password_hash'])) {
            //$rateLimit->logAttempt($ip, $login);
            return 'Неверный логин или пароль.';
        }

        // Проверка IP (для суперадмина)
        if (!empty($user['is_superadmin']) && (int)$user['is_superadmin'] === 1 && !self::isIpAllowed($user, $_SERVER['REMOTE_ADDR'] ?? 'unknown')) {
            error_log("Попытка входа суперадмина с недопустимого IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            //$rateLimit->logAttempt($ip, $login);
            return 'Доступ с этого IP запрещён.';
        }

        // Успешная аутентификация
        self::loginUser($user);
        $rateLimit->clearAttempts($ip); // Сброс неудачных попыток
        return true;
    }

    /**
     * Авторизовать пользователя в сессии
     * @param array $user Данные пользователя из БД
     */
    private static function loginUser(array $user): void
    {
        // Регенерация ID сессии для безопасности
        session_regenerate_id(true);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user_uuid']     = $user['uuid'];
        $_SESSION['user_name']     = $user['name'];
        $_SESSION['is_superadmin'] = (bool) ($user['is_superadmin'] ?? false);
        $_SESSION['last_activity'] = time();

        // Обновляем время последнего входа в БД
        $repo = new EmployeeRepository();
        $repo->updateLastLogin($user['id']);
    }

    /**
     * Проверка IP для суперадмина
     * @todo Реализовать, если будет храниться список разрешённых IP
     */
    private static function isIpAllowed(array $user, string $ip): bool
    {
        // Пока что разрешаем все IP для суперадмина
        // В будущем можно добавить проверку из БД или .env
        return true;
    }

    /**
     * Выход пользователя
     */
    public static function logout(): void
    {
        // 1. Очищаем все данные сессии
        $_SESSION = [];

        // 2. Если сессия активна, попробуем закрыть её
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // 3. Удаляем cookie сессии, если она была установлена
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            
            // Устанавливаем cookie в прошлом, чтобы браузер её удалил
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // 4. Принудительно уничтожаем сессию на сервере
        // session_destroy() удаляет файл данных сессии
        session_destroy();
    }

    // ... (остальные методы attempt, loginUser, logout остаются без изменений) ...
}
?>