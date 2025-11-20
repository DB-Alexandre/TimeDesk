<?php
/**
 * Contrôleur API pour les requêtes AJAX
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Helpers\Auth;

class ApiController
{
    private EntryManager $entryManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->entryManager = new EntryManager($db);
    }

    /**
     * Retourne la dernière heure de fin pour une date
     */
    public function lastEndTime(): void
    {
        Auth::check();

        header('Content-Type: application/json; charset=utf-8');
        
        $date = $_GET['date'] ?? '';
        $lastEnd = $this->entryManager->getLastEndTime($date);
        
        echo json_encode([
            'lastEnd' => $lastEnd
        ], JSON_UNESCAPED_UNICODE);
        
        exit;
    }

    /**
     * Exporte les données en CSV
     */
    public function exportCsv(): void
    {
        Auth::check();

        $entries = $this->entryManager->getAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="timedesk_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, ['Date', 'Début', 'Fin', 'Type', 'Description'], ';');
        
        // Données
        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry['date'],
                $entry['start_time'],
                $entry['end_time'],
                $entry['type'],
                $entry['description']
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}
