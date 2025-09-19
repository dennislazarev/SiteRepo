<!-- app/Views/user-roles/create.php -->

<form action="/admin/user-roles" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="row">
        <!-- Левая колонка: Основные данные -->
        <div class="col-md-6">
            <h2>Создание новой роли пользователя</h2>
            <div class="mb-3">
                <label for="display_name" class="form-label required-label">Название</label>
                <input type="text" class="form-control" id="display_name" name="display_name" value="<?= \App\Core\ViewHelpers::fieldValue('display_name') ?>" required>
                <div class="form-text">Название роли, которое будет отображаться в интерфейсе (например, `Гость`, `Подписчик`).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('display_name'); ?>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label required-label">Тип</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\ViewHelpers::fieldValue('name') ?>" required>
                <div class="form-text">Только латинские буквы, цифры и подчеркивание (например, `guest`, `subscriber`). Должно быть уникальным.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('name'); ?>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= \App\Core\ViewHelpers::fieldValue('description') ?></textarea>
                <div class="form-text">Подробное описание, что позволяет делать эта роль.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('description'); ?>
            </div>

            <div class="mb-3">
                <label for="level" class="form-label">Уровень</label>
                <input type="number" class="form-control" id="level" name="level" value="<?= \App\Core\ViewHelpers::fieldValue('level', null, '0') ?>" min="0">
                <div class="form-text">Числовое значение уровня роли (чем выше, тем "сильнее"). Например, "Гость": уровень = 0, "Подписчик": уровень = 10.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('level'); ?>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_default" name="is_default" value="1" 
                       <?= \App\Core\ViewHelpers::isChecked('is_default') ? 'checked' : '' ?>
                       <?= (!empty($hasDefaultRole)) ? 'disabled' : '' ?>
                >
                <label class="form-check-label" for="is_default">Роль по умолчанию</label>
                <?php if (!empty($hasDefaultRole)): ?>
                    <div class="form-text text-warning">Роль уже назначена. Невозможно создать вторую роль по умолчанию.</div>
                <?php else: ?>
                    <div class="form-text">Если отмечено, эта роль будет назначаться новым пользователям автоматически.</div>
                <?php endif; ?>
                <?php \App\Core\ViewHelpers::renderFieldError('is_default'); ?>
            </div>
        </div>

        <!-- Правая колонка: Назначение прав -->
        <div class="col-md-6">
            <h2 class="mb-4">Назначить права</h2>
            
            <?php if (isset($permissions) && is_array($permissions) && count($permissions) > 0): ?>
                <?php
                // Группируем права по модулям
                $groupedPermissions = [];
                foreach ($permissions as $perm) {
                    $module = $perm['module'] ?? 'Без категории';
                    $groupedPermissions[$module][] = $perm;
                }
                
                // Карта перевода названий модулей
                $moduleLabels = [
                    'dashboard' => 'Главная',
                    'tabs' => 'Табы',
                    'fields' => 'Поля',
                    'employees' => 'Сотрудники',
                    'users' => 'Пользователи',
                    'settings' => 'Настройки',
                    'roles' => 'Роли',
                    'permissions' => 'Права',
                    'entities' => 'Сущности (Табы)',
                    'calendar' => 'Календарь',
                    'library' => 'Библиотека',
                    'system' => 'Система'
                ];
                ?>
                <?php foreach ($groupedPermissions as $module => $modulePermissions): ?>
                    <?php
                    // Получаем отображаемое имя модуля
                    $label = $moduleLabels[$module] ?? ucfirst($module);
                    ?>
                    <!-- Блок для одного модуля -->
                    <div class="mb-4 border-bottom">
                        <!-- 1 строка: Название модуля -->
                        <h5 class="mb-2"><?= htmlspecialchars($label) ?></h5>

                        <!-- 2 строка: Права в 4 колонках -->
                        <div class="d-flex flex-wrap gap-3 mb-4">
                            <?php foreach ($modulePermissions as $perm): ?>
                                <?php
                                // Используем display_name_short, если оно есть, иначе display_name
                                $displayNameToShow = !empty($perm['display_name_short']) ? $perm['display_name_short'] : $perm['display_name'];
                                // Проверяем, было ли это право отмечено при предыдущем запросе (например, после ошибки валидации)
                                $isChecked = isset($_SESSION['old_input']['permissions']) && 
                                             in_array($perm['id'], (array)$_SESSION['old_input']['permissions']);
                                // Генерируем уникальный ID для атрибута for
                                $permCheckboxId = 'perm_' . $perm['id'];
                                ?>
                                <div class="d-flex align-items-center">
                                    <div class="form-check me-2">
                                        <input class="form-check-input" type="checkbox"
                                               id="<?= $permCheckboxId ?>"
                                               name="permissions[]" value="<?= $perm['id'] ?>"
                                            <?= $isChecked ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="<?= $permCheckboxId ?>">
                                            <?= htmlspecialchars($displayNameToShow) ?>
                                            <?php if ($perm['is_system']): ?>
                                                <span class="badge bg-danger ms-1">Системное</span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                    <?php if (!empty($perm['description'])): ?>
                                        <span class="text-info" data-bs-toggle="tooltip" data-bs-placement="top"
                                              data-bs-title="<?= htmlspecialchars($perm['description']) ?>">
                                            <i class="fas fa-question-circle"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <!-- Конец блока для модуля -->
                <?php endforeach; ?>
                <?php if (!empty($_SESSION['errors']['permissions'])): ?>
                    <div class="text-danger"><?= $_SESSION['errors']['permissions'] ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">Права пользователей не найдены. Создайте их сначала.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">Создать роль</button>
        <a href="/admin/user-roles" class="btn btn-secondary">Отмена</a>
    </div>
</form>