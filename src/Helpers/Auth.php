<?php
/**
 * Gestion de l'authentification - VERSION CORRIGÉE
 */

declare(strict_types=1);

namespace Helpers;

use Core\Session;
use Core\Router;

class Auth
{
    /**
     * Vérifie si l'utilisateur est authentifié
     */
    public static function isAuthenticated(): bool
    {
        return Session::get('authenticated', false) === true;
    }

    /**
     * Tente de connecter l'utilisateur
     */
    public static function login(string $username, string $password): bool
    {
        if ($username === AUTH_USERNAME && password_verify($password, AUTH_PASSWORD_HASH)) {
            Session::set('authenticated', true);
            Session::set('username', $username);
            Session::set('login_time', time());
            Session::regenerate();
            return true;
        }
        
        return false;
    }

    /**
     * Déconnecte l'utilisateur
     */
    public static function logout(): void
    {
        Session::destroy();
    }

    /**
     * Vérifie l'authentification et redirige si nécessaire - VERSION CORRIGÉE
     */
    public static function check(): void
    {
        if (ENABLE_AUTH && !self::isAuthenticated()) {
            // Utiliser une query string au lieu d'un chemin absolu
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
     * Génère un hash de mot de passe
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
