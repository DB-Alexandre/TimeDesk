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
use Helpers\Logger;
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

        // Récupération des entrées (filtrées par utilisateur si non-admin)
        $userId = Auth::isAdmin() ? null : Auth::getUserId();
        $entries = $this->entryManager->getAll(
            $filterFrom ?: null,
            $filterTo ?: null,
            $userId
        );

        // Calcul des statistiques (filtrées par utilisateur si non-admin)
        $today = new DateTimeImmutable('today');
        $statsUserId = Auth::isAdmin() ? null : Auth::getUserId();
        $dailyStats = $this->statsCalculator->getDailyStats($today, $statsUserId);
        $weeklyStats = $this->statsCalculator->getWeeklyStats($today, $statsUserId);
        $monthlyStats = $this->statsCalculator->getMonthlyStats($today, $statsUserId);
        $yearlyStats = $this->statsCalculator->getYearlyStats($today, $statsUserId);

        // Heure de début par défaut (dernière heure de fin)
        $defaultStartTime = $this->entryManager->getLastEndTime(
            $today->format('Y-m-d'),
            Auth::isAdmin() ? null : Auth::getUserId()
        );

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
                'user_id' => Auth::getUserId(),
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => ($_POST['type'] ?? 'work') === 'break' ? 'break' : 'work',
                'description' => Validator::clean($_POST['description'] ?? ''),
            ];

            $this->entryManager->create($data);
            Session::setFlash('success', 'Entrée ajoutée avec succès');
            
            Logger::userAction('entry_created', Auth::getUserId(), [
                'date' => $data['date'],
                'type' => $data['type'],
            ]);
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to create entry', [
                'user_id' => Auth::getUserId(),
                'error' => $e->getMessage(),
            ]);
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
            
            // Vérifier que l'utilisateur peut modifier cette entrée
            $entry = $this->entryManager->findById($id);
            if (!$entry) {
                throw new Exception('Entrée introuvable');
            }
            
            if (!Auth::canAccess((int)$entry['user_id'])) {
                throw new Exception('Vous n\'avez pas la permission de modifier cette entrée');
            }
            
            $data = [
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => ($_POST['type'] ?? 'work') === 'break' ? 'break' : 'work',
                'description' => Validator::clean($_POST['description'] ?? ''),
            ];

            $this->entryManager->update($id, $data);
            Session::setFlash('success', 'Entrée mise à jour avec succès');
            
            Logger::userAction('entry_updated', Auth::getUserId(), [
                'entry_id' => $id,
                'date' => $data['date'],
            ]);
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to update entry', [
                'user_id' => Auth::getUserId(),
                'entry_id' => $id ?? null,
                'error' => $e->getMessage(),
            ]);
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
            
            // Vérifier que l'utilisateur peut supprimer cette entrée
            $entry = $this->entryManager->findById($id);
            if (!$entry) {
                throw new Exception('Entrée introuvable');
            }
            
            if (!Auth::canAccess((int)$entry['user_id'])) {
                throw new Exception('Vous n\'avez pas la permission de supprimer cette entrée');
            }
            
            $this->entryManager->delete($id);
            Session::setFlash('success', 'Entrée supprimée avec succès');
            
            Logger::userAction('entry_deleted', Auth::getUserId(), [
                'entry_id' => $id,
            ]);
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to delete entry', [
                'user_id' => Auth::getUserId(),
                'entry_id' => $id ?? null,
                'error' => $e->getMessage(),
            ]);
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
