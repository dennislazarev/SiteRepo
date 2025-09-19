<!-- app/Views/errors/403.php -->

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Доступ запрещен</h4>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        <?php if (isset($message)): ?>
                            <?= htmlspecialchars($message) ?>
                        <?php else: ?>
                            У вас нет прав для просмотра этой страницы.
                        <?php endif; ?>
                    </p>
                    <a href="/admin" class="btn btn-primary">Перейти на главную</a>
                </div>
            </div>
        </div>
    </div>
</div>