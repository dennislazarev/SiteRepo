<?php
// app/Controllers/AdminController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;

class AdminController
{
    /**
     * Показать dashboard админки
     */
    public function dashboard(): void
    {
        // Проверяем аутентификацию (это должно делаться middleware в будущем)
        // В routes.php маршрут /admin уже защищен middleware 'auth'
        if (!Auth::check()) {
           header('Location: /login');
           exit;
        }

        $user = Auth::user();
        
        // Передаем данные пользователя и доступные модули в представление
        $data = [
            'user' => $user,
            'can' => [
                'view_roles' => Auth::can('role_view'),
                'view_permissions' => Auth::can('permission_view'),
                'view_employees' => Auth::can('employee_view'),
                'view_users' => Auth::can('user_view'),
                'view_tabs' => Auth::can('tab_view'),
                'view_fields' => Auth::can('field_view'),
                'view_calendar' => Auth::can('calendar_view'),
                'view_library' => Auth::can('library_view'),
                'view_system' => Auth::can('system_view'),
            ]
        ];

        View::render('admin/dashboard', $data);
    }
}
?>