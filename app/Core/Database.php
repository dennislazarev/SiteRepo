<?php
// app/Core/Database.php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Класс для подключения к базе данных (Singleton)
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Получить экземпляр PDO
     *
     * @return PDO
     * @throws PDOException
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }

        return self::$instance;
    }

    /**
     * Установить соединение с БД
     *
     * @return PDO
     * @throws PDOException
     */
    private static function connect(): PDO
    {
        // Предполагается, что .env уже загружен в index.php
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Ошибка подключения к БД: " . $e->getMessage());
            throw new PDOException("Не удалось подключиться к базе данных", 0, $e);
        }
    }

    /**
    * Выполнить DDL-запрос (например, ALTER TABLE)
    * @param string $sql SQL-запрос DDL
    * @param array $params Параметры для подготовленного выражения
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function executeDdl(string $sql, array $params = []): bool
    {
        try {
            // Используем prepare для безопасности, даже если params пуст
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database::executeDdl() ошибка БД: " . $e->getMessage() . " SQL: $sql");
            return false;
        }
    }

    /**
    * Добавить колонку в таблицу
    * @param string $tableName Имя таблицы
    * @param string $columnName Имя колонки
    * @param string $columnDefinition Определение колонки (например, "VARCHAR(255) NOT NULL")
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function addColumn(string $tableName, string $columnName, string $columnDefinition): bool
    {
        // Экранируем имя таблицы и имя колонки (простая защита, можно улучшить)
        // Важно: columnDefinition НЕ экранируется, она должна быть проверена заранее!
        $sql = "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$columnDefinition}";
        return $this->executeDdl($sql);
    }

    /**
    * Удалить колонку из таблицы
    * @param string $tableName Имя таблицы
    * @param string $columnName Имя колонки
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function dropColumn(string $tableName, string $columnName): bool
    {
        // Экранируем имя таблицы и имя колонки
        $sql = "ALTER TABLE `{$tableName}` DROP COLUMN `{$columnName}`";
        return $this->executeDdl($sql);
    }

    /**
    * Добавить индекс в таблицу
    * @param string $tableName Имя таблицы
    * @param string $indexName Имя индекса
    * @param array $columns Массив имен колонок для индекса
    * @param string $indexType Тип индекса (INDEX, UNIQUE, FULLTEXT)
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function addIndex(string $tableName, string $indexName, array $columns, string $indexType = 'INDEX'): bool
    {
        // Экранируем имя таблицы и имя индекса
        $escapedColumns = array_map(fn($col) => "`{$col}`", $columns);
        $columnsList = implode(', ', $escapedColumns);
        $sql = "ALTER TABLE `{$tableName}` ADD {$indexType} `{$indexName}` ({$columnsList})";
        return $this->executeDdl($sql);
    }

    /**
    * Удалить индекс из таблицы
    * @param string $tableName Имя таблицы
    * @param string $indexName Имя индекса
    * @return bool True в случае успеха, false в случае ошибки
    */
    public function dropIndex(string $tableName, string $indexName): bool
    {
        // Экранируем имя таблицы и имя индекса
        $sql = "ALTER TABLE `{$tableName}` DROP INDEX `{$indexName}`";
        return $this->executeDdl($sql);
    }

    /**
    * Получить экземпляр PDO
    * @return PDO
    */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Закрытый конструктор для запрета создания через new
     */
    private function __construct()
    {
    }

    /**
     * Закрытый клон для запрета клонирования
     */
    private function __clone()
    {
    }

    /**
     * Закрытый wakeup для запрета десериализации
     */
    public function __wakeup()
    {
        throw new \Exception("Нельзя десериализовать синглтон.");
    }
}