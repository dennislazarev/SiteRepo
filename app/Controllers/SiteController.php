<?php
// app/Controllers/SiteController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

class SiteController
{
    /**
     * Главная страница сайта
     * Перенаправляет в зависимости от статуса авторизации
     */
    public function index(): void
    {
        if (Auth::check()) {
            // Если авторизован - на dashboard
            header('Location: /admin');
        } else {
            // Если не авторизован - на страницу входа
            header('Location: /login');
        }
        exit;
    }
}