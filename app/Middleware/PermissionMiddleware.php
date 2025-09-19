<?php
// app/Middleware/PermissionMiddleware.php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\View;

class PermissionMiddleware
{
    private string $permissionName;

    public function __construct(string $permissionName)
    {
        $this->permissionName = $permissionName;
    }

    public function handle(): void
    {
        if (!Auth::can($this->permissionName)) {
            // Доступ запрещен
            http_response_code(403);
                   
            \App\Core\View::render('errors/403', [
                 'message' => "Недостаточно прав (" . htmlspecialchars($this->permissionName) . ")."
            ]);
            exit;
            
            // Вариант 3: Прямой вывод HTML (если View::render ещё не готов для ошибок)
             echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ запрещен</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">Доступ запрещен</h4>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            У вас нет прав для выполнения этого действия или просмотра этой страницы.
                            <br>
                            Необходимое право: <code>{$this->permissionName}</code>
                        </p>
                        <a href="/admin" class="btn btn-primary">Перейти на главную</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
            exit;
        }
    }
}
?>