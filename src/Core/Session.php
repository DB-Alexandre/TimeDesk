<?php
/**
 * Gestionnaire de sessions
 */

declare(strict_types=1);

namespace Core;

class Session
{
    private static bool $started = false;

    /**
     * Démarre la session
     */
    public static function start(): void
    {
        if (!self::$started) {
            session_name(SESSION_NAME);
            session_start();
            self::$started = true;
            
            // Génération du token CSRF
            if (!self::has('csrf_token')) {
                self::set('csrf_token', bin2hex(random_bytes(CSRF_TOKEN_LENGTH)));
            }
        }
    }

    /**
     * Définit une valeur de session
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Récupère une valeur de session
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Vérifie si une clé existe
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Supprime une valeur de session
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Récupère et supprime une valeur (flash)
     */
    public static function flash(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::remove($key);
        return $value;
    }

    /**
     * Définit un message flash
     */
    public static function setFlash(string $type, string $message): void
    {
        self::set('flash', ['type' => $type, 'message' => $message]);
    }

    /**
     * Récupère le message flash
     */
    public static function getFlash(): ?array
    {
        return self::flash('flash');
    }

    /**
     * Récupère le token CSRF
     */
    public static function getCsrfToken(): string
    {
        return self::get('csrf_token', '');
    }

    /**
     * Détruit la session
     */
    public static function destroy(): void
    {
        if (self::$started) {
            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Régénère l'ID de session
     */
    public static function regenerate(): void
    {
        if (self::$started) {
            session_regenerate_id(true);
        }
    }
}
