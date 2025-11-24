<?php
use Helpers\Validator;
use Helpers\Auth;
?>
<header class="navbar-custom">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="?action=index">
                <span class="accent"><?= Validator::escape(APP_TITLE) ?></span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="?action=index">
                            🏠 Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?action=calendar">
                            📅 Calendrier
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?action=import">
                            📥 Importer
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?action=report-create">
                            📊 Rapport
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if (Auth::isAuthenticated()): ?>
                        <?php if (Auth::isAdmin()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    ⚙️ Administration
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="?action=users">
                                        👥 Utilisateurs
                                    </a></li>
                                    <li><a class="dropdown-item" href="?action=admin-db">
                                        🗄️ Base de données
                                    </a></li>
                                    <li><a class="dropdown-item" href="?action=backup-list">
                                        💾 Sauvegardes
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="?action=audit-log">
                                        🔐 Audit de sécurité
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                👤 <?= Validator::escape(Auth::getUsername()) ?>
                                <?php if (Auth::isAdmin()): ?>
                                    <span class="badge bg-primary ms-1">Admin</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="?action=logout">
                                    🚪 Déconnexion
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?action=login">
                                🔑 Connexion
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
