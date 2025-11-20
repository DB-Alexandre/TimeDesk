<?php
use Helpers\Validator;
use Helpers\Auth;
?>
<header class="bg-body-tertiary border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center py-3">
            <div>
                <h1 class="h3 m-0">
                    <span class="accent"><?= Validator::escape(APP_TITLE) ?></span>
                    <small class="text-secondary">(<?= Validator::escape(TIMEZONE) ?>)</small>
                </h1>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if (Auth::isAuthenticated()): ?>
                    <span class="text-secondary small">
                        <?= Validator::escape(Auth::getUsername()) ?>
                        <?php if (Auth::isAdmin()): ?>
                            <span class="badge bg-primary ms-1">Admin</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
                <?php if (Auth::isAdmin()): ?>
                    <a href="?action=users" class="btn btn-sm btn-outline-primary">
                        👥 Utilisateurs
                    </a>
                <?php endif; ?>
                <button class="btn btn-sm btn-outline-secondary" id="toggleTheme" type="button">
                    🌙/☀️
                </button>
                <?php if (ENABLE_AUTH && Auth::isAuthenticated()): ?>
                    <a href="?action=logout" class="btn btn-sm btn-outline-danger">
                        Déconnexion
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
