<!-- app/Views/layouts/minimal.php -->

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Админка') ?></title>
    
    <?php require_once dirname(__DIR__) . '/partials/cssLib.php'; ?>

</head>
<body class="bg-light"> <!-- Добавим фон для лучшего вида -->
    <!-- Контейнер для фиксированных алертов -->
    <div id="alert-container" class="alert-container">
        <?php
            // Отображение алертов из сессии через модуль
            // Предполагается, что сессия уже запущена в index.php
            \App\Core\Alert::renderFromSession();
        ?>
    </div>
    
    <div class="container-fluid">
        <!-- Содержимое будет вставлено сюда -->
        <?= $content ?? '' ?>
    </div>

    <?php require_once dirname(__DIR__) . '/partials/jsLib.php'; ?>

</body>
</html>