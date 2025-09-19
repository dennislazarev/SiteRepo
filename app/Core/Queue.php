<?php
// app/Core/Queue.php

declare(strict_types=1);

namespace App\Core;

use Predis\Client as RedisClient;
use Exception;

/**
 * Класс для работы с очередями через Redis
 * Использует библиотеку predis/predis
 */
class Queue
{
    private RedisClient $redis;
    private string $queueName;

    public function __construct(string $queueName = 'default')
    {
        $this->queueName = $queueName;
        // Подключение к Redis используя настройки из .env
        $redisHost = $_ENV['REDIS_HOST'] ?? '127.0.0.1';
        $redisPort = $_ENV['REDIS_PORT'] ?? 6379;
        $redisPassword = $_ENV['REDIS_PASSWORD'] ?? null;
        $redisDatabase = $_ENV['REDIS_DATABASE'] ?? 0;

        $redisOptions = [
            'host' => $redisHost,
            'port' => $redisPort,
        ];
        if ($redisPassword) {
            $redisOptions['password'] = $redisPassword;
        }

        $this->redis = new RedisClient($redisOptions);
        $this->redis->select($redisDatabase);
    }

    /**
     * Добавить задачу в очередь
     * @param array $jobData Данные задачи (массив)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function push(array $jobData): bool
    {
        try {
            $jsonData = json_encode($jobData);
            if ($jsonData === false) {
                error_log("Queue::push() ошибка json_encode: " . json_last_error_msg());
                return false;
            }
            // LPUSH добавляет в начало списка (очередь FIFO)
            $this->redis->lpush($this->queueName, $jsonData);
            return true;
        } catch (Exception $e) {
            error_log("Queue::push() ошибка Redis: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить задачу из очереди (ожидание блокирующее)
     * @param int $timeout Таймаут ожидания в секундах (0 - бесконечно)
     * @return array|null Данные задачи или null, если очередь пуста и истек таймаут
     */
    public function pop(int $timeout = 0): ?array
    {
        try {
            // BRPOP блокирует, пока не появится элемент или не истечет таймаут
            $result = $this->redis->brpop([$this->queueName], $timeout);
            if ($result === null) {
                // Таймаут истек или очередь пуста
                return null;
            }
            // $result[0] - имя ключа (queueName), $result[1] - значение (JSON)
            $jsonData = $result[1];
            $jobData = json_decode($jsonData, true);
            if ($jobData === null && json_last_error() !== JSON_ERROR_NONE) {
                error_log("Queue::pop() ошибка json_decode: " . json_last_error_msg() . " Data: $jsonData");
                return null; // Или выбросить исключение?
            }
            return $jobData;
        } catch (Exception $e) {
            error_log("Queue::pop() ошибка Redis: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить длину очереди
     * @return int Длина очереди
     */
    public function length(): int
    {
        try {
            return $this->redis->llen($this->queueName);
        } catch (Exception $e) {
            error_log("Queue::length() ошибка Redis: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Получить имя очереди
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }
}
?>