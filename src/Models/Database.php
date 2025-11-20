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
        // Table des utilisateurs
        self::$instance->exec('
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT "user" CHECK(role IN ("admin", "user")),
                is_active INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                last_login TEXT
            );
            
            CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
            CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
        ');

        // Migration : Ajouter user_id à entries si elle n'existe pas
        try {
            $result = self::$instance->query("PRAGMA table_info(entries)");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('user_id', $columns)) {
                self::$instance->exec('
                    ALTER TABLE entries ADD COLUMN user_id INTEGER;
                    CREATE INDEX IF NOT EXISTS idx_entries_user_id ON entries(user_id);
                ');
            }
        } catch (\Exception $e) {
            // Ignorer si la colonne existe déjà
        }

        // Table des entrées
        self::$instance->exec('
            CREATE TABLE IF NOT EXISTS entries (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                date TEXT NOT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                type TEXT NOT NULL CHECK(type IN ("work","break")),
                description TEXT DEFAULT "",
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE(user_id, date, start_time, end_time, type) ON CONFLICT IGNORE
            );
            
            CREATE INDEX IF NOT EXISTS idx_entries_date ON entries(date);
            CREATE INDEX IF NOT EXISTS idx_entries_type ON entries(type);
            CREATE INDEX IF NOT EXISTS idx_entries_datetime ON entries(date, start_time);
            CREATE INDEX IF NOT EXISTS idx_entries_user_id ON entries(user_id);
        ');

        // Créer un utilisateur admin par défaut si aucun utilisateur n'existe
        $stmt = self::$instance->query('SELECT COUNT(*) as count FROM users');
        $result = $stmt->fetch();
        
        if ((int)$result['count'] === 0) {
            $defaultPassword = password_hash('admin', PASSWORD_DEFAULT);
            $now = (new \DateTimeImmutable())->format('c');
            self::$instance->exec("
                INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at)
                VALUES ('admin', 'admin@timedesk.local', '{$defaultPassword}', 'admin', 1, '{$now}', '{$now}')
            ");
        }
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
