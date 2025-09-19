<?php
// app/Controllers/HomeController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

class HomeController
{
    /**
     * Обработка запроса к корню сайта /
     */
    public function index(): void
    {
        // Проверяем аутентификацию
        if (Auth::check()) {
            // Если авторизован - перенаправляем в админку
            header('Location: /admin');
        } else {
            // Если не авторизован - на страницу логина
            header('Location: /login');
        }
        exit;
    }
}
?>