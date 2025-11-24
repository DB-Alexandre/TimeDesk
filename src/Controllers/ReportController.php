<?php
/**
 * Contrôleur de rapports personnalisables
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Models\StatsCalculator;
use Helpers\Auth;
use Helpers\Validator;
use Helpers\Logger;
use Helpers\EntryFilters;
use Helpers\PdfReport;
use Helpers\TimeHelper;
use Core\Session;
use Core\Router;
use DateTimeImmutable;

class ReportController
{
    private EntryManager $entryManager;
    private StatsCalculator $statsCalculator;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->entryManager = new EntryManager($db);
        $this->statsCalculator = new StatsCalculator($db);
    }

    /**
     * Affiche le formulaire de création de rapport
     */
    public function createForm(): void
    {
        Auth::check();

        $db = Database::getInstance();
        $this->render('pages/report-builder', [
            'flash' => Session::getFlash(),
            'usersList' => Auth::isAdmin() ? (new \Models\UserManager($db))->getAll() : [],
        ]);
    }

    /**
     * Génère un rapport personnalisé
     */
    public function generate(): void
    {
        Auth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('?action=report-create');
            return;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            Router::redirect('?action=report-create');
            return;
        }

        $format = $_POST['format'] ?? 'pdf';
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $userId = Auth::isAdmin() && isset($_POST['user_id']) ? (int)$_POST['user_id'] : Auth::getUserId();
        $includeStats = isset($_POST['include_stats']);
        $includeChart = isset($_POST['include_chart']);
        $title = Validator::clean($_POST['title'] ?? 'Rapport TimeDesk');

        $filters = [
            'date_from' => $dateFrom ?: null,
            'date_to' => $dateTo ?: null,
            'user_id' => $userId,
        ];

        $entries = $this->entryManager->export($filters);

        if ($format === 'pdf') {
            $this->generatePdfReport($title, $entries, $filters, $includeStats, $includeChart);
        } elseif ($format === 'csv') {
            $this->generateCsvReport($title, $entries);
        } else {
            Session::setFlash('error', 'Format non supporté');
            Router::redirect('?action=report-create');
        }

        Logger::userAction('custom_report_generated', Auth::getUserId(), [
            'format' => $format,
            'entries_count' => count($entries),
        ]);
    }

    /**
     * Génère un rapport PDF personnalisé moderne
     */
    private function generatePdfReport(string $title, array $entries, array $filters, bool $includeStats, bool $includeChart): void
    {
        $builder = new PdfReport();
        
        // Configuration du rapport
        $builder->setTitle($title);
        $builder->setSubtitle('Rapport d\'activité TimeDesk');
        
        // Métadonnées
        if ($filters['date_from'] || $filters['date_to']) {
            $period = ($filters['date_from'] ?? 'Début') . ' → ' . ($filters['date_to'] ?? 'Fin');
            $builder->addMetadata('Période', $period);
        }
        $builder->addMetadata('Total d\'entrées', (string)count($entries));
        $builder->addMetadata('Date de génération', date('d/m/Y à H:i'));
        
        // Statistiques calculées depuis les entrées filtrées
        if ($includeStats && count($entries) > 0) {
            $totalWork = 0;
            $totalBreak = 0;
            $totalCourse = 0;
            
            foreach ($entries as $entry) {
                $duration = TimeHelper::calculateDuration($entry['start_time'], $entry['end_time']);
                
                if ($entry['type'] === 'break') {
                    $totalBreak += $duration;
                } elseif ($entry['type'] === 'course') {
                    $totalCourse += $duration;
                } else {
                    $totalWork += $duration;
                }
            }
            
            $workHours = round($totalWork / 60, 2);
            $breakHours = round($totalBreak / 60, 2);
            $courseHours = round($totalCourse / 60, 2);
            $netHours = round(($totalWork + $totalCourse) / 60, 2);
            
            $builder->addStats([
                'Temps de travail' => $workHours . ' h',
                'Temps de pause' => $breakHours . ' h',
                'Temps net' => $netHours . ' h',
            ]);
        }
        
        // Tableau des entrées
        $tableData = [];
        $tableData[] = ['Date', 'Début', 'Fin', 'Type', 'Description'];
        
        foreach (array_slice($entries, 0, 50) as $entry) {
            $typeLabels = [
                'work' => 'Travail',
                'break' => 'Pause',
                'course' => 'Cours',
            ];
            
            $tableData[] = [
                $entry['date'],
                $entry['start_time'],
                $entry['end_time'],
                $typeLabels[$entry['type']] ?? $entry['type'],
                mb_strimwidth($entry['description'] ?? '-', 0, 40, '...'),
            ];
        }
        
        $builder->addSection('Détail des entrées', $tableData, 'table');
        
        if (count($entries) > 50) {
            $builder->addSection('Note', [[
                'Information' => (count($entries) - 50) . ' entrées supplémentaires non affichées. Utilisez l\'export CSV pour voir toutes les données.'
            ]], 'table');
        }
        
        $builder->output('report_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Génère un rapport CSV personnalisé
     */
    private function generateCsvReport(string $title, array $entries): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $title . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Date', 'Début', 'Fin', 'Type', 'Description'], ';');

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

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require VIEWS_PATH . '/layouts/main.php';
    }
}

