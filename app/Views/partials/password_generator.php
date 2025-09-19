<!-- app/Views/partials/password_generator.php -->

<div class="mb-3">
    <!-- Кнопка для открытия аккордеона -->
    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#passwordGeneratorCollapse" aria-expanded="false" aria-controls="passwordGeneratorCollapse">
        Сгенерировать пароль
    </button>

    <!-- Содержимое аккордеона -->
    <div class="collapse mt-2" id="passwordGeneratorCollapse">
        <div class="card card-body">
            <div class="row">
                <!-- Левая колонка: Настройки -->
                <div class="col-md-6">
                    <h6>Настройки</h6>
                    <div class="mb-2">
                        <label for="pg-length" class="form-label">Длина пароля (8-32):</label>
                        <input type="number" class="form-control form-control-sm" id="pg-length" name="pg_length" min="8" max="32" value="12">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pg-uppercase" name="pg_uppercase" checked>
                        <label class="form-check-label" for="pg-uppercase">Заглавные буквы (A-Z)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pg-lowercase" name="pg_lowercase" checked>
                        <label class="form-check-label" for="pg-lowercase">Строчные буквы (a-z)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pg-numbers" name="pg_numbers" checked>
                        <label class="form-check-label" for="pg-numbers">Цифры (0-9)</label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pg-symbols" name="pg_symbols" checked>
                        <label class="form-check-label" for="pg-symbols">Спецсимволы (!@#$%^&*)</label>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary mt-2" id="pg-generate-btn">Сгенерировать</button>
                </div>
                <!-- Правая колонка: Результаты -->
                <div class="col-md-6">
                    <h6>Результаты</h6>
                    <div id="pg-results-container">
                        <!-- Здесь будут отображаться сгенерированные пароли -->
                        <p class="text-muted">Нажмите "Сгенерировать"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>