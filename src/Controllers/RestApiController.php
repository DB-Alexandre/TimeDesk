<?php
/**
 * API REST complète pour TimeDesk
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Models\UserManager;
use Models\StatsCalculator;
use Helpers\Auth;
use Helpers\Validator;
use Helpers\Logger;
use Helpers\EntryFilters;
use Core\Session;
use DateTimeImmutable;

class RestApiController
{
    private EntryManager $entryManager;
    private UserManager $userManager;
    private StatsCalculator $statsCalculator;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->entryManager = new EntryManager($db);
        $this->userManager = new UserManager($db);
        $this->statsCalculator = new StatsCalculator($db);
    }

    /**
     * Routeur principal de l'API
     */
    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        
        // CORS headers
        $this->setCorsHeaders();
        
        // Gérer les requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Authentification
        if (!$this->authenticate()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $path = $_GET['path'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        $pathParts = explode('/', trim($path, '/'));

        try {
            $resource = $pathParts[0] ?? '';
            $id = $pathParts[1] ?? null;

            switch ($resource) {
                case 'entries':
                    $this->handleEntries($method, $id);
                    break;
                case 'users':
                    $this->handleUsers($method, $id);
                    break;
                case 'stats':
                    $this->handleStats($method, $pathParts);
                    break;
                case 'me':
                    $this->handleMe();
                    break;
                default:
                    $this->jsonResponse(['error' => 'Resource not found'], 404);
            }
        } catch (\Exception $e) {
            Logger::error('REST API error', [
                'error' => $e->getMessage(),
                'path' => $path,
                'method' => $method,
            ]);
            $this->jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Gère les entrées
     */
    private function handleEntries(string $method, ?string $id): void
    {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getEntry((int)$id);
                } else {
                    $this->listEntries();
                }
                break;
            case 'POST':
                $this->createEntry();
                break;
            case 'PUT':
            case 'PATCH':
                if ($id) {
                    $this->updateEntry((int)$id);
                } else {
                    $this->jsonResponse(['error' => 'Entry ID required'], 400);
                }
                break;
            case 'DELETE':
                if ($id) {
                    $this->deleteEntry((int)$id);
                } else {
                    $this->jsonResponse(['error' => 'Entry ID required'], 400);
                }
                break;
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }

    /**
     * Liste les entrées
     */
    private function listEntries(): void
    {
        $filters = EntryFilters::build($_GET, Auth::isAdmin(), Auth::getUserId());
        $entries = $this->entryManager->search($filters);
        
        $this->jsonResponse([
            'data' => $entries,
            'count' => count($entries),
        ]);
    }

    /**
     * Récupère une entrée
     */
    private function getEntry(int $id): void
    {
        $entry = $this->entryManager->findById($id);
        
        if (!$entry) {
            $this->jsonResponse(['error' => 'Entry not found'], 404);
            return;
        }

        if (!Auth::canAccess((int)$entry['user_id'])) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $this->jsonResponse(['data' => $entry]);
    }

    /**
     * Crée une entrée
     */
    private function createEntry(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid JSON'], 400);
            return;
        }

        $entryData = [
            'user_id' => Auth::getUserId(),
            'date' => Validator::clean($data['date'] ?? ''),
            'start_time' => Validator::clean($data['start_time'] ?? ''),
            'end_time' => Validator::clean($data['end_time'] ?? ''),
            'type' => $this->validateType($data['type'] ?? 'work'),
            'description' => Validator::clean($data['description'] ?? ''),
        ];

        $this->entryManager->create($entryData);
        
        Logger::userAction('entry_created_api', Auth::getUserId(), [
            'date' => $entryData['date'],
        ]);

        $this->jsonResponse(['data' => $entryData, 'message' => 'Entry created'], 201);
    }

    /**
     * Met à jour une entrée
     */
    private function updateEntry(int $id): void
    {
        $entry = $this->entryManager->findById($id);
        
        if (!$entry) {
            $this->jsonResponse(['error' => 'Entry not found'], 404);
            return;
        }

        if (!Auth::canAccess((int)$entry['user_id'])) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            $this->jsonResponse(['error' => 'Invalid JSON'], 400);
            return;
        }

        $updateData = [];
        if (isset($data['date'])) $updateData['date'] = Validator::clean($data['date']);
        if (isset($data['start_time'])) $updateData['start_time'] = Validator::clean($data['start_time']);
        if (isset($data['end_time'])) $updateData['end_time'] = Validator::clean($data['end_time']);
        if (isset($data['type'])) $updateData['type'] = $this->validateType($data['type']);
        if (isset($data['description'])) $updateData['description'] = Validator::clean($data['description']);

        $this->entryManager->update($id, $updateData);
        
        Logger::userAction('entry_updated_api', Auth::getUserId(), [
            'entry_id' => $id,
        ]);

        $this->jsonResponse(['data' => $this->entryManager->findById($id), 'message' => 'Entry updated']);
    }

    /**
     * Supprime une entrée
     */
    private function deleteEntry(int $id): void
    {
        $entry = $this->entryManager->findById($id);
        
        if (!$entry) {
            $this->jsonResponse(['error' => 'Entry not found'], 404);
            return;
        }

        if (!Auth::canAccess((int)$entry['user_id'])) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        $this->entryManager->delete($id);
        
        Logger::userAction('entry_deleted_api', Auth::getUserId(), [
            'entry_id' => $id,
        ]);

        $this->jsonResponse(['message' => 'Entry deleted']);
    }

    /**
     * Gère les utilisateurs (admin uniquement)
     */
    private function handleUsers(string $method, ?string $id): void
    {
        if (!Auth::isAdmin()) {
            $this->jsonResponse(['error' => 'Forbidden'], 403);
            return;
        }

        switch ($method) {
            case 'GET':
                if ($id) {
                    $user = $this->userManager->findById((int)$id);
                    $this->jsonResponse($user ? ['data' => $user] : ['error' => 'User not found'], $user ? 200 : 404);
                } else {
                    $this->jsonResponse(['data' => $this->userManager->getAll()]);
                }
                break;
            default:
                $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
    }

    /**
     * Gère les statistiques
     */
    private function handleStats(string $method, array $pathParts): void
    {
        if ($method !== 'GET') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        $period = $pathParts[1] ?? 'today';
        $userId = Auth::isAdmin() && isset($pathParts[2]) ? (int)$pathParts[2] : Auth::getUserId();
        $today = new DateTimeImmutable('today');

        $stats = match($period) {
            'today', 'daily' => $this->statsCalculator->getDailyStats($today, $userId),
            'week', 'weekly' => $this->statsCalculator->getWeeklyStats($today, $userId),
            'month', 'monthly' => $this->statsCalculator->getMonthlyStats($today, $userId),
            'year', 'yearly' => $this->statsCalculator->getYearlyStats($today, $userId),
            default => $this->statsCalculator->getDailyStats($today, $userId),
        };

        $this->jsonResponse(['data' => $stats]);
    }

    /**
     * Informations sur l'utilisateur connecté
     */
    private function handleMe(): void
    {
        $user = Auth::getUser();
        $this->jsonResponse(['data' => $user]);
    }

    /**
     * Authentification API (token ou session)
     */
    private function authenticate(): bool
    {
        // Vérifier session existante
        if (Auth::isAuthenticated()) {
            return true;
        }

        // Vérifier token API dans header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            // TODO: Implémenter validation token API
            // Pour l'instant, on accepte seulement les sessions
        }

        return false;
    }

    /**
     * Valide le type d'entrée
     */
    private function validateType(string $type): string
    {
        return in_array($type, ['work', 'break', 'course'], true) ? $type : 'work';
    }

    /**
     * Envoie une réponse JSON
     */
    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Définit les headers CORS
     */
    private function setCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
    }
}

