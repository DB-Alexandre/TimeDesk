<?php
/**
 * Système de logs pour TimeDesk
 */

declare(strict_types=1);

namespace Helpers;

use DateTimeImmutable;
use Exception;

class Logger
{
    private const LOG_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
    ];

    private const LOG_FILE_PREFIX = 'timedesk_';
    private const LOG_FILE_EXTENSION = '.log';

    /**
     * Enregistre un log
     */
    public static function log(
        string $level,
        string $message,
        array $context = []
    ): void {
        $logLevel = strtoupper($level);
        
        if (!isset(self::LOG_LEVELS[$logLevel])) {
            $logLevel = 'INFO';
        }

        // En production, ignorer les logs DEBUG
        if (!DEBUG && $logLevel === 'DEBUG') {
            return;
        }

        $timestamp = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logEntry = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $logLevel,
            $message,
            $contextStr
        );

        $logFile = self::getLogFile();
        
        // Rotation des logs si nécessaire
        $maxSize = max(1, LOG_MAX_SIZE_MB) * 1024 * 1024;
        if (file_exists($logFile) && filesize($logFile) > $maxSize) {
            self::rotateLog($logFile);
        }

        // Création du dossier logs s'il n'existe pas
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Écriture du log
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        // Nettoyage périodique
        self::cleanupOldLogs();
    }

    /**
     * Log de niveau DEBUG
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }

    /**
     * Log de niveau INFO
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    /**
     * Log de niveau WARNING
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Log de niveau ERROR
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    /**
     * Log de niveau CRITICAL
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }

    /**
     * Log une action utilisateur
     */
    public static function userAction(
        string $action,
        ?int $userId = null,
        array $details = []
    ): void {
        $context = array_merge([
            'action' => $action,
            'user_id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ], $details);

        self::info("User action: {$action}", $context);

        if (self::shouldDispatchAlert($action)) {
            Notifier::send('Alerte TimeDesk - ' . $action, $context);
        }
    }

    /**
     * Obtient le chemin du fichier de log
     */
    private static function getLogFile(): string
    {
        $date = date('Y-m-d');
        return LOGS_PATH . '/' . self::LOG_FILE_PREFIX . $date . self::LOG_FILE_EXTENSION;
    }

    /**
     * Effectue la rotation du fichier de log
     */
    private static function rotateLog(string $logFile): void
    {
        $backupFile = $logFile . '.' . time() . '.bak';
        @rename($logFile, $backupFile);
        
        // Supprimer les anciens backups selon la rétention
        $files = glob(dirname($logFile) . '/' . self::LOG_FILE_PREFIX . '*.bak');
        $retention = max(1, LOG_RETENTION_DAYS);
        if (count($files) > $retention) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            foreach (array_slice($files, 0, -$retention) as $oldFile) {
                @unlink($oldFile);
            }
        }
    }

    /**
     * Nettoie les fichiers de log trop anciens
     */
    private static function cleanupOldLogs(): void
    {
        $retention = max(1, LOG_RETENTION_DAYS);
        $threshold = (new \DateTimeImmutable("-{$retention} days"))->format('Y-m-d');
        $files = glob(LOGS_PATH . '/' . self::LOG_FILE_PREFIX . '*.log');

        foreach ($files as $file) {
            $datePart = substr(basename($file), strlen(self::LOG_FILE_PREFIX), 10);
            if ($datePart < $threshold) {
                @unlink($file);
            }
        }
    }

    private static function shouldDispatchAlert(string $action): bool
    {
        if (!ALERT_ENABLED) {
            return false;
        }

        if (empty(ALERT_EVENTS)) {
            return false;
        }

        return in_array($action, ALERT_EVENTS, true);
    }

    /**
     * Lit les logs récents
     */
    public static function getRecentLogs(int $days = 7, int $limit = 100): array
    {
        $logs = [];
        $endDate = new DateTimeImmutable();
        
        for ($i = 0; $i < $days; $i++) {
            $date = $endDate->modify("-{$i} days")->format('Y-m-d');
            $logFile = LOGS_PATH . '/' . self::LOG_FILE_PREFIX . $date . self::LOG_FILE_EXTENSION;
            
            if (file_exists($logFile)) {
                $fileLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($fileLines) {
                    foreach (array_reverse($fileLines) as $line) {
                        $logs[] = $line;
                        if (count($logs) >= $limit) {
                            break 2;
                        }
                    }
                }
            }
        }
        
        return array_slice($logs, 0, $limit);
    }
}

