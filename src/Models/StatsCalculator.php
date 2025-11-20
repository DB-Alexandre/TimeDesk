<?php
/**
 * Calculateur de statistiques
 */

declare(strict_types=1);

namespace Models;

use PDO;
use Helpers\TimeHelper;
use DateTimeImmutable;

class StatsCalculator
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Calcule les statistiques pour une période
     */
    public function getStats(string $dateFrom, string $dateTo, ?int $userId = null): array
    {
        $where = 'date BETWEEN ? AND ?';
        $params = [$dateFrom, $dateTo];
        
        if ($userId !== null) {
            $where .= ' AND user_id = ?';
            $params[] = $userId;
        }
        
        $stmt = $this->db->prepare("
            SELECT date, start_time, end_time, type 
            FROM entries 
            WHERE {$where}
        ");
        $stmt->execute($params);

        $totalWork = 0;
        $totalBreak = 0;
        $byDay = [];

        foreach ($stmt->fetchAll() as $row) {
            $duration = TimeHelper::calculateDuration($row['start_time'], $row['end_time']);
            
            if ($row['type'] === 'break') {
                $totalBreak += $duration;
            } else {
                $totalWork += $duration;
                $byDay[$row['date']] = ($byDay[$row['date']] ?? 0) + $duration;
            }
        }

        return [
            'work_minutes' => $totalWork,
            'break_minutes' => $totalBreak,
            'net_minutes' => $totalWork,
            'by_day' => $byDay,
        ];
    }

    /**
     * Statistiques journalières
     */
    public function getDailyStats(DateTimeImmutable $date, ?int $userId = null): array
    {
        $dateStr = $date->format('Y-m-d');
        $stats = $this->getStats($dateStr, $dateStr, $userId);
        $targetMinutes = (int)round(CONTRACT_WEEKLY_HOURS * 60 / 5);
        
        return $this->enrichStats($stats, $targetMinutes);
    }

    /**
     * Statistiques hebdomadaires
     */
    public function getWeeklyStats(DateTimeImmutable $today, ?int $userId = null): array
    {
        $weekStart = TimeHelper::getWeekStart($today);
        $weekEnd = TimeHelper::getWeekEnd($today);
        $stats = $this->getStats($weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), $userId);
        $targetMinutes = (int)round(CONTRACT_WEEKLY_HOURS * 60);
        
        return array_merge(
            $this->enrichStats($stats, $targetMinutes),
            [
                'start_date' => $weekStart,
                'end_date' => $weekEnd
            ]
        );
    }

    /**
     * Statistiques mensuelles
     */
    public function getMonthlyStats(DateTimeImmutable $today, ?int $userId = null): array
    {
        $monthStart = TimeHelper::getMonthStart($today);
        $monthEnd = TimeHelper::getMonthEnd($today);
        $stats = $this->getStats($monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'), $userId);
        $targetMinutes = (int)round(MONTHLY_TARGET_HOURS * 60);
        
        return array_merge(
            $this->enrichStats($stats, $targetMinutes),
            [
                'start_date' => $monthStart,
                'end_date' => $monthEnd
            ]
        );
    }

    /**
     * Statistiques annuelles
     */
    public function getYearlyStats(DateTimeImmutable $today, ?int $userId = null): array
    {
        $yearStart = new DateTimeImmutable($today->format('Y-01-01'));
        $yearEnd = new DateTimeImmutable($today->format('Y-12-31'));
        $stats = $this->getStats($yearStart->format('Y-m-d'), $yearEnd->format('Y-m-d'), $userId);
        $targetMinutes = (int)round(MONTHLY_TARGET_HOURS * 12 * 60);
        
        return array_merge(
            $this->enrichStats($stats, $targetMinutes),
            [
                'start_date' => $yearStart,
                'end_date' => $yearEnd
            ]
        );
    }

    /**
     * Enrichit les statistiques avec des calculs supplémentaires
     */
    private function enrichStats(array $stats, int $targetMinutes): array
    {
        $delta = $stats['net_minutes'] - $targetMinutes;
        $percentage = $targetMinutes > 0 
            ? min(100, round($stats['net_minutes'] / $targetMinutes * 100)) 
            : 0;

        return array_merge($stats, [
            'target_minutes' => $targetMinutes,
            'delta_minutes' => $delta,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Récupère le temps travaillé par jour sur une période
     */
    public function getWorkByDay(string $dateFrom, string $dateTo, ?int $userId = null): array
    {
        $stats = $this->getStats($dateFrom, $dateTo, $userId);
        return $stats['by_day'];
    }

    /**
     * Calcule la moyenne journalière sur une période
     */
    public function getDailyAverage(string $dateFrom, string $dateTo, ?int $userId = null): float
    {
        $stats = $this->getStats($dateFrom, $dateTo, $userId);
        $days = count($stats['by_day']);
        
        return $days > 0 ? $stats['net_minutes'] / $days : 0;
    }
}
