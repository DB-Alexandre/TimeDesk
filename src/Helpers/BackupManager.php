<?php
/**
 * Gestionnaire de backup automatique
 */

declare(strict_types=1);

namespace Helpers;

use Models\Database;
use DateTimeImmutable;
use ZipArchive;

class BackupManager
{
    private const BACKUP_DIR = DATA_PATH . '/backups';
    private const MAX_BACKUPS = 10;

    /**
     * Crée un backup de la base de données
     */
    public static function createBackup(): string
    {
        if (!is_dir(self::BACKUP_DIR)) {
            @mkdir(self::BACKUP_DIR, 0755, true);
        }

        $db = Database::getInstance();
        $dbFile = defined('DB_SQLITE_PATH') ? DB_SQLITE_PATH : (DATA_PATH . '/timesheet.sqlite');

        // Pour SQLite, copier directement le fichier
        if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite' && file_exists($dbFile)) {
            $backupFile = self::BACKUP_DIR . '/backup_' . date('Y-m-d_His') . '.sqlite';
            @copy($dbFile, $backupFile);
            
            // Compresser
            $zipFile = $backupFile . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                $zip->addFile($backupFile, 'timesheet.sqlite');
                $zip->close();
                @unlink($backupFile);
                $backupFile = $zipFile;
            }

            self::cleanOldBackups();
            Logger::info('Backup created', ['file' => basename($backupFile)]);
            return $backupFile;
        }

        // Pour MySQL, exporter en SQL
        if (defined('DB_DRIVER') && DB_DRIVER === 'mysql') {
            $backupFile = self::BACKUP_DIR . '/backup_' . date('Y-m-d_His') . '.sql';
            $sql = self::exportMysqlDatabase();
            
            file_put_contents($backupFile, $sql);
            
            // Compresser
            $zipFile = $backupFile . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFile, ZipArchive::CREATE) === true) {
                $zip->addFile($backupFile, 'backup.sql');
                $zip->close();
                @unlink($backupFile);
                $backupFile = $zipFile;
            }

            self::cleanOldBackups();
            Logger::info('Backup created', ['file' => basename($backupFile)]);
            return $backupFile;
        }

        throw new \RuntimeException('Backup non supporté pour ce type de base de données');
    }

    /**
     * Exporte une base MySQL en SQL
     */
    private static function exportMysqlDatabase(): string
    {
        $db = Database::getInstance();
        $sql = "-- TimeDesk Backup\n";
        $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

        // Récupérer toutes les tables
        $tables = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $sql .= "\n-- Table: $table\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createTable = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= $createTable['Create Table'] . ";\n\n";

            // Données
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            if (count($rows) > 0) {
                $sql .= "INSERT INTO `$table` VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $rowValues = [];
                    foreach ($row as $value) {
                        $rowValues[] = $value === null ? 'NULL' : $db->quote($value);
                    }
                    $values[] = '(' . implode(',', $rowValues) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }

        return $sql;
    }

    /**
     * Nettoie les anciens backups
     */
    private static function cleanOldBackups(): void
    {
        $files = glob(self::BACKUP_DIR . '/backup_*.zip') ?: [];
        $files = array_merge($files, glob(self::BACKUP_DIR . '/backup_*.sqlite') ?: []);

        if (count($files) <= self::MAX_BACKUPS) {
            return;
        }

        // Trier par date de modification (plus ancien en premier)
        usort($files, fn($a, $b) => filemtime($a) - filemtime($b));

        // Supprimer les plus anciens
        $toDelete = array_slice($files, 0, count($files) - self::MAX_BACKUPS);
        foreach ($toDelete as $file) {
            @unlink($file);
        }
    }

    /**
     * Liste les backups disponibles
     */
    public static function listBackups(): array
    {
        if (!is_dir(self::BACKUP_DIR)) {
            return [];
        }

        $files = glob(self::BACKUP_DIR . '/backup_*.zip') ?: [];
        $files = array_merge($files, glob(self::BACKUP_DIR . '/backup_*.sqlite') ?: []);

        $backups = [];
        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        // Trier par date (plus récent en premier)
        usort($backups, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $backups;
    }
}

