<?php
/**
 * Routeur simple
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
     * Redirige vers une URL
     */
    public static function redirect(string $path): never
    {
        $basePath = dirname($_SERVER['SCRIPT_NAME']);
        $url = $basePath . ($basePath !== '/' ? '' : '') . $path;
        header('Location: ' . $url);
        exit;
    }
}
