<!-- app/Views/roles/create.php -->

<?php
// Определяем, является ли текущий пользователь суперадмином
// Предполагаем, что $isSuperAdmin передается из контроллера
// Если нет, определим здесь
if (!isset($isSuperAdmin)) {
    $currentUser = \App\Core\Auth::user();
    $isSuperAdmin = $currentUser && !empty($currentUser['is_superadmin']) && (int)$currentUser['is_superadmin'] === 1;
}

// Убедимся, что $allPermissions и $modules определены (должны приходить из контроллера)
$allPermissions = $allPermissions ?? [];
$modules = $modules ?? [];

// Группируем все права по модулям для отображения в форме
// Это та же логика, что и в roles/edit.php
$groupedPermissions = [];
foreach ($allPermissions as $perm) {
    // Только существующие права попадают в список
    $groupedPermissions[$perm['module']][] = $perm;
}
?>

<!-- Форма создания -->
<form action="/admin/roles" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

    <div class="row">
        <!-- Левая колонка: Основные данные -->
        <div class="col-lg-6">
            <h2 class="mb-4">Создание новой роли</h2>

            <div class="mb-3">
                <label for="display_name" class="form-label required-label">Название</label>
                <input type="text" class="form-control" id="display_name" name="display_name" value="<?= \App\Core\ViewHelpers::fieldValue('display_name') ?>" required>
                <div class="form-text">Название роли, которое будет отображаться в интерфейсе (например, `Администратор`, `Редактор`).</div>
                <?php \App\Core\ViewHelpers::renderFieldError('display_name'); ?>
            </div>

            <div class="mb-3">
                <label for="name" class="form-label required-label">Тип</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= \App\Core\ViewHelpers::fieldValue('name') ?>" required>
                <div class="form-text">Только латинские буквы, цифры и подчеркивание (например, `admin`, `editor`). Должно быть уникальным.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('name'); ?>
                <?php if (!empty($_SESSION['errors']['name_unique'])): ?>
                    <div class="text-danger"><?= $_SESSION['errors']['name_unique'] ?></div>
                <?php endif; ?>
            </div>

            <!-- Системная роль (видна только суперадмину) -->
            <?php if ($isSuperAdmin): ?>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_system" name="is_system" value="1" <?= \App\Core\ViewHelpers::isChecked('is_system') ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_system">Системная роль</label>
                <div class="form-text">Системные роли нельзя удалить или переименовать. Используется для суперадмина и других критических ролей.</div>
                <?php \App\Core\ViewHelpers::renderFieldError('is_system'); ?>
            </div>
            <?php endif; ?>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Создать роль</button>
                <a href="/admin/roles" class="btn btn-secondary">Отмена</a>
            </div>
        </div>

        <!-- Правая колонка: Назначение прав -->
        <div class="col-lg-6">
            <h2 class="mb-4">Назначить права</h2>
            
            <?php if (!empty($groupedPermissions)): ?>
                <?php
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
                
                // Определим, какие модули показывать (скрываем 'system' для не-суперадминов)
                $visibleModules = array_filter(array_keys($groupedPermissions), function($module) use ($isSuperAdmin) {
                    return $module !== 'system' || $isSuperAdmin;
                });
                
                if (empty($visibleModules)):
                ?>
                    <div class="form-text">Нет доступных модулей для назначения прав.</div>
                <?php else: ?>
                    <?php
                     // Определим желаемый порядок модулей для согласованности
                     $moduleOrder = ['dashboard', 'tabs', 'fields', 'employees', 'users', 'settings', 'roles', 'permissions', 'entities', 'calendar', 'library', 'system'];
                     // Сначала добавляем модули в нужном порядке
                     $orderedVisibleModules = [];
                     foreach ($moduleOrder as $moduleKey) {
                         if (in_array($moduleKey, $visibleModules)) {
                             $orderedVisibleModules[] = $moduleKey;
                         }
                     }
                     // Затем добавляем остальные (если есть)
                     foreach ($visibleModules as $moduleKey) {
                         if (!in_array($moduleKey, $orderedVisibleModules)) {
                             $orderedVisibleModules[] = $moduleKey;
                         }
                     }
                    ?>
                    <?php foreach ($orderedVisibleModules as $module): ?>
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
                                <?php foreach ($groupedPermissions[$module] as $perm): ?>
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
                <?php endif; ?>
            <?php else: ?>
                <div class="form-text">Права не найдены.</div>
            <?php endif; ?>
        </div>
    </div>
</form>