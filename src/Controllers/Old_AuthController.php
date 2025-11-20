<?php
/**
 * Contrôleur d'authentification
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
            Router::redirect('/');
        }

        $error = Session::flash('login_error');
        require VIEWS_PATH . '/pages/login.php';
    }

    /**
     * Traite la connexion
     */
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('/login');
        }

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (Auth::login($username, $password)) {
            Session::setFlash('success', 'Connexion réussie !');
            Router::redirect('/');
        } else {
            Session::set('login_error', 'Identifiants incorrects');
            Router::redirect('/login');
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): void
    {
        Auth::logout();
        Session::setFlash('success', 'Vous êtes déconnecté');
        Router::redirect('/login');
    }
}
