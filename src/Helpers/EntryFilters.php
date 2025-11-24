<?php
/**
 * Normalisation des filtres pour les entrées
 */

declare(strict_types=1);

namespace Helpers;

class EntryFilters
{
    public static function sanitizeDate(?string $value): ?string
    {
        $value = $value ? trim($value) : null;
        return ($value && Validator::date($value)) ? $value : null;
    }

    public static function sanitizeType(?string $value): ?string
    {
        $value = $value ? trim(strtolower($value)) : null;
        return in_array($value, ['work', 'break'], true) ? $value : null;
    }

    public static function sanitizeSearch(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;
        return $value !== '' ? $value : null;
    }

    public static function sanitizeUser(?string $value): ?int
    {
        $value = $value !== null ? trim($value) : null;
        return ($value !== null && ctype_digit($value)) ? (int)$value : null;
    }

    public static function sanitizePage(array $source): int
    {
        $page = isset($source['page']) ? (int)$source['page'] : 1;
        return max(1, $page);
    }

    public static function sanitizePerPage(array $source, int $default = 25): int
    {
        $perPage = isset($source['perPage']) ? (int)$source['perPage'] : $default;
        return max(10, min(100, $perPage));
    }

    /**
     * Construit le tableau de filtres prêts pour EntryManager
     */
    public static function build(array $source, bool $isAdmin, ?int $currentUserId): array
    {
        $filters = [];

        if ($from = self::sanitizeDate($source['from'] ?? null)) {
            $filters['from'] = $from;
        }

        if ($to = self::sanitizeDate($source['to'] ?? null)) {
            $filters['to'] = $to;
        }

        if ($type = self::sanitizeType($source['type'] ?? null)) {
            $filters['type'] = $type;
        }

        if ($search = self::sanitizeSearch($source['search'] ?? null)) {
            $filters['search'] = $search;
        }

        if ($isAdmin) {
            $userId = self::sanitizeUser($source['user'] ?? null);
            if ($userId !== null) {
                $filters['user_id'] = $userId;
            }
        } elseif ($currentUserId !== null) {
            $filters['user_id'] = $currentUserId;
        }

        return $filters;
    }
}

