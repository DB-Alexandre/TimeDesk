<?php
/**
 * Gestionnaire des entrées de temps
 */

declare(strict_types=1);

namespace Models;

use PDO;
use Helpers\Validator;
use InvalidArgumentException;
use RuntimeException;
use DateTimeImmutable;

class EntryManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crée une nouvelle entrée
     */
    public function create(array $data): bool
    {
        $this->validateEntryData($data);
        $this->checkDailyLimit($data['date'], $data['user_id'] ?? null);

        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->db->prepare('
            INSERT INTO entries (user_id, date, start_time, end_time, type, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');

        return $stmt->execute([
            $data['user_id'] ?? null,
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['type'],
            $data['description'],
            $now,
            $now
        ]);
    }

    /**
     * Met à jour une entrée existante
     */
    public function update(int $id, array $data): bool
    {
        $this->validateEntryData($data);

        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->db->prepare('
            UPDATE entries 
            SET date = ?, start_time = ?, end_time = ?, type = ?, description = ?, updated_at = ?
            WHERE id = ?
        ');

        return $stmt->execute([
            $data['date'],
            $data['start_time'],
            $data['end_time'],
            $data['type'],
            $data['description'],
            $now,
            $id
        ]);
    }

    /**
     * Supprime une entrée
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM entries WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Récupère toutes les entrées (legacy)
     */
    public function getAll(?string $dateFrom = null, ?string $dateTo = null, ?int $userId = null): array
    {
        $filters = [];

        if ($dateFrom && Validator::date($dateFrom)) {
            $filters['from'] = $dateFrom;
        }

        if ($dateTo && Validator::date($dateTo)) {
            $filters['to'] = $dateTo;
        }

        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }

        return $this->export($filters);
    }

    /**
     * Recherche paginée avec filtres
     */
    public function search(array $filters, int $limit = 25, int $page = 1): array
    {
        $limit = max(10, min(100, $limit));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        [$whereSql, $params] = $this->buildWhereClause($filters);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM entries e $whereSql");
        $this->bindFilterParams($countStmt, $params);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $sql = "
            SELECT e.*, u.username 
            FROM entries e
            LEFT JOIN users u ON u.id = e.user_id
            $whereSql
            ORDER BY e.date DESC, e.start_time DESC, e.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $this->bindFilterParams($stmt, $params);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'entries' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * Exporte toutes les entrées correspondant aux filtres
     */
    public function export(array $filters): array
    {
        [$whereSql, $params] = $this->buildWhereClause($filters);
        $sql = "
            SELECT e.*, u.username 
            FROM entries e
            LEFT JOIN users u ON u.id = e.user_id
            $whereSql
            ORDER BY e.date DESC, e.start_time DESC, e.id DESC
        ";
        $stmt = $this->db->prepare($sql);
        $this->bindFilterParams($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Récupère une entrée par son ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM entries WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Vérifie si une entrée appartient à un utilisateur
     */
    public function belongsToUser(int $entryId, int $userId): bool
    {
        $entry = $this->findById($entryId);
        return $entry && (int)$entry['user_id'] === $userId;
    }

    /**
     * Récupère la dernière heure de fin pour une date
     */
    public function getLastEndTime(string $date, ?int $userId = null): ?string
    {
        if (!Validator::date($date)) {
            return null;
        }

        $where = 'date = ?';
        $params = [$date];

        if ($userId !== null) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }

        $stmt = $this->db->prepare("
            SELECT end_time 
            FROM entries 
            WHERE {$where}
            ORDER BY end_time DESC 
            LIMIT 1
        ");
        $stmt->execute($params);
        $result = $stmt->fetch();

        return $result['end_time'] ?? null;
    }

    /**
     * Valide les données d'une entrée
     */
    private function validateEntryData(array $data): void
    {
        if (!isset($data['date']) || !Validator::date($data['date'])) {
            throw new InvalidArgumentException('Date invalide');
        }

        if (!isset($data['start_time']) || !Validator::time($data['start_time'])) {
            throw new InvalidArgumentException('Heure de début invalide');
        }

        if (!isset($data['end_time']) || !Validator::time($data['end_time'])) {
            throw new InvalidArgumentException('Heure de fin invalide');
        }

        if (!isset($data['type']) || !Validator::type($data['type'])) {
            throw new InvalidArgumentException('Type invalide');
        }

        if (!isset($data['description']) || !Validator::description($data['description'])) {
            throw new InvalidArgumentException('Description trop longue (max ' . MAX_DESCRIPTION_LENGTH . ' caractères)');
        }
    }

    /**
     * Vérifie la limite quotidienne d'entrées
     */
    private function checkDailyLimit(string $date, ?int $userId = null): void
    {
        $where = 'date = ?';
        $params = [$date];

        if ($userId !== null) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM entries WHERE {$where}");
        $stmt->execute($params);
        $result = $stmt->fetch();

        if ($result['count'] >= MAX_ENTRIES_PER_DAY) {
            throw new RuntimeException('Limite d\'entrées quotidiennes atteinte (' . MAX_ENTRIES_PER_DAY . ' max)');
        }
    }

    /**
     * Compte le nombre total d'entrées
     */
    public function count(?int $userId = null): int
    {
        if ($userId !== null) {
            $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM entries WHERE user_id = ?');
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM entries');
        }
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Construit la clause WHERE en fonction des filtres
     */
    private function buildWhereClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'e.user_id = :user_id';
            $params['user_id'] = (int)$filters['user_id'];
        }

        if (!empty($filters['from']) && Validator::date($filters['from'])) {
            $where[] = 'e.date >= :from';
            $params['from'] = $filters['from'];
        }

        if (!empty($filters['to']) && Validator::date($filters['to'])) {
            $where[] = 'e.date <= :to';
            $params['to'] = $filters['to'];
        }

        if (!empty($filters['type']) && Validator::type($filters['type'])) {
            $where[] = 'e.type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(e.description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereSql, $params];
    }

    /**
     * Lie les paramètres nommés à une requête
     */
    private function bindFilterParams(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }
}
