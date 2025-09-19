<?php
// app/Core/ErrorHandler.php

declare(strict_types=1);

namespace App\Core;

class ErrorHandler
{
    public static function register(): void
    {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleError(int $level, string $message, string $file, int $line): bool
    {
        // Преобразуем ошибку в исключение, если она попадает под настройки error_reporting
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }
        // Не останавливаем стандартный обработчик ошибок
        return false;
    }

    public static function handleException(\Throwable $exception): void
    {
        // Логируем ошибку
        error_log("Непойманное исключение: " . $exception->getMessage() . " в " . $exception->getFile() . " на строке " . $exception->getLine());

        // В режиме разработки можно показать подробности
        $isDebug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($isDebug) {
            // Выводим подробности (только в dev)
            echo "<h1>Ошибка приложения</h1>";
            echo "<p><strong>Тип:</strong> " . get_class($exception) . "</p>";
            echo "<p><strong>Сообщение:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>Файл:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Строка:</strong> " . $exception->getLine() . "</p>";
            echo "<h2>Стек вызовов:</h2>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            // В продакшене показываем общее сообщение
            // Можно также записать в сессию, чтобы показать на следующей странице
            // Но для простоты просто покажем сообщение
            http_response_code(500);
            echo "<h1>Внутренняя ошибка сервера</h1>";
            echo "<p>Произошла ошибка. Пожалуйста, попробуйте позже.</p>";
            // Или, если мы уверены, что сессия еще жива:
            // $_SESSION['error'] = 'Произошла внутренняя ошибка сервера.';
            // header('Location: /'); // Или на предыдущую страницу
            // exit;
        }
        // Останавливаем выполнение
        exit(1);
    }
}