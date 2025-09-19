<!-- app/Views/partials/table_filters_row.php -->
<?php
/**
 * @var array $headers Массив ассоциативных массивов с данными заголовков.
 *                     Каждый элемент должен содержать ключи:
 *                     - 'text': string - Текст заголовка.
 *                     - 'filterable': string|bool - Тип фильтра ('true', 'false', 'select') или false.
 *                     Пример:
 *                     [
 *                         ['text' => 'ID', 'filterable' => 'true'],
 *                         ['text' => 'Название', 'filterable' => 'true'],
 *                         ['text' => 'Системное', 'filterable' => 'select'],
 *                         ['text' => 'Действия', 'filterable' => 'false'],
 *                     ]
 * @var string $tableId (Опционально) Уникальный ID таблицы для генерации уникальных ID фильтров.
 */
// Убедимся, что $headers определен и является массивом
$headers = isset($headers) && is_array($headers) ? $headers : [];
$tableId = isset($tableId) ? $tableId : uniqid('table_', true);
$filterIndex = 0;
?>

<tr class="filter-row bg-light">
    <?php foreach ($headers as $header):
        $filterType = $header['filterable'] ?? false;
        $headerText = $header['text'] ?? '';
        // Генерируем уникальный ID для каждого фильтра
        $filterId = "{$tableId}_filter_{$filterIndex}";
    ?>
        <th scope="col" class="p-2 align-middle">
            <?php if ($filterType === 'false' || $filterType === false): ?>
                <!-- Пустая ячейка для ненужных фильтров -->
                &nbsp;
            <?php elseif ($filterType === 'select'): ?>
                <!-- Выпадающий список для булевых значений или предопределенных опций -->
                <select
                    id="<?= $filterId ?>"
                    class="form-select form-select-sm filter-element w-100"
                    data-column-index="<?= $filterIndex ?>"
                >
                    <option value="">Все</option>
                    <option value="1">Да</option>
                    <option value="0">Нет</option>
                </select>
            <?php elseif ($filterType === 'module_select'): ?>
                <!-- Выпадающий список для модулей -->
                <select
                    id="<?= $filterId ?>"
                    class="form-select form-select-sm filter-element w-100"
                    data-column-index="<?= $filterIndex ?>"
                >
                    <option value="">Все модули</option>
                    <?php foreach ($modules as $module): ?>
                        <option value="<?= htmlspecialchars($module) ?>"><?= htmlspecialchars($module) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <!-- Текстовое поле для обычного поиска -->
                <div class="position-relative filter-input-wrapper">
                    <input
                        type="text"
                        id="<?= $filterId ?>"
                        class="form-control form-control-sm filter-element"
                        placeholder="Отфильтровать..."
                        data-column-index="<?= $filterIndex ?>"
                    >
                    <span class="clear-filter position-absolute top-50 translate-middle-y"
                          style="right: 10px; cursor: pointer; display: none;"
                          title="Очистить">&times;</span>
                </div>
            <?php endif; ?>
        </th>
        <?php
        $filterIndex++;
        endforeach;
        ?>
</tr>