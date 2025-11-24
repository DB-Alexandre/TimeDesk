<?php
/**
 * Simple .env loader
 */

declare(strict_types=1);

if (!function_exists('app_load_env')) {
    /**
     * Charge un fichier .env et peuple $_ENV / $_SERVER
     */
    function app_load_env(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $value = trim($value, "\"'");
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('env')) {
    /**
     * Récupère une variable d'environnement avec valeur par défaut
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        return $value !== false && $value !== null ? $value : $default;
    }
}

if (!function_exists('env_bool')) {
    /**
     * Convertit une variable d'environnement en booléen
     */
    function env_bool(string $key, bool $default = false): bool
    {
        $value = env($key);
        if ($value === null) {
            return $default;
        }

        $value = strtolower((string)$value);
        return in_array($value, ['1', 'true', 'yes', 'on'], true) ? true
            : (in_array($value, ['0', 'false', 'no', 'off'], true) ? false : $default);
    }
}

if (!function_exists('env_array')) {
    /**
     * Convertit une variable d'environnement en tableau (séparateur virgule)
     */
    function env_array(string $key, array $default = []): array
    {
        $value = env($key);
        if ($value === null || trim($value) === '') {
            return $default;
        }

        $parts = array_filter(array_map('trim', explode(',', (string)$value)));
        return $parts ?: $default;
    }
}

