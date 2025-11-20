<?php
/**
 * TimeDesk - Point d'entrée de l'application
 * 
 * @version 2.0.0
 */

declare(strict_types=1);

// Chargement de la configuration
require_once __DIR__ . '/../config/config.php';

use Core\Session;
use Core\Router;
use Controllers\EntryController;
use Controllers\ApiController;
use Controllers\AuthController;

// Démarrage de la session
Session::start();

// Gestion de la déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $authController = new AuthController();
    $authController->logout();
}

// Routage simple basé sur le paramètre 'action'
$action = $_GET['action'] ?? 'index';

try {
    // Routes API (AJAX)
    if ($action === 'lastEnd') {
        $apiController = new ApiController();
        $apiController->lastEndTime();
        exit;
    }

    if ($action === 'export-csv') {
        $apiController = new ApiController();
        $apiController->exportCsv();
        exit;
    }

    // Routes d'authentification
    if ($action === 'login') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $authController = new AuthController();
            $authController->login();
        } else {
            $authController = new AuthController();
            $authController->loginForm();
        }
        exit;
    }

    // Routes des entrées
    $entryController = new EntryController();

    switch ($action) {
        case 'create':
            $entryController->create();
            break;

        case 'update':
            $entryController->update();
            break;

        case 'delete':
            $entryController->delete();
            break;

        case 'index':
        default:
            $entryController->index();
            break;
    }

} catch (Throwable $e) {
    // Gestion des erreurs
    if (DEBUG) {
        echo '<pre>';
        echo 'Erreur: ' . $e->getMessage() . "\n";
        echo 'Fichier: ' . $e->getFile() . ':' . $e->getLine() . "\n";
        echo 'Trace: ' . "\n" . $e->getTraceAsString();
        echo '</pre>';
    } else {
        error_log($e->getMessage());
        Session::setFlash('error', 'Une erreur est survenue');
        Router::redirect('/');
    }
}
