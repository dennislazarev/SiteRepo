<!-- app/Views/employees/create.php -->

<h2>Создание нового сотрудника</h2>

<form action="/admin/employees" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="row">
        <!-- Левая колонка: Основные данные -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="fio" class="form-label required-label">ФИО</label>
                <input type="text" class="form-control" id="fio" name="fio" value="<?= \App\Core\ViewHelpers::fieldValue('fio') ?>" required>
                <div class="form-text">Полное имя сотрудника.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('fio'); ?>
            </div>

            <div class="mb-3">
                <label for="login" class="form-label required-label">Логин</label>
                <input type="text" class="form-control" id="login" name="login" value="<?= \App\Core\ViewHelpers::fieldValue('login') ?>" required>
                <div class="form-text">Логин для входа в систему. Только латинские буквы, цифры, "_", "-", "."</div>
                <?php \App\Core\ViewHelpers::renderFieldError('login'); ?>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label required-label">Телефон</label>
                <input type="text" class="form-control phone-input" id="phone" name="phone" value="<?= \App\Core\ViewHelpers::fieldValue('phone') ?>" placeholder="+7 xxx xxx-xx-xx" required>
                <div class="form-text">Номер телефона в формате +7 xxx xxx-xx-xx.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('phone'); ?>
            </div>
        </div>

        <!-- Правая колонка: Пароль, Роль, Статус -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="password" class="form-label required-label">Пароль</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <?php if (isset($currentUser) && $currentUser && $currentUser['is_superadmin']): ?>
                    <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="form-text">Минимум 8 символов.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('password'); ?>
            </div>

            <?php require dirname(__DIR__) . '/partials/password_generator.php'; ?>

            <div class="mb-3">
                <label for="password_confirm" class="form-label required-label">Подтверждение пароля</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    <?php if (isset($currentUser) && $currentUser && $currentUser['is_superadmin']): ?>
                    <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password_confirm">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php \App\Core\ViewHelpers::renderFieldError('password_confirm'); ?>
            </div>

            <div class="mb-3">
                <label for="role_id" class="form-label">Роль</label>
                <select class="form-select" id="role_id" name="role_id">
                    <option value="">Не назначена</option>
                    <?php if (!empty($roles) && is_array($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= (\App\Core\ViewHelpers::fieldValue('role_id', null, $role['id']) == $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="form-text">Роль, определяющая права доступа сотрудника.</div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= \App\Core\ViewHelpers::isChecked('is_active') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Активен</label>
                <div class="form-text">Если не отмечено, сотрудник не сможет войти в систему.</div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Создать сотрудника</button>
        <a href="/admin/employees" class="btn btn-secondary">Отмена</a>
    </div>
</form>