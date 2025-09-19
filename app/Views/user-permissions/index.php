<!-- app/Views/user-permissions/index.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Список прав пользователей</h2>
    <?php if (\App\Core\Auth::can('user_permission_create')): ?>
    <a href="/admin/user-permissions/create" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Создать право</a>
    <?php endif; ?>
</div>

<?php if (isset($permissions) && is_array($permissions) && count($permissions) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle filterable-table" id="user-permissions-table">
            <thead class="table-dark">
                <tr class="text-center">
                    <th scope="col" style="width: 80px;" data-filterable="true">ID</th>
                    <th scope="col" data-filterable="true">Отображаемое имя</th>
                    <th scope="col" data-filterable="true">Машинное имя</th>
                    <th scope="col" data-filterable="module_select">Категория</th>
                    <th scope="col" style="width: 120px;" data-filterable="select">Системное</th>
                    <th scope="col" style="width: 200px;" data-filterable="false">Действия</th>
                </tr>
                <!-- НОВОЕ: Подключение строки фильтров ВНУТРИ таблицы -->
                <?php
                // Подготавливаем данные для компонента строки фильтров
                $tableHeaders = [
                    ['text' => 'ID', 'filterable' => 'true'],
                    ['text' => 'Отображаемое имя', 'filterable' => 'true'],
                    ['text' => 'Машинное имя', 'filterable' => 'true'],
                    ['text' => 'Категория', 'filterable' => 'module_select'],
                    ['text' => 'Системное', 'filterable' => 'select'],
                    ['text' => 'Действия', 'filterable' => 'false'],
                ];
                $tableId = 'user-permissions-table'; // Должен совпадать с ID таблицы

                // Убедимся, что переменные передаются корректно
                $headers = $tableHeaders; // table_filters_row.php ожидает $headers
                $modules = $modules ?? []; // Передаем модули

                require dirname(__DIR__) . '/partials/table_filters_row.php';
                ?>
                <!-- КОНЕЦ НОВОГО -->
            </thead>
            <tbody class="text-center">
                <?php foreach ($permissions as $perm): ?>
                <tr>
                    <td scope="row" class="ids-cell"><?= htmlspecialchars($perm['id']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($perm['display_name']) ?></td>
                    <td><?= htmlspecialchars($perm['name']) ?></td>
                    <td><?= htmlspecialchars($perm['module'] ?? 'Без категории') ?></td>
                    <td class="system-cell">
                        <?php if ($perm['is_system']): ?>
                            <span class="badge bg-danger">Да</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <?php if (\App\Core\Auth::can('user_permission_edit')): ?>
                            <a href="/admin/user-permissions/<?= $perm['id'] ?>/edit" class="btn btn-sm btn-actions btn-outline-primary me-1" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('user_permission_delete') && !$perm['is_system']): ?>
                            <button type="button" class="btn btn-actions btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserPermissionModal"
                                    data-permission-id="<?= $perm['id'] ?>" data-permission-name="<?= htmlspecialchars($perm['display_name']) ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php elseif ($perm['is_system']): ?>
                             <button class="btn btn-actions btn-sm btn-outline-danger" disabled title="Нельзя удалить системное право">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="alert alert-info text-center">
        Права пользователей не найдены.
        <?php if (!empty(array_filter($_GET))): ?>
            <a href="/admin/user-permissions" class="alert-link">Сбросить фильтры</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Подключение универсального модального окна подтверждения удаления для Прав Пользователей -->
<?php
    $modalId = 'deleteUserPermissionModal';
    $title = 'Подтвердите удаление права пользователя';
    $itemNameDisplay = 'delete-permission-name'; // Имя переменной для отображения в модальном окне
    $formActionBase = 'delete-user-permission';     // Базовая часть action формы

    require dirname(__DIR__) . '/partials/modals/confirm_delete.php';
?>