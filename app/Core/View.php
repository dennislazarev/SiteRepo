<?php
// app/Core/View.php

declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * Рендерить представление
     * @param string $view Путь к файлу представления (например, 'auth/login')
     * @param array $data Данные для передачи в представление
     */
    public static function render(string $view, array $data = []): void
    {
        // Извлекаем данные в переменные
        extract($data, EXTR_SKIP);

        // Формируем путь к файлу
        $viewPath = dirname(__DIR__) . "/Views/{$view}.php";

        if (!file_exists($viewPath)) {
            throw new \Exception("Представление не найдено: {$viewPath}");
        }

        // Начинаем буферизацию вывода
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Рендерим основной шаблон
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}