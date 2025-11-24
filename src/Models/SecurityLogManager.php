<?php
/**
 * Gestion des logs de sécurité (tentatives de connexion, resets)
 */

declare(strict_types=1);

namespace Models;

use PDO;
use DateTimeImmutable;

class SecurityLogManager
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Enregistre une tentative de connexion
     */
    public function recordLoginAttempt(string $username, string $ip, bool $success): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO login_attempts (username, ip_address, success, created_at)
            VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([
            $username,
            $ip,
            $success ? 1 : 0,
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Vérifie si le compte/IP doit être verrouillé
     */
    public function isLocked(string $username, string $ip): bool
    {
        $thresholdDate = (new DateTimeImmutable('-' . LOGIN_LOCK_WINDOW . ' seconds'))->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            SELECT COUNT(*) as failures
            FROM login_attempts
            WHERE success = 0
              AND created_at >= ?
              AND (username = ? OR ip_address = ?)
        ');
        $stmt->execute([$thresholdDate, $username, $ip]);
        $result = $stmt->fetch();

        return (int)($result['failures'] ?? 0) >= LOGIN_MAX_ATTEMPTS;
    }

    /**
     * Récupère les tentatives récentes
     */
    public function getRecentLoginAttempts(int $limit = 50): array
    {
        $stmt = $this->db->prepare('
            SELECT username, ip_address, success, created_at
            FROM login_attempts
            ORDER BY created_at DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Crée un jeton de réinitialisation de mot de passe
     */
    public function createPasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable('+' . PASSWORD_RESET_EXPIRY . ' seconds'))->format('Y-m-d H:i:s');
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('
            INSERT INTO password_resets (user_id, token_hash, expires_at, created_at)
            VALUES (?, ?, ?, ?)
        ');

        $stmt->execute([$userId, $tokenHash, $expiresAt, $now]);

        return $token;
    }

    /**
     * Vérifie un jeton de reset
     */
    public function findValidResetToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        $stmt = $this->db->prepare('
            SELECT * FROM password_resets
            WHERE token_hash = ?
              AND used_at IS NULL
              AND expires_at >= ?
            ORDER BY id DESC
            LIMIT 1
        ');
        $stmt->execute([$tokenHash, (new DateTimeImmutable())->format('Y-m-d H:i:s')]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Marque un jeton comme utilisé
     */
    public function markResetTokenUsed(int $resetId): void
    {
        $stmt = $this->db->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?');
        $stmt->execute([(new DateTimeImmutable())->format('Y-m-d H:i:s'), $resetId]);
    }

    /**
     * Liste les dernières demandes de reset
     */
    public function getRecentPasswordResets(int $limit = 50): array
    {
        $stmt = $this->db->prepare('
            SELECT pr.id, pr.user_id, pr.created_at, pr.expires_at, pr.used_at, u.username, u.email
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            ORDER BY pr.created_at DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

