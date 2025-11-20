<?php
/**
 * Contrôleur des entrées de temps - VERSION CORRIGÉE
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Models\StatsCalculator;
use Helpers\Validator;
use Helpers\Auth;
use Core\Session;
use Core\Router;
use DateTimeImmutable;
use Exception;

class EntryController
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
     * Affiche le tableau de bord
     */
    public function index(): void
    {
        Auth::check();

        // Récupération des filtres
        $filterFrom = $_GET['from'] ?? '';
        $filterTo = $_GET['to'] ?? '';

        // Récupération des entrées
        $entries = $this->entryManager->getAll(
            $filterFrom ?: null,
            $filterTo ?: null
        );

        // Calcul des statistiques
        $today = new DateTimeImmutable('today');
        $dailyStats = $this->statsCalculator->getDailyStats($today);
        $weeklyStats = $this->statsCalculator->getWeeklyStats($today);
        $monthlyStats = $this->statsCalculator->getMonthlyStats($today);
        $yearlyStats = $this->statsCalculator->getYearlyStats($today);

        // Heure de début par défaut (dernière heure de fin)
        $defaultStartTime = $this->entryManager->getLastEndTime($today->format('Y-m-d'));

        // Variables pour la vue
        $data = [
            'entries' => $entries,
            'today' => $today,
            'dailyStats' => $dailyStats,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'yearlyStats' => $yearlyStats,
            'defaultStartTime' => $defaultStartTime,
            'filterFrom' => $filterFrom,
            'filterTo' => $filterTo,
            'flash' => Session::getFlash(),
        ];

        $this->render('pages/dashboard', $data);
    }

    /**
     * Crée une nouvelle entrée - VERSION CORRIGÉE
     */
    public function create(): void
    {
        Auth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        try {
            $data = [
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => ($_POST['type'] ?? 'work') === 'break' ? 'break' : 'work',
                'description' => Validator::clean($_POST['description'] ?? ''),
            ];

            $this->entryManager->create($data);
            Session::setFlash('success', 'Entrée ajoutée avec succès');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }

    /**
     * Met à jour une entrée existante - VERSION CORRIGÉE
     */
    public function update(): void
    {
        Auth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            
            $data = [
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => ($_POST['type'] ?? 'work') === 'break' ? 'break' : 'work',
                'description' => Validator::clean($_POST['description'] ?? ''),
            ];

            $this->entryManager->update($id, $data);
            Session::setFlash('success', 'Entrée mise à jour avec succès');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }

    /**
     * Supprime une entrée - VERSION CORRIGÉE
     */
    public function delete(): void
    {
        Auth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $this->entryManager->delete($id);
            Session::setFlash('success', 'Entrée supprimée avec succès');
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
        }

        header('Location: ' . $_SERVER['SCRIPT_NAME']);
        exit;
    }

    /**
     * Rend une vue
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);
        require VIEWS_PATH . '/layouts/main.php';
    }
}
