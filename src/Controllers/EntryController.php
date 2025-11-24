<?php
/**
 * Contrôleur des entrées de temps - VERSION CORRIGÉE
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Models\StatsCalculator;
use Models\UserManager;
use Helpers\Validator;
use Helpers\Auth;
use Helpers\Logger;
use Helpers\TimeHelper;
use Helpers\EntryFilters;
use Core\Session;
use Core\Router;
use DateTimeImmutable;
use DateInterval;
use Exception;

class EntryController
{
    private EntryManager $entryManager;
    private StatsCalculator $statsCalculator;
    private UserManager $userManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->entryManager = new EntryManager($db);
        $this->statsCalculator = new StatsCalculator($db);
        $this->userManager = new UserManager($db);
    }

    /**
     * Affiche le tableau de bord
     */
    public function index(): void
    {
        Auth::check();

        $isAdmin = Auth::isAdmin();
        $currentUserId = Auth::getUserId();

        // Filtres
        $filterFrom = EntryFilters::sanitizeDate($_GET['from'] ?? null);
        $filterTo = EntryFilters::sanitizeDate($_GET['to'] ?? null);
        $filterType = EntryFilters::sanitizeType($_GET['type'] ?? null);
        $filterSearch = EntryFilters::sanitizeSearch($_GET['search'] ?? null);
        $filterUser = $isAdmin ? EntryFilters::sanitizeUser($_GET['user'] ?? null) : null;
        $page = EntryFilters::sanitizePage($_GET);
        $perPage = EntryFilters::sanitizePerPage($_GET);

        $filters = [];
        if ($filterFrom) { $filters['from'] = $filterFrom; }
        if ($filterTo) { $filters['to'] = $filterTo; }
        if ($filterType) { $filters['type'] = $filterType; }
        if ($filterSearch) { $filters['search'] = $filterSearch; }

        if ($isAdmin) {
            if ($filterUser !== null) {
                $filters['user_id'] = $filterUser;
            }
        } else {
            $filters['user_id'] = $currentUserId;
        }

        $searchResult = $this->entryManager->search($filters, $perPage, $page);
        $entries = $searchResult['entries'];
        $totalEntries = $searchResult['total'];
        $totalPages = max(1, (int)ceil($totalEntries / $perPage));
        $userId = $isAdmin ? null : $currentUserId;
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
        $filterArray = $this->filterArray([
            'from' => $filterFrom,
            'to' => $filterTo,
            'type' => $filterType,
            'search' => $filterSearch,
            'user' => $filterUser,
        ]);

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
            'filterType' => $filterType,
            'filterSearch' => $filterSearch,
            'filterUser' => $filterUser,
            'perPage' => $perPage,
            'page' => $page,
            'totalEntries' => $totalEntries,
            'totalPages' => $totalPages,
            'usersList' => $isAdmin ? $this->userManager->getAll() : [],
            'filterQuery' => http_build_query($filterArray),
            'filterQueryArray' => $filterArray,
            'flash' => Session::getFlash(),
        ];

        $this->render('pages/dashboard', $data);
    }

    /**
     * Vue calendrier
     */
    public function calendar(): void
    {
        Auth::check();

        $isAdmin = Auth::isAdmin();
        $currentUserId = Auth::getUserId();

        $monthParam = $_GET['month'] ?? (new DateTimeImmutable('first day of this month'))->format('Y-m');
        try {
            $currentMonth = new DateTimeImmutable($monthParam . '-01');
        } catch (\Exception) {
            $currentMonth = new DateTimeImmutable('first day of this month');
        }

        $selectedUser = $isAdmin ? EntryFilters::sanitizeUser($_GET['user'] ?? null) : $currentUserId;

        $monthStart = $currentMonth->modify('first day of this month');
        $monthEnd = $currentMonth->modify('last day of this month');

        $entries = $this->entryManager->getAll(
            $monthStart->format('Y-m-d'),
            $monthEnd->format('Y-m-d'),
            $selectedUser
        );

        $entriesByDay = [];
        foreach ($entries as $entry) {
            $entriesByDay[$entry['date']][] = $entry;
        }

        $calendarStart = $monthStart->modify('monday this week');
        $calendarEnd = $monthEnd->modify('sunday this week');
        $period = new \DatePeriod($calendarStart, new DateInterval('P1D'), $calendarEnd->modify('+1 day'));

        $weeks = [];
        $currentWeek = [];

        foreach ($period as $date) {
            $dayString = $date->format('Y-m-d');
            $dayEntries = $entriesByDay[$dayString] ?? [];
            $workMinutes = 0;
            $breakMinutes = 0;

            foreach ($dayEntries as $entry) {
                $duration = TimeHelper::calculateDuration($entry['start_time'], $entry['end_time']);
                if ($entry['type'] === 'break') {
                    $breakMinutes += $duration;
                } else {
                    $workMinutes += $duration;
                }
            }

            $currentWeek[] = [
                'date' => $date,
                'day' => (int)$date->format('d'),
                'isCurrentMonth' => $date->format('m') === $monthStart->format('m'),
                'isToday' => $dayString === (new DateTimeImmutable('today'))->format('Y-m-d'),
                'entries' => $dayEntries,
                'work_minutes' => $workMinutes,
                'break_minutes' => $breakMinutes,
            ];

            if (count($currentWeek) === 7) {
                $weeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        $prevMonthParam = $monthStart->modify('-1 month')->format('Y-m');
        $nextMonthParam = $monthStart->modify('+1 month')->format('Y-m');

        $this->render('pages/calendar', [
            'weeks' => $weeks,
            'currentMonth' => $monthStart,
            'selectedUser' => $selectedUser,
            'usersList' => $isAdmin ? $this->userManager->getAll() : [],
            'isAdmin' => $isAdmin,
            'prevMonth' => $prevMonthParam,
            'nextMonth' => $nextMonthParam,
            'flash' => Session::getFlash(),
        ]);
    }

    private function filterArray(array $filters): array
    {
        $query = [];
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }
        return $query;
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
            $type = $_POST['type'] ?? 'work';
            if (!in_array($type, ['work', 'break', 'course'], true)) {
                $type = 'work';
            }
            
            $data = [
                'user_id' => Auth::getUserId(),
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => $type,
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
            
            $type = $_POST['type'] ?? 'work';
            if (!in_array($type, ['work', 'break', 'course'], true)) {
                $type = 'work';
            }
            
            $data = [
                'date' => Validator::clean($_POST['date'] ?? ''),
                'start_time' => Validator::clean($_POST['start_time'] ?? ''),
                'end_time' => Validator::clean($_POST['end_time'] ?? ''),
                'type' => $type,
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
