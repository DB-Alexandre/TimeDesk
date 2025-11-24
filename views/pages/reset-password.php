<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nouveau mot de passe - <?= Helpers\Validator::escape(APP_TITLE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card glass" style="width:100%; max-width:420px;">
        <div class="card-body">
            <h1 class="h4 text-center mb-3 accent">Définir un nouveau mot de passe</h1>

            <?php if (isset($flash) && is_array($flash)): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                    <?= Helpers\Validator::escape($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="?action=reset-password">
                <input type="hidden" name="csrf" value="<?= Helpers\Validator::csrfToken() ?>">
                <input type="hidden" name="token" value="<?= Helpers\Validator::escape($_GET['token'] ?? '') ?>">

                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" class="form-control" name="password" required>
                    <small class="text-muted">
                        Minimum <?= PASSWORD_MIN_LENGTH ?> caractères. Inclure majuscules, minuscules, chiffres et caractères spéciaux.
                    </small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input type="password" class="form-control" name="password_confirm" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Mettre à jour</button>
            </form>

            <div class="text-center mt-3">
                <a href="?action=login" class="text-decoration-none small">Retour à la connexion</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

