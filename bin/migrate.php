#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../config/config.php';

use Models\Database;

echo "Exécution des migrations pour le driver " . DB_DRIVER . PHP_EOL;

try {
    Database::getInstance(); // Le migrateur est exécuté lors de l'initialisation
    echo "Migrations appliquées avec succès." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur lors des migrations: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

