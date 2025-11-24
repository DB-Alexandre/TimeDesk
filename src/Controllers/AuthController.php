<?php
/**
 * Contrôleur d'authentification - VERSION CORRIGÉE
 */

declare(strict_types=1);

namespace Controllers;

use Helpers\Auth;
use Helpers\Logger;
use Helpers\Validator;
use Helpers\Mailer;
use Core\Session;
use Core\Router;
use Models\Database;
use Models\UserManager;
use Models\SecurityLogManager;

class AuthController
{
    private UserManager $userManager;
    private SecurityLogManager $securityLogManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->userManager = new UserManager($db);
        $this->securityLogManager = new SecurityLogManager($db);
    }

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
        $flash = Session::getFlash();
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
            Logger::warning('Failed login attempt', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]);
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

    /**
     * Formulaire de récupération de mot de passe
     */
    public function forgotPasswordForm(): void
    {
        $flash = Session::getFlash();
        require VIEWS_PATH . '/pages/forgot-password.php';
    }

    /**
     * Envoi du lien de réinitialisation
     */
    public function sendResetLink(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('?action=forgot-password');
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            Router::redirect('?action=forgot-password');
        }

        $email = trim((string)($_POST['email'] ?? ''));

        if ($email === '') {
            Session::setFlash('error', 'Veuillez saisir votre email.');
            Router::redirect('?action=forgot-password');
        }

        $user = $this->userManager->findByEmail($email);

        if ($user && !empty($user['email'])) {
            $token = $this->securityLogManager->createPasswordResetToken((int)$user['id']);
            $resetUrl = Router::getBaseUrl() . '/?action=reset-password&token=' . urlencode($token);

            $body = "Bonjour {$user['username']},\n\n";
            $body .= "Vous (ou quelqu'un d'autre) avez demandé la réinitialisation de votre mot de passe.\n";
            $body .= "Cliquez sur le lien ci-dessous pour en définir un nouveau (valide pendant " . (int)(PASSWORD_RESET_EXPIRY / 60) . " minutes):\n";
            $body .= "{$resetUrl}\n\n";
            $body .= "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n\n";
            $body .= '— ' . APP_TITLE;

            Mailer::send($user['email'], 'Réinitialisation de mot de passe', $body);

            Logger::info('Password reset requested', [
                'user_id' => $user['id'],
                'email' => $user['email'],
            ]);
        }

        Session::setFlash('success', 'Si un compte existe avec cet email, un lien lui a été envoyé.');
        Router::redirect('?action=login');
    }

    /**
     * Formulaire de réinitialisation
     */
    public function resetPasswordForm(): void
    {
        $token = $_GET['token'] ?? '';
        $valid = $token && $this->securityLogManager->findValidResetToken($token);

        if (!$valid) {
            Session::setFlash('error', 'Lien invalide ou expiré.');
            Router::redirect('?action=login');
        }

        $flash = Session::getFlash();
        require VIEWS_PATH . '/pages/reset-password.php';
    }

    /**
     * Traitement du reset
     */
    public function resetPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('?action=login');
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            Router::redirect('?action=login');
        }

        $token = $_POST['token'] ?? '';
        $record = $token ? $this->securityLogManager->findValidResetToken($token) : null;

        if (!$record) {
            Session::setFlash('error', 'Lien invalide ou expiré.');
            Router::redirect('?action=login');
        }

        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirm'] ?? '';

        if ($password !== $confirm) {
            Session::setFlash('error', 'Les mots de passe ne correspondent pas.');
            Router::redirect('?action=reset-password&token=' . urlencode($token));
        }

        try {
            $this->userManager->updatePassword((int)$record['user_id'], $password);
            $this->securityLogManager->markResetTokenUsed((int)$record['id']);
            Session::setFlash('success', 'Mot de passe mis à jour. Vous pouvez vous connecter.');

            Logger::info('Password reset completed', [
                'user_id' => $record['user_id'],
            ]);
        } catch (\Throwable $e) {
            Session::setFlash('error', $e->getMessage());
            Router::redirect('?action=reset-password&token=' . urlencode($token));
        }

        Router::redirect('?action=login');
    }
}
