<?php
/**
 * Script CLI pour créer des backups automatiques
 * Usage: php bin/backup.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

use Helpers\BackupManager;
use Helpers\Logger;

try {
    echo "Création d'une sauvegarde...\n";
    $backupFile = BackupManager::createBackup();
    echo "✓ Sauvegarde créée: " . basename($backupFile) . "\n";
    Logger::info('Automatic backup created', ['file' => basename($backupFile)]);
    exit(0);
} catch (\Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    Logger::error('Automatic backup failed', ['error' => $e->getMessage()]);
    exit(1);
}

