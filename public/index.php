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
use Controllers\UserController;
use Controllers\AdminController;
use Helpers\Logger;

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

    if ($action === 'admin-db') {
        $adminController = new AdminController();
        $adminController->database();
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

    // Routes de gestion des utilisateurs (admin uniquement)
    if (in_array($action, ['users', 'user-create', 'user-edit', 'user-delete', 'user-stats'])) {
        $userController = new UserController();
        
        switch ($action) {
            case 'users':
                $userController->index();
                break;
                
            case 'user-create':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $userController->create();
                } else {
                    $userController->createForm();
                }
                break;
                
            case 'user-edit':
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $userController->update();
                } else {
                    $userController->editForm();
                }
                break;
                
            case 'user-delete':
                $userController->delete();
                break;
                
            case 'user-stats':
                $userController->userStats();
                break;
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
    // Log de l'erreur
    Logger::error('Application error', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    
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
