<!-- app/Views/users/index.php -->

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Список пользователей сайта</h2>
    <!-- TODO: Добавить проверку права user_create -->
    <a href="/admin/users/create" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Создать пользователя
    </a>
</div>

<?php if (isset($users) && is_array($users) && count($users) > 0): ?>
    <div class="table-responsive">
        <!-- Добавлен ID и класс filterable-table -->
        <table class="table table-striped table-hover align-middle filterable-table" id="users-table">
            <thead class="table-dark">
                <!-- Строка ЗАГОЛОВКОВ -->
                <tr class="text-center">
                    <th scope="col" data-filterable="true">ID</th>
                    <th scope="col" data-filterable="true">ФИО</th>
                    <th scope="col" data-filterable="true">Логин</th>
                    <th scope="col" data-filterable="true">Подписка до</th>
                    <th scope="col" data-filterable="true">Телефон</th>
                    <th scope="col" data-filterable="select">Роль</th>
                    <th scope="col" data-filterable="select">Статус</th>
                    <th scope="col" data-filterable="false">Действия</th>
                </tr>
                <!-- /Строка ЗАГОЛОВКОВ -->

                <!-- НОВОЕ: Подключение строки фильтров ВНУТРИ таблицы -->
                <?php
                // Подготавливаем данные для компонента строки фильтров
                $tableHeaders = [
                    ['text' => 'ID', 'filterable' => 'true'],
                    ['text' => 'ФИО', 'filterable' => 'true'],
                    ['text' => 'Логин', 'filterable' => 'true'],
                    ['text' => 'Подписка до', 'filterable' => 'true'],
                    ['text' => 'Телефон', 'filterable' => 'true'],
                    ['text' => 'Роль', 'filterable' => 'select'],
                    ['text' => 'Статус', 'filterable' => 'select'],
                    ['text' => 'Действия', 'filterable' => 'false'],
                ];
                $tableId = 'users-table'; // Должен совпадать с ID таблицы

                // Убедимся, что переменные передаются корректно
                $headers = $tableHeaders; // table_filters_row.php ожидает $headers

                require dirname(__DIR__) . '/partials/table_filters_row.php';
                ?>
                <!-- КОНЕЦ НОВОГО -->

            </thead>
            <tbody class="text-center">
                <?php foreach ($users as $user): ?>
                <tr>
                    <td scope="row" class="ids-cell"><?= htmlspecialchars($user['id']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($user['fio']) ?></td>
                    <td><?= htmlspecialchars($user['login']) ?></td>
                    <td class="actions-cell">
                        <?php
                        // Логика отображения статуса подписки
                        $subscriptionEndsAt = $user['subscription_ends_at'] ?? null;
                        if ($subscriptionEndsAt === null): ?>
                            <span class="text-muted">-</span>
                        <?php else:
                            $subEndDate = new DateTime($subscriptionEndsAt);
                            $now = new DateTime();
                            if ($subEndDate > $now): ?>
                                <span class="badge bg-primary ms-1"><?= $subEndDate->format('d.m.Y H:i') ?></span>
                            <?php else: ?>
                                <span class="badge bg-danger ms-1"><?= $subEndDate->format('d.m.Y H:i') ?></span>
                            <?php endif;
                        endif; ?>
                    </td>
                    <td class="phone-number"><?= htmlspecialchars($user['phone']) ?></td>
                    <td><?= htmlspecialchars($user['role_display_name'] ?? 'Не назначена') ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                            <span class="badge bg-success">Активен</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Неактивен</span>
                        <?php endif; ?>
                    </td>
                    <td class="actions-cell">
                        <!-- TODO: Добавить проверку прав user_edit и user_delete -->
                        <a href="/admin/users/<?= $user['id'] ?>/edit" class="btn btn-actions btn-sm btn-outline-primary me-1" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-actions btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                data-user-id="<?= $user['id'] ?>" data-user-fio="<?= htmlspecialchars($user['fio']) ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="alert alert-info text-center">
        Пользователи не найдены.
        <?php if (!empty(array_filter($_GET))): ?>
            <a href="/admin/users" class="alert-link">Сбросить фильтры</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Подключение универсального модального окна подтверждения удаления для Пользователей -->
<?php
    $modalId = 'deleteUserModal';
    $title = 'Подтвердите удаление пользователя';
    $itemNameDisplay = 'delete-user-fio'; // Имя переменной для отображения в модальном окне
    $formActionBase = 'delete-user';     // Базовая часть action формы

    require dirname(__DIR__) . '/partials/modals/confirm_delete.php';
?>