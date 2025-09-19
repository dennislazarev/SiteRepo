<?php
// app/Core/Alert.php

declare(strict_types=1);

namespace App\Core;

/**
 * Класс для отображения алертов из сессии
 */
class Alert
{
    /**
     * Выводит HTML для алертов, хранящихся в сессии
     * Поддерживаемые типы: error, error_persistent (array), error_rate_limit, success, warning, info
     * После вывода сообщения удаляются из сессии.
     */
    public static function renderFromSession(): void
    {   
        // Убедимся, что сессия запущена
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log("Alert::renderFromSession() вызван до session_start() или после session_destroy()");
            return;
        }

        // Определяем соответствие между ключами сессии, классами Bootstrap, иконками, признаком постоянства и наличием крестика
        // Формат: 'session_key' => ['bootstrap_class', 'icon_html', is_persistent, has_close_button]
        $alertTypes = [
            // Обычные алерты - закрываются автоматически и имеют крестик
            'error'             => ['alert-danger', '&#10060;', false, true],  // Красный крестик, обычный, с крестиком
            'success'           => ['alert-success', '&#10004;', false, true], // Зеленая галочка, обычный, с крестиком
            'warning'           => ['alert-warning', '&#9888;', false, true],  // Желтый восклицательный знак, обычный, с крестиком
            'info'              => ['alert-info', '&#8505;', false, true],     // Синий символ информации, обычный, с крестиком
            
            // Постоянные алерты - не закрываются автоматически, но имеют крестик
            'error_persistent'  => ['alert-danger', '', true, false],   // Красный крестик, постоянный, с крестиком
            
            // Специальный алерт для rate limit - не закрывается, не имеет крестика
            'error_rate_limit'  => ['alert-danger', '', true, false],  // Красный крестик, постоянный, БЕЗ крестика
        ];

        // Обработка сообщений
        $messagesToProcess = [];
        foreach ($alertTypes as $sessionKey => $config) {
            if (isset($_SESSION[$sessionKey])) {
                // Если значение - массив, обрабатываем каждый элемент
                if (is_array($_SESSION[$sessionKey])) {
                    foreach ($_SESSION[$sessionKey] as $message) {
                        $messagesToProcess[] = [$sessionKey, $message];
                    }
                } else {
                    // Если значение - строка, обрабатываем как одно сообщение
                    $messagesToProcess[] = [$sessionKey, $_SESSION[$sessionKey]];
                }
                // Удаляем из сессии после постановки в очередь на обработку
                unset($_SESSION[$sessionKey]);
            }
        }

        // Перебираем все сообщения для отображения
        foreach ($messagesToProcess as [$sessionKey, $message]) {
            // Получаем конфигурацию для типа алерта
            if (!isset($alertTypes[$sessionKey])) {
                continue; // Пропускаем неизвестные типы
            }
            $config = $alertTypes[$sessionKey];
            [$alertClass, $iconHtml, $isPersistent, $hasCloseButton] = $config;
            
            // Для безопасности экранируем HTML в сообщении
            $safeMessage = htmlspecialchars($message);

            // Определяем CSS-классы для контейнера алерта
            $alertClassList = $isPersistent 
                ? "alert {$alertClass}" // Постоянный алерт без автоматического закрытия
                : "alert {$alertClass} alert-dismissible fade show"; // Обычный алерт с возможностью закрытия
            
            // Формируем и выводим HTML алерта
            echo "<div class='{$alertClassList}' role='alert' data-alert-type='{$sessionKey}'>";
            
            // Обертка для иконки и текста
            echo "<div class='alert-content'>";
            
            // Иконка
            echo "<span class='alert-icon'>{$iconHtml}</span>";
            
            // Текст сообщения
            echo "<span class='alert-text ms-2 me-4'>{$safeMessage}</span>";

            // Кнопка закрытия (только если разрешена)
            if ($hasCloseButton) {
                echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Закрыть'></button>";
            }
            
            echo "</div>"; // .alert-content
            echo "</div>"; // .alert
        }
    }
}
?>