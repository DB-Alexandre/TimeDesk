<?php
/**
 * Classe de validation des données
 */

declare(strict_types=1);

namespace Helpers;

use Core\Session;

class Validator
{
    /**
     * Valide une date au format YYYY-MM-DD
     */
    public static function date(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        
        $parts = explode('-', $date);
        return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
    }

    /**
     * Valide une heure au format HH:MM
     */
    public static function time(string $time): bool
    {
        return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time);
    }

    /**
     * Valide un type d'entrée
     */
    public static function type(string $type): bool
    {
        return in_array($type, ['work', 'break', 'course'], true);
    }

    /**
     * Valide une description
     */
    public static function description(string $description): bool
    {
        return mb_strlen($description) <= MAX_DESCRIPTION_LENGTH;
    }

    /**
     * Valide le token CSRF
     */
    public static function csrf(string $token): bool
    {
        return hash_equals(Session::getCsrfToken(), $token);
    }

    /**
     * Récupère le token CSRF
     */
    public static function csrfToken(): string
    {
        return Session::getCsrfToken();
    }

    /**
     * Échappe une chaîne HTML
     */
    public static function escape(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Nettoie une chaîne
     */
    public static function clean(string $string): string
    {
        return trim($string);
    }
}
