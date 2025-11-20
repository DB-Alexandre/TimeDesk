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
        $this->checkDailyLimit($data['date']);

        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->db->prepare('
            INSERT INTO entries (date, start_time, end_time, type, description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        return $stmt->execute([
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
     * Récupère toutes les entrées avec filtres optionnels
     */
    public function getAll(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = [];
        $params = [];

        if ($dateFrom && Validator::date($dateFrom)) {
            $where[] = 'date >= ?';
            $params[] = $dateFrom;
        }

        if ($dateTo && Validator::date($dateTo)) {
            $where[] = 'date <= ?';
            $params[] = $dateTo;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = $this->db->prepare("
            SELECT * FROM entries 
            $whereSql 
            ORDER BY date DESC, start_time DESC, id DESC
        ");
        $stmt->execute($params);

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
     * Récupère la dernière heure de fin pour une date
     */
    public function getLastEndTime(string $date): ?string
    {
        if (!Validator::date($date)) {
            return null;
        }

        $stmt = $this->db->prepare('
            SELECT end_time 
            FROM entries 
            WHERE date = ? 
            ORDER BY end_time DESC 
            LIMIT 1
        ');
        $stmt->execute([$date]);
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
    private function checkDailyLimit(string $date): void
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) as count FROM entries WHERE date = ?');
        $stmt->execute([$date]);
        $result = $stmt->fetch();

        if ($result['count'] >= MAX_ENTRIES_PER_DAY) {
            throw new RuntimeException('Limite d\'entrées quotidiennes atteinte (' . MAX_ENTRIES_PER_DAY . ' max)');
        }
    }

    /**
     * Compte le nombre total d'entrées
     */
    public function count(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM entries');
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}
