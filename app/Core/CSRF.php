<?php
// app/Core/CSRF.php

declare(strict_types=1);

namespace App\Core;

class CSRF
{
    /**
     * Сгенерировать и сохранить CSRF-токен
     */
    public static function generate(): string
    {
        // Проверим, существует ли сессия
        if (session_status() !== PHP_SESSION_ACTIVE) {
             error_log("CSRF::generate() вызван до session_start()");
             // Попробуем запустить сессию
             if (session_status() === PHP_SESSION_NONE) {
                 session_start();
             }
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
             error_log("CSRF токен сгенерирован: " . $_SESSION['csrf_token']);
        } else {
             error_log("CSRF токен уже существует: " . $_SESSION['csrf_token']);
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Проверить CSRF-токен
     */
    public static function validate(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $isValid = hash_equals($sessionToken, $token);
         error_log("CSRF валидация: " . ($isValid ? "УСПЕХ" : "НЕУДАЧА") . " (сессия: $sessionToken, запрос: $token)");
        return $isValid;
    }

    /**
     * Получить текущий токен (для отображения в форме)
     */
    public static function token(): string
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}