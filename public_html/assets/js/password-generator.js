// public_html/assets/js/password-generator.js
document.addEventListener('DOMContentLoaded', function () {
    const generatorCollapse = document.getElementById('passwordGeneratorCollapse');
    if (!generatorCollapse) return; // Если компонент не на странице, выходим

    const lengthInput = document.getElementById('pg-length');
    const uppercaseCheckbox = document.getElementById('pg-uppercase');
    const lowercaseCheckbox = document.getElementById('pg-lowercase');
    const numbersCheckbox = document.getElementById('pg-numbers');
    const symbolsCheckbox = document.getElementById('pg-symbols');
    const generateBtn = document.getElementById('pg-generate-btn');
    const resultsContainer = document.getElementById('pg-results-container');

    // --- Функция генерации одного пароля ---
    function generateSinglePassword(length, useUppercase, useLowercase, useNumbers, useSymbols) {
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const numbers = '0123456789';
        const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';

        let charset = '';
        if (useUppercase) charset += uppercase;
        if (useLowercase) charset += lowercase;
        if (useNumbers) charset += numbers;
        if (useSymbols) charset += symbols;

        if (!charset) {
            alert('Выберите хотя бы один тип символов.');
            return '';
        }

        let password = '';
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }

    // --- Функция генерации и отображения 5 паролей ---
    function generatePasswords() {
        // Проверяем существование элементов
        if (!lengthInput || !uppercaseCheckbox || !lowercaseCheckbox || 
            !numbersCheckbox || !symbolsCheckbox || !generateBtn || !resultsContainer) {
            console.warn('Не все элементы генератора паролей найдены.');
            return;
        }

        const length = parseInt(lengthInput.value) || 12;
        const useUppercase = uppercaseCheckbox.checked;
        const useLowercase = lowercaseCheckbox.checked;
        const useNumbers = numbersCheckbox.checked;
        const useSymbols = symbolsCheckbox.checked;

        // Ограничиваем длину
        if (length < 8) lengthInput.value = 8;
        if (length > 32) lengthInput.value = 32;

        resultsContainer.innerHTML = ''; // Очищаем предыдущие результаты

        for (let i = 0; i < 5; i++) {
            const password = generateSinglePassword(length, useUppercase, useLowercase, useNumbers, useSymbols);
            if (!password) return; // Если ошибка, прекращаем

            const passwordItem = document.createElement('div');
            passwordItem.className = 'mb-2 p-2 border rounded';
            passwordItem.style.cursor = 'pointer';
            passwordItem.textContent = password;
            passwordItem.title = 'Кликните, чтобы выбрать';

            passwordItem.addEventListener('click', function () {
                // Заполняем поля формы
                const passwordField = document.getElementById('password');
                const confirmPasswordField = document.getElementById('password_confirm');
                if (passwordField) passwordField.value = password;
                if (confirmPasswordField) confirmPasswordField.value = password;
                
                // Закрываем аккордеон
                const bsCollapse = bootstrap.Collapse.getInstance(generatorCollapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                } else {
                    // Если экземпляр не найден, скрываем вручную
                    generatorCollapse.classList.remove('show');
                }
            });

            resultsContainer.appendChild(passwordItem);
        }
    }

    // --- Обработчики событий ---
    if (generateBtn) {
        generateBtn.addEventListener('click', generatePasswords);
    }

});