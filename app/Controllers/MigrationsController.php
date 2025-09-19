<?php
// app/Controllers/MigrationsController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Migrations\MigrationRunner; // Предполагаем, что MigrationRunner уже существует и адаптирован

/**
 * Контроллер для управления миграциями через системное меню
 */
class MigrationsController
{
    private MigrationRunner $migrationRunner;

    public function __construct()
    {
        // Проверка авторизации и прав должна быть на уровне маршрутизатора/middleware
        // Здесь мы считаем, что пользователь уже авторизован, является суперадмином и имеет доступ

        $this->migrationRunner = new MigrationRunner();
    }

    /**
     * Отобразить страницу управления миграциями
     */
    public function index(): void
    {
        // --- Проверка прав доступа ---
        // Уже проверяется в маршруте через middleware 'permission:system_view'
        // if (!Auth::can('system_view')) {
        //     http_response_code(403);
        //     echo "Доступ запрещен.";
        //     exit;
        // }

        // --- Получение данных о миграциях ---
        $availableMigrations = $this->migrationRunner->getAvailableMigrations();
        $executedMigrations = $this->migrationRunner->getExecutedMigrations();
        $pendingMigrations = array_diff($availableMigrations, $executedMigrations);

        // --- Передача данных в представление ---
        View::render('migrations/migrations', [
            'availableMigrations' => $availableMigrations,
            'executedMigrations' => $executedMigrations,
            'pendingMigrations' => $pendingMigrations,
            'currentUser' => Auth::user()
        ]);
    }

    /**
     * Запустить миграцию
     */
    public function run(): void
    {
        // --- Проверка прав доступа ---
        // Уже проверяется в маршруте через middleware 'permission:system_edit'
        // if (!Auth::can('system_edit')) {
        //     http_response_code(403);
        //     echo "Доступ запрещен.";
        //     exit;
        // }

        $migrationName = trim($_POST['migration'] ?? '');

        if (empty($migrationName)) {
            $_SESSION['error'] = 'Не указано имя миграции для запуска.';
            header('Location: /admin/system/migrations');
            exit;
        }

        // --- Запуск миграции ---
        if ($this->migrationRunner->runMigration($migrationName)) {
            $_SESSION['success'] = "Миграция {$migrationName} успешно выполнена.";
        } else {
            $_SESSION['error'] = "Ошибка при выполнении миграции {$migrationName}.";
        }

        header('Location: /admin/system/migrations');
        exit;
    }

    /**
     * Откатить миграцию
     */
    public function rollback(): void
    {
        // --- Проверка прав доступа ---
        // Уже проверяется в маршруте через middleware 'permission:system_edit'
        // if (!Auth::can('system_edit')) {
        //     http_response_code(403);
        //     echo "Доступ запрещен.";
        //     exit;
        // }

        $migrationName = trim($_POST['migration'] ?? '');

        if (empty($migrationName)) {
            $_SESSION['error'] = 'Не указано имя миграции для отката.';
            header('Location: /admin/system/migrations');
            exit;
        }

        // --- Откат миграции ---
        if ($this->migrationRunner->rollbackMigration($migrationName)) {
            $_SESSION['success'] = "Миграция {$migrationName} успешно откачена.";
        } else {
            $_SESSION['error'] = "Ошибка при откате миграции {$migrationName}.";
        }

        header('Location: /admin/system/migrations');
        exit;
    }
}
?>