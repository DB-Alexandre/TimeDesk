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

// Chargement des variables d'environnement
require_once __DIR__ . '/env.php';
app_load_env(ROOT_PATH . '/.env');

// Base de données
define('DB_DRIVER', strtolower((string)env('DB_DRIVER', 'sqlite')));

// SQLite
define('DB_SQLITE_PATH', env('DB_SQLITE_PATH', DATA_PATH . '/timesheet.sqlite'));

// MySQL
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int)env('DB_PORT', 3306));
define('DB_DATABASE', env('DB_DATABASE', 'timedesk'));
define('DB_USERNAME', env('DB_USERNAME', 'root'));
define('DB_PASSWORD', env('DB_PASSWORD', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('DB_COLLATION', env('DB_COLLATION', 'utf8mb4_unicode_ci'));

// Compatibilité historique
define('DB_FILE', DB_SQLITE_PATH);

// Sécurité session & auth
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600)); // 1h
define('LOGIN_MAX_ATTEMPTS', (int)env('LOGIN_MAX_ATTEMPTS', 5));
define('LOGIN_LOCK_WINDOW', (int)env('LOGIN_LOCK_WINDOW', 900)); // 15 min
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 10));
define('PASSWORD_REQUIRE_UPPERCASE', env_bool('PASSWORD_REQUIRE_UPPERCASE', true));
define('PASSWORD_REQUIRE_LOWERCASE', env_bool('PASSWORD_REQUIRE_LOWERCASE', true));
define('PASSWORD_REQUIRE_DIGIT', env_bool('PASSWORD_REQUIRE_DIGIT', true));
define('PASSWORD_REQUIRE_SPECIAL', env_bool('PASSWORD_REQUIRE_SPECIAL', true));
define('PASSWORD_RESET_EXPIRY', (int)env('PASSWORD_RESET_EXPIRY', 3600)); // 1h

// Email
define('MAIL_ENABLED', env_bool('MAIL_ENABLED', false));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', ''));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'TimeDesk'));

// Logs & monitoring
define('LOG_MAX_SIZE_MB', (int)env('LOG_MAX_SIZE_MB', 10));
define('LOG_RETENTION_DAYS', (int)env('LOG_RETENTION_DAYS', 7));

define('ALERT_ENABLED', env_bool('ALERT_ENABLED', false));
define('ALERT_WEBHOOK_URL', env('ALERT_WEBHOOK_URL', ''));
define('ALERT_WEBHOOK_METHOD', strtoupper(env('ALERT_WEBHOOK_METHOD', 'POST')));
define('ALERT_EVENTS', env_array('ALERT_EVENTS', ['login_blocked', 'db_console_query', 'user_deleted']));

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
