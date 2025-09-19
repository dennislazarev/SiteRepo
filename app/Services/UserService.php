<?php
// app/Services/UserService.php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserRepository;
use App\Models\UserRoleRepository; // Импортируем, но нужно использовать
use DateTime;

/**
 * Сервис для работы с логикой пользователей фронтенда
 * Цель: Вынести бизнес-логику из контроллера UsersController
 */
class UserService
{
    private UserRepository $userRepo;
    private UserRoleRepository $userRoleRepo; // Добавлено: объявление свойства

    public function __construct()
    {
        $this->userRepo = new UserRepository();
        $this->userRoleRepo = new UserRoleRepository(); // Добавлено: инициализация UserRoleRepository
    }

    /**
     * Получить список пользователей с фильтрацией (делегирует UserRepository)
     * @param array $filters Массив фильтров
     * @return array Массив пользователей
     */
    public function getAll(array $filters = []): array
    {
        // Делегируем вызов репозиторию
        // Предполагаем, что UserRepository::getAll принимает фильтры
        // Вам может понадобиться добавить пагинацию, если она нужна
        return $this->userRepo->getAll($filters);
    }

    /**
     * Получить список ролей пользователей для выпадающих списков (делегирует UserRepository/UserRoleRepository)
     * @return array Массив ['id' => ..., 'display_name' => ...]
     */
    public function getUserRoleOptions(): array
    {
        // Делегируем вызов репозиторию ролей
        // Предполагаем, что в UserRoleRepository есть аналогичный метод
        // Или используем метод из UserRepository, если он там реализован
        // return $this->userRepo->getUserRoleOptions();
        return $this->userRoleRepo->getAll(); // Предполагаем, что getAll() возвращает нужный формат
    }

    /**
     * Найти пользователя по ID (делегирует UserRepository)
     * @param int $id ID пользователя
     * @return array|null Данные пользователя или null
     */
    public function findById(int $id): ?array
    {
        // Делегируем вызов репозиторию
        return $this->userRepo->findById($id);
    }

