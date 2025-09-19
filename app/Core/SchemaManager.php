<?php
// app/Core/SchemaManager.php

declare(strict_types=1);

namespace App\Core;

use App\Core\Database;
use Exception;

/**
 * Класс для управления схемой динамических таблиц
 * Генерирует SQL, взаимодействует с Database
 */
class SchemaManager
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Создать таблицу для таба
     * @param string $tableName Имя таблицы (например, tab_products)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function createTable(string $tableName): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::createTable() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        // Базовая структура таблицы таба
        // Используем InnoDB для поддержки транзакций и внешних ключей
        // CHARSET и COLLATE должны соответствовать основной БД
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `uuid` CHAR(36) NOT NULL COMMENT 'Уникальный идентификатор (UUIDv4)',
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `deleted_at` DATETIME NULL DEFAULT NULL COMMENT 'Soft delete',
              PRIMARY KEY (`id`),
              UNIQUE KEY `idx_uuid` (`uuid`),
              KEY `idx_created_at` (`created_at`),
              KEY `idx_updated_at` (`updated_at`),
              KEY `idx_deleted_at` (`deleted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Динамическая таблица для таба: {$tableName}';
        ";

        try {
            // Используем executeDdl для выполнения CREATE TABLE
            return $this->db->executeDdl($sql);
        } catch (Exception $e) {
            error_log("SchemaManager::createTable($tableName) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить таблицу таба (физически)
     * @param string $tableName Имя таблицы (например, tab_products)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function dropTable(string $tableName): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::dropTable() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        $sql = "DROP TABLE IF EXISTS `{$tableName}`";

        try {
            // Используем executeDdl для выполнения DROP TABLE
            return $this->db->executeDdl($sql);
        } catch (Exception $e) {
            error_log("SchemaManager::dropTable($tableName) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Добавить колонку в таблицу таба
     * @param string $tableName Имя таблицы (например, tab_products)
     * @param string $columnName Имя колонки (например, price)
     * @param string $columnDefinition Определение колонки (например, DECIMAL(10,2) NOT NULL DEFAULT '0.00')
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function addColumn(string $tableName, string $columnName, string $columnDefinition): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::addColumn() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        // Проверяем, что имя колонки допустимо
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $columnName)) {
             error_log("SchemaManager::addColumn() Недопустимое имя колонки '$columnName'.");
             return false;
        }

        // Проверяем, что определение колонки допустимо (простая проверка)
        // Это не идеально, но предотвращает некоторые очевидные ошибки
        // В production лучше использовать полноценный парсер SQL или строгую валидацию
        if (stripos($columnDefinition, 'DROP') !== false || 
            stripos($columnDefinition, 'DELETE') !== false ||
            stripos($columnDefinition, 'UPDATE') !== false ||
            stripos($columnDefinition, 'INSERT') !== false) {
             error_log("SchemaManager::addColumn() Подозрительное определение колонки '$columnDefinition'.");
             return false;
        }

        try {
            // Используем метод addColumn из Database
            return $this->db->addColumn($tableName, $columnName, $columnDefinition);
        } catch (Exception $e) {
            error_log("SchemaManager::addColumn($tableName, $columnName, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить колонку из таблицы таба
     * @param string $tableName Имя таблицы (например, tab_products)
     * @param string $columnName Имя колонки (например, price)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function dropColumn(string $tableName, string $columnName): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::dropColumn() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        // Проверяем, что имя колонки допустимо
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $columnName)) {
             error_log("SchemaManager::dropColumn() Недопустимое имя колонки '$columnName'.");
             return false;
        }

        try {
            // Используем метод dropColumn из Database
            return $this->db->dropColumn($tableName, $columnName);
        } catch (Exception $e) {
            error_log("SchemaManager::dropColumn($tableName, $columnName) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Добавить индекс в таблицу таба
     * @param string $tableName Имя таблицы (например, tab_products)
     * @param string $indexName Имя индекса (например, idx_price)
     * @param array $columns Массив имен колонок для индекса (например, ['price'])
     * @param string $indexType Тип индекса (INDEX, UNIQUE, FULLTEXT)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function addIndex(string $tableName, string $indexName, array $columns, string $indexType = 'INDEX'): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::addIndex() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        // Проверяем, что имя индекса допустимо
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $indexName)) {
             error_log("SchemaManager::addIndex() Недопустимое имя индекса '$indexName'.");
             return false;
        }

        // Проверяем, что имена колонок допустимы
        foreach ($columns as $column) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/', $column)) {
                 error_log("SchemaManager::addIndex() Недопустимое имя колонки '$column' в индексе '$indexName'.");
                 return false;
            }
        }

        try {
            // Используем метод addIndex из Database
            return $this->db->addIndex($tableName, $indexName, $columns, $indexType);
        } catch (Exception $e) {
            error_log("SchemaManager::addIndex($tableName, $indexName, ...) ошибка БД: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить индекс из таблицы таба
     * @param string $tableName Имя таблицы (например, tab_products)
     * @param string $indexName Имя индекса (например, idx_price)
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function dropIndex(string $tableName, string $indexName): bool
    {
        // Определяем префикс таблицы табов
        $tablePrefix = $_ENV['TAB_TABLE_PREFIX'] ?? 'tab_';
        if (strpos($tableName, $tablePrefix) !== 0) {
            error_log("SchemaManager::dropIndex() Имя таблицы '$tableName' не начинается с префикса '$tablePrefix'.");
            return false;
        }

        // Проверяем, что имя индекса допустимо
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $indexName)) {
             error_log("SchemaManager::dropIndex() Недопустимое имя индекса '$indexName'.");
             return false;
        }

        try {
            // Используем метод dropIndex из Database
            return $this->db->dropIndex($tableName, $indexName);
        } catch (Exception $e) {
            error_log("SchemaManager::dropIndex($tableName, $indexName) ошибка БД: " . $e->getMessage());
            return false;
        }
    }
}
?>