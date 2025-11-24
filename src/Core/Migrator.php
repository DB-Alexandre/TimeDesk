<?php
/**
 * Gestionnaire de migrations simple (SQLite / MySQL)
 */

declare(strict_types=1);

namespace Core;

use PDO;
use RuntimeException;

class Migrator
{
    private const MIGRATIONS_DIR = ROOT_PATH . '/database/migrations';

    public function __construct(
        private PDO $pdo,
        private string $driver
    ) {
        $this->driver = strtolower($this->driver);
    }

    /**
     * Exécute toutes les migrations non appliquées
     */
    public function run(): void
    {
        $this->ensureMigrationsTable();

        $applied = $this->getAppliedMigrations();

        foreach ($this->getMigrationFiles() as $file) {
            $migration = require $file;

            if (!is_array($migration) || empty($migration['id'])) {
                throw new RuntimeException("Migration invalide: {$file}");
            }

            if (in_array($migration['id'], $applied, true)) {
                continue;
            }

            $statements = $migration['statements'][$this->driver] ?? null;

            if (!$statements) {
                throw new RuntimeException("Migration {$migration['id']} indisponible pour le driver {$this->driver}");
            }

            $this->pdo->beginTransaction();

            try {
                foreach ($statements as $sql) {
                    $this->pdo->exec($sql);
                }

                if (isset($migration['seed']) && is_callable($migration['seed'])) {
                    $seed = $migration['seed'];
                    $seed($this->pdo, $this->driver);
                }

                $this->markAsApplied($migration['id'], $migration['description'] ?? null);
                $this->pdo->commit();
            } catch (\Throwable $e) {
                $this->pdo->rollBack();
                throw $e;
            }
        }
    }

    /**
     * Table des migrations
     */
    private function ensureMigrationsTable(): void
    {
        $sql = match ($this->driver) {
            'sqlite' => '
                CREATE TABLE IF NOT EXISTS migrations (
                    id TEXT PRIMARY KEY,
                    description TEXT,
                    executed_at TEXT NOT NULL
                )',
            'mysql' => '
                CREATE TABLE IF NOT EXISTS migrations (
                    id VARCHAR(191) PRIMARY KEY,
                    description VARCHAR(255) NULL,
                    executed_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=' . DB_CHARSET . ' COLLATE=' . DB_COLLATION,
            default => throw new RuntimeException('Driver non supporté: ' . $this->driver),
        };

        $this->pdo->exec($sql);
    }

    /**
     * Renvoie la liste des migrations déjà appliquées
     */
    private function getAppliedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT id FROM migrations ORDER BY id ASC');
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Enregistre l'exécution d'une migration
     */
    private function markAsApplied(string $id, ?string $description = null): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO migrations (id, description, executed_at) VALUES (:id, :description, :executed_at)');
        $stmt->execute([
            ':id' => $id,
            ':description' => $description,
            ':executed_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Retourne les fichiers de migrations triés
     */
    private function getMigrationFiles(): array
    {
        if (!is_dir(self::MIGRATIONS_DIR)) {
            return [];
        }

        $files = glob(self::MIGRATIONS_DIR . '/*.php');
        sort($files);

        return $files;
    }
}

