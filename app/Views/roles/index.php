<!-- app/Views/roles/index.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Список ролей</h2>
    <a href="/admin/roles/create" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Создать роль
    </a>
</div>

<?php if (isset($roles) && is_array($roles) && count($roles) > 0): ?>
    <div class="table-responsive">
        <!-- Добавлен ID для таблицы -->
        <table class="table table-striped table-hover align-middle filterable-table" id="roles-table">
            <thead class="table-dark">
                <!-- Строка ЗАГОЛОВКОВ -->
                <tr class="text-center">
                    <th scope="col" data-filterable="true">ID</th>
                    <th scope="col" data-filterable="true">Название</th>
                    <th scope="col" data-filterable="true">Тип</th>
                    <th scope="col" data-filterable="select">Системное</th>
                    <th scope="col" data-filterable="false">Действия</th>
                </tr>
                <!-- /Строка ЗАГОЛОВКОВ -->

                <!-- НОВОЕ: Подключение строки фильтров ВНУТРИ таблицы -->
                <?php
                // Подготавливаем данные для компонента строки фильтров
                $tableHeaders = [
                    ['text' => 'ID', 'filterable' => 'true'],
                    ['text' => 'Название', 'filterable' => 'true'],
                    ['text' => 'Тип', 'filterable' => 'true'],
                    ['text' => 'Системное', 'filterable' => 'select'],
                    ['text' => 'Действия', 'filterable' => 'false'],
                ];
                $tableId = 'roles-table'; // Должен совпадать с ID таблицы

                // Убедимся, что переменные передаются корректно
                $headers = $tableHeaders; // table_filters_row.php ожидает $headers

                require dirname(__DIR__) . '/partials/table_filters_row.php';
                ?>
                <!-- КОНЕЦ НОВОГО -->

            </thead>
            <tbody class="text-center">
                <?php foreach ($roles as $role): ?>
                <tr data-role-id="<?= $role['id'] ?>">
                    <td scope="row" class="ids-cell"><?= htmlspecialchars($role['id']) ?></td>
                    <td><?= htmlspecialchars($role['display_name']) ?></td>
                    <td><?= htmlspecialchars($role['name']) ?></td>
                    <td class="system-cell">
                        <?php if ($role['is_system']): ?>
                            <span class="badge bg-danger">Да</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <a href="/admin/roles/<?= $role['id'] ?>/edit" class="btn btn-sm btn-actions btn-outline-primary me-1" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </a>
                        <?php if (!$role['is_system']): ?>
                            <button type="button" class="btn btn-actions btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal" data-role-id="<?= $role['id'] ?>" data-role-name="<?= htmlspecialchars($role['display_name']) ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-actions btn-sm btn-outline-danger" disabled title="Нельзя удалить системную роль">
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
        Роли не найдены.
        <?php if (!empty(array_filter($_GET))): ?>
            <a href="/admin/roles" class="alert-link">Сбросить фильтры</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
    // Подключение универсального модального окна подтверждения удаления для Ролей
    $modalId = 'deleteRoleModal';
    $title = 'Подтвердите удаление роли';
    $itemNameDisplay = 'delete-role-name';
    $formActionBase = 'delete-role';

    require dirname(__DIR__) . '/partials/modals/confirm_delete.php';
?>