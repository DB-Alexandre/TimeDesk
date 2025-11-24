<?php
/**
 * Gestion de l'authentification - Multi-utilisateurs
 */

declare(strict_types=1);

namespace Helpers;

use Core\Session;
use Core\Router;
use Models\Database;
use Models\UserManager;
use Models\SecurityLogManager;

class Auth
{
    private static ?UserManager $userManager = null;
    private static ?SecurityLogManager $securityLogManager = null;

    /**
     * Obtient l'instance UserManager
     */
    private static function getUserManager(): UserManager
    {
        if (self::$userManager === null) {
            self::$userManager = new UserManager(Database::getInstance());
        }
        return self::$userManager;
    }

    /**
     * Gestionnaire de sécurité
     */
    private static function getSecurityLogManager(): SecurityLogManager
    {
        if (self::$securityLogManager === null) {
            self::$securityLogManager = new SecurityLogManager(Database::getInstance());
        }
        return self::$securityLogManager;
    }

    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public static function isAuthenticated(): bool
    {
        if (!ENABLE_AUTH) {
            return true; // Si l'auth est désactivée, considérer comme authentifié
        }
        
        return Session::get('authenticated', false) === true && 
               Session::get('user_id') !== null;
    }

    /**
     * Tente de connecter l'utilisateur
     */
    public static function login(string $username, string $password): bool
    {
        if (!ENABLE_AUTH) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $security = self::getSecurityLogManager();

        if ($security->isLocked($username, $ip)) {
            Logger::warning('Login blocked (rate limit)', [
                'username' => $username,
                'ip' => $ip,
            ]);
            Session::set('login_error', 'Trop de tentatives échouées. Veuillez réessayer plus tard.');
            Logger::userAction('login_blocked', null, [
                'username' => $username,
                'ip' => $ip,
            ]);
            return false;
        }

        $userManager = self::getUserManager();
        $user = $userManager->verifyCredentials($username, $password);

        if ($user) {
            Session::set('authenticated', true);
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('user_role', $user['role']);
            Session::set('login_time', time());
            Session::set('last_activity', time());
            Session::set('session_ip', $ip);
            Session::set('session_token', bin2hex(random_bytes(32)));
            Session::regenerate();
            $security->recordLoginAttempt($username, $ip, true);
            
            Logger::userAction('login', $user['id'], [
                'username' => $user['username'],
            ]);
            
            return true;
        }
        
        $security->recordLoginAttempt($username, $ip, false);
        Logger::warning('Failed login attempt', [
            'username' => $username,
            'ip' => $ip,
        ]);
        
        return false;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        $userId = self::getUserId();
        if ($userId) {
            Logger::userAction('logout', $userId);
        }
        Session::destroy();
    }

    /**
     * Vérifie l'authentification et redirige si nécessaire
     */
    public static function check(): void
    {
        if (!ENABLE_AUTH) {
            return;
        }

        if (!self::isAuthenticated()) {
            Router::redirect('?action=login');
        }

        if (self::hasSessionExpired()) {
            self::logout();
            Session::start();
            Session::set('login_error', 'Votre session a expiré. Veuillez vous reconnecter.');
            Router::redirect('?action=login');
        }

        $sessionIp = Session::get('session_ip');
        $currentIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($sessionIp && $sessionIp !== $currentIp) {
            self::logout();
            Session::start();
            Session::set('login_error', 'Session invalide (adresse IP différente).');
            Router::redirect('?action=login');
        }

        Session::set('last_activity', time());
    }

    /**
     * Vérifie l'expiration de session
     */
    private static function hasSessionExpired(): bool
    {
        if (SESSION_TIMEOUT <= 0) {
            return false;
        }

        $lastActivity = Session::get('last_activity');
        if (!$lastActivity) {
            return false;
        }

        return (time() - (int)$lastActivity) > SESSION_TIMEOUT;
    }

    /**
     * Force le rôle admin
     */
    public static function requireAdmin(): void
    {
        self::check();
        if (!self::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            Router::redirect('/');
        }
    }

    /**
     * Obtient le nom d'utilisateur connecté
     */
    public static function getUsername(): ?string
    {
        return Session::get('username');
    }

    /**
     * Obtient l'ID de l'utilisateur connecté
     */
    public static function getUserId(): ?int
    {
        $userId = Session::get('user_id');
        return $userId !== null ? (int)$userId : null;
    }

    /**
     * Obtient le rôle de l'utilisateur connecté
     */
    public static function getRole(): ?string
    {
        return Session::get('user_role');
    }

    /**
     * Vérifie si l'utilisateur est administrateur
     */
    public static function isAdmin(): bool
    {
        return self::getRole() === 'admin';
    }

    /**
     * Vérifie si l'utilisateur peut accéder à une ressource (propriétaire ou admin)
     */
    public static function canAccess(int $resourceUserId): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        
        return self::getUserId() === $resourceUserId;
    }

    /**
     * Génère un hash de mot de passe
     */
    public static function hashPassword(string $password): string
    {
        PasswordPolicy::validate($password);
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Obtient les informations de l'utilisateur connecté
     */
    public static function getUser(): ?array
    {
        $userId = self::getUserId();
        if (!$userId) {
            return null;
        }

        $userManager = self::getUserManager();
        return $userManager->findById($userId);
    }
}
