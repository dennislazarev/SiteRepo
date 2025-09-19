<?php
// app/Services/EmployeeService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\EmployeeRepository;
use App\Core\CSRF; // Если потребуется для внутренней валидации

/**
 * Сервис для работы с логикой сотрудников
 * Цель: Вынести бизнес-логику из контроллера EmployeesController
 */
class EmployeeService
{
    private EmployeeRepository $employeeRepo;

    public function __construct()
    {
        $this->employeeRepo = new EmployeeRepository();
    }

    /**
     * Валидация данных для создания/обновления сотрудника
     * @param array $data Данные из $_POST
     * @param int|null $existingId ID существующего сотрудника при обновлении
     * @return array Массив ошибок, пустой если валидация успешна
     */
    public function validate(array $data, ?int $existingId = null): array
    {
        $errors = [];
        $fio = trim($data['fio'] ?? '');
        $login = trim($data['login'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';
        
        // --- Простая валидация на стороне сервера ---
        if (empty($fio)) {
            $errors['fio'] = 'ФИО обязательно для заполнения.';
        }
        
        if (empty($login)) {
            $errors['login'] = 'Логин обязателен для заполнения.';
        } else if (preg_match('/[^a-zA-Z0-9_.\-]/', $login)) {
            $errors['login'] = 'Логин может содержать только латинские буквы, цифры и подчеркивание.';
        } else if ($this->employeeRepo->isLoginExists($login, $existingId)) {
            $errors['login'] = 'Логин уже занят.';
        }
        
        if (empty($phone)) {
            $errors['phone'] = 'Телефон обязателен для заполнения.';
        } else if (!preg_match('/^\+7\s\d{3}\s\d{3}-\d{2}-\d{2}$/', $phone)) {
             $errors['phone'] = 'Телефон должен быть в формате +7 xxx xxx-xx-xx.';
        } else if ($this->employeeRepo->isPhoneExists($phone, $existingId)) {
             $errors['phone'] = 'Телефон уже занят.';
        }
        
        // Валидация пароля только при создании или если он был введен при обновлении
        if (($existingId === null && empty($password)) || (!empty($password))) {
            if (empty($password)) {
                $errors['password'] = 'Пароль обязателен для заполнения.';
            } else if (strlen($password) < 8) { 
                 $errors['password'] = 'Пароль должен быть не менее 8 символов.';
            } else if ($password !== $passwordConfirm) {
                 $errors['password_confirm'] = 'Пароли не совпадают.';
            }
        }
        
        return $errors;
    }

    /**
     * Создание нового сотрудника
     * @param array $data Данные сотрудника
     * @return int|false ID нового сотрудника или false в случае ошибки
     */
    public function create(array $data)
    {
        // Валидация (предполагаем, что она уже была выполнена в контроллере или будет там)
        // $errors = $this->validate($data, null);
        // if (!empty($errors)) {
        //     // Обработка ошибок валидации
        //     return false; 
        // }

        $fio = trim($data['fio'] ?? '');
        $login = trim($data['login'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $isActive = isset($data['is_active']) && $data['is_active'] == '1';
        $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;

        // Хэширование пароля
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        if ($passwordHash === false) {
            // Логируем ошибку или обрабатываем её
            return false;
        }
        
        // Создание сотрудника в БД
        $employeeId = $this->employeeRepo->create([
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'is_active' => $isActive,
            'role_id' => $roleId,
            'is_superadmin' => 0 // Новые сотрудники не суперадмины по умолчанию
        ]);
        
        return $employeeId;
    }

    /**
     * Обновление данных сотрудника
     * @param int $id ID сотрудника
     * @param array $data Новые данные
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
         // Валидация (предполагаем, что она уже была выполнена в контроллере или будет там)
        // $errors = $this->validate($data, $id);
        // if (!empty($errors)) {
        //     // Обработка ошибок валидации
        //     return false; 
        // }

        $fio = trim($data['fio'] ?? '');
        $login = trim($data['login'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        $isActive = isset($data['is_active']) && $data['is_active'] == '1';
        $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;

        // Подготовка данных для обновления
        $updateData = [
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'is_active' => $isActive,
            'role_id' => $roleId
        ];
        
        // Обновление данных сотрудника
        $result = $this->employeeRepo->update($id, $updateData);
        
        if (!$result) {
            return false;
        }
        
        // Обновление пароля, если он был введен
        if (!empty($password)) {
            $newPasswordHash = password_hash($password, PASSWORD_ARGON2ID);
            if ($newPasswordHash === false) {
                // Логируем ошибку или обрабатываем её
                // Не прерываем, просто не обновляем пароль
                return false; // Или true, если считаем обновление данных успехом, а пароль - нет
            } else {
                $this->employeeRepo->updatePassword($id, $newPasswordHash);
            }
        }
        
        return true;
    }

    // Другие методы сервиса (delete, findById, и т.д.) могут быть добавлены по мере необходимости
}
?>