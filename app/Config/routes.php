<?php
// app/Config/routes.php

return [
    // Маршрут для корня сайта
    ['GET', '/', ['App\Controllers\HomeController', 'index']],
    
    // Гостевые маршруты
    ['GET', '/login', ['App\Controllers\AuthController', 'showLogin']],
    ['POST', '/login', ['App\Controllers\AuthController', 'login'], ['csrf']], // rate_limit временно отключен
    
    // Маршрут для выхода
    ['POST', '/logout', ['App\Controllers\AuthController', 'logout'], ['auth']],
    
    // Защищённые маршруты
    ['GET', '/admin', ['App\Controllers\AdminController', 'dashboard'], ['auth']],

    // --- МАРШРУТЫ ДЛЯ РОЛЕЙ ---
    // Список ролей
    ['GET', '/admin/roles', ['App\Controllers\RolesController', 'index'], ['auth', 'permission:role_view']],
    
    // Форма создания роли
    ['GET', '/admin/roles/create', ['App\Controllers\RolesController', 'create'], ['auth', 'permission:role_create']],
    
    // Обработка создания роли
    ['POST', '/admin/roles', ['App\Controllers\RolesController', 'store'], ['auth', 'csrf', 'permission:role_create']],
    
    // Форма редактирования роли
    ['GET', '/admin/roles/{id}/edit', ['App\Controllers\RolesController', 'edit'], ['auth', 'permission:role_edit']],
    
    // Обработка редактирования роли
    ['POST', '/admin/roles/{id}', ['App\Controllers\RolesController', 'update'], ['auth', 'csrf', 'permission:role_edit']],
    
    // Удаление роли (через POST, так как это изменение состояния)
    ['POST', '/admin/roles/{id}/delete', ['App\Controllers\RolesController', 'delete'], ['auth', 'csrf', 'permission:role_delete']],
    // --- КОНЕЦ МАРШРУТОВ ДЛЯ РОЛЕЙ ---

    // --- МАРШРУТЫ ДЛЯ ПРАВ ---
    // Список прав
    ['GET', '/admin/permissions', ['App\Controllers\PermissionsController', 'index'], ['auth', 'permission:permission_view']],
    
    // Форма создания права
    ['GET', '/admin/permissions/create', ['App\Controllers\PermissionsController', 'create'], ['auth', 'permission:permission_create']],
    
    // Обработка создания права
    ['POST', '/admin/permissions', ['App\Controllers\PermissionsController', 'store'], ['auth', 'csrf', 'permission:permission_create']],
    
    // Форма редактирования права
    ['GET', '/admin/permissions/{id}/edit', ['App\Controllers\PermissionsController', 'edit'], ['auth', 'permission:permission_edit']],
    
    // Обработка редактирования права
    ['POST', '/admin/permissions/{id}', ['App\Controllers\PermissionsController', 'update'], ['auth', 'csrf', 'permission:permission_edit']],
    
    // Удаление права
    ['POST', '/admin/permissions/{id}/delete', ['App\Controllers\PermissionsController', 'delete'], ['auth', 'csrf', 'permission:permission_delete']],
    // --- КОНЕЦ МАРШРУТОВ ДЛЯ ПРАВ ---

    // --- Маршруты для управления Сотрудниками ---
    // Список сотрудников
    ['GET', '/admin/employees', ['App\Controllers\EmployeesController', 'index'], ['auth', 'permission:employee_view']],
    // Форма создания сотрудника
    ['GET', '/admin/employees/create', ['App\Controllers\EmployeesController', 'create'], ['auth', 'permission:employee_create']], 
    // Обработка создания сотрудника
    ['POST', '/admin/employees', ['App\Controllers\EmployeesController', 'store'], ['auth', 'csrf', 'permission:employee_create']], 
    // Форма редактирования сотрудника
    ['GET', '/admin/employees/{id}/edit', ['App\Controllers\EmployeesController', 'edit'], ['auth', 'permission:employee_edit']], 
    // Обработка редактирования сотрудника
    ['POST', '/admin/employees/{id}', ['App\Controllers\EmployeesController', 'update'], ['auth', 'csrf', 'permission:employee_edit']], 
    // Обработка "удаления" сотрудника (soft delete)
    ['POST', '/admin/employees/{id}/delete', ['App\Controllers\EmployeesController', 'delete'], ['auth', 'csrf', 'permission:employee_delete']], 
    // --- Конец маршрутов для управления Сотрудниками ---

    // --- МАРШРУТЫ ДЛЯ УПРАВЛЕНИЯ ПОЛЬЗОВАТЕЛЯМИ ФРОНТЕНДА ---
    // Список пользователей
    ['GET', '/admin/users', ['App\Controllers\UsersController', 'index'], ['auth', 'permission:user_view']], 
    
    // Форма создания пользователя
    ['GET', '/admin/users/create', ['App\Controllers\UsersController', 'create'], ['auth', 'permission:user_create']], 
    
    // Обработка создания пользователя
    ['POST', '/admin/users', ['App\Controllers\UsersController', 'store'], ['auth', 'csrf', 'permission:user_create']],
    
    // Форма редактирования пользователя
    ['GET', '/admin/users/{id}/edit', ['App\Controllers\UsersController', 'edit'], ['auth', 'permission:user_edit']],
    
    // Обработка редактирования пользователя
    ['POST', '/admin/users/{id}', ['App\Controllers\UsersController', 'update'], ['auth', 'csrf', 'permission:user_edit']],
    
    // "Удаление" пользователя (soft delete)
    ['POST', '/admin/users/{id}/delete', ['App\Controllers\UsersController', 'delete'], ['auth', 'csrf', 'permission:user_delete']], 
    
    // Список ролей пользователей
    ['GET', '/admin/user-roles', ['App\Controllers\UserRolesController', 'index'], ['auth', 'permission:user_role_view']],
    
    // Форма создания роли пользователя
    ['GET', '/admin/user-roles/create', ['App\Controllers\UserRolesController', 'create'], ['auth', 'permission:user_role_create']],
    
    // Обработка создания роли пользователя
    ['POST', '/admin/user-roles', ['App\Controllers\UserRolesController', 'store'], ['auth', 'csrf', 'permission:user_role_create']],
    
    // Форма редактирования роли пользователя
    ['GET', '/admin/user-roles/{id}/edit', ['App\Controllers\UserRolesController', 'edit'], ['auth', 'permission:user_role_edit']],
    
    // Обработка редактирования роли пользователя
    ['POST', '/admin/user-roles/{id}', ['App\Controllers\UserRolesController', 'update'], ['auth', 'csrf', 'permission:user_role_edit']],
    
    // Удаление роли пользователя
    ['POST', '/admin/user-roles/{id}/delete', ['App\Controllers\UserRolesController', 'delete'], ['auth', 'csrf', 'permission:user_role_delete']],

    // Список прав пользователей
    ['GET', '/admin/user-permissions', ['App\Controllers\UserPermissionsController', 'index'], ['auth', 'permission:user_permission_view']],
    
    // Форма создания права пользователя
    ['GET', '/admin/user-permissions/create', ['App\Controllers\UserPermissionsController', 'create'], ['auth', 'permission:user_permission_create']],
    
    // Обработка создания права пользователя
    ['POST', '/admin/user-permissions', ['App\Controllers\UserPermissionsController', 'store'], ['auth', 'csrf', 'permission:user_permission_create']],
    
    // Форма редактирования права пользователя
    ['GET', '/admin/user-permissions/{id}/edit', ['App\Controllers\UserPermissionsController', 'edit'], ['auth', 'permission:user_permission_edit']],
    
    // Обработка редактирования права пользователя
    ['POST', '/admin/user-permissions/{id}', ['App\Controllers\UserPermissionsController', 'update'], ['auth', 'csrf', 'permission:user_permission_edit']],
    
    // Удаление права пользователя
    ['POST', '/admin/user-permissions/{id}/delete', ['App\Controllers\UserPermissionsController', 'delete'], ['auth', 'csrf', 'permission:user_permission_delete']],
    // --- КОНЕЦ МАРШРУТОВ ДЛЯ ПРАВ ПОЛЬЗОВАТЕЛЕЙ ---

    // --- Маршруты для системы миграций ---
    ['GET', '/admin/migrations', ['App\Controllers\MigrationsController', 'index'], ['auth', 'permission:system_view']],
    ['POST', '/admin/migrations/run', ['App\Controllers\MigrationsController', 'run'], ['auth', 'csrf', 'permission:system_edit']],
    ['POST', '/admin/migrations/rollback', ['App\Controllers\MigrationsController', 'rollback'], ['auth', 'csrf', 'permission:system_edit']],
    // --- Конец маршрутов для системы миграций ---

];
?>