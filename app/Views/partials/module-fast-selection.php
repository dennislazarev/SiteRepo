<!-- app/Views/partials/module-fast-selection.php -->
<!-- Список существующих модулей для удобства -->
<?php if (!empty($modules) && is_array($modules)): ?>
    <div class="mt-2">
        <label class="form-label">Или выберите из существующих:</label>
        <div>
            <?php foreach ($modules as $mod): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm me-2 mb-2 existing-module-btn"
                        data-value="<?= htmlspecialchars($mod) ?>">
                    <?= htmlspecialchars($mod) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>