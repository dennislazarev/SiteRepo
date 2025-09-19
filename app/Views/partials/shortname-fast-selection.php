<!-- app/Views/partials/shortname-fast-selection.php -->
<!-- Выбор из стандартных значений -->
<?php if (!empty($standardShortNames) && is_array($standardShortNames)): ?>
    <div class="mt-2">
        <label class="form-label">Или выберите из стандартных:</label>
        <div>
            <?php foreach ($standardShortNames as $shortName): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm me-2 mb-2 standard-short-name-btn"
                        data-value="<?= htmlspecialchars($shortName) ?>">
                    <?= htmlspecialchars($shortName) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>