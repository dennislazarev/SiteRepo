document.addEventListener('DOMContentLoaded', function () {
    // --- НОВАЯ ЛОГИКА: Универсальные кнопки быстрого выбора для форм ---
    // Находим все кнопки быстрого выбора
    const quickSelectButtons = document.querySelectorAll('.standard-short-name-btn, .existing-module-btn');

    quickSelectButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Получаем значение из data-атрибута кнопки
            const value = this.getAttribute('data-value');
            
            // Определяем, для какого инпута предназначена эта кнопка
            // по классу кнопки или по её расположению (ближайший input)
            let targetInput = null;
            
            if (this.classList.contains('standard-short-name-btn')) {
                // Кнопка для "Короткое название"
                targetInput = document.getElementById('display_name_short');
            } else if (this.classList.contains('existing-module-btn')) {
                // Кнопка для "Модуль"
                targetInput = document.getElementById('module');
            }

            // Если целевой инпут найден, устанавливаем в него значение
            if (targetInput) {
                targetInput.value = value;
                // Переводим фокус на поле ввода для удобства
                targetInput.focus();
            }
        });
    });
    // --- КОНЕЦ НОВОЙ ЛОГИКИ ---

    // --- НОВАЯ ЛОГИКА: Маска ввода телефона ---
    // Найдем все поля ввода с классом phone-input или атрибутом data-mask="phone"
    const phoneInputs = document.querySelectorAll('input.phone-input, input[data-mask="phone"]');
    phoneInputs.forEach(function(input) {
        // Простая маска для +7 xxx xxx-xx-xx
        // Можно заменить на более robust библиотеку, если потребуется
        input.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Удаляем все нецифры
            if (value.startsWith('7')) {
                value = value.substring(1); // Убираем первую 7, если ввели
            }
            if (value.startsWith('8')) {
                value = value.substring(1); // Убираем первую 8, если ввели
            }

            let formattedValue = '+7 ';
            if (value.length > 0) {
                formattedValue += value.substring(0, 3);
            }
            if (value.length >= 4) {
                formattedValue += ' ' + value.substring(3, 6);
            }
            if (value.length >= 7) {
                formattedValue += '-' + value.substring(6, 8);
            }
            if (value.length >= 9) {
                formattedValue += '-' + value.substring(8, 10);
            }

            e.target.value = formattedValue;
        });

        // Предотвращаем ввод нецифровых символов (кроме backspace, tab, delete и т.д.)
        input.addEventListener('keydown', function (e) {
            // Разрешаем: backspace, delete, tab, escape, enter, ctrl+A, Ctrl+C, Ctrl+V, Home, End, стрелки
            if ([46, 8, 9, 27, 13, 116].indexOf(e.keyCode) !== -1 ||
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode >= 35 && e.keyCode <= 40)) {
                return;
            }
            // Запрещаем, если это не число и не разрешенный символ
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    });
    // --- КОНЕЦ НОВОЙ ЛОГИКИ для телефона ---

    // --- НОВАЯ ЛОГИКА: Переключение видимости пароля ---
    // Найти все кнопки переключения видимости пароля
    const togglePasswordButtons = document.querySelectorAll('.toggle-password-visibility');

    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);

            if (targetInput) {
                const type = targetInput.getAttribute('type') === 'password' ? 'text' : 'password';
                targetInput.setAttribute('type', type);
                
                // Переключаем иконку
                const icon = this.querySelector('i');
                if (icon) {
                    if (type === 'password') {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            }
        });
    });
    // --- КОНЕЦ НОВОЙ ЛОГИКИ ---
});