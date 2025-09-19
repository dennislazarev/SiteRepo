<!-- app/Views/user-permissions/edit.php -->

<h2>Редактирование права пользователя: <?= htmlspecialchars($permission['display_name'] ?? '') ?></h2>

<?php if (isset($permission)): ?>
    <form action="/admin/user-permissions/<?= $permission['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row">
            <div class="col-md-6">                       
                <div class="mb-3">
                    <label for="display_name" class="form-label required-label">Название</label>
                    <input type="text" class="form-control" id="display_name" name="display_name" value="<?= \App\Core\ViewHelpers::fieldValue('display_name', $permission) ?>" required <?= $permission['is_system'] ? 'readonly' : '' ?>>
                    <div class="form-text">Название права, которое будет отображаться в интерфейсе (например, `Создание пользователя`, `Удаление постов`).</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('display_name'); ?>
                    <?php if ($permission['is_system']): ?>
                        <div class="form-text badge bg-danger">Название системного права изменить нельзя.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="display_name_short" class="form-label required-label">Короткое название</label>
                    <input type="text" class="form-control" id="display_name_short" name="display_name_short"
                        value="<?= \App\Core\ViewHelpers::fieldValue('display_name_short', $permission) ?>"
                        required <?= $permission['is_system'] ? 'readonly' : '' ?>>
                    <div class="form-text">Короткое название для компактных интерфейсов (например, в списке прав роли).</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('display_name_short'); ?>
                    <?php if ($permission['is_system']): ?>
                        <div class="form-text badge bg-danger">Короткое название системного права изменить нельзя.</div>
                    <?php else: ?>
                    
                        <?php require dirname(__DIR__) . '/partials/shortname-fast-selection.php'; ?>

                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="name" class="form-label required-label">Тип</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\ViewHelpers::fieldValue('name', $permission) ?>" required <?= $permission['is_system'] ? 'readonly' : '' ?>>
                    <div class="form-text">Уникальный идентификатор, только латинские буквы, цифры и подчеркивание. {Модуль}_{действие}, например, `user_create`, `post_delete`.</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('name'); ?>
                    <?php if ($permission['is_system']): ?>
                        <div class="form-text badge bg-danger">Тип системного права изменить нельзя.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="mb-3">
                    <label for="description" class="form-label">Описание</label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= \App\Core\ViewHelpers::fieldValue('description', $permission) ?></textarea>
                    <div class="form-text">Подробное описание, что позволяет делать это право.</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('description'); ?>
                </div>

                <div class="mb-3">
                    <label for="module" class="form-label required-label">Модуль</label>
                    <input type="text" class="form-control" id="module" name="module" value="<?= \App\Core\ViewHelpers::fieldValue('module', $permission) ?>" required <?= $permission['is_system'] ? 'readonly' : '' ?>>
                    <div class="form-text">Группировка прав по функциональным областям (например, `users`, `posts`).</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('module'); ?>
                    <?php if ($permission['is_system']): ?>
                        <div class="form-text badge bg-danger">Модуль системного права изменить нельзя.</div>
                    <?php else: ?>
                    <?php if (!empty($_SESSION['errors']['module'])): ?>
                        <div class="text-danger"><?= $_SESSION['errors']['module'] ?></div>
                    <?php endif; ?>
                    
                    <?php require dirname(__DIR__) . '/partials/module-fast-selection.php'; ?>

                    <?php endif; ?>
                </div>

                <?php
                // Определяем, является ли текущий пользователь суперадмином
                // Предполагаем, что переменная $isCurrentUserSuperAdmin передается из контроллера
                // Если нет, определим здесь
                if (!isset($isCurrentUserSuperAdmin)) {
                    $currentUser = \App\Core\Auth::user();
                    $isCurrentUserSuperAdmin = $currentUser && !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;
                }
                ?>
                <?php if ($isCurrentUserSuperAdmin): ?>
                    <!-- Отображается только суперадмину -->
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_system" name="is_system" value="1" <?= \App\Core\ViewHelpers::isChecked('is_system', null, '1') || (!isset($_SESSION['old_input']['is_system']) && $permission['is_system']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_system">Системное право</label>
                        <div class="form-text">Системные права нельзя удалить. Используется для критических функций (например, `dashboard_access`).</div>
                        <?php \App\Core\ViewHelpers::renderFieldError('is_system'); ?>
                    </div>
                <?php elseif ($permission['is_system']): ?>
                    <!-- Отображается обычным пользователям, если право системное -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="is_system_readonly" name="is_system_readonly" value="1" checked disabled>
                            <label class="form-check-label" for="is_system_readonly">Системное право</label>
                        </div>
                        <div class="form-text">Это системное право. Его нельзя изменить.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary" <?= $permission['is_system'] && !$isCurrentUserSuperAdmin ? 'disabled' : '' ?>>Обновить право</button>
            <a href="/admin/user-permissions" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-danger">Право не найдено.</div>
    <a href="/admin/user-permissions" class="btn btn-primary">Назад к списку прав</a>
<?php endif; ?>