<?php
/**
 * Configuration de l'application TimeDesk
 */

declare(strict_types=1);

// Informations de l'application
define('APP_TITLE', 'TimeDesk');
define('APP_VERSION', '2.0.0');
define('TIMEZONE', 'Europe/Paris');

// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('SRC_PATH', ROOT_PATH . '/src');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('DATA_PATH', ROOT_PATH . '/data');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Base de données
define('DB_FILE', DATA_PATH . '/timesheet.sqlite');

// Configuration du temps de travail
define('CONTRACT_WEEKLY_HOURS', 35.0);
define('MONTHLY_TARGET_HOURS', 151.67);

// Limites de sécurité
define('MAX_DESCRIPTION_LENGTH', 500);
define('MAX_ENTRIES_PER_DAY', 50);
define('CSRF_TOKEN_LENGTH', 32);

// Authentification
define('ENABLE_AUTH', true); // Mettre à true pour activer
define('AUTH_USERNAME', 'admin');
// Généré avec: password_hash('password', PASSWORD_DEFAULT)
define('AUTH_PASSWORD_HASH', '$2y$10$t/zzdiG9wgarVdJJkXR62.n8CrOyRHaQVc2DWNku1eyNSJnOFtini');

// Session
define('SESSION_NAME', 'timedesk_session');
define('SESSION_LIFETIME', 3600 * 24); // 24 heures

// Environnement
define('ENV', 'production'); // 'development' ou 'production'
define('DEBUG', ENV === 'development');

// Configuration d'erreurs
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// Timezone
date_default_timezone_set(TIMEZONE);

// Autoloader simple
spl_autoload_register(function (string $class) {
    $prefix = '';
    $base_dir = SRC_PATH . '/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
