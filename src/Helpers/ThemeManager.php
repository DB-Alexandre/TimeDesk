<?php
/**
 * Gestionnaire de thèmes personnalisables
 */

declare(strict_types=1);

namespace Helpers;

use Core\Session;

class ThemeManager
{
    private const THEMES = [
        'light' => [
            'name' => 'Clair',
            'primary' => '#0d6efd',
            'bg' => '#ffffff',
            'text' => '#212529',
        ],
        'dark' => [
            'name' => 'Sombre',
            'primary' => '#0d6efd',
            'bg' => '#212529',
            'text' => '#ffffff',
        ],
        'blue' => [
            'name' => 'Bleu',
            'primary' => '#0066cc',
            'bg' => '#f0f8ff',
            'text' => '#1a1a1a',
        ],
        'green' => [
            'name' => 'Vert',
            'primary' => '#28a745',
            'bg' => '#f0fff4',
            'text' => '#1a1a1a',
        ],
        'purple' => [
            'name' => 'Violet',
            'primary' => '#6f42c1',
            'bg' => '#f8f4ff',
            'text' => '#1a1a1a',
        ],
    ];

    /**
     * Récupère le thème actuel
     */
    public static function getCurrentTheme(): string
    {
        return Session::get('theme', 'light');
    }

    /**
     * Définit le thème
     */
    public static function setTheme(string $theme): void
    {
        if (self::themeExists($theme)) {
            Session::set('theme', $theme);
        }
    }

    /**
     * Vérifie si un thème existe
     */
    public static function themeExists(string $theme): bool
    {
        return isset(self::THEMES[$theme]);
    }

    /**
     * Liste tous les thèmes disponibles
     */
    public static function getAllThemes(): array
    {
        return self::THEMES;
    }

    /**
     * Récupère les informations d'un thème
     */
    public static function getThemeInfo(string $theme): ?array
    {
        return self::THEMES[$theme] ?? null;
    }

    /**
     * Génère le CSS personnalisé pour le thème
     */
    public static function generateThemeCss(string $theme): string
    {
        $info = self::getThemeInfo($theme);
        if (!$info) {
            $info = self::THEMES['light'];
        }

        return "
        :root {
            --theme-primary: {$info['primary']};
            --theme-bg: {$info['bg']};
            --theme-text: {$info['text']};
        }
        body {
            background-color: var(--theme-bg);
            color: var(--theme-text);
        }
        .btn-primary {
            background-color: var(--theme-primary);
            border-color: var(--theme-primary);
        }
        .btn-primary:hover {
            background-color: " . self::darkenColor($info['primary']) . ";
            border-color: " . self::darkenColor($info['primary']) . ";
        }
        ";
    }

    /**
     * Assombrit une couleur hexadécimale
     */
    private static function darkenColor(string $hex, float $percent = 0.15): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r - ($r * $percent)));
        $g = max(0, min(255, $g - ($g * $percent)));
        $b = max(0, min(255, $b - ($b * $percent)));

        return '#' . str_pad(dechex((int)$r), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex((int)$g), 2, '0', STR_PAD_LEFT) .
               str_pad(dechex((int)$b), 2, '0', STR_PAD_LEFT);
    }
}

