<?php
/**
 * Règles de mots de passe
 */

declare(strict_types=1);

namespace Helpers;

use InvalidArgumentException;

class PasswordPolicy
{
    /**
     * Valide un mot de passe selon la configuration
     */
    public static function validate(string $password): void
    {
        $errors = [];

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $errors[] = 'doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères';
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'doit contenir une lettre majuscule';
        }

        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'doit contenir une lettre minuscule';
        }

        if (PASSWORD_REQUIRE_DIGIT && !preg_match('/\d/', $password)) {
            $errors[] = 'doit contenir un chiffre';
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[\W_]/', $password)) {
            $errors[] = 'doit contenir un caractère spécial';
        }

        if (!empty($errors)) {
            throw new InvalidArgumentException('Le mot de passe ' . implode(', ', $errors));
        }
    }
}

