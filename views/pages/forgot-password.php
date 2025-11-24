<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié - <?= Helpers\Validator::escape(APP_TITLE) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card glass" style="width: 100%; max-width: 420px;">
        <div class="card-body">
            <h1 class="h4 mb-3 text-center accent">Réinitialiser le mot de passe</h1>
            <p class="text-muted small text-center">
                Entrez votre adresse email. Si un compte existe, vous recevrez un lien de réinitialisation.
            </p>

            <?php if (isset($flash) && is_array($flash)): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?>">
                    <?= Helpers\Validator::escape($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="?action=forgot-password">
                <input type="hidden" name="csrf" value="<?= Helpers\Validator::csrfToken() ?>">
                <div class="mb-3">
                    <label class="form-label">Adresse email</label>
                    <input type="email" class="form-control" name="email" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100">Envoyer le lien</button>
            </form>

            <div class="text-center mt-3">
                <a href="?action=login" class="text-decoration-none small">← Retour à la connexion</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

