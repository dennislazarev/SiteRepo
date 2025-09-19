// public_html/assets/js/alerts.js
document.addEventListener('DOMContentLoaded', function () {
    // --- Логика автоматического закрытия обычных алертов ---
    // Находим все алерты, которые МОГУТ быть автоматически закрыты (у них есть класс 'alert-dismissible')
    const autoCloseAlerts = document.querySelectorAll('.alert.alert-dismissible');
    
    autoCloseAlerts.forEach(function (alert) {
        // Устанавливаем таймер для автоматического закрытия через 5 секунд
        const autoCloseTimer = setTimeout(function () {
            // Проверяем, существует ли элемент перед закрытием
            if (document.body.contains(alert)) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            }
        }, 5000); // 5 секунд

        // Если пользователь сам нажимает кнопку закрытия, отменяем таймер
        const closeButton = alert.querySelector('[data-bs-dismiss="alert"]');
        if (closeButton) {
            closeButton.addEventListener('click', function () {
                clearTimeout(autoCloseTimer);
            });
        }
    });
    // --- Конец логики алертов ---
});