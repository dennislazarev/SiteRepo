<?php
// app/Core/ViewHelpers.php

declare(strict_types=1);

namespace App\Core;

/**
 * Хелперы для представлений
 */
class ViewHelpers
{
    /**
     * Отображает ошибку валидации для поля, если она существует
     * @param string $fieldName Имя поля
     */
    public static function renderFieldError(string $fieldName): void
    {
        if (!empty($_SESSION['errors'][$fieldName])) {
            // Преобразуем в строку на случай, если сообщение об ошибке не является строкой
            $errorMessage = is_scalar($_SESSION['errors'][$fieldName]) ? (string) $_SESSION['errors'][$fieldName] : 'Произошла неизвестная ошибка.';
            echo '<div class="text-danger">' . htmlspecialchars($errorMessage) . '</div>';
        }
    }

    /**
     * Возвращает значение поля из старого ввода или из данных модели
     * Удобно для заполнения форм при ошибках валидации или редактировании
     * @param string $fieldName Имя поля
     * @param mixed $modelData Данные модели (например, $employee)
     * @param string $defaultValue Значение по умолчанию (должно быть строкой)
     * @return string
     */
    // Исправлено: $defaultValue теперь имеет тип mixed, чтобы принимать null
    public static function fieldValue(string $fieldName, $modelData = null, $defaultValue = ''): string
    {
        // Приоритет: старый ввод -> данные модели -> значение по умолчанию
        if (isset($_SESSION['old_input'][$fieldName])) {
            // Исправлено: преобразуем в строку
            return htmlspecialchars((string) $_SESSION['old_input'][$fieldName]);
        }
        
        if (is_array($modelData) && isset($modelData[$fieldName])) {
            // Исправлено: преобразуем в строку
            return htmlspecialchars((string) $modelData[$fieldName]);
        }
        
        // Исправлено: преобразуем $defaultValue в строку, если оно не строка
        // Это решает проблему с null и другими нестроковыми значениями по умолчанию
        return htmlspecialchars(is_scalar($defaultValue) ? (string) $defaultValue : '');
        // Альтернатива: return htmlspecialchars((string) ($defaultValue ?? ''));
    }

    /**
     * Проверяет, был ли чекбокс отмечен в старом вводе или в данных модели
     * @param string $fieldName Имя поля чекбокса
     * @param mixed $modelData Данные модели
     * @param mixed $compareValue Значение, с которым сравнивается (обычно '1')
     * @return bool
     */
    public static function isChecked(string $fieldName, $modelData = null, $compareValue = '1'): bool
    {
        // Приоритет: старый ввод -> данные модели
        if (isset($_SESSION['old_input'][$fieldName])) {
            return $_SESSION['old_input'][$fieldName] == $compareValue;
        }
        
        if (is_array($modelData) && isset($modelData[$fieldName])) {
            return $modelData[$fieldName] == $compareValue;
        }
        
        return false;
    }
    
    /**
     * Проверяет, выбран ли option в выпадающем списке
     * @param string $fieldName Имя поля (например, 'role_id')
     * @param mixed $modelData Данные модели (например, $employee)
     * @param mixed $optionValue Значение option (например, 1)
     * @param mixed $defaultValue Значение по умолчанию (например, 1)
     * @return bool
     */
    public static function isSelected(string $fieldName, $modelData, $optionValue, $defaultValue = null): bool
    {
        // Приоритет: старый ввод -> данные модели -> значение по умолчанию
        $currentValue = null;
        if (isset($_SESSION['old_input'][$fieldName])) {
            $currentValue = $_SESSION['old_input'][$fieldName];
        } elseif (is_array($modelData) && array_key_exists($fieldName, $modelData)) {
            $currentValue = $modelData[$fieldName];
        } else {
            $currentValue = $defaultValue;
        }

        // Сравниваем значения. Для безопасности приводим к строке.
        // Это решает проблему с int/null в $optionValue и $currentValue
        return (string) $currentValue === (string) $optionValue;
    }
}
?>