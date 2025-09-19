<?php
// app/Services/RateLimitService.php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class RateLimitService
{
    private PDO $pdo;
    private int $maxAttempts;
    private int $blockMinutes;
    private array $loggedIps = [];

    public function __construct()
    {
        $this->maxAttempts  = (int) ($_ENV['RATE_LIMIT_ATTEMPTS'] ?? 3);
        $this->blockMinutes = (int) ($_ENV['RATE_LIMIT_MINUTES'] ?? 15);
        $this->pdo          = Database::getInstance();
    }

    /**
     * Проверить, заблокирован ли IP
     */
    public function isBlocked(string $ip): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM login_attempts 
             WHERE ip = ? AND blocked_until > NOW() 
             LIMIT 1"
        );
        $stmt->execute([$ip]);
        return (bool) $stmt->fetchColumn();
    }

    /**
     * Получить время окончания блокировки для IP (в формате Unix Timestamp)
     */
    public function getBlockedUntil(string $ip): ?int
    {
        $stmt = $this->pdo->prepare(
            "SELECT UNIX_TIMESTAMP(blocked_until) as blocked_until_ts FROM login_attempts 
             WHERE ip = ? AND blocked_until > NOW() 
             LIMIT 1"
        );
        $stmt->execute([$ip]);
        $blockedUntilTs = $stmt->fetchColumn();
        return $blockedUntilTs ? (int) $blockedUntilTs : null;
    }

    /**
     * Залогировать неудачную попытку
     * Исправленная правильная логика:
     * 1. `attempts` в БД = количество уже совершенных неудачных попыток.
     * 2. При новой попытке:
     *   - Читаем `current_attempts` из БД.
     *   - `potential_new_total = current_attempts + 1`.
     *   - Если `potential_new_total >= MAX_ATTEMPTS` -> устанавливаем блокировку, записываем `attempts = potential_new_total`.
     *   - Иначе -> записываем `attempts = potential_new_total`.
     * 3. Если обнаружена истекшая блокировка -> сбрасываем `attempts` в БД до 0 перед началом подсчета.
     */
    public function logAttempt(string $ip, string $login = null): void
    {
        if (in_array($ip, $this->loggedIps)) {
            return;
        }
        $this->loggedIps[] = $ip;
        
        // Проверяем, есть ли уже запись для этого IP
        $stmt = $this->pdo->prepare(
            "SELECT id, attempts, blocked_until FROM login_attempts WHERE ip = ? LIMIT 1"
        );
        $stmt->execute([$ip]);
        $attempt = $stmt->fetch();

        if ($attempt) {
            // Запись существует
            $currentAttempts = (int) $attempt['attempts'];
            $blockedUntilStr = $attempt['blocked_until'];

            // Определяем, заблокирован ли пользователь в данный момент
            $isCurrentlyBlocked = false;
            if ($blockedUntilStr) {
                $blockedUntilDT = new \DateTime($blockedUntilStr);
                $nowDT = new \DateTime();
                if ($blockedUntilDT > $nowDT) {
                    $isCurrentlyBlocked = true;
                }
            }

            if ($isCurrentlyBlocked) {
                // Уже заблокирован - обновляем только время последней попытки и логин
                $stmt = $this->pdo->prepare(
                    "UPDATE login_attempts 
                     SET last_attempt = NOW(), 
                         login = COALESCE(login, ?)
                     WHERE ip = ?"
                );
                $stmt->execute([$login, $ip]);
            } else {
                // Не заблокирован в данный момент
                if ($blockedUntilStr !== null) {
                    // Была истекшая блокировка - сбрасываем счётчик попыток до 0
                    // Это первая попытка после истечения блокировки
                    $stmt = $this->pdo->prepare(
                        "UPDATE login_attempts 
                         SET attempts = 0, -- Сброс счётчика до 0
                             last_attempt = NOW(), 
                             blocked_until = NULL, -- Сбрасываем флаг блокировки
                             login = COALESCE(login, ?)
                         WHERE ip = ?"
                    );
                    $stmt->execute([$login, $ip]);
                    // После сброса у нас 0 попыток. Продолжаем логику как для новой записи.
                    $currentAttempts = 0;
                }
                
                // --- ИСПРАВЛЕННАЯ ПРАВИЛЬНАЯ ЛОГИКА ---
                // 1. Вычисляем, сколько попыток будет, если засчитать текущую
                $potentialNewTotalAttempts = $currentAttempts + 1;
                
                // 2. Проверяем, достигли ли лимита
                if ($potentialNewTotalAttempts >= $this->maxAttempts) {
                    // Достигнут или превышен лимит - устанавливаем блокировку
                    // Записываем в БД финальное значение attempts (будет 3)
                    $stmt = $this->pdo->prepare(
                        "UPDATE login_attempts 
                         SET attempts = ?, 
                             last_attempt = NOW(), 
                             blocked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                             login = COALESCE(login, ?)
                         WHERE ip = ?"
                    );
                    $stmt->execute([$potentialNewTotalAttempts, $this->blockMinutes, $login, $ip]);
                } else {
                    // Просто увеличиваем счётчик на 1
                    // Записываем в БД новое значение attempts
                    $stmt = $this->pdo->prepare(
                        "UPDATE login_attempts 
                         SET attempts = ?, 
                             last_attempt = NOW(),
                             login = COALESCE(login, ?)
                         WHERE ip = ?"
                    );
                    $stmt->execute([$potentialNewTotalAttempts, $login, $ip]);
                }
                // --- КОНЕЦ ИСПРАВЛЕННОЙ ЛОГИКИ ---
            }
        } else {
            // Нет записи - создаём новую
            // Сначала записываем attempts = 0 (0 неудачных попыток завершено)
            $stmt = $this->pdo->prepare(
                "INSERT INTO login_attempts (ip, login, attempts, last_attempt) 
                VALUES (?, ?, 0, NOW())"
            );
            $stmt->execute([$ip, $login]);

            // Теперь у нас есть запись с attempts = 0.
            // Продолжаем логику так, как если бы мы прочитали эту запись.
            $currentAttempts = (int) $attempt['attempts']; 
            $potentialNewTotalAttempts = $currentAttempts + 1; // 0 + 1 = 1

            if ($potentialNewTotalAttempts >= $this->maxAttempts) {
                // Очень редкий случай: RATE_LIMIT_ATTEMPTS = 1
                // Первая же попытка вызывает блокировку
                $stmt = $this->pdo->prepare(
                    "UPDATE login_attempts 
                    SET attempts = ?, 
                        blocked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                    WHERE ip = ?"
                );
                $stmt->execute([$potentialNewTotalAttempts, $this->blockMinutes, $ip]);
            } else {
                // Обычный случай: первая попытка не вызывает блокировку
                // Обновляем запись, увеличивая attempts с 0 до 1
                $stmt = $this->pdo->prepare(
                    "UPDATE login_attempts 
                    SET attempts = ?
                    WHERE ip = ?"
                );
                $stmt->execute([$potentialNewTotalAttempts, $ip]); 
            }
        }
    }

    /**
     * Сбросить попытки для IP (удаление записи)
     * Вызывается при успешном входе
     */
    public function clearAttempts(string $ip): void
    {
        // Вариант 1: Удалить запись полностью
        // $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
        // $stmt->execute([$ip]);
        
        // Вариант 2 (предпочтительный): Сбросить счётчик, сохранить запись для истории
        $stmt = $this->pdo->prepare(
            "UPDATE login_attempts 
             SET attempts = 0, 
                 blocked_until = NULL,
                 last_attempt = NOW()
             WHERE ip = ?"
        );
        $stmt->execute([$ip]);
    }
    
    /**
     * Получить количество неудачных попыток для IP
     * (Для отладки или дополнительной логики)
     */
    public function getAttempts(string $ip): int
    {
        $stmt = $this->pdo->prepare(
            "SELECT attempts FROM login_attempts WHERE ip = ? LIMIT 1"
        );
        $stmt->execute([$ip]);
        $attempts = $stmt->fetchColumn();
        return $attempts ? (int) $attempts : 0;
    }
}
?>