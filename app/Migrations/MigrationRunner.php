<?php
// app/Migrations/MigrationRunner.php

declare(strict_types=1);

namespace App\Migrations;

use App\Core\Auth;
use App\Core\Database;
use Exception;
use PDO;

/**
 * Класс для запуска миграций через веб-интерфейс
 * Только для суперадмина!
 */
class MigrationRunner
{
    private PDO $pdo;
    private string $migrationDir;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->migrationDir = dirname(__DIR__) . '/Migrations';
    }

    /**
     * Проверить, является ли текущий пользователь суперадмином
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        $user = Auth::user();
        return $user && !empty($user['is_superadmin']) && (int)$user['is_superadmin'] === 1;
    }

    /**
     * Получить список всех доступных миграций
     * @return array
     */
    public function getAvailableMigrations(): array
    {
        $migrations = [];
        if (!is_dir($this->migrationDir)) {
            return $migrations;
        }

        $files = scandir($this->migrationDir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $fullClassName = "\\App\\Migrations\\{$className}";
                if (class_exists($fullClassName)) {
                    $migrations[] = $className;
                }
            }
        }

        // Сортируем по имени файла
        sort($migrations);
        return $migrations;
    }

    /**
     * Получить список уже выполненных миграций
     * @return array
     */
    public function getExecutedMigrations(): array
    {
        try {
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'migrations'");
            $stmt->execute();
            $tableExists = $stmt->fetch();

            if (!$tableExists) {
                // Создаем таблицу migrations, если её нет
                $this->createMigrationsTable();
            }

            $stmt = $this->pdo->prepare("SELECT migration FROM migrations ORDER BY id ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            error_log("MigrationRunner::getExecutedMigrations() ошибка БД: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Создать таблицу для отслеживания миграций
     */
    private function createMigrationsTable(): void
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS `migrations` (
                  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `migration` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
                  `batch` INT(11) NOT NULL,
                  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `idx_migration` (`migration`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            $this->pdo->exec($sql);
        } catch (Exception $e) {
            error_log("MigrationRunner::createMigrationsTable() ошибка БД: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Запустить миграцию
     * @param string $migrationName Имя класса миграции
     * @return bool
     */
    public function runMigration(string $migrationName): bool
    {
        if (!$this->isSuperAdmin()) {
            error_log("MigrationRunner::runMigration({$migrationName}) отказано в доступе. Пользователь не является суперадмином.");
            return false;
        }

        $fullClassName = "\\App\\Migrations\\{$migrationName}";
        if (!class_exists($fullClassName)) {
            error_log("MigrationRunner::runMigration({$migrationName}) класс не найден: {$fullClassName}");
            return false;
        }

        try {
            $migration = new $fullClassName();
            if (!method_exists($migration, 'up')) {
                error_log("MigrationRunner::runMigration({$migrationName}) метод up не найден.");
                return false;
            }

            $result = $migration->up();
            if ($result === true) {
                // Записываем в таблицу migrations
                $stmt = $this->pdo->prepare("INSERT IGNORE INTO migrations (migration, batch) VALUES (?, 1)");
                $stmt->execute([$migrationName]);
                return true;
            } else {
                error_log("MigrationRunner::runMigration({$migrationName}) метод up вернул false.");
                return false;
            }
        } catch (Exception $e) {
            error_log("MigrationRunner::runMigration({$migrationName}) ошибка: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Откатить миграцию
     * @param string $migrationName Имя класса миграции
     * @return bool
     */
    public function rollbackMigration(string $migrationName): bool
    {
        if (!$this->isSuperAdmin()) {
            error_log("MigrationRunner::rollbackMigration({$migrationName}) отказано в доступе. Пользователь не является суперадмином.");
            return false;
        }

        $fullClassName = "\\App\\Migrations\\{$migrationName}";
        if (!class_exists($fullClassName)) {
            error_log("MigrationRunner::rollbackMigration({$migrationName}) класс не найден: {$fullClassName}");
            return false;
        }

        try {
            $migration = new $fullClassName();
            if (!method_exists($migration, 'down')) {
                error_log("MigrationRunner::rollbackMigration({$migrationName}) метод down не найден.");
                return false;
            }

            $result = $migration->down();
            if ($result === true) {
                // Удаляем из таблицы migrations
                $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
                $stmt->execute([$migrationName]);
                return true;
            } else {
                error_log("MigrationRunner::rollbackMigration({$migrationName}) метод down вернул false.");
                return false;
            }
        } catch (Exception $e) {
            error_log("MigrationRunner::rollbackMigration({$migrationName}) ошибка: " . $e->getMessage());
            return false;
        }
    }
}
?>