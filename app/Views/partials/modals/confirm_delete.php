<!-- app/Views/partials/modals/confirm_delete.php -->
<!-- Универсальное модальное окно подтверждения удаления -->
<!-- Ожидает переменные: $modalId, $title, $itemNameId, $itemNameDisplay, $formActionBase -->

<div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($modalId) ?>Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="<?= htmlspecialchars($modalId) ?>Label"><?= htmlspecialchars($title ?? 'Подтвердите удаление') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Закрыть"></button>
            </div>
            <div class="modal-body">
                Вы уверены, что хотите удалить <strong id="<?= htmlspecialchars($itemNameDisplay) ?>"></strong>?
                <div class="mt-2">
                    <small class="text-muted">Это действие невозможно отменить.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form id="<?= htmlspecialchars($formActionBase) ?>-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <!-- action будет установлен JS -->
                    <button type="submit" class="btn btn-danger">Удалить</button>
                </form>
            </div>
        </div>
    </div>
</div>