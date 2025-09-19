<?php
// app/Middleware/AuthMiddleware.php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    /**
     * Обработать запрос
     * @throws \Exception Если не авторизован
     */
    public function handle(): void
    {
        if (!Auth::check()) {
            // Не авторизован
            http_response_code(401);
            // Перенаправляем на страницу логина
            header('Location: /login');
            exit;
        }
        // Если авторизован, выполнение продолжится
    }
}
?>