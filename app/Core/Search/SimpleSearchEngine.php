<?php
// app/Core/Search/SearchEngine.php

declare(strict_types=1);

namespace App\Core\Search;

use App\Core\Database;
use App\Core\Search\MysqlFtsAdapter;
use Exception;

/**
 * Простой поисковый движок
 * Использует MySQL Full-Text Search (FTS) через адаптер
 */
class SimpleSearchEngine
{
    private Database $db;
    private MysqlFtsAdapter $adapter;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->adapter = new MysqlFtsAdapter($this->db);
    }

    /**
     * Поиск по документам
     * @param string $query Поисковый запрос
     * @param array $options Опции поиска
     *   - tab_name (string|null): Имя таба для поиска (null для глобального)
     *   - fields (array): Список полей для поиска (пустой для всех)
     *   - mode (string): Режим поиска ('natural_language', 'boolean', 'like_fulltext', 'like_per_field')
     *   - case_sensitive (bool): Учитывать ли регистр (для like режимов)
     *   - exact_phrase (bool): Искать точную фразу (для boolean режима)
     *   - highlight (bool): Подсвечивать найденные фрагменты
     *   - limit (int): Лимит результатов
     *   - offset (int): Смещение результатов
     *   - sort_by_relevance (bool): Сортировать по релевантности
     *   - sort_field (string): Поле для сортировки
     *   - sort_dir (string): Направление сортировки (ASC|DESC)
     * @return array Массив результатов поиска
     */
    public function search(string $query, array $options = []): array
    {
        try {
            return $this->adapter->search($query, $options);
        } catch (Exception $e) {
            error_log("SimpleSearchEngine::search() ошибка: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Индексировать документ
     * @param string $tabName Имя таба
     * @param int $recordId ID записи
     * @param array $data Данные записи для индексации
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function indexDocument(string $tabName, int $recordId, array $data): bool
    {
        try {
            return $this->adapter->indexDocument($tabName, $recordId, $data);
        } catch (Exception $e) {
            error_log("SimpleSearchEngine::indexDocument() ошибка: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Удалить документ из индекса
     * @param string $tabName Имя таба
     * @param int $recordId ID записи
     * @return bool True в случае успеха, false в случае ошибки
     */
    public function deleteDocument(string $tabName, int $recordId): bool
    {
        try {
            return $this->adapter->deleteDocument($tabName, $recordId);
        } catch (Exception $e) {
            error_log("SimpleSearchEngine::deleteDocument() ошибка: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Получить подсказки по слову
     * @param string $word Часть слова
     * @param int $limit Лимит результатов
     * @return array Массив подсказок
     */
    public function getSuggestions(string $word, int $limit = 10): array
    {
        try {
            return $this->adapter->getSuggestions($word, $limit);
        } catch (Exception $e) {
            error_log("SimpleSearchEngine::getSuggestions() ошибка: " . $e->getMessage());
            return [];
        }
    }
}
?>