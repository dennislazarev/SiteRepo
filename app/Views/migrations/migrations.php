<!-- app/Views/migrations/migrations.php -->

<h2>Управление миграциями</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <h3>Доступные миграции</h3>
        <?php if (!empty($availableMigrations)): ?>
            <ul class="list-group">
                <?php foreach ($availableMigrations as $migration): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($migration) ?>
                        <?php if (in_array($migration, $executedMigrations)): ?>
                            <span class="badge bg-success">Выполнена</span>
                        <?php else: ?>
                            <form method="POST" action="/admin/system/migrations/run" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="migration" value="<?= htmlspecialchars($migration) ?>">
                                <button type="submit" class="btn btn-sm btn-primary">Выполнить</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет доступных миграций.</p>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h3>Выполненные миграции</h3>
        <?php if (!empty($executedMigrations)): ?>
            <ul class="list-group">
                <?php foreach (array_reverse($executedMigrations) as $migration): // Отображаем в обратном порядке ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($migration) ?>
                        <form method="POST" action="/admin/system/migrations/rollback" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="migration" value="<?= htmlspecialchars($migration) ?>">
                            <button type="submit" class="btn btn-sm btn-warning"
                                    onclick="return confirm('Вы уверены, что хотите откатить миграцию <?= htmlspecialchars($migration) ?>? Это может привести к потере данных!')">
                                Откатить
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Нет выполненных миграций.</p>
        <?php endif; ?>
        
        <h3 class="mt-4">Ожидающие миграции</h3>
        <?php if (!empty($pendingMigrations)): ?>
            <ul class="list-group">
                <?php foreach ($pendingMigrations as $migration): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <?= htmlspecialchars($migration) ?>
                        <form method="POST" action="/admin/system/migrations/run" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="migration" value="<?= htmlspecialchars($migration) ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Выполнить</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <!-- <form method="POST" action="/admin/system/migrations/run-all" class="mt-3">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('Вы уверены, что хотите выполнить все ожидающие миграции?')">
                    Выполнить все ожидающие
                </button>
            </form> -->
        <?php else: ?>
            <p>Нет ожидающих миграций.</p>
        <?php endif; ?>
    </div>
</div>