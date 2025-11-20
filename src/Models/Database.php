<?php
/**
 * Gestionnaire de base de données SQLite
 */

declare(strict_types=1);

namespace Models;

use PDO;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Récupère l'instance PDO (Singleton)
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new PDO('sqlite:' . DB_FILE);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            self::initSchema();
        }
        
        return self::$instance;
    }

    /**
     * Initialise le schéma de la base de données
     */
    private static function initSchema(): void
    {
        self::$instance->exec('
            CREATE TABLE IF NOT EXISTS entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("work","break")),
                description TEXT DEFAULT "",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE(date, start_time, end_time, type) ON CONFLICT IGNORE
            );
            
            CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(date);
            CREATE INDEX IF NOT EXISTS idx_entries_type ON entries(type);
            CREATE INDEX IF NOT EXISTS idx_entries_datetime ON entries(date, start_time);
        ');
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
}
