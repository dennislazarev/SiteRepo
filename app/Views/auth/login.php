<!-- app/Views/auth/login.php -->

<div class="container mt-5">    
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header text-center bg-primary text-white">
                    <h4>Вход в админку</h4>
                </div>
                <div class="card-body">

                    <!-- Отображение алертов из сессии -->
                    <?php \App\Core\Alert::renderFromSession(); ?>

                    <!-- Форма с data-атрибутом для передачи времени окончания блокировки -->
                    <form method="POST" action="/login" id="loginForm" 
                          <?= (!empty($blockedUntil)) ? 'data-blocked-until="' . htmlspecialchars((string)$blockedUntil) . '"' : '' ?>
                          <?= ($isBlocked) ? 'data-blocked="true"' : '' ?>>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <div class="mb-3">
                            <label for="login" class="form-label">Логин</label>
                            <input type="text" class="form-control" id="login" name="login"
                                value="<?= htmlspecialchars($login ?? '') ?>" 
                                required autofocus
                                <?= ($isBlocked) ? 'readonly style="background-color: #e9ecef; cursor: not-allowed;"' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Пароль</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                <?= ($isBlocked) ? 'readonly style="background-color: #e9ecef; cursor: not-allowed;"' : '' ?>>
                        </div>
                        
                        <!-- Блок для отображения таймера блокировки -->
                        <div id="login-block-timer" class="mb-3" style="<?= ($isBlocked) ? 'display: block;' : 'display: none;' ?>">
                            <div class="alert alert-warning mb-0" role="alert">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>Попробуйте снова через:</div>
                                    <div class="fs-5 fw-bold" id="countdown-timer">--:--</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="login-button"
                                    <?= ($isBlocked) ? 'disabled style="cursor: not-allowed;"' : '' ?>>Войти</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <p class="mt-3 text-center text-muted">
                        <small>Логин: Тринадцатый<br>Пароль: 13thRebel#1985</small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>