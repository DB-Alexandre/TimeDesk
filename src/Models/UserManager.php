<?php
/**
 * Gestionnaire des utilisateurs
 */

declare(strict_types=1);

namespace Models;

use PDO;
use Helpers\Validator;
use Helpers\PasswordPolicy;
use InvalidArgumentException;
use RuntimeException;
use DateTimeImmutable;

class UserManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Crée un nouvel utilisateur
     */
    public function create(array $data): int
    {
        $this->validateUserData($data, true);

        // Vérifier si le username existe déjà
        if ($this->usernameExists($data['username'])) {
            throw new InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé');
        }

        // Vérifier si l'email existe déjà (si fourni)
        if (!empty($data['email']) && $this->emailExists($data['email'])) {
            throw new InvalidArgumentException('Cet email est déjà utilisé');
        }

        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->db->prepare('
            INSERT INTO users (username, email, password_hash, role, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->execute([
            $data['username'],
            $data['email'] ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'user',
            $data['is_active'] ?? 1,
            $now,
            $now
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour un utilisateur
     */
    public function update(int $id, array $data): bool
    {
        $this->validateUserData($data, false);

        // Vérifier si l'utilisateur existe
        $user = $this->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('Utilisateur introuvable');
        }

        // Vérifier si le username existe déjà (pour un autre utilisateur)
        if (isset($data['username']) && $data['username'] !== $user['username']) {
            if ($this->usernameExists($data['username'])) {
                throw new InvalidArgumentException('Ce nom d\'utilisateur est déjà utilisé');
            }
        }

        // Vérifier si l'email existe déjà (pour un autre utilisateur)
        if (isset($data['email']) && !empty($data['email']) && $data['email'] !== $user['email']) {
            if ($this->emailExists($data['email'])) {
                throw new InvalidArgumentException('Cet email est déjà utilisé');
            }
        }

        $fields = [];
        $params = [];

        if (isset($data['username'])) {
            $fields[] = 'username = ?';
            $params[] = $data['username'];
        }

        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $data['email'] ?: null;
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = $data['is_active'] ? 1 : 0;
        }

        if (empty($fields)) {
            return true;
        }

        $fields[] = 'updated_at = ?';
        $params[] = (new DateTimeImmutable())->format('c');
        $params[] = $id;

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Supprime un utilisateur
     */
    public function delete(int $id): bool
    {
        // Empêcher la suppression du dernier admin
        $user = $this->findById($id);
        if ($user && $user['role'] === 'admin') {
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM users WHERE role = "admin" AND is_active = 1');
            $result = $stmt->fetch();
            if ((int)$result['count'] <= 1) {
                throw new RuntimeException('Impossible de supprimer le dernier administrateur');
            }
        }

        $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    }

    /**
     * Récupère un utilisateur par son ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result) {
            unset($result['password_hash']); // Ne pas exposer le hash
        }
        
        return $result ?: null;
    }

    /**
     * Récupère un utilisateur par son username
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function getAll(): array
    {
        $stmt = $this->db->query('SELECT id, username, email, role, is_active, created_at, last_login FROM users ORDER BY username');
        $results = $stmt->fetchAll();
        
        // Ne pas exposer les mots de passe
        foreach ($results as &$result) {
            unset($result['password_hash']);
        }
        
        return $results;
    }

    /**
     * Vérifie les identifiants de connexion
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return null;
        }

        if (!$user['is_active']) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Mettre à jour la dernière connexion
        $this->updateLastLogin($user['id']);

        // Ne pas retourner le hash du mot de passe
        unset($user['password_hash']);
        
        return $user;
    }

    /**
     * Met à jour la date de dernière connexion
     */
    public function updateLastLogin(int $userId): void
    {
        $now = (new DateTimeImmutable())->format('c');
        $stmt = $this->db->prepare('UPDATE users SET last_login = ? WHERE id = ?');
        $stmt->execute([$now, $userId]);
    }

    /**
     * Met à jour uniquement le mot de passe
     */
    public function updatePassword(int $userId, string $password): void
    {
        PasswordPolicy::validate($password);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([
            password_hash($password, PASSWORD_DEFAULT),
            (new DateTimeImmutable())->format('c'),
            $userId,
        ]);
    }

    /**
     * Vérifie si un username existe
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE username = ?';
        $params = [$username];
        
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int)$result['count'] > 0;
    }

    /**
     * Vérifie si un email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) as count FROM users WHERE email = ?';
        $params = [$email];
        
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return (int)$result['count'] > 0;
    }

    /**
     * Valide les données d'un utilisateur
     */
    private function validateUserData(array $data, bool $requirePassword = false): void
    {
        if ($requirePassword || array_key_exists('password', $data)) {
            if (empty($data['password'])) {
                throw new InvalidArgumentException('Le mot de passe est requis');
            }
            PasswordPolicy::validate($data['password']);
        }

        if (isset($data['username'])) {
            if (empty($data['username'])) {
                throw new InvalidArgumentException('Le nom d\'utilisateur est requis');
            }
            if (strlen($data['username']) < 3) {
                throw new InvalidArgumentException('Le nom d\'utilisateur doit contenir au moins 3 caractères');
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                throw new InvalidArgumentException('Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores');
            }
        }

        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email invalide');
            }
        }

        if (isset($data['role']) && !in_array($data['role'], ['admin', 'user'], true)) {
            throw new InvalidArgumentException('Rôle invalide');
        }
    }
}

