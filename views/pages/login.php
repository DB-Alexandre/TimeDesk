<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - <?= Helpers\Validator::escape(APP_TITLE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card glass" style="width: 100%; max-width: 400px;">
        <div class="card-body">
            <h1 class="h3 mb-4 text-center accent"><?= Helpers\Validator::escape(APP_TITLE) ?></h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= Helpers\Validator::escape($error) ?></div>
            <?php endif; ?>
            
            <form method="post" action="?action=login">
                <div class="mb-3">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" class="form-control" name="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Se connecter</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/theme.js"></script>
</body>
</html>
