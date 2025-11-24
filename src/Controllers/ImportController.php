<?php
/**
 * Contrôleur d'import de données
 */

declare(strict_types=1);

namespace Controllers;

use Models\Database;
use Models\EntryManager;
use Helpers\Auth;
use Helpers\Validator;
use Helpers\Logger;
use Core\Session;
use Core\Router;
use DateTimeImmutable;

class ImportController
{
    private EntryManager $entryManager;

    public function __construct()
    {
        $db = Database::getInstance();
        $this->entryManager = new EntryManager($db);
    }

    /**
     * Affiche le formulaire d'import
     */
    public function form(): void
    {
        Auth::check();

        $this->render('pages/import', [
            'flash' => Session::getFlash(),
        ]);
    }

    /**
     * Traite l'import
     */
    public function process(): void
    {
        Auth::check();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Router::redirect('?action=import');
            return;
        }

        if (!Validator::csrf($_POST['csrf'] ?? '')) {
            Session::setFlash('error', 'Token CSRF invalide');
            Router::redirect('?action=import');
            return;
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Session::setFlash('error', 'Erreur lors de l\'upload du fichier');
            Router::redirect('?action=import');
            return;
        }

        $file = $_FILES['file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        try {
            $imported = 0;
            $errors = [];

            if ($extension === 'csv') {
                $imported = $this->importCsv($file['tmp_name'], $errors);
            } elseif (in_array($extension, ['xls', 'xlsx'], true)) {
                Session::setFlash('error', 'Import Excel non encore implémenté (utilisez CSV)');
                Router::redirect('?action=import');
                return;
            } else {
                Session::setFlash('error', 'Format de fichier non supporté (CSV uniquement)');
                Router::redirect('?action=import');
                return;
            }

            if ($imported > 0) {
                Session::setFlash('success', "$imported entrée(s) importée(s) avec succès");
                Logger::userAction('data_imported', Auth::getUserId(), [
                    'count' => $imported,
                ]);
            } else {
                Session::setFlash('warning', 'Aucune entrée importée. ' . implode(', ', $errors));
            }

            Router::redirect('/');
        } catch (\Exception $e) {
            Session::setFlash('error', 'Erreur lors de l\'import: ' . $e->getMessage());
            Logger::error('Import failed', [
                'user_id' => Auth::getUserId(),
                'error' => $e->getMessage(),
            ]);
            Router::redirect('?action=import');
        }
    }

    /**
     * Importe un fichier CSV
     */
    private function importCsv(string $filePath, array &$errors): int
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier');
        }

        // Lire l'en-tête
        $header = fgetcsv($handle, 0, ';');
        if (!$header) {
            fclose($handle);
            return 0;
        }

        // Normaliser les colonnes (insensible à la casse)
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $normalized = strtolower(trim($col));
            $headerMap[$normalized] = $idx;
        }

        // Mapping attendu
        $dateCol = $headerMap['date'] ?? $headerMap['jour'] ?? null;
        $startCol = $headerMap['début'] ?? $headerMap['start'] ?? $headerMap['start_time'] ?? null;
        $endCol = $headerMap['fin'] ?? $headerMap['end'] ?? $headerMap['end_time'] ?? null;
        $typeCol = $headerMap['type'] ?? null;
        $descCol = $headerMap['description'] ?? $headerMap['desc'] ?? null;

        if ($dateCol === null || $startCol === null || $endCol === null) {
            fclose($handle);
            throw new \RuntimeException('Colonnes requises manquantes (Date, Début, Fin)');
        }

        $imported = 0;
        $lineNum = 1;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lineNum++;

            if (count($row) < max($dateCol, $startCol, $endCol) + 1) {
                $errors[] = "Ligne $lineNum: colonnes manquantes";
                continue;
            }

            $date = trim($row[$dateCol] ?? '');
            $startTime = trim($row[$startCol] ?? '');
            $endTime = trim($row[$endCol] ?? '');
            $type = $typeCol !== null ? strtolower(trim($row[$typeCol] ?? 'work')) : 'work';
            $description = $descCol !== null ? trim($row[$descCol] ?? '') : '';

            // Validation
            if (!Validator::date($date)) {
                $errors[] = "Ligne $lineNum: date invalide ($date)";
                continue;
            }

            if (!Validator::time($startTime)) {
                $errors[] = "Ligne $lineNum: heure de début invalide ($startTime)";
                continue;
            }

            if (!Validator::time($endTime)) {
                $errors[] = "Ligne $lineNum: heure de fin invalide ($endTime)";
                continue;
            }

            if (!in_array($type, ['work', 'break', 'course'], true)) {
                $type = 'work';
            }

            try {
                $this->entryManager->create([
                    'user_id' => Auth::getUserId(),
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'type' => $type,
                    'description' => $description,
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Ligne $lineNum: " . $e->getMessage();
            }
        }

        fclose($handle);
        return $imported;
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require VIEWS_PATH . '/layouts/main.php';
    }
}

