<?php
/**
 * Utilitaires de manipulation du temps
 */

declare(strict_types=1);

namespace Helpers;

class TimeHelper
{
    /**
     * Convertit une heure HH:MM en minutes
     */
    public static function parseToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        return $hours * 60 + $minutes;
    }

    /**
     * Convertit des minutes en format HH:MM
     */
    public static function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';
        $minutes = abs($minutes);
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        return sprintf('%s%02d:%02d', $sign, $hours, $mins);
    }

    /**
     * Calcule la durée entre deux heures
     */
    public static function calculateDuration(string $startTime, string $endTime): int
    {
        $start = self::parseToMinutes($startTime);
        $end = self::parseToMinutes($endTime);
        
        // Gestion des périodes sur minuit (ex: 22:00 -> 02:00)
        if ($end < $start) {
            $end += 24 * 60;
        }
        
        return max(0, $end - $start);
    }

    /**
     * Formatte une date
     */
    public static function formatDate(string $date, string $format = 'd/m/Y'): string
    {
        try {
            $dateTime = new \DateTimeImmutable($date);
            return $dateTime->format($format);
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Obtient le début de la semaine (lundi)
     */
    public static function getWeekStart(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('monday this week');
    }

    /**
     * Obtient la fin de la semaine (dimanche)
     */
    public static function getWeekEnd(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('sunday this week');
    }

    /**
     * Obtient le début du mois
     */
    public static function getMonthStart(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('first day of this month');
    }

    /**
     * Obtient la fin du mois
     */
    public static function getMonthEnd(\DateTimeImmutable $date): \DateTimeImmutable
    {
        return $date->modify('last day of this month');
    }
}
