<?php
/**
 * Contrôleur d'authentification - VERSION CORRIGÉE
 */

declare(strict_types=1);

namespace Controllers;

use Helpers\Auth;
use Core\Session;
use Core\Router;

class AuthController
{
    /**
     * Affiche la page de connexion
     */
    public function loginForm(): void
    {
        if (Auth::isAuthenticated()) {
            // Redirection vers l'accueil
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        $error = Session::flash('login_error');
        require VIEWS_PATH . '/pages/login.php';
    }

    /**
     * Traite la connexion - VERSION CORRIGÉE
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=login');
            exit;
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            Session::setFlash('success', 'Connexion réussie !');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        } else {
            Session::set('login_error', 'Identifiants incorrects');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=login');
            exit;
        }
    }

    /**
     * Déconnexion - VERSION CORRIGÉE
     */
    public function logout(): void
    {
        Auth::logout();
        Session::start(); // Redémarrer pour le flash message
        Session::setFlash('success', 'Vous êtes déconnecté');
        header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=login');
        exit;
    }
}
