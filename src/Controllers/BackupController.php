<?php
/**
 * Contrôleur de gestion des backups
 */

declare(strict_types=1);

namespace Controllers;

use Helpers\Auth;
use Helpers\BackupManager;
use Helpers\Logger;
use Core\Session;
use Core\Router;

class BackupController
{
    /**
     * Affiche la liste des backups
     */
    public function list(): void
    {
        Auth::check();
        Auth::requireAdmin();

        $backups = BackupManager::listBackups();

        $this->render('pages/backup-list', [
            'backups' => $backups,
            'flash' => Session::getFlash(),
        ]);
    }

    /**
     * Crée un nouveau backup
     */
    public function create(): void
    {
        Auth::check();
        Auth::requireAdmin();

        try {
            $backupFile = BackupManager::createBackup();
            Session::setFlash('success', 'Sauvegarde créée avec succès');
            Logger::userAction('backup_created', Auth::getUserId());
        } catch (\Exception $e) {
            Session::setFlash('error', 'Erreur lors de la création de la sauvegarde: ' . $e->getMessage());
            Logger::error('Backup creation failed', [
                'user_id' => Auth::getUserId(),
                'error' => $e->getMessage(),
            ]);
        }

        Router::redirect('?action=backup-list');
    }

    /**
     * Télécharge un backup
     */
    public function download(): void
    {
        Auth::check();
        Auth::requireAdmin();

        $file = $_GET['file'] ?? '';
        if (empty($file)) {
            Session::setFlash('error', 'Fichier non spécifié');
            Router::redirect('?action=backup-list');
            return;
        }

        $backupPath = DATA_PATH . '/backups/' . basename($file);
        if (!file_exists($backupPath) || !is_readable($backupPath)) {
            Session::setFlash('error', 'Fichier introuvable');
            Router::redirect('?action=backup-list');
            return;
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backupPath) . '"');
        header('Content-Length: ' . filesize($backupPath));
        readfile($backupPath);
        exit;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require VIEWS_PATH . '/layouts/main.php';
    }
}

