<?php
/**
 * Contrôleur de gestion des utilisateurs
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\UserManager;
use Models\EntryManager;
use Models\StatsCalculator;
use Helpers\Validator;
use Helpers\Auth;
use Helpers\Logger;
use Core\Session;
use DateTimeImmutable;
use Exception;

class UserController
{
    private UserManager $userManager;
    private EntryManager $entryManager;
    private StatsCalculator $statsCalculator;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->userManager = new UserManager($db);
        $this->entryManager = new EntryManager($db);
        $this->statsCalculator = new StatsCalculator($db);
    }

    /**
     * Affiche la liste des utilisateurs (admin uniquement)
     */
    public function index(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        $users = $this->userManager->getAll();
        $flash = Session::getFlash();

        $this->render('pages/users', [
            'users' => $users,
            'flash' => $flash,
        ]);
    }

    /**
     * Affiche le formulaire de création d'utilisateur
     */
    public function createForm(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        $flash = Session::getFlash();
        $this->render('pages/user-form', [
            'user' => null,
            'flash' => $flash,
        ]);
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        try {
            $data = [
                'username' => Validator::clean($_POST['username'] ?? ''),
                'email' => Validator::clean($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'role' => ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user',
                'is_active' => isset($_POST['is_active']),
            ];

            $userId = $this->userManager->create($data);
            Session::setFlash('success', 'Utilisateur créé avec succès');
            
            Logger::userAction('user_created', Auth::getUserId(), [
                'created_user_id' => $userId,
                'username' => $data['username'],
            ]);
            
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to create user', [
                'user_id' => Auth::getUserId(),
                'error' => $e->getMessage(),
            ]);
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users&create=1');
            exit;
        }
    }

    /**
     * Affiche le formulaire d'édition d'utilisateur
     */
    public function editForm(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = $this->userManager->findById($id);

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        $flash = Session::getFlash();
        $this->render('pages/user-form', [
            'user' => $user,
            'flash' => $flash,
        ]);
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            
            $data = [
                'username' => Validator::clean($_POST['username'] ?? ''),
                'email' => Validator::clean($_POST['email'] ?? ''),
                'role' => ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user',
                'is_active' => isset($_POST['is_active']),
            ];

            // Mettre à jour le mot de passe seulement s'il est fourni
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }

            $this->userManager->update($id, $data);
            Session::setFlash('success', 'Utilisateur mis à jour avec succès');
            
            Logger::userAction('user_updated', Auth::getUserId(), [
                'updated_user_id' => $id,
                'username' => $data['username'],
            ]);
            
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to update user', [
                'user_id' => Auth::getUserId(),
                'updated_user_id' => $id ?? null,
                'error' => $e->getMessage(),
            ]);
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users&edit=' . ($id ?? 0));
            exit;
        }
    }

    /**
     * Supprime un utilisateur
     */
    public function delete(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            
            // Empêcher la suppression de soi-même
            if ($id === Auth::getUserId()) {
                throw new Exception('Vous ne pouvez pas supprimer votre propre compte');
            }
            
            $user = $this->userManager->findById($id);
            $username = $user['username'] ?? 'unknown';
            
            $this->userManager->delete($id);
            Session::setFlash('success', 'Utilisateur supprimé avec succès');
            
            Logger::userAction('user_deleted', Auth::getUserId(), [
                'deleted_user_id' => $id,
                'username' => $username,
            ]);
            
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        } catch (Exception $e) {
            Session::setFlash('error', $e->getMessage());
            Logger::error('Failed to delete user', [
                'user_id' => Auth::getUserId(),
                'deleted_user_id' => $id ?? null,
                'error' => $e->getMessage(),
            ]);
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }
    }

    /**
     * Affiche les statistiques détaillées d'un utilisateur
     */
    public function userStats(): void
    {
        Auth::check();
        
        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            header('Location: ' . $_SERVER['SCRIPT_NAME']);
            exit;
        }

        $userId = (int)($_GET['id'] ?? 0);
        $user = $this->userManager->findById($userId);

        if (!$user) {
            Session::setFlash('error', 'Utilisateur introuvable');
            header('Location: ' . $_SERVER['SCRIPT_NAME'] . '?action=users');
            exit;
        }

        $today = new DateTimeImmutable('today');
        
        // Statistiques de base
        $dailyStats = $this->statsCalculator->getDailyStats($today, $userId);
        $weeklyStats = $this->statsCalculator->getWeeklyStats($today, $userId);
        $monthlyStats = $this->statsCalculator->getMonthlyStats($today, $userId);
        $yearlyStats = $this->statsCalculator->getYearlyStats($today, $userId);

        // Statistiques supplémentaires
        $totalEntries = $this->entryManager->count($userId);
        $totalWorkEntries = $this->getWorkEntriesCount($userId);
        $totalBreakEntries = $this->getBreakEntriesCount($userId);
        
        // Première et dernière entrée
        $firstEntry = $this->getFirstEntry($userId);
        $lastEntry = $this->getLastEntry($userId);
        
        // Statistiques par mois (12 derniers mois)
        $monthlyBreakdown = $this->getMonthlyBreakdown($userId);
        
        // Statistiques par semaine (12 dernières semaines)
        $weeklyBreakdown = $this->getWeeklyBreakdown($userId);
        
        // Moyennes
        $avgDaily = $this->statsCalculator->getDailyAverage(
            $firstEntry ? $firstEntry['date'] : $today->format('Y-m-d'),
            $today->format('Y-m-d'),
            $userId
        );
        
        // Temps total travaillé (toutes périodes)
        $allTimeStats = $this->statsCalculator->getStats(
            $firstEntry ? $firstEntry['date'] : $today->format('Y-m-d'),
            $today->format('Y-m-d'),
            $userId
        );

        // Entrées récentes
        $recentEntries = $this->entryManager->getAll(null, null, $userId);
        $recentEntries = array_slice($recentEntries, 0, 10); // 10 dernières

        // Log de consultation des stats
        Logger::userAction('user_stats_viewed', Auth::getUserId(), [
            'viewed_user_id' => $userId,
            'username' => $user['username'],
        ]);

        $data = [
            'user' => $user,
            'today' => $today,
            'dailyStats' => $dailyStats,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'yearlyStats' => $yearlyStats,
            'totalEntries' => $totalEntries,
            'totalWorkEntries' => $totalWorkEntries,
            'totalBreakEntries' => $totalBreakEntries,
            'firstEntry' => $firstEntry,
            'lastEntry' => $lastEntry,
            'monthlyBreakdown' => $monthlyBreakdown,
            'weeklyBreakdown' => $weeklyBreakdown,
            'avgDaily' => $avgDaily,
            'allTimeStats' => $allTimeStats,
            'recentEntries' => $recentEntries,
            'flash' => Session::getFlash(),
        ];

        $this->render('pages/user-stats', $data);
    }

    /**
     * Compte les entrées de travail
     */
    private function getWorkEntriesCount(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM entries WHERE user_id = ? AND type = "work"');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Compte les entrées de pause
     */
    private function getBreakEntriesCount(int $userId): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT COUNT(*) as count FROM entries WHERE user_id = ? AND type = "break"');
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Récupère la première entrée
     */
    private function getFirstEntry(int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM entries WHERE user_id = ? ORDER BY date ASC, start_time ASC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère la dernière entrée
     */
    private function getLastEntry(int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM entries WHERE user_id = ? ORDER BY date DESC, start_time DESC LIMIT 1');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère les statistiques par mois (12 derniers mois)
     */
    private function getMonthlyBreakdown(int $userId): array
    {
        $today = new DateTimeImmutable('today');
        $breakdown = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $today->modify("-{$i} months");
            $monthStart = new DateTimeImmutable($date->format('Y-m-01'));
            $monthEnd = new DateTimeImmutable($date->format('Y-m-t'));
            
            $stats = $this->statsCalculator->getStats(
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
                $userId
            );
            
            $breakdown[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'work_minutes' => $stats['work_minutes'],
                'break_minutes' => $stats['break_minutes'],
                'net_minutes' => $stats['net_minutes'],
                'target_minutes' => (int)round(MONTHLY_TARGET_HOURS * 60),
            ];
        }
        
        return $breakdown;
    }

    /**
     * Récupère les statistiques par semaine (12 dernières semaines)
     */
    private function getWeeklyBreakdown(int $userId): array
    {
        $today = new DateTimeImmutable('today');
        $breakdown = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = $today->modify("-{$i} weeks");
            $weekStart = $date->modify('monday this week');
            $weekEnd = $weekStart->modify('+6 days');
            
            $stats = $this->statsCalculator->getStats(
                $weekStart->format('Y-m-d'),
                $weekEnd->format('Y-m-d'),
                $userId
            );
            
            $breakdown[] = [
                'week' => $weekStart->format('Y-W'),
                'label' => $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m'),
                'work_minutes' => $stats['work_minutes'],
                'break_minutes' => $stats['break_minutes'],
                'net_minutes' => $stats['net_minutes'],
                'target_minutes' => (int)round(CONTRACT_WEEKLY_HOURS * 60),
            ];
        }
        
        return $breakdown;
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

