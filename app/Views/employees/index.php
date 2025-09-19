<!-- app/Views/employees/index.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Список сотрудников</h2>
    <?php if (isset($currentUser) && $currentUser && \App\Core\Auth::can('employee_create')): ?>
    <a href="/admin/employees/create" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Создать сотрудника
    </a>
    <?php endif; ?>
</div>

<?php if (isset($employees) && is_array($employees) && count($employees) > 0): ?>
    <div class="table-responsive">
        <!-- Добавлен ID и класс filterable-table для универсальной фильтрации/сортировки -->
        <table class="table table-striped table-hover align-middle filterable-table" id="employees-table">
            <thead class="table-dark">
                <!-- Строка ЗАГОЛОВКОВ -->
                <tr class="text-center">
                    <!-- Добавлены data-filterable и классы sortable с иконками -->
                    <th scope="col" data-filterable="true" class="ids-cell sortable" data-sort="id">
                        ID
                        <span class="sort-icons">
                            <i class="fas fa-sort-up sort-asc" style="font-size: 0.8em;"></i>
                            <i class="fas fa-sort-down sort-desc" style="font-size: 0.8em;"></i>
                        </span>
                    </th>
                    <th scope="col" data-filterable="true" class="sortable" data-sort="fio">
                        ФИО
                        <span class="sort-icons">
                            <i class="fas fa-sort-up sort-asc" style="font-size: 0.8em;"></i>
                            <i class="fas fa-sort-down sort-desc" style="font-size: 0.8em;"></i>
                        </span>
                    </th>
                    <th scope="col" data-filterable="true" class="sortable" data-sort="login">
                        Логин
                        <span class="sort-icons">
                            <i class="fas fa-sort-up sort-asc" style="font-size: 0.8em;"></i>
                            <i class="fas fa-sort-down sort-desc" style="font-size: 0.8em;"></i>
                        </span>
                    </th>
                    <th scope="col" data-filterable="true" class="sortable" data-sort="phone">
                        Телефон
                        <span class="sort-icons">
                            <i class="fas fa-sort-up sort-asc" style="font-size: 0.8em;"></i>
                            <i class="fas fa-sort-down sort-desc" style="font-size: 0.8em;"></i>
                        </span>
                    </th>
                    <th scope="col" data-filterable="true" class="sortable" data-sort="role_display_name">
                        Роль
                        <span class="sort-icons">
                            <i class="fas fa-sort-up sort-asc" style="font-size: 0.8em;"></i>
                            <i class="fas fa-sort-down sort-desc" style="font-size: 0.8em;"></i>
                        </span>
                    </th>
                    <th scope="col" style="width: 120px;" data-filterable="select">
                        Статус
                    </th>
                    <th scope="col" style="width: 120px;" data-filterable="false">
                        Действия
                    </th>
                </tr>
                <!-- /Строка ЗАГОЛОВКОВ -->

                <!-- НОВОЕ: Подключение строки фильтров ВНУТРИ таблицы -->
                <?php
                // Подготавливаем данные для компонента строки фильтров
                $tableHeaders = [
                    ['text' => 'ID', 'filterable' => 'true'],
                    ['text' => 'ФИО', 'filterable' => 'true'],
                    ['text' => 'Логин', 'filterable' => 'true'],
                    ['text' => 'Телефон', 'filterable' => 'true'],
                    ['text' => 'Роль', 'filterable' => 'true'],
                    ['text' => 'Статус', 'filterable' => 'select'],
                    ['text' => 'Действия', 'filterable' => 'false'],
                ];
                $tableId = 'employees-table'; // Должен совпадать с ID таблицы

                // Убедимся, что переменные передаются корректно
                $headers = $tableHeaders; // table_filters_row.php ожидает $headers

                require dirname(__DIR__) . '/partials/table_filters_row.php';
                ?>
                <!-- КОНЕЦ НОВОГО -->

            </thead>
            <tbody class="text-center">
                <?php foreach ($employees as $employee): ?>
                <tr>
                    <td scope="row" class="ids-cell"><?= htmlspecialchars($employee['id']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($employee['fio']) ?></td>
                    <td class="login-cell"><?= htmlspecialchars($employee['login']) ?></td>
                    <td class="phone-number actions-cell"><?= htmlspecialchars($employee['phone']) ?></td>
                    <td class="actions-cell"><?= htmlspecialchars($employee['role_display_name'] ?? 'Не назначена') ?></td>
                    <td class="system-cell">
                        <?php if ($employee['is_active']): ?>
                            <span class="badge bg-success">Активен</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Неактивен</span>
                        <?php endif; ?>
                        <?php if ($employee['is_superadmin']): ?>
                            <span class="badge bg-primary ms-1" title="Суперадмин">СА</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell text-center">
                        <?php if (isset($currentUser) && $currentUser): ?>
                            <?php if (\App\Core\Auth::can('employee_edit')): ?>
                                <a href="/admin/employees/<?= $employee['id'] ?>/edit" class="btn btn-actions btn-sm btn-outline-primary me-2" title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (\App\Core\Auth::can('employee_delete') && !$employee['is_superadmin']): ?>
                                <button type="button" class="btn btn-actions btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteEmployeeModal"
                                        data-employee-id="<?= $employee['id'] ?>" data-employee-fio="<?= htmlspecialchars($employee['fio']) ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php elseif ($employee['is_superadmin']): ?>
                                <button class="btn btn-actions btn-sm btn-outline-danger" disabled title="Нельзя удалить суперадмина">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<!-- Пагинация -->
<?php if (isset($totalPages) && $totalPages > 1): ?>
    <nav aria-label="Навигация по страницам">
        <ul class="pagination justify-content-center">
            <?php if (isset($currentPage) && $currentPage > 1):
                // Подготовим строку запроса без page
                $queryWithoutPage = array_diff_key($_GET, array_flip(['page']));
                $queryString = http_build_query($queryWithoutPage);
                $baseLink = '?' . ($queryString ? $queryString . '&' : '');
                ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseLink ?>page=<?= $currentPage - 1 ?>" aria-label="Предыдущая">
                    <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            // Логика отображения страниц (упрощенная)
            if (isset($currentPage, $totalPages)):
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);

                if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

                for ($i = $start; $i <= $end; $i++):
                    $isActive = ($i == $currentPage) ? 'active' : '';
                    // Подготовим ссылку для каждой страницы
                    $queryWithoutPage = array_diff_key($_GET, array_flip(['page']));
                    $queryString = http_build_query($queryWithoutPage);
                    $pageLink = '?' . ($queryString ? $queryString . '&' : '') . 'page=' . $i;
                ?>
                    <li class="page-item <?= $isActive ?>">
                        <a class="page-link" href="<?= $pageLink ?>"><?= $i ?></a>
                    </li>
                <?php endfor;
                    if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                ?>

            <?php if (isset($currentPage) && $currentPage < $totalPages):
                // Подготовим строку запроса без page
                $queryWithoutPage = array_diff_key($_GET, array_flip(['page']));
                $queryString = http_build_query($queryWithoutPage);
                $baseLink = '?' . ($queryString ? $queryString . '&' : '');
            ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $baseLink ?>page=<?= $currentPage + 1 ?>" aria-label="Следующая">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            <?php endif; ?>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>
<!-- Конец Пагинации -->

<?php else: ?>
    <div class="alert alert-info text-center">
        Сотрудники не найдены.
        <?php if (!empty(array_filter($_GET ?? []))): ?>
            <a href="/admin/employees" class="alert-link">Сбросить фильтры</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($currentUser) && $currentUser && \App\Core\Auth::can('employee_delete')): ?>
<!-- Подключение универсального модального окна подтверждения удаления для Сотрудников -->
<?php
    $modalId = 'deleteEmployeeModal';
    $title = 'Подтвердите удаление сотрудника';
    $itemNameDisplay = 'delete-employee-fio'; // Имя переменной для отображения в модальном окне
    $formActionBase = 'delete-employee';     // Базовая часть action формы

    require dirname(__DIR__) . '/partials/modals/confirm_delete.php';
?>
<?php endif; ?>