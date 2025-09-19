<!-- app/Views/partials/header.php -->
<header class="app-header text-white p-3">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <!-- Левая часть -->
            <div class="d-flex align-items-center">
                <i class="fas fa-gears me-2"></i>
                <strong><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Админка') ?></strong>
            </div>
            
            <!-- Центр: основное меню -->
            <nav class="d-flex align-items-center">
                <a href="/admin" class="text-white text-decoration-none mx-3">Главная</a>
                
                <?php if (\App\Core\Auth::can('tab_view')): ?>
                    <a href="/admin/tabs" class="text-white text-decoration-none mx-3">Табы</a>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('field_view')): ?>
                    <a href="/admin/fields" class="text-white text-decoration-none mx-3">Поля</a>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('employee_view')): ?>
                    <a href="/admin/employees" class="text-white text-decoration-none mx-3">Сотрудники</a>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('user_view')): ?>
                    <a href="/admin/users" class="text-white text-decoration-none mx-3">Пользователи</a>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('role_view') || \App\Core\Auth::can('permission_view')): ?>
                    <!-- Выпадающее меню "Настройки" -->
                    <div class="dropdown mx-3">
                        <a class="text-white text-decoration-none dropdown-toggle" href="#" role="button" id="settingsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            Настройки
                        </a>
                        <ul class="dropdown-menu header-dropdown" aria-labelledby="settingsDropdown">
                            <?php if (\App\Core\Auth::can('role_view')): ?>
                                <li><a class="dropdown-item" href="/admin/roles">Роли сотрудников</a></li>
                            <?php endif; ?>
                            <?php if (\App\Core\Auth::can('permission_view')): ?>
                                <li><a class="dropdown-item" href="/admin/permissions">Права сотрудников</a></li>
                            <?php endif; ?>

                            <!-- Горизонтальный разделитель -->
                            <?php if ((\App\Core\Auth::can('role_view') || \App\Core\Auth::can('permission_view')) && (\App\Core\Auth::can('user_role_view') || \App\Core\Auth::can('user_permission_view'))): ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>

                            <?php if (\App\Core\Auth::can('role_view')): ?>
                                <li><a class="dropdown-item" href="/admin/user-roles">Роли пользователей</a></li>
                            <?php endif; ?>
                            <?php if (\App\Core\Auth::can('permission_view')): ?>
                                <li><a class="dropdown-item" href="/admin/user-permissions">Права пользователей</a></li>
                            <?php endif; ?>
                            <!-- Другие пункты настроек можно добавить аналогично -->
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('calendar_view')): ?>
                    <a href="/admin/calendar" class="text-white text-decoration-none mx-3">Календарь</a>
                <?php endif; ?>
                
                <?php if (\App\Core\Auth::can('library_view')): ?>
                    <a href="/admin/library" class="text-white text-decoration-none mx-3">Библиотека</a>
                <?php endif; ?>      

            </nav>
            
            <!-- Правая часть: пользователь -->
            <?php if (\App\Core\Auth::check()): ?>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <?php if (\App\Core\Auth::can('system_view')): ?>
                            <!-- Для суперадмина -->
                            <!-- Пункт "Система" перемещен сюда, но отображается отдельно -->
                            <a href="#" class="text-white text-decoration-none mx-3 dropdown-toggle" role="button" id="systemDropdown" data-bs-toggle="dropdown" aria-expanded="false">Система</a>
                            <ul class="dropdown-menu header-dropdown" aria-labelledby="systemDropdown">
                                <li><a class="dropdown-item" href="/admin/migrations">Миграции</a></li>
                                <!-- Другие пункты системы можно добавить аналогично -->
                            </ul>
                             | <span class="text-white text-decoration-none mx-3"><?= htmlspecialchars(\App\Core\Auth::user()['fio'] ?? 'Пользователь') ?></span>
                        <?php else: ?>
                            <!-- Для обычных пользователей -->
                            <span class="text-white text-decoration-none mx-3"><?= htmlspecialchars(\App\Core\Auth::user()['fio'] ?? 'Пользователь') ?></span>
                        <?php endif; ?>
                    </div>
                    <!-- Используем button внутри формы для отправки POST-запроса -->
                    <form method="POST" action="/logout" class="d-inline mb-0">
                        <button type="submit" class="btn btn-outline-light btn-sm">Выйти</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>