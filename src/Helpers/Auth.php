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

class Auth
{
    private static ?UserManager $userManager = null;

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

        $userManager = self::getUserManager();
        $user = $userManager->verifyCredentials($username, $password);

        if ($user) {
            Session::set('authenticated', true);
            Session::set('user_id', $user['id']);
            Session::set('username', $user['username']);
            Session::set('user_role', $user['role']);
            Session::set('login_time', time());
            Session::regenerate();
            
            Logger::userAction('login', $user['id'], [
                'username' => $user['username'],
            ]);
            
            return true;
        }
        
        Logger::warning('Failed login attempt', [
            'username' => $username,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
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
        if (ENABLE_AUTH && !self::isAuthenticated()) {
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=login');
            exit;
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
