<!-- app/Views/partials/footer.php -->

<footer class="app-footer text-white text-center py-3 mt-5">
    <div class="container">
        <?php
            $firstYear = 2025;
            $currentYear = date('Y');
            $range = $currentYear > $firstYear ? "$firstYear - $currentYear" : $currentYear;
        ?>
        <p class="mb-0"><?php echo $range; ?> © Каталогизатор BSG. Все права защищены.</p>
    </div>
</footer>