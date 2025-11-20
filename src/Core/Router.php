<?php
/**
 * Routeur simple - VERSION CORRIGÉE
 * Gère automatiquement les chemins avec ou sans /public/
 */

declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    /**
     * Ajoute une route GET
     */
    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    /**
     * Ajoute une route POST
     */
    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    /**
     * Exécute le routeur
     */
    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Retirer le chemin de base si nécessaire
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        if ($basePath !== '/') {
            $path = substr($path, strlen($basePath));
        }
        $path = $path ?: '/';

        // Recherche de la route
        if (isset($this->routes[$method][$path])) {
            call_user_func($this->routes[$method][$path]);
        } else {
            http_response_code(404);
            echo '404 - Page non trouvée';
        }
    }

    /**
     * Redirige vers une URL - VERSION CORRIGÉE
     */
    public static function redirect(string $path): never
    {
        // Si c'est une query string (?action=...)
        if (strpos($path, '?') === 0) {
            // Utiliser l'URL courante
            $currentScript = $_SERVER['SCRIPT_NAME'];
            $url = $currentScript . $path;
        } else {
            // Chemin absolu
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            
            // Si on est dans /public/, on remonte d'un niveau
            if (basename($scriptDir) === 'public') {
                $baseDir = dirname($scriptDir);
            } else {
                $baseDir = $scriptDir;
            }
            
            // Construire l'URL
            if ($path[0] === '/') {
                $url = $baseDir . $path;
            } else {
                $url = $baseDir . '/' . $path;
            }
        }
        
        // Nettoyer l'URL
        $url = str_replace('//', '/', $url);
        if ($url !== '/') {
            $url = rtrim($url, '/');
        }
        
        header('Location: ' . $url);
        exit;
    }

    /**
     * Obtient l'URL de base de l'application
     */
    public static function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Si on est dans /public/, on remonte d'un niveau
        if (basename($scriptDir) === 'public') {
            $baseDir = dirname($scriptDir);
        } else {
            $baseDir = $scriptDir;
        }
        
        return $protocol . '://' . $host . $baseDir;
    }

    /**
     * Obtient le chemin du script courant
     */
    public static function getCurrentPath(): string
    {
        return $_SERVER['SCRIPT_NAME'];
    }
}
