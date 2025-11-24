<?php
/**
 * Gestionnaire de langues
 */

declare(strict_types=1);

namespace Helpers;

use Core\Session;

class LanguageManager
{
    private const LANGUAGES = [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
    ];

    private static array $translations = [];

    /**
     * Récupère la langue actuelle
     */
    public static function getCurrentLanguage(): string
    {
        return Session::get('language', 'fr');
    }

    /**
     * Définit la langue
     */
    public static function setLanguage(string $lang): void
    {
        if (self::languageExists($lang)) {
            Session::set('language', $lang);
            self::loadTranslations($lang);
        }
    }

    /**
     * Vérifie si une langue existe
     */
    public static function languageExists(string $lang): bool
    {
        return isset(self::LANGUAGES[$lang]);
    }

    /**
     * Liste toutes les langues disponibles
     */
    public static function getAllLanguages(): array
    {
        return self::LANGUAGES;
    }

    /**
     * Charge les traductions pour une langue
     */
    public static function loadTranslations(string $lang = null): void
    {
        $lang = $lang ?? self::getCurrentLanguage();
        $file = ROOT_PATH . "/lang/{$lang}.php";

        if (file_exists($file)) {
            self::$translations = require $file;
        } else {
            // Fallback vers français
            $fallback = ROOT_PATH . "/lang/fr.php";
            if (file_exists($fallback)) {
                self::$translations = require $fallback;
            } else {
                self::$translations = [];
            }
        }
    }

    /**
     * Traduit une clé
     */
    public static function translate(string $key, array $params = []): string
    {
        if (empty(self::$translations)) {
            self::loadTranslations();
        }

        $translation = self::$translations[$key] ?? $key;

        // Remplacement des paramètres
        foreach ($params as $param => $value) {
            $translation = str_replace("{{$param}}", $value, $translation);
        }

        return $translation;
    }

    /**
     * Alias court pour translate
     */
    public static function t(string $key, array $params = []): string
    {
        return self::translate($key, $params);
    }
}

