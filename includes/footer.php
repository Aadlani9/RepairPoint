
<?php if (isset($_SESSION['user_id'])): ?>
</div> <!-- End main-content -->
<?php endif; ?>

<!-- Footer -->
<footer class="footer mt-auto py-3 <?= isset($_SESSION['user_id']) ? 'bg-light' : 'bg-primary text-white' ?>">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <span class="<?= isset($_SESSION['user_id']) ? 'text-muted' : 'text-white-50' ?>">
                    &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <span class="<?= isset($_SESSION['user_id']) ? 'text-muted' : 'text-white-50' ?>">
                    Desarrollado por <?= APP_AUTHOR ?>
                </span>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="<?= asset('js/main.js') ?>"></script>

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Print JavaScript -->
<script src="<?= asset('js/print.js') ?>"></script>
<?php endif; ?>

<!-- Page-specific JavaScript -->
<?php if (isset($page_scripts)): ?>
    <?= $page_scripts ?>
<?php endif; ?>

</body>
</html>