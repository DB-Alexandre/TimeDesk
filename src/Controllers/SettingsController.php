<?php
/**
 * Contrôleur des paramètres (thèmes, langues)
 */

declare(strict_types=1);

namespace Controllers;

use Helpers\Auth;
use Helpers\ThemeManager;
use Helpers\LanguageManager;
use Core\Session;
use Core\Router;

class SettingsController
{
    /**
     * Change le thème
     */
    public function setTheme(): void
    {
        Auth::check();

        $theme = $_GET['theme'] ?? 'light';
        if (ThemeManager::themeExists($theme)) {
            ThemeManager::setTheme($theme);
            Session::setFlash('success', 'Thème mis à jour');
        }

        // Rediriger vers l'accueil (plus simple et plus sûr)
        Router::redirect('?action=index');
    }

    /**
     * Change la langue
     */
    public function setLanguage(): void
    {
        Auth::check();

        $lang = $_GET['lang'] ?? 'fr';
        if (LanguageManager::languageExists($lang)) {
            LanguageManager::setLanguage($lang);
            Session::setFlash('success', 'Langue mise à jour');
        }

        // Rediriger vers l'accueil (plus simple et plus sûr)
        Router::redirect('?action=index');
    }
}

