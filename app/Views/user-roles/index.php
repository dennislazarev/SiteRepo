<!-- app/Views/user-roles/index.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Список ролей пользователей</h2>
    <?php if (\App\Core\Auth::can('user_role_create')): ?>
    <a href="/admin/user-roles/create" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Создать роль
    </a>
    <?php endif; ?>
</div>

<?php if (isset($roles) && is_array($roles) && count($roles) > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle filterable-table" id="user-roles-table">
            <thead class="table-dark">
                <tr class="text-center">
                    <th scope="col" data-filterable="true">ID</th>
                    <th scope="col" data-filterable="true">Название</th>
                    <th scope="col" data-filterable="true">Тип</th>
                    <th scope="col" data-filterable="true">Уровень</th>
                    <th scope="col" data-filterable="select">По умолчанию</th>
                    <th scope="col" data-filterable="false">Действия</th>
                </tr>
                <!-- НОВОЕ: Подключение строки фильтров ВНУТРИ таблицы -->
                <?php
                // Подготавливаем данные для компонента строки фильтров
                $tableHeaders = [
                    ['text' => 'ID', 'filterable' => 'true'],
                    ['text' => 'Название', 'filterable' => 'true'],
                    ['text' => 'Тип', 'filterable' => 'true'],
                    ['text' => 'Уровень', 'filterable' => 'true'],
                    ['text' => 'По умолчанию', 'filterable' => 'select'],
                    ['text' => 'Действия', 'filterable' => 'false'],
                ];
                $tableId = 'user-roles-table'; // Должен совпадать с ID таблицы

                // Убедимся, что переменные передаются корректно
                $headers = $tableHeaders; // table_filters_row.php ожидает $headers

                require dirname(__DIR__) . '/partials/table_filters_row.php';
                ?>
                <!-- КОНЕЦ НОВОГО -->
            </thead>
            <tbody class="text-center">
                <?php foreach ($roles as $role): ?>
                <tr>
                    <td scope="row" class="ids-cell"><?= htmlspecialchars($role['id']) ?></td>
                    <td><?= htmlspecialchars($role['display_name']) ?></td>
                    <td><?= htmlspecialchars($role['name']) ?></td>
                    <td><?= htmlspecialchars($role['level']) ?></td>
                    <td class="system-cell">
                        <?php if ($role['is_default']): ?>
                            <span class="badge bg-danger">Да</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Нет</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <?php if (\App\Core\Auth::can('user_role_edit')): ?>
                            <a href="/admin/user-roles/<?= $role['id'] ?>/edit" class="btn btn-sm btn-actions btn-outline-primary me-1" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('user_role_delete') && !$role['is_default']): ?>
                            <button type="button" class="btn btn-actions btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserRoleModal"
                                    data-role-id="<?= $role['id'] ?>" data-role-name="<?= htmlspecialchars($role['display_name']) ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php elseif ($role['is_default']): ?>
                             <button class="btn btn-actions btn-sm btn-outline-danger" disabled title="Нельзя удалить роль по умолчанию">
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
        Роли пользователей не найдены.
        <?php if (!empty(array_filter($_GET))): ?>
            <a href="/admin/user-roles" class="alert-link">Сбросить фильтры</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Подключение универсального модального окна подтверждения удаления для Ролей Пользователей -->
<?php
    $modalId = 'deleteUserRoleModal';
    $title = 'Подтвердите удаление роли пользователя';
    $itemNameDisplay = 'delete-role-name'; // Имя переменной для отображения в модальном окне
    $formActionBase = 'delete-user-role';     // Базовая часть action формы

    require dirname(__DIR__) . '/partials/modals/confirm_delete.php';
?>