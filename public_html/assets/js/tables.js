document.addEventListener('DOMContentLoaded', function () {
    // --- Логика сортировки таблиц ---
    // Найти все таблицы с возможностью сортировки
    const sortableTables = document.querySelectorAll('table.table-hover');
    sortableTables.forEach(function (table) {
        const headers = table.querySelectorAll('thead th.sortable');
        const tbody = table.querySelector('tbody');

        if (!tbody) return; // Если нет тела таблицы, выходим

        // --- ИНИЦИАЛИЗАЦИЯ: Сохраняем оригинальный порядок строк ---
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            // Сохраняем оригинальный индекс строки
            row.setAttribute('data-original-index', index);
        });
        // --- КОНЕЦ ИНИЦИАЛИЗАЦИИ ---

        headers.forEach(function (header) {
            header.style.cursor = 'pointer';

            header.addEventListener('click', function () {
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                const rowsArray = Array.from(tbody.querySelectorAll('tr'));

                // Определяем текущее состояние сортировки
                let currentState = 'none';
                if (header.classList.contains('asc')) {
                    currentState = 'asc';
                } else if (header.classList.contains('desc')) {
                    currentState = 'desc';
                }

                // Определяем следующее состояние по циклу: none -> asc -> desc -> none
                let nextState;
                if (currentState === 'none') {
                    nextState = 'asc';
                } else if (currentState === 'asc') {
                    nextState = 'desc';
                } else { // currentState === 'desc'
                    nextState = 'none';
                }

                // Убираем классы сортировки со всех заголовков этой таблицы
                headers.forEach(h => h.classList.remove('asc', 'desc'));

                let sortedRows = rowsArray;
                if (nextState === 'none') {
                    // --- ВОЗВРАТ К ИСХОДНОМУ ПОРЯДКУ ---
                    sortedRows = rowsArray.sort((a, b) => {
                        const indexA = parseInt(a.getAttribute('data-original-index'), 10);
                        const indexB = parseInt(b.getAttribute('data-original-index'), 10);
                        return indexA - indexB;
                    });
                } else {
                    // Добавляем класс текущему заголовку
                    header.classList.add(nextState);

                    // --- СОРТИРОВКА ---
                    sortedRows = rowsArray.sort(function (a, b) {
                        // Получаем текст из ячейки нужного столбца
                        const aCell = a.children[columnIndex];
                        const bCell = b.children[columnIndex];

                        // Получаем текст, игнорируя HTML
                        let aText = (aCell.textContent || aCell.innerText || "").toString().trim();
                        let bText = (bCell.textContent || bCell.innerText || "").toString().trim();

                        let comparisonResult = 0;

                        // Попробуем сначала отсортировать как числа, если оба значения выглядят как числа
                        const aNum = parseFloat(aText);
                        const bNum = parseFloat(bText);
                        const isANum = !isNaN(aNum) && isFinite(aNum) && aText === aNum.toString();
                        const isBNum = !isNaN(bNum) && isFinite(bNum) && bText === bNum.toString();

                        if (isANum && isBNum) {
                            comparisonResult = aNum - bNum;
                        } else {
                            // Сравнение строк
                            const aLower = aText.toLowerCase();
                            const bLower = bText.toLowerCase();
                            if (aLower < bLower) comparisonResult = -1;
                            else if (aLower > bLower) comparisonResult = 1;
                            else {
                                // Если строки равны, сохраняем стабильность по оригинальному индексу
                                const indexA = parseInt(a.getAttribute('data-original-index'), 10);
                                const indexB = parseInt(b.getAttribute('data-original-index'), 10);
                                comparisonResult = indexA - indexB;
                            }
                        }

                        // Применяем направление сортировки
                        return nextState === 'asc' ? comparisonResult : -comparisonResult;
                    });
                }

                // Переставляем отсортированные строки в tbody
                const fragment = document.createDocumentFragment();
                sortedRows.forEach(row => fragment.appendChild(row));
                // Очищаем tbody и добавляем отсортированные строки
                tbody.innerHTML = '';
                tbody.appendChild(fragment);
            });
        });
    });
    // --- Конец логики сортировки таблиц ---

    // --- НОВАЯ ЛОГИКА: Универсальный фильтр таблиц (только интерактивность) ---
    // Найти все таблицы, помеченные как фильтруемые
    const filterableTables = document.querySelectorAll('.filterable-table');
    filterableTables.forEach(function (table) {
        // Найти строку с фильтрами, созданную сервером
        const filterRow = table.querySelector('.filter-row');
        if (!filterRow) {
            // console.log("Таблица не содержит строки фильтров (.filter-row)", table);
            return; // Эта таблица, возможно, не предназначена для фильтрации
        }

        const tbody = table.querySelector('tbody');
        if (!tbody) {
            console.warn("Фильтруемая таблица не содержит <tbody>.", table);
            return;
        }

        // Найти все фильтрующие элементы внутри строки фильтров
        // Ожидаем, что они имеют класс .filter-element и атрибут data-column-index
        const filterElements = filterRow.querySelectorAll('.filter-element[data-column-index]');
        if (filterElements.length === 0) {
            // console.log("Строка фильтров не содержит активных элементов (.filter-element[data-column-index])", filterRow);
            return;
        }

        const rows = tbody.querySelectorAll('tr');

        // --- Функция применения фильтров ---
        const applyFilters = function () {
            // console.log("--- Применение фильтров ---");
            
            // 1. Собрать активные фильтры
            const activeFilters = [];
            filterElements.forEach(filter => {
                const rawValue = filter.value || '';
                const value = rawValue.toString().toLowerCase().trim();
                const columnIndex = parseInt(filter.dataset.columnIndex, 10);

                // Добавляем фильтр, если он активен (не пуст) и индекс корректен
                if (value !== '' && !isNaN(columnIndex)) {
                    activeFilters.push({
                        index: columnIndex,
                        value: value,
                        type: filter.tagName === 'SELECT' ? 'select' : 'text'
                    });
                }

                // 2. Управление видимостью крестика очистки
                const wrapper = filter.closest('.filter-input-wrapper');
                if (wrapper) {
                    const clearIcon = wrapper.querySelector('.clear-filter');
                    if (clearIcon) {
                        if (value) {
                            clearIcon.style.display = 'block'; // Показываем крестик
                        } else {
                            clearIcon.style.display = 'none';  // Скрываем крестик
                        }
                    }
                }
            });

            // 3. Применить фильтры к строкам таблицы
            rows.forEach(row => {
                let showRow = true;
                const cells = row.querySelectorAll('td');

                // Проверяем каждый активный фильтр
                for (const filter of activeFilters) {
                    const cell = cells[filter.index];
                    if (!cell) {
                        // Если ячейки с таким индексом нет, строка не подходит
                        showRow = false;
                        break;
                    }

                    const cellText = cell.textContent || cell.innerText || '';
                    const cellTextLower = cellText.toString().toLowerCase().trim();
                    const filterValueLower = filter.value.toLowerCase();

                    if (filter.type === 'select') {
                        // Логика для выпадающего списка
                        // Проверяем, является ли это булевым фильтром (системное право)
                        // или обычным текстовым фильтром (модуль)
                        if (filter.value === '1' || filter.value === '0') {
                            // Логика для булевых значений (системное право)
                            let cellBoolValue = '';
                            if (cellTextLower.includes('да') || cellTextLower.includes('yes') || cellTextLower.includes('1') || cellTextLower.includes('+')) {
                                cellBoolValue = '1';
                            } else if (cellTextLower.includes('нет') || cellTextLower.includes('no') || cellTextLower.includes('0') || cellTextLower.includes('-')) {
                                cellBoolValue = '0';
                            }

                            // Сравниваем определенное булево значение с выбранным в фильтре
                            if (cellBoolValue !== filterValueLower) {
                                showRow = false;
                                break; // Прерываем проверку по другим фильтрам для этой строки
                            }
                        } else {
                            // Логика для обычного текстового фильтра (модуль)
                            // Прямое сравнение значений
                            if (cellTextLower !== filterValueLower) {
                                showRow = false;
                                break; // Прерываем проверку по другим фильтрам для этой строки
                            }
                        }
                    } else {
                        // Логика для текстового поля (поиск подстроки)
                        if (!cellTextLower.includes(filterValueLower)) {
                            showRow = false;
                            break; // Прерываем проверку по другим фильтрам для этой строки
                        }
                    }
                }

                // Показываем или скрываем строку в зависимости от результата
                row.style.display = showRow ? '' : 'none';
            });
            // console.log("--- Конец применения фильтров ---");
        };

        // --- Добавление обработчиков событий ---
        filterElements.forEach(filter => {
            if (filter.tagName === 'INPUT') {
                // Для текстовых полей - событие 'input'
                filter.addEventListener('input', applyFilters);
            } else if (filter.tagName === 'SELECT') {
                // Для выпадающих списков - событие 'change'
                filter.addEventListener('change', applyFilters);
            }

            // --- Логика для крестика очистки ---
            const wrapper = filter.closest('.filter-input-wrapper');
            if (wrapper) {
                const clearIcon = wrapper.querySelector('.clear-filter');
                if (clearIcon) {
                    // Обработчик клика по крестику
                    clearIcon.addEventListener('click', (e) => {
                        e.stopPropagation(); // Останавливаем всплытие события
                        filter.value = ''; // Очищаем поле фильтра
                        applyFilters(); // Переприменяем фильтры
                        filter.focus(); // Возвращаем фокус в поле
                    });
                }
            }
        });
    });
    // --- КОНЕЦ НОВОЙ ЛОГИКИ ФИЛЬТРА ---
});