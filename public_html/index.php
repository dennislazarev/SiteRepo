<?php
// public/index.php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Загрузка переменных окружения
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// --- Новое: Настройка параметров сессии из .env ---
// ВАЖНО: Эти настройки должны идти ДО session_start()

// --- ИСПРАВЛЕННОЕ: Настройка параметров сессии из .env ---
error_log("--- НАЧАЛО НАСТРОЙКИ СЕССИИ ---");
error_log("SESSION_COOKIE_NAME из .env: " . ($_ENV['SESSION_COOKIE_NAME'] ?? 'не задано'));

// 1. Имя сессии
$sessionName = $_ENV['SESSION_COOKIE_NAME'] ?? 'PHPSESSID';
error_log("Установка имени сессии через session_name(): $sessionName");
session_name($sessionName);

// 2. Настройки cookie сессии - ИСПРАВЛЕН МАССИВ
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => (int) ($_ENV['SESSION_LIFETIME'] ?? 0) * 60, // Преобразуем минуты в секунды
    'path'     => $cookieParams['path'],
    'domain'   => $cookieParams['domain'],
    // Если APP_URL использует HTTPS, то secure=true
    'secure'   => filter_var($_ENV['SESSION_COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'httponly' => filter_var($_ENV['SESSION_COOKIE_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'samesite' => $_ENV['SESSION_COOKIE_SAMESITE'] ?? 'Lax', // 'Lax', 'Strict', 'None'
]);
error_log("Параметры cookie сессии установлены.");

// Принудительно устанавливаем use_strict_mode, если указано в .env
$useStrictMode = filter_var($_ENV['SESSION_USE_STRICT_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN);
if ($useStrictMode) {
    ini_set('session.use_strict_mode', '1');
    error_log("session.use_strict_mode включен.");
}

error_log("Вызов session_start()");
session_start();
error_log("session_start() завершен. Текущее имя сессии: " . session_name() . ", ID: " . session_id());
error_log("--- КОНЕЦ НАСТРОЙКИ СЕССИИ ---");

// --- Новое: Регистрация обработчика ошибок ---
\App\Core\ErrorHandler::register();

use App\Core\Router;

// Определяем метод и URI
$method = $_SERVER['REQUEST_METHOD'];
$uri    = $_SERVER['REQUEST_URI'];

// Убираем query string из URI
$uri = parse_url($uri, PHP_URL_PATH);

// Подключаем маршруты
$routes = require_once __DIR__ . '/../app/Config/routes.php';

error_log("ПОСЛЕ session_start(). session_name(): " . session_name());

// Запуск маршрутизатора
$router = new Router($routes);
$router->dispatch($method, $uri);