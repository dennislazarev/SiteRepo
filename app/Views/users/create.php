<!-- app/Views/users/create.php -->

<h2>Создание нового пользователя</h2>

<form action="/admin/users" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="row">
        <!-- Левая колонка: Основные данные -->
        <div class="col-md-6">
            <div class="mb-3">
                <label for="fio" class="form-label required-label">ФИО</label>
                <input type="text" class="form-control" id="fio" name="fio" value="<?= \App\Core\ViewHelpers::fieldValue('fio') ?>" required>
                <div class="form-text">Полное имя пользователя.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('fio'); ?>
            </div>

            <div class="mb-3">
                <label for="login" class="form-label required-label">Логин</label>
                <input type="text" class="form-control" id="login" name="login" value="<?= \App\Core\ViewHelpers::fieldValue('login') ?>" required>
                <div class="form-text">Уникальный логин для входа. Только латинские буквы, цифры, подчеркивание, точка и дефис.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('login'); ?>
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label required-label">Телефон</label>
                <input type="text" class="form-control phone-input" id="phone" name="phone" value="<?= \App\Core\ViewHelpers::fieldValue('phone') ?>" placeholder="+7 xxx xxx-xx-xx" required>
                <div class="form-text">Номер телефона в формате +7 xxx xxx-xx-xx.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('phone'); ?>
            </div>

            <div class="mb-3">
                <label for="role_id" class="form-label">Роль</label>
                <select class="form-select" id="role_id" name="role_id">
                    <option value="">Не назначена</option>
                    <?php if (!empty($roles) && is_array($roles)): ?>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" 
                                <?= (\App\Core\ViewHelpers::fieldValue('role_id', null, (string)($defaultRoleId ?? '')) == $role['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="form-text">Роль пользователя фронтенда.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('role_id'); ?>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?= \App\Core\ViewHelpers::isChecked('is_active') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Активен</label>
                <div class="form-text">Если не отмечено, пользователь не сможет войти в систему.</div>
            </div>
        </div>

        <!-- Правая колонка: Пароль, Социальные сети, Подписка -->
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

            <!-- Генератор паролей будет подключен здесь -->
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
                <label for="subscription_ends_at" class="form-label">Окончание подписки</label>
                <!-- Используем datetime-local для нативного виджета выбора даты/времени -->
                <input type="datetime-local" class="form-control" id="subscription_ends_at" name="subscription_ends_at" 
                    value="<?= \App\Core\ViewHelpers::fieldValue('subscription_ends_at') ?>">
                <div class="form-text">Дата и время окончания подписки (ГГГГ-ММ-ДДTЧЧ:ММ).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('subscription_ends_at'); ?>
            </div>

            <!-- Аккордеон для дополнительных полей -->
            <div class="accordion mb-3" id="additionalFieldsAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                            Дополнительные поля
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#additionalFieldsAccordion">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label for="telegram_id" class="form-label">Telegram ID</label>
                                <input type="text" class="form-control" id="telegram_id" name="telegram_id" value="<?= \App\Core\ViewHelpers::fieldValue('telegram_id') ?>">
                                <div class="form-text">Уникальный ID пользователя в Telegram.</div>
                                <?php \App\Core\ViewHelpers::renderFieldError('telegram_id'); ?>
                            </div>

                            <div class="mb-3">
                                <label for="whatsapp_id" class="form-label">WhatsApp ID</label>
                                <input type="text" class="form-control" id="whatsapp_id" name="whatsapp_id" value="<?= \App\Core\ViewHelpers::fieldValue('whatsapp_id') ?>">
                                <div class="form-text">Уникальный ID пользователя в WhatsApp.</div>
                                <?php \App\Core\ViewHelpers::renderFieldError('whatsapp_id'); ?>
                            </div>

                            <div class="mb-3">
                                <label for="viber_id" class="form-label">Viber ID</label>
                                <input type="text" class="form-control" id="viber_id" name="viber_id" value="<?= \App\Core\ViewHelpers::fieldValue('viber_id') ?>">
                                <div class="form-text">Уникальный ID пользователя в Viber.</div>
                                <?php \App\Core\ViewHelpers::renderFieldError('viber_id'); ?>
                            </div>

                            <div class="mb-3">
                                <label for="vk_id" class="form-label">VK ID</label>
                                <input type="number" class="form-control" id="vk_id" name="vk_id" value="<?= \App\Core\ViewHelpers::fieldValue('vk_id') ?>">
                                <div class="form-text">Уникальный ID пользователя ВКонтакте.</div>
                                <?php \App\Core\ViewHelpers::renderFieldError('vk_id'); ?>
                            </div>

                            <div class="mb-3">
                                <label for="ok_id" class="form-label">OK ID</label>
                                <input type="number" class="form-control" id="ok_id" name="ok_id" value="<?= \App\Core\ViewHelpers::fieldValue('ok_id') ?>">
                                <div class="form-text">Уникальный ID пользователя в Одноклассниках.</div>
                                <?php \App\Core\ViewHelpers::renderFieldError('ok_id'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Создать пользователя</button>
        <a href="/admin/users" class="btn btn-secondary">Отмена</a>
    </div>
</form>