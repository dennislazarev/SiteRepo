<!-- app/Views/employees/edit.php -->

<h2>Редактирование сотрудника: <?= htmlspecialchars($employee['fio'] ?? '') ?></h2>

<?php if (isset($employee)): ?>
    <form action="/admin/employees/<?= $employee['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="_method" value="PUT"> <!-- Эмуляция PUT-запроса -->

        <div class="row">
            <!-- Левая колонка: Основные данные -->
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="fio" class="form-label required-label">ФИО</label>
                    <input type="text" class="form-control" id="fio" name="fio" value="<?= \App\Core\ViewHelpers::fieldValue('fio', $employee) ?>" required>
                    <div class="form-text">Полное имя сотрудника.</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('fio'); ?>
                </div>

                <div class="mb-3">
                    <label for="login" class="form-label required-label">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" value="<?= \App\Core\ViewHelpers::fieldValue('login', $employee) ?>" required <?= $employee['is_superadmin'] ? 'readonly' : '' ?>>
                    <div class="form-text">Логин для входа в систему. Только латинские буквы, цифры и подчеркивание.</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('login'); ?>
                    <?php if ($employee['is_superadmin']): ?>
                        <div class="form-text">Логин суперадмина изменить нельзя.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label required-label">Телефон</label>
                    <input type="text" class="form-control phone-input" id="phone" name="phone" value="<?= \App\Core\ViewHelpers::fieldValue('phone', $employee) ?>" placeholder="+7 xxx xxx-xx-xx" required>
                    <div class="form-text">Номер телефона в формате +7 xxx xxx-xx-xx.</div>
                    <?php \App\Core\ViewHelpers::renderFieldError('phone'); ?>
                </div>
            </div>

            <!-- Правая колонка: Пароль, Роль, Статус -->
            <div class="col-md-6">
                <div class="mb-3">
                <label for="password" class="form-label">Новый пароль</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password">
                    <?php if (isset($currentUser) && $currentUser && $currentUser['is_superadmin']): ?>
                        <button class="btn btn-outline-secondary toggle-password-visibility" type="button" data-target="password">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <div class="form-text">Оставьте пустым, если не хотите менять. Минимум 8 символов.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('password'); ?>
                </div>

                <?php require dirname(__DIR__) . '/partials/password_generator.php'; ?>

                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Подтверждение нового пароля</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm">
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
                    <select class="form-select" id="role_id" name="role_id" <?= $employee['is_superadmin'] ? 'disabled' : '' ?>>
                        <?php if (!empty($roles) && is_array($roles)): ?>
                            <option value="">Не назначена</option>
                            <?php foreach ($roles as $role): ?>
                                <!-- Используем новый хелпер isSelected -->
                                <option value="<?= $role['id'] ?>" <?= \App\Core\ViewHelpers::isSelected('role_id', $employee, $role['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['display_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <div class="form-text">Роль, определяющая права доступа сотрудника.</div>
                    <?php if ($employee['is_superadmin']): ?>
                        <div class="form-text">Роль суперадмина изменить нельзя.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= \App\Core\ViewHelpers::isChecked('is_active', $employee) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Активен</label>
                    <div class="form-text">Если не отмечено, сотрудник не сможет войти в систему.</div>
                </div>
                
                <?php if ($employee['is_superadmin']): ?>
                    <div class="badge text-bg-warning px-3 py-3 fs-6">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><strong> Суперадмин: </strong></span>
                        <span>Этот сотрудник имеет все права и игнорирует систему ролей.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Обновить сотрудника</button>
            <a href="/admin/employees" class="btn btn-secondary">Отмена</a>
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-danger">Сотрудник не найден.</div>
    <a href="/admin/employees" class="btn btn-primary">Назад к списку</a>
<?php endif; ?>