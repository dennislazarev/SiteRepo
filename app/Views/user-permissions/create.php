<!-- app/Views/user-permissions/create.php -->

<h2>Создание нового права пользователя</h2>

<form action="/admin/user-permissions" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="row">
        <!-- Левая колонка: Основные данные -->
        <div class="col-md-6">    
            <div class="mb-3">
                <label for="display_name" class="form-label required-label">Название</label>
                <input type="text" class="form-control" id="display_name" name="display_name"
                    value="<?= \App\Core\ViewHelpers::fieldValue('display_name') ?>" required>
                <div class="form-text">Название права, которое будет отображаться в интерфейсе (например, `Создание пользователя`, `Удаление постов`).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('display_name'); ?>
            </div>

            <!-- Новое поле: Короткое отображаемое имя -->
            <div class="mb-3">
                <label for="display_name_short" class="form-label required-label">Короткое название</label>
                <input type="text" class="form-control" id="display_name_short" name="display_name_short"
                    value="<?= \App\Core\ViewHelpers::fieldValue('display_name_short') ?>"
                    placeholder="Например, Просмотр, Создание..." required>
                <div class="form-text">Короткое название для компактных интерфейсов (например, в списке прав роли).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('display_name_short'); ?>

                <?php require dirname(__DIR__) . '/partials/shortname-fast-selection.php'; ?>

            </div>

            <div class="mb-3">
                <label for="name" class="form-label required-label">Тип</label>
                <input type="text" class="form-control" id="name" name="name"
                    value="<?= \App\Core\ViewHelpers::fieldValue('name') ?>" required>
                <div class="form-text">Уникальный идентификатор, только латинские буквы, цифры и подчеркивание. {Модуль}_{действие}, например, `user_create`, `post_delete`.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('name'); ?>
                <?php if (!empty($_SESSION['errors']['name_unique'])): ?>
                    <div class="text-danger"><?= $_SESSION['errors']['name_unique'] ?></div>
                <?php endif; ?>
            </div>
        </div>

         <!-- Правая колонка -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description"
                        rows="3"><?= \App\Core\ViewHelpers::fieldValue('description') ?></textarea>
                <div class="form-text">Подробное описание, что позволяет делать это право.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('description'); ?>
            </div>

            <div class="mb-3">
                <label for="module" class="form-label required-label">Модуль</label>
                <input type="text" class="form-control" id="module" name="module"
                    value="<?= \App\Core\ViewHelpers::fieldValue('module') ?>" required>
                <div class="form-text">Группировка прав по функциональным областям (например, `users`, `posts`).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('module'); ?>
                
                <?php require dirname(__DIR__) . '/partials/module-fast-selection.php'; ?>

            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_system" name="is_system" value="1" <?= \App\Core\ViewHelpers::isChecked('is_system') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_system">Системное право</label>
                <div class="form-text">Если отмечено, право нельзя будет удалить через интерфейс.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('is_system'); ?>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Создать право</button>
        <a href="/admin/user-permissions" class="btn btn-secondary">Отмена</a>
    </div>
</form>