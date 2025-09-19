<!-- app/Views/roles/edit.php -->

<?php if (isset($role)): ?>
    <!-- Форма редактирования -->
    <form action="/admin/roles/<?= $role['id'] ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row">
            <div class="col-lg-6">
                <h2 class="mb-4">Редактирование роли: <?= htmlspecialchars($role['display_name'] ?? '') ?></h2>
                <div class="mb-3">
                    <label for="display_name" class="form-label">Название</label>
                    <input type="text" class="form-control" id="display_name" name="display_name" value="<?= \App\Core\ViewHelpers::fieldValue('display_name', $role) ?>" required <?= $role['is_system'] ? 'readonly' : '' ?>>
                    <?php \App\Core\ViewHelpers::renderFieldError('display_name'); ?>
                    <?php if ($role['is_system']): ?>
                        <div class="form-text">Название системной роли изменить нельзя.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="name" class="form-label">Тип</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\ViewHelpers::fieldValue('name', $role) ?>" required <?= $role['is_system'] ? 'readonly' : '' ?>>
                    <?php \App\Core\ViewHelpers::renderFieldError('name'); ?>
                    <?php if ($role['is_system']): ?>
                        <div class="form-text">Тип системной роли изменить нельзя.</div>
                    <?php else: ?>
                        <div class="form-text">Только латинские буквы, цифры и подчеркивание. Должно быть уникальным.</div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['errors']['name_unique'])): ?>
                        <div class="text-danger"><?= $_SESSION['errors']['name_unique'] ?></div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary">Обновить роль</button>
                <a href="/admin/roles" class="btn btn-secondary">Отмена</a>
            </div>

            <div class="col-lg-6">
                <!-- Выбор прав для роли -->
                <div class="mb-3">
                    <h2 class="mb-4">Назначенные права</h2>
                    
                    <?php
                    // Определяем желаемый порядок и отображаемые имена модулей
                    $moduleOrderAndNames = [
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
                        'system' => 'Система',
                    ];
                    ?>

                    <?php
                    // 1. Сначала отображаем известные модули в заданном порядке
                    foreach ($moduleOrderAndNames as $moduleKey => $moduleDisplayName):
                        if (isset($groupedPermissions[$moduleKey]) && count($groupedPermissions[$moduleKey]) > 0):
                            ?>
                            <!-- Блок для модуля: <?= htmlspecialchars($moduleKey) ?> -->
                            <div class="mb-4 border-bottom">
                                <h5 class="mb-2"><?= htmlspecialchars($moduleDisplayName) ?></h5>
                                <div class="d-flex flex-wrap gap-3 mb-4">
                                    <?php
                                    foreach ($groupedPermissions[$moduleKey] as $perm):
                                        // Используем display_name_short, если оно есть, иначе display_name
                                        $displayNameToShow = !empty($perm['display_name_short']) ? $perm['display_name_short'] : $perm['display_name'];
                                        $permCheckboxId = 'perm_' . $perm['id'];
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="form-check me-2">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>" id="<?= $permCheckboxId ?>"
                                                    <?php if (in_array($perm['id'], $assignedPermissionIds)) echo 'checked'; ?>
                                                    <?php
                                                    // Исправлено: Убрано автоматическое назначение и блокировка системных прав
                                                    // Согласно обсуждению, системный флаг роли/права не должен блокировать назначение
                                                    // if ($role['is_system'] && $perm['is_system']) {
                                                    //    echo 'disabled checked ';
                                                    // }
                                                    ?>
                                                >
                                                <label class="form-check-label" for="<?= $permCheckboxId ?>">
                                                    <?= htmlspecialchars($displayNameToShow) ?>
                                                    <?php if ($perm['is_system']): ?>
                                                        <span class="badge bg-danger ms-1">Системное</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <?php if (!empty($perm['description'])): ?>
                                                <span class="text-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars($perm['description']) ?>">
                                                    <i class="fas fa-question-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Конец блока для модуля: <?= htmlspecialchars($moduleKey) ?> -->
                        <?php endif;
                    endforeach; ?>

                    <?php
                    // 2. Затем отображаем остальные (неизвестные/новые) модули
                    // Получаем список всех модулей, которые есть в $groupedPermissions
                    $allKnownModules = array_keys($moduleOrderAndNames);
                    $unknownModules = array_diff(array_keys($groupedPermissions), $allKnownModules);

                    foreach ($unknownModules as $unknownModuleKey):
                        if (isset($groupedPermissions[$unknownModuleKey]) && count($groupedPermissions[$unknownModuleKey]) > 0):
                            // Для неизвестных модулей используем имя ключа как отображаемое имя
                            $unknownModuleDisplayName = ucfirst(str_replace('_', ' ', $unknownModuleKey)); // Простая обработка
                            ?>
                            <!-- Блок для нового модуля: <?= htmlspecialchars($unknownModuleKey) ?> -->
                            <div class="mb-4">
                                <h5 class="mb-2"><?= htmlspecialchars($unknownModuleDisplayName) ?> (Новый)</h5>
                                <div class="d-flex flex-wrap gap-3">
                                    <?php
                                    foreach ($groupedPermissions[$unknownModuleKey] as $perm):
                                        // Используем display_name_short, если оно есть, иначе display_name
                                        $displayNameToShow = !empty($perm['display_name_short']) ? $perm['display_name_short'] : $perm['display_name'];
                                        $permCheckboxId = 'perm_' . $perm['id'];
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <div class="form-check me-2">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $perm['id'] ?>" id="<?= $permCheckboxId ?>"
                                                    <?php if (in_array($perm['id'], $assignedPermissionIds)) echo 'checked'; ?>
                                                    <?php
                                                    // Исправлено: Убрано автоматическое назначение и блокировка системных прав
                                                    // if ($role['is_system'] && $perm['is_system']) {
                                                    //    echo 'disabled checked ';
                                                    // }
                                                    ?>
                                                >
                                                <label class="form-check-label" for="<?= $permCheckboxId ?>">
                                                    <?= htmlspecialchars($displayNameToShow) ?>
                                                    <?php if ($perm['is_system']): ?>
                                                        <span class="badge bg-danger ms-1">Системное</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                            <?php if (!empty($perm['description'])): ?>
                                                <span class="text-info" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?= htmlspecialchars($perm['description']) ?>">
                                                    <i class="fas fa-question-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Конец блока для нового модуля: <?= htmlspecialchars($unknownModuleKey) ?> -->
                        <?php endif;
                    endforeach; ?>

                    <?php
                    // Проверка на случай, если вообще нет прав
                    if (empty($groupedPermissions) || array_sum(array_map('count', $groupedPermissions)) == 0):
                        ?>
                        <div class="form-text">Права не найдены.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
<?php else: ?>
    <div class="alert alert-danger">Роль не найдена.</div>
    <a href="/admin/roles" class="btn btn-primary">Назад к списку ролей</a>
<?php endif; ?>