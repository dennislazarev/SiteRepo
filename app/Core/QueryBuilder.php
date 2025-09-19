<?php
// app/Core/QueryBuilder.php

declare(strict_types=1);

namespace App\Core;

/**
 * Простой Query Builder для построения SELECT запросов
 */
class QueryBuilder
{
    private string $select = '*';
    private string $from = '';
    private array $joins = [];
    private array $wheres = [];
    private array $whereParams = [];
    private string $orderBy = '';
    private string $limit = '';
    private string $offset = '';

    public function select(string $columns): self
    {
        $this->select = $columns;
        return $this;
    }

    public function from(string $table, ?string $alias = null): self
    {
        $this->from = $alias ? "$table AS $alias" : $table;
        return $this;
    }

    public function join(string $table, string $condition, string $type = 'JOIN'): self
    {
        $this->joins[] = "$type $table ON $condition";
        return $this;
    }

    public function leftJoin(string $table, string $condition): self
    {
        return $this->join($table, $condition, 'LEFT JOIN');
    }

    public function where(string $condition, $value = null): self
    {
        $this->wheres[] = "($condition)";
        if ($value !== null) {
            // Предполагаем, что condition содержит placeholder '?'
            $this->whereParams[] = $value;
        }
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        if (empty($values)) {
            // Если массив пуст, добавляем условие, которое никогда не выполнится
            $this->wheres[] = "(1=0)";
            return $this;
        }
        $placeholders = str_repeat('?,', count($values) - 1) . '?';
        $this->wheres[] = "($column IN ($placeholders))";
        $this->whereParams = array_merge($this->whereParams, $values);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        $this->orderBy = "ORDER BY $column $direction";
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = "LIMIT $limit";
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = "OFFSET $offset";
        return $this;
    }

    public function toSql(): string
    {
        $sql = "SELECT {$this->select} FROM {$this->from}";
        
        if (!empty($this->joins)) {
            $sql .= ' ' . implode(' ', $this->joins);
        }
        
        if (!empty($this->wheres)) {
            $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        
        if ($this->orderBy) {
            $sql .= ' ' . $this->orderBy;
        }
        
        if ($this->limit) {
            $sql .= ' ' . $this->limit;
        }
        
        if ($this->offset) {
            $sql .= ' ' . $this->offset;
        }
        
        return $sql;
    }

    public function getParams(): array
    {
        return $this->whereParams;
    }
}
?>