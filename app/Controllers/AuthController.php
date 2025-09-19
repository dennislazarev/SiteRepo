<?php
// app/Controllers/AuthController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Services\RateLimitService;
use App\Core\Database;

class AuthController
{
    /**
     * Показать форму входа
     */
    public function showLogin(): void
    {
        // Если пользователь уже авторизован — редирект в админку
        if (Auth::check()) {
            header('Location: /admin');
            exit;
        }

        // Генерируем CSRF-токен
        $csrfToken = CSRF::generate();
        
        // Проверяем, заблокирован ли IP
        $blockedUntil = null;
        $isBlocked = false;
        $rateLimit = new RateLimitService();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        if ($rateLimit->isBlocked($ip)) {
            // Получаем время окончания блокировки
            $blockedUntil = $rateLimit->getBlockedUntil($ip);
            $isBlocked = true;
        }

        // Рендерим форму входа с минимальным layout'ом
        self::renderLoginView('auth/login', [
            'csrfToken' => $csrfToken,
            'login'     => $_SESSION['last_login_attempt'] ?? '',
            'blockedUntil' => $blockedUntil, // Передаем время окончания блокировки в шаблон
            'isBlocked' => $isBlocked
        ]);
    }

    /**
     * Обработка входа
     */
    public function login(): void
    {
        // Проверка CSRF-токена
        if (!CSRF::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['error_persistent'][] = 'Неверный CSRF-токен.';
            header('Location: /login');
            exit;
        }

        $login    = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        // Сохраняем логин для автозаполнения при ошибке
        $_SESSION['last_login_attempt'] = $login;

        if (empty($login) || empty($password)) {
            $_SESSION['error_persistent'][] = 'Логин и пароль обязательны.';
            header('Location: /login');
            exit;
        }

        $rateLimit = new RateLimitService();
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Проверяем блокировку ДО попытки входа
        if ($rateLimit->isBlocked($ip)) {
            $_SESSION['error_rate_limit'] = 'Слишком много неудачных попыток.';
            header('Location: /login');
            exit;
        }

        // Попытка аутентификации
        $result = Auth::attempt($login, $password);

        if ($result === true) {
            // Успешный вход
            unset($_SESSION['last_login_attempt']);
            $rateLimit->clearAttempts($ip); // Полный сброс при успешном входе
            $_SESSION['success'] = 'Вы успешно вошли в систему.';
            header('Location: /admin');
            exit;
        } else {
            // Ошибка входа - $result содержит сообщение об ошибке
            // Логируем попытку
            $rateLimit->logAttempt($ip, $login);
            
            // Получаем текущее количество попыток
            $attempts = $rateLimit->getAttempts($ip);
            
            // Проверим снова, может быть пользователь уже заблокирован этой попыткой
            if ($rateLimit->isBlocked($ip)) {
                // Пользователь заблокирован этой попыткой
                $_SESSION['error_rate_limit'] = 'Слишком много неудачных попыток.';
            } else {
                // Просто сообщение об ошибке входа
                $_SESSION['error_persistent'][] = $result;
            }
            header('Location: /login');
            exit;
        }
    }

    /**
     * Выход из системы
     */
    public function logout(): void
    {
        error_log("AuthController::logout() вызван");
        
        // Выполняем выход через Auth
        Auth::logout();
        
        // Устанавливаем сообщение об успехе в новую сессию (она начнется при следующем session_start())
        // Но так как мы уничтожили сессию, нужно быть аккуратным.
        // Лучше использовать параметр URL или установить новую сессию
        
        // Вариант 1: Установить сообщение через URL (простой и надежный)
        // header('Location: /login?logout=success');
        
        // Вариант 2: Запустить новую сессию и установить сообщение в ней
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['success'] = 'Вы успешно вышли из системы.';
        
        error_log("Редирект на /login после выхода.");
        header('Location: /login');
        exit;
    }

    /**
     * Статический метод для рендеринга страницы логина с минимальным layout'ом
     * @param string $view Путь к файлу представления (например, 'auth/login')
     * @param array $data Данные для передачи в представление
     */
    private static function renderLoginView(string $view, array $data = []): void
    {
        // Извлекаем данные в переменные
        extract($data, EXTR_SKIP);

        // Формируем путь к файлу основного контента
        $viewPath = dirname(__DIR__) . "/Views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new \Exception("Представление не найдено: {$viewPath}");
        }

        // Начинаем буферизацию вывода для основного контента
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Подключаем минимальный шаблон
        require dirname(__DIR__) . '/Views/layouts/minimal.php';
    }
}