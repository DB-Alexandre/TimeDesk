<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers\Validator::escape(APP_TITLE) ?></title>
    <meta name="theme-color" content="#0d6efd">
    <link rel="manifest" href="/manifest.json">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php require VIEWS_PATH . '/partials/header.php'; ?>
    
    <div class="container py-4">
        <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        <?php require VIEWS_PATH . '/' . $view . '.php'; ?>
    </div>

    <?php require VIEWS_PATH . '/partials/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/pwa.js"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
