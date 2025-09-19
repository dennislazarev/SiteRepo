<?php
// app/Controllers/EmployeesController.php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\EmployeeRepository;
use App\Models\RoleRepository; // Понадобится для получения списка ролей
use App\Services\EmployeeService; // Подключаем новый сервис

/**
 * Контроллер для управления сотрудниками
 */
class EmployeesController
{
    private EmployeeRepository $employeeRepo;
    private RoleRepository $roleRepo; // Для получения списка ролей в формах
    private EmployeeService $employeeService; // Новый сервис

    public function __construct()
    {
        // Проверка авторизации должна быть на уровне маршрутизатора/middleware
        // Здесь мы считаем, что пользователь уже авторизован

        $this->employeeRepo = new EmployeeRepository();
        $this->roleRepo = new RoleRepository(); // Инициализируем RoleRepository
        $this->employeeService = new EmployeeService(); // Инициализируем сервис
    }

    /**
     * Отобразить список всех сотрудников с пагинацией и фильтрами
     */
    public function index(): void
    {
        if (!Auth::can('employee_view')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        // --- Обработка параметров пагинации и фильтрации ---
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20; // Количество записей на страницу
        $offset = ($page - 1) * $limit;

        // Получение параметров фильтрации из GET-запроса
        $filters = [
            'fio' => trim($_GET['fio'] ?? ''),
            'login' => trim($_GET['login'] ?? ''),
            'phone' => trim($_GET['phone'] ?? ''),
            'is_active' => $_GET['is_active'] ?? '', // Может быть '', '0', '1'
            'role_id' => (int)($_GET['role_id'] ?? 0) ?: null
        ];
        
        // Убираем пустые фильтры, чтобы не мешали запросу
        $filters = array_filter($filters, function($value) {
             return $value !== '' && $value !== null;
        });

        // --- Получение данных ---
        $employees = $this->employeeRepo->getAll($filters);
        $totalEmployees = $this->employeeRepo->countAll($filters);
        $totalPages = ceil($totalEmployees / $limit);
        
        // Получаем список ролей для фильтра и формы
        $roles = $this->roleRepo->getAll();

        // --- Передача данных в представление ---
        View::render('employees/index', [
            'employees' => $employees,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'filters' => $filters,
            'roles' => $roles,
            'currentUser' => Auth::user() // <- Эта строка обязательна
        ]);
    }

    /**
     * Показать форму создания нового сотрудника
     */
    public function create(): void
    {
        if (!Auth::can('employee_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        // Получаем список ролей для выпадающего списка
        $roles = $this->roleRepo->getAll();
        
        // Передаем данные в представление
        View::render('employees/create', [
            'roles' => $roles,
            'currentUser' => Auth::user()
        ]);
    }

    /**
     * Обработать отправку формы создания нового сотрудника
     */
    public function store(): void
    {
        if (!Auth::can('employee_create')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        // 1. Получение и валидация данных из POST-запроса
        // --- Валидация через сервис ---
        $errors = $this->employeeService->validate($_POST);
        // --- Конец валидации ---
        
        if (!empty($errors)) {
            // Если есть ошибки, возвращаем пользователя на форму с ошибками
            $_SESSION['errors'] = $errors;
            // Сохраняем введенные данные для повторного отображения в форме
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/employees/create');
            exit;
        }
        
        // 2. Создание сотрудника через сервис
        $employeeId = $this->employeeService->create($_POST);
        
        if ($employeeId === false) {
            $_SESSION['error'] = 'Не удалось создать сотрудника. Попробуйте позже.';
            $_SESSION['old_input'] = $_POST;
            header('Location: /admin/employees/create');
            exit;
        }
        
        $_SESSION['success'] = 'Сотрудник успешно создан.';
        unset($_SESSION['old_input']); // Очищаем сохраненные данные
        unset($_SESSION['errors']);
        header('Location: /admin/employees');
        exit;
    }

    /**
     * Показать форму редактирования сотрудника
     */
    public function edit(int $id): void
    {
        if (!Auth::can('employee_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        // Также можно добавить проверку, что пользователь не пытается редактировать самого себя, если это нужно
        
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            $_SESSION['error'] = 'Сотрудник не найден.';
            header('Location: /admin/employees');
            exit;
        }
        
        // Получаем список ролей для выпадающего списка
        $roles = $this->roleRepo->getAll();
        
        View::render('employees/edit', [
            'employee' => $employee,
            'roles' => $roles,
            'currentUser' => Auth::user()
        ]);
    }

    /**
     * Обработать отправку формы редактирования сотрудника
     */
    public function update(int $id): void
    {
        if (!Auth::can('employee_edit')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            $_SESSION['error'] = 'Сотрудник не найден.';
            header('Location: /admin/employees');
            exit;
        }
        
        // 1. Получение и валидация данных
        // --- Валидация через сервис ---
        $errors = $this->employeeService->validate($_POST, $id);
        // --- Конец валидации ---
        
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/employees/{$id}/edit");
            exit;
        }
        
        // 2. Обновление данных сотрудника через сервис
        $result = $this->employeeService->update($id, $_POST);
        
        if (!$result) {
            $_SESSION['error'] = 'Не удалось обновить данные сотрудника. Попробуйте позже.';
            $_SESSION['old_input'] = $_POST;
            header("Location: /admin/employees/{$id}/edit");
            exit;
        }
        
        $_SESSION['success'] = 'Данные сотрудника успешно обновлены.';
        unset($_SESSION['old_input']);
        unset($_SESSION['errors']);
        header('Location: /admin/employees');
        exit;
    }

    /**
     * Удалить сотрудника (soft delete)
     */
    public function delete(int $id): void
    {
        if (!Auth::can('employee_delete')) {
            http_response_code(403);
            echo "Доступ запрещен.";
            exit;
        }
        
        $employee = $this->employeeRepo->findById($id);
        if (!$employee) {
            $_SESSION['error'] = 'Сотрудник не найден.';
            header('Location: /admin/employees');
            exit;
        }
        
        // Проверка, что это не суперадмин (на всякий случай, репозиторий тоже проверяет)
        if ($employee['is_superadmin']) {
            $_SESSION['error'] = 'Невозможно удалить суперадмина.';
            header('Location: /admin/employees');
            exit;
        }
        
        $result = $this->employeeRepo->delete($id);
        
        if (!$result) {
            $_SESSION['error'] = 'Не удалось удалить сотрудника.';
        } else {
            $_SESSION['success'] = 'Сотрудник успешно удален (помечен как удаленный).';
        }
        
        header('Location: /admin/employees');
        exit;
    }
}
?>