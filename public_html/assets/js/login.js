// public_html/assets/js/login.js
// Этот файл будет обрабатывать логику блокировки формы входа и таймера

document.addEventListener('DOMContentLoaded', function () {
    // Найдем форму входа
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return; // Если форма не найдена, выходим

    const loginButton = document.getElementById('login-button');
    const loginInputs = loginForm.querySelectorAll('input:not([type="hidden"])');
    const loginBlockTimer = document.getElementById('login-block-timer');
    const countdownTimer = document.getElementById('countdown-timer');
    
    if (!loginButton || !loginBlockTimer || !countdownTimer) {
        console.error('Не найдены необходимые элементы для работы таймера блокировки.');
        return;
    }

    // --- Функция для блокировки формы ---
    function lockForm() {
        loginButton.disabled = true;
        loginInputs.forEach(input => {
            // Вместо disabled, используем readonly и добавляем атрибут data-locked
            input.readOnly = true;
            input.setAttribute('data-locked', 'true');
            // Добавим визуальный стиль для заблокированных полей
            input.style.backgroundColor = '#e9ecef';
            input.style.cursor = 'not-allowed';
        });
    }

    // --- Функция для разблокировки формы ---
    function unlockForm() {
        loginButton.disabled = false;
        loginInputs.forEach(input => {
            // Убираем readonly и атрибут data-locked
            if (input.hasAttribute('data-locked')) {
                input.readOnly = false;
                input.removeAttribute('data-locked');
                // Восстанавливаем обычные стили
                input.style.backgroundColor = '';
                input.style.cursor = '';
            }
        });
    }

    // --- Функция обновления таймера ---
    let countdownInterval = null;
    function updateCountdown(blockedUntilDate) {
        const now = new Date();
        const timeLeft = blockedUntilDate - now; // разница в миллисекундах
        
        if (timeLeft <= 0) {
            // Время блокировки истекло
            clearInterval(countdownInterval);
            countdownInterval = null;
            
            // Скроем таймер
            loginBlockTimer.style.display = 'none';
            
            // Разблокируем форму
            unlockForm();
            
            // Скроем алерт error_rate_limit, если он есть
            const rateLimitAlert = document.querySelector('.alert[data-alert-type="error_rate_limit"]');
            if (rateLimitAlert) {
                rateLimitAlert.style.display = 'none';
            }
            
        } else {
            // Рассчитываем минуты и секунды
            const totalSeconds = Math.floor(timeLeft / 1000);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            
            // Форматируем строку таймера
            const formattedTime = 
                minutes.toString().padStart(2, '0') + ':' + 
                seconds.toString().padStart(2, '0');
            countdownTimer.textContent = formattedTime;
        }
    }

    // --- Основная логика ---
    
    // Проверим, есть ли атрибут data-blocked-until у формы
    if (loginForm.hasAttribute('data-blocked-until')) {
        const blockedUntilTimestamp = parseInt(loginForm.getAttribute('data-blocked-until'), 10);
        
        if (!isNaN(blockedUntilTimestamp) && blockedUntilTimestamp > 0) {
            const blockedUntilDate = new Date(blockedUntilTimestamp * 1000);
            const now = new Date();
            
            // Проверим, истекло ли время блокировки
            if (blockedUntilDate > now) {
                // Блокировка еще активна
                // 1. Покажем таймер
                loginBlockTimer.style.display = 'block';
                
                // 2. Заблокируем форму
                lockForm();
                
                // 3. Найдем и покажем алерт error_rate_limit, если он есть
                const rateLimitAlert = document.querySelector('.alert[data-alert-type="error_rate_limit"]');
                if (rateLimitAlert) {
                    // Убедимся, что у него нет крестика (на всякий случай)
                    const closeButton = rateLimitAlert.querySelector('.btn-close');
                    if (closeButton) {
                        closeButton.remove();
                    }
                    // Сделаем его видимым, если он был скрыт
                    rateLimitAlert.style.display = 'block';
                }
                
                // 4. Запустим таймер
                updateCountdown(blockedUntilDate);
                countdownInterval = setInterval(() => updateCountdown(blockedUntilDate), 1000);
                
            } else {
                // Время блокировки истекло, но атрибут остался
                // Это может произойти, если пользователь перешел по старой ссылке
                // Просто уберем атрибут и разблокируем форму
                loginForm.removeAttribute('data-blocked-until');
                unlockForm();
                loginBlockTimer.style.display = 'none';
            }
        }
    } else {
        // Нет активной блокировки, форма должна быть разблокирована
        unlockForm();
        loginBlockTimer.style.display = 'none';
    }
    
    // --- Дополнительная защита от обхода через инспектор ---
    // Блокируем отправку формы, если она заблокирована
    loginForm.addEventListener('submit', function(e) {
        // Проверяем наличие атрибута data-blocked-until
        if (loginForm.hasAttribute('data-blocked-until')) {
            const blockedUntilTimestamp = parseInt(loginForm.getAttribute('data-blocked-until'), 10);
            if (!isNaN(blockedUntilTimestamp) && blockedUntilTimestamp > 0) {
                const blockedUntilDate = new Date(blockedUntilTimestamp * 1000);
                const now = new Date();
                
                // Если время блокировки еще не истекло
                if (blockedUntilDate > now) {
                    e.preventDefault();
                    e.stopPropagation();
                    alert('Форма заблокирована. Пожалуйста, подождите окончания таймера.');
                    return false;
                }
            }
        }
        
        // Дополнительная проверка на disabled кнопки
        if (loginButton.disabled) {
            e.preventDefault();
            e.stopPropagation();
            alert('Форма заблокирована. Пожалуйста, подождите окончания таймера.');
            return false;
        }
    });
});