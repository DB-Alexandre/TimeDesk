<?php
/**
 * Gestionnaire de base de données SQLite
 */

declare(strict_types=1);

namespace Models;

use Core\Migrator;
use PDO;
use RuntimeException;

class Database
{
    private static ?PDO $instance = null;
    private static bool $migrated = false;

    /**
     * Récupère l'instance PDO (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
            self::initSchema();
        }
        
        return self::$instance;
    }

    /**
     * Initialise le schéma de la base de données
     */
    private static function initSchema(): void
    {
        if (self::$migrated) {
            return;
        }

        $migrator = new Migrator(self::$instance, DB_DRIVER);
        $migrator->run();
        self::$migrated = true;
    }

    /**
     * Commence une transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Valide une transaction
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Annule une transaction
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Crée la connexion PDO en fonction du driver
     */
    private static function createConnection(): PDO
    {
        return match (DB_DRIVER) {
            'sqlite' => self::createSqliteConnection(),
            'mysql' => self::createMysqlConnection(),
            default => throw new RuntimeException('Driver de base de données non supporté: ' . DB_DRIVER),
        };
    }

    /**
     * Connexion SQLite
     */
    private static function createSqliteConnection(): PDO
    {
        $dbPath = self::resolveSqlitePath(DB_SQLITE_PATH);
        $directory = dirname($dbPath);

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    /**
     * Connexion MySQL
     */
    private static function createMysqlConnection(): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_DATABASE,
            DB_CHARSET
        );

        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec(sprintf('SET NAMES %s COLLATE %s', DB_CHARSET, DB_COLLATION));

        return $pdo;
    }

    /**
     * Résout le chemin SQLite (absolu ou relatif au projet)
     */
    private static function resolveSqlitePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:\\\\/', $path) || str_starts_with($path, '/')) {
            return $path;
        }

        return ROOT_PATH . '/' . ltrim($path, '/');
    }
}
