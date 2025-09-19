<!-- app/Views/layouts/main.php -->

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">
    <link rel="manifest" href="/assets/favicon/site.webmanifest">
    <title><?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Админка') ?></title>

    <?php require_once dirname(__DIR__) . '/partials/cssLib.php'; ?>

</head>

<body>

    <!-- Контейнер для фиксированных алертов -->
    <div id="alert-container" class="alert-container">
        <?php
            // Отображение алертов из сессии через модуль
            // Предполагается, что сессия уже запущена в index.php
            \App\Core\Alert::renderFromSession();
        ?>
    </div>

    <?php require_once dirname(__DIR__) . '/partials/header.php'; ?>

    <!-- --- Новое: Обернем основной контент в скроллируемый контейнер --- -->
    <div class="main-content-wrapper">
        <div class="container-fluid">
            <?= $content ?? '' ?>
        </div>
    </div>
    <!-- --- Конец нового --- -->

    <?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

    <?php require_once dirname(__DIR__) . '/partials/jsLib.php'; ?>

</body>
</html>