    /**
     * "Удалить" пользователя (soft delete) (делегирует UserRepository)
     * @param int $id ID пользователя
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function delete(int $id): bool
    {
        // Делегируем вызов репозиторию
        return $this->userRepo->delete($id);
    }

    /**
     * Валидация данных для создания/обновления пользователя
     * @param array $data Данные из $_POST
     * @param int|null $existingId ID существующего пользователя при обновлении
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
        // Исправлено: is_active приходит как строка из контроллера
        $isActive = ($data['is_active'] ?? '0') === '1'; 
        $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;
        $subscriptionEndsAt = !empty($data['subscription_ends_at']) ? trim($data['subscription_ends_at']) : null;

        if (empty($fio)) {
            $errors['fio'] = 'ФИО обязательно для заполнения.';
        }
        
        if (empty($login)) {
            $errors['login'] = 'Логин обязателен для заполнения.';
        } else if (preg_match('/[^a-z0-9_.\-]/i', $login)) {
            $errors['login'] = 'Логин может содержать только латинские буквы, цифры, подчеркивание, точку и дефис.';
        } else if ($this->userRepo->isLoginExists($login, $existingId)) {
            $errors['login'] = 'Логин уже занят.';
        }

        if (empty($phone)) {
            $errors['phone'] = 'Телефон обязателен для заполнения.';
        } else if (!preg_match('/^\+7\s\d{3}\s\d{3}-\d{2}-\d{2}$/', $phone)) {
             $errors['phone'] = 'Телефон должен быть в формате +7 xxx xxx-xx-xx.';
        } else if ($this->userRepo->isPhoneExists($phone, $existingId)) {
             $errors['phone'] = 'Телефон уже зарегистрирован.';
        }
        
        // Валидация пароля только при создании или если он был введен при обновлении
        // Исправлено: Проверяем, что пароль пуст только если это создание или пароль был введен
        if (($existingId === null && empty($password)) || (!empty($password))) {
            if (empty($password)) {
                $errors['password'] = 'Пароль обязателен для заполнения.';
            } else if (strlen($password) < 8) {
                 $errors['password'] = 'Пароль должен быть не менее 8 символов.';
            } else if ($password !== $passwordConfirm) {
                 $errors['password_confirm'] = 'Пароли не совпадают.';
            }
        }
        
        // Валидация subscription_ends_at, если передана
        if ($subscriptionEndsAt !== null && $subscriptionEndsAt !== '') {
            $dateTime = DateTime::createFromFormat('Y-m-d\TH:i', $subscriptionEndsAt);
            if ($dateTime === false) {
                $errors['subscription_ends_at'] = 'Неверный формат даты окончания подписки. Используйте календарь или введите дату в формате ГГГГ-ММ-ДДTЧЧ:ММ (например, 2025-12-31T23:59).';
            } else {
                $formattedDate = $dateTime->format('Y-m-d H:i:s');
                $checkDate = DateTime::createFromFormat('Y-m-d H:i:s', $formattedDate);
                if ($checkDate === false || $checkDate->format('Y-m-d H:i:s') !== $formattedDate) {
                    $errors['subscription_ends_at'] = 'Введена недопустимая дата.';
                }
            }
        }
        
        // Валидация roleId: проверяем, что роль существует
        // Исправлено: Используем инициализированный $this->userRoleRepo
        if ($roleId !== null) {
            // $userRoleRepo = new UserRoleRepository(); // Удалено: используем свойство класса
            $role = $this->userRoleRepo->findById($roleId); // Используем свойство класса
            if (!$role) {
                $errors['role_id'] = 'Выбрана недопустимая роль.';
            }
            // TODO: Проверить, что выбранная роль - это роль пользователя (user_role)
        }
        
        return $errors;
    }

    /**
     * Создание нового пользователя
     * @param array $data Данные пользователя
     * @return int|false ID нового пользователя или false в случае ошибки
     */
    public function create(array $data)
    {
        // Предполагается, что валидация уже пройдена

        $fio = trim($data['fio'] ?? '');
        $login = trim($data['login'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        // Исправлено: is_active приходит как строка, преобразуем
        $isActive = ($data['is_active'] ?? '0') === '1'; 
        $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;
        $telegramId = !empty($data['telegram_id']) ? trim($data['telegram_id']) : null;
        $whatsappId = !empty($data['whatsapp_id']) ? trim($data['whatsapp_id']) : null;
        $viberId = !empty($data['viber_id']) ? trim($data['viber_id']) : null;
        $vkId = !empty($data['vk_id']) ? (int)trim($data['vk_id']) : null;
        $okId = !empty($data['ok_id']) ? (int)trim($data['ok_id']) : null;
        $subscriptionEndsAt = !empty($data['subscription_ends_at']) ? trim($data['subscription_ends_at']) : null;

        // Хэширование пароля
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        if ($passwordHash === false) {
            return false;
        }
        
        // Подготовка данных для создания пользователя
        $userData = [
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'password_hash' => $passwordHash,
            'is_active' => $isActive,
            'role_id' => $roleId,
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            'subscription_ends_at' => $subscriptionEndsAt
        ];

        // Создание пользователя в БД
        $userId = $this->userRepo->create($userData);
        
        return $userId;
    }

    /**
     * Обновление данных пользователя
     * @param int $id ID пользователя
     * @param array $data Новые данные
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function update(int $id, array $data): bool
    {
        // Предполагается, что валидация уже пройдена

        $fio = trim($data['fio'] ?? '');
        $login = trim($data['login'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $password = $data['password'] ?? '';
        // Исправлено: is_active приходит как строка, преобразуем
        $isActive = ($data['is_active'] ?? false) === true; 
        $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;
        $telegramId = !empty($data['telegram_id']) ? trim($data['telegram_id']) : null;
        $whatsappId = !empty($data['whatsapp_id']) ? trim($data['whatsapp_id']) : null;
        $viberId = !empty($data['viber_id']) ? trim($data['viber_id']) : null;
        $vkId = !empty($data['vk_id']) ? (int)trim($data['vk_id']) : null;
        $okId = !empty($data['ok_id']) ? (int)trim($data['ok_id']) : null;
        $subscriptionEndsAt = !empty($data['subscription_ends_at']) ? trim($data['subscription_ends_at']) : null;

        // Подготовка данных для обновления пользователя
        $updateData = [
            'fio' => $fio,
            'login' => $login,
            'phone' => $phone,
            'is_active' => (int) $isActive,
            'role_id' => $roleId,
            'telegram_id' => $telegramId,
            'whatsapp_id' => $whatsappId,
            'viber_id' => $viberId,
            'vk_id' => $vkId,
            'ok_id' => $okId,
            'subscription_ends_at' => $subscriptionEndsAt
        ];
        
        // Обновление данных пользователя
        $result = $this->userRepo->update($id, $updateData);
        
        if (!$result) {
            return false;
        }
        
        // Обновление пароля, если он был введен
        if (!empty($password)) {
            $newPasswordHash = password_hash($password, PASSWORD_ARGON2ID);
            if ($newPasswordHash === false) {
                // Можно залогировать ошибку, но не прерывать обновление других данных
                // error_log("UserService::update($id) ошибка хэширования пароля");
                return false; // Или true, если считаем обновление данных успехом
            } else {
                return $this->userRepo->updatePassword($id, $newPasswordHash);
            }
        }
        
        return true;
    }

    // Другие методы сервиса могут быть добавлены по мере необходимости
    // Например:
    // public function updatePassword(int $id, string $newPassword): bool { ... }
    // public function getUserCount(array $filters = []): int { ... }
}
?>