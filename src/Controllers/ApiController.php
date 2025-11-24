<?php
/**
 * Contrôleur API pour les requêtes AJAX
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Helpers\Auth;
use Helpers\Logger;
use Helpers\EntryFilters;
use Helpers\PdfReport;

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
        $userId = Auth::isAdmin() ? null : Auth::getUserId();
        $lastEnd = $this->entryManager->getLastEndTime($date, $userId);
        
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

        $filters = EntryFilters::build($_GET, Auth::isAdmin(), Auth::getUserId());
        $entries = $this->entryManager->export($filters);
        
        Logger::userAction('csv_export', Auth::getUserId());
        
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

    /**
     * Export Excel (HTML)
     */
    public function exportXlsx(): void
    {
        Auth::check();

        $filters = EntryFilters::build($_GET, Auth::isAdmin(), Auth::getUserId());
        $entries = $this->entryManager->export($filters);

        Logger::userAction('xlsx_export', Auth::getUserId());

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="timedesk_report_' . date('Y-m-d') . '.xls"');

        echo "<table border=\"1\">";
        echo "<thead><tr><th>Date</th><th>Début</th><th>Fin</th><th>Type</th><th>Description</th></tr></thead><tbody>";
        foreach ($entries as $entry) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($entry['date'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($entry['start_time'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($entry['end_time'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($entry['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '<td>' . htmlspecialchars($entry['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        exit;
    }

    /**
     * Export PDF simple
     */
    public function exportPdf(): void
    {
        Auth::check();

        $filters = EntryFilters::build($_GET, Auth::isAdmin(), Auth::getUserId());
        $entries = $this->entryManager->export($filters);

        $builder = new PdfReport();
        $builder->addLine('Rapport TimeDesk - ' . date('d/m/Y'));
        $builder->addLine('Total entrées: ' . count($entries));
        $builder->addLine(str_repeat('-', 60));

        $maxLines = 90;
        $lineCount = 0;

        foreach ($entries as $entry) {
            $label = sprintf(
                '%s %s-%s [%s] %s',
                $entry['date'],
                $entry['start_time'],
                $entry['end_time'],
                strtoupper($entry['type']),
                mb_strimwidth($entry['description'] ?? '', 0, 60, '...')
            );
            $builder->addLine($label);
            $lineCount++;
            if ($lineCount >= $maxLines) {
                $builder->addLine('... (liste tronquée, utilisez CSV/Excel pour le détail complet)');
                break;
            }
        }

        Logger::userAction('pdf_export', Auth::getUserId());
        $builder->output('timedesk_report_' . date('Y-m-d') . '.pdf');
    }
}
