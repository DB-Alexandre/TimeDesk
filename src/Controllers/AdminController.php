<?php
/**
 * Console d'administration de la base (style mini-phpMyAdmin)
 */

declare(strict_types=1);

namespace Controllers;

use Core\Router;
use Core\Session;
use Helpers\Auth;
use Helpers\Logger;
use Helpers\Validator;
use Models\Database;
use PDO;
use InvalidArgumentException;

class AdminController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Page principale : listing tables + console SQL
     */
    public function database(): void
    {
        Auth::check();

        if (!Auth::isAdmin()) {
            Session::setFlash('error', 'Accès refusé');
            Router::redirect('/');
        }

        $tables = $this->getTables();
        $selectedTable = $_GET['table'] ?? ($tables[0] ?? null);

        if ($selectedTable && !in_array($selectedTable, $tables, true)) {
            Session::setFlash('error', 'Table inconnue');
            Router::redirect('?action=admin-db');
        }

        $columns = $selectedTable ? $this->getTableColumns($selectedTable) : [];
        $primaryKey = $this->detectPrimaryKey($columns);
        $limit = $this->normalizeLimit($_GET['limit'] ?? 50);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        [$rows, $totalRows] = $selectedTable ? $this->getTableRows($selectedTable, $limit, $offset) : [[], 0];

        $queryResult = null;
        $queryError = null;
        $sqlDraft = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Validator::csrf($_POST['csrf'] ?? '')) {
                Session::setFlash('error', 'Token CSRF invalide');
                Router::redirect('?action=admin-db');
            }

            $action = $_POST['action'] ?? '';

            try {
                switch ($action) {
                    case 'run-sql':
                        $sqlDraft = $_POST['sql'] ?? '';
                        $queryResult = $this->runSql($sqlDraft);
                        Logger::userAction('db_console_query', Auth::getUserId(), [
                            'verb' => $queryResult['verb'],
                        ]);
                        break;

                    case 'insert-row':
                        $this->assertTableSelected($selectedTable);
                        $this->insertRow($selectedTable, $columns, $_POST['row'] ?? []);
                        Session::setFlash('success', 'Ligne insérée');
                        Router::redirect($this->buildTableUrl($selectedTable, $page, $limit));
                        return;

                    case 'update-row':
                        $this->assertTableSelected($selectedTable);
                        $this->assertPrimaryKey($primaryKey);
                        $this->updateRow(
                            $selectedTable,
                            $primaryKey,
                            $columns,
                            $_POST['row'] ?? [],
                            $_POST['pk_value'] ?? null
                        );
                        Session::setFlash('success', 'Ligne mise à jour');
                        Router::redirect($this->buildTableUrl($selectedTable, $page, $limit));
                        return;

                    case 'delete-row':
                        $this->assertTableSelected($selectedTable);
                        $this->assertPrimaryKey($primaryKey);
                        $this->deleteRow(
                            $selectedTable,
                            $primaryKey,
                            $_POST['pk_value'] ?? null
                        );
                        Session::setFlash('success', 'Ligne supprimée');
                        Router::redirect($this->buildTableUrl($selectedTable, $page, $limit));
                        return;

                    default:
                        throw new InvalidArgumentException('Action inconnue.');
                }
            } catch (\Throwable $e) {
                $queryError = $e->getMessage();
                Logger::error('Admin DB action failed', [
                    'action' => $action,
                    'user_id' => Auth::getUserId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($selectedTable) {
            [$rows, $totalRows] = $this->getTableRows($selectedTable, $limit, $offset);
        }

        $this->render('pages/admin-database', [
            'flash' => Session::getFlash(),
            'tables' => $tables,
            'selectedTable' => $selectedTable,
            'columns' => $columns,
            'rows' => $rows,
            'primaryKey' => $primaryKey,
            'limit' => $limit,
            'page' => $page,
            'totalRows' => $totalRows,
            'queryResult' => $queryResult,
            'queryError' => $queryError,
            'sqlDraft' => $sqlDraft !== '' ? $sqlDraft : ($queryResult['sql'] ?? ''),
            'currentUrl' => $this->buildTableUrl($selectedTable, $page, $limit),
        ]);
    }

    private function getTables(): array
    {
        $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function getTableColumns(string $table): array
    {
        $stmt = $this->db->query('PRAGMA table_info(' . $this->quoteIdentifier($table) . ')');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function detectPrimaryKey(array $columns): ?string
    {
        foreach ($columns as $column) {
            if ((int)$column['pk'] === 1) {
                return $column['name'];
            }
        }
        return null;
    }

    private function normalizeLimit(int|string $limit): int
    {
        $value = max(10, min(200, (int)$limit));
        return $value ?: 50;
    }

    private function getTableRows(string $table, int $limit, int $offset): array
    {
        $countStmt = $this->db->query('SELECT COUNT(*) FROM ' . $this->quoteIdentifier($table));
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(sprintf(
            'SELECT * FROM %s LIMIT :limit OFFSET :offset',
            $this->quoteIdentifier($table)
        ));
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [$stmt->fetchAll(PDO::FETCH_ASSOC), $total];
    }

    private function buildTableUrl(?string $table, int $page, int $limit): string
    {
        $query = http_build_query([
            'action' => 'admin-db',
            'table' => $table,
            'page' => $page,
            'limit' => $limit,
        ]);

        return $_SERVER['SCRIPT_NAME'] . '?' . $query;
    }

    private function assertTableSelected(?string $table): void
    {
        if (!$table) {
            throw new InvalidArgumentException('Veuillez sélectionner une table.');
        }
    }

    private function assertPrimaryKey(?string $primaryKey): void
    {
        if (!$primaryKey) {
            throw new InvalidArgumentException('Cette table ne possède pas de clé primaire.');
        }
    }

    private function runSql(string $sql): array
    {
        $query = trim($sql);
        if ($query === '') {
            throw new InvalidArgumentException('La requête SQL est vide.');
        }

        $verb = strtoupper(strtok($query, " \r\n\t"));
        $isSelect = in_array($verb, ['SELECT', 'PRAGMA', 'EXPLAIN', 'SHOW'], true);

        if ($isSelect) {
            $stmt = $this->db->query($query);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return [
                'type' => 'select',
                'verb' => $verb,
                'sql' => $query,
                'columns' => array_keys($rows[0] ?? []),
                'rows' => $rows,
                'rowCount' => count($rows),
            ];
        }

        $affected = $this->db->exec($query);
        return [
            'type' => 'write',
            'verb' => $verb,
            'sql' => $query,
            'affectedRows' => $affected === false ? 0 : $affected,
        ];
    }

    private function insertRow(string $table, array $columns, array $input): void
    {
        $data = $this->sanitizeRow($columns, $input, true);
        if (empty($data)) {
            throw new InvalidArgumentException('Aucune donnée à insérer.');
        }

        $colNames = array_map([$this, 'quoteIdentifier'], array_keys($data));
        $placeholders = array_map(fn($column) => ':' . $column, array_keys($data));

        $stmt = $this->db->prepare(sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($table),
            implode(', ', $colNames),
            implode(', ', $placeholders)
        ));

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }

        $stmt->execute();
    }

    private function updateRow(string $table, string $primaryKey, array $columns, array $input, ?string $pkValue): void
    {
        if ($pkValue === null || $pkValue === '') {
            throw new InvalidArgumentException('Clé primaire manquante.');
        }

        $data = $this->sanitizeRow($columns, $input, false);
        if (empty($data)) {
            throw new InvalidArgumentException('Aucune donnée à mettre à jour.');
        }

        $assignments = [];
        foreach ($data as $column => $_) {
            $assignments[] = $this->quoteIdentifier($column) . ' = :' . $column;
        }

        $sql = sprintf(
            'UPDATE %s SET %s WHERE %s = :pk',
            $this->quoteIdentifier($table),
            implode(', ', $assignments),
            $this->quoteIdentifier($primaryKey)
        );

        $stmt = $this->db->prepare($sql);

        foreach ($data as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->bindValue(':pk', $pkValue);

        $stmt->execute();
    }

    private function deleteRow(string $table, string $primaryKey, ?string $pkValue): void
    {
        if ($pkValue === null || $pkValue === '') {
            throw new InvalidArgumentException('Clé primaire manquante.');
        }

        $stmt = $this->db->prepare(sprintf(
            'DELETE FROM %s WHERE %s = :pk',
            $this->quoteIdentifier($table),
            $this->quoteIdentifier($primaryKey)
        ));
        $stmt->bindValue(':pk', $pkValue);
        $stmt->execute();
    }

    private function sanitizeRow(array $columns, array $input, bool $includePrimaryKey): array
    {
        $data = [];
        foreach ($columns as $column) {
            $name = $column['name'];
            if (!$includePrimaryKey && (int)$column['pk'] === 1) {
                continue;
            }
            if (!array_key_exists($name, $input)) {
                continue;
            }

            $value = $input[$name];
            if ($value === '' && (int)$column['notnull'] === 0) {
                $value = null;
            }
            $data[$name] = $value;
        }
        return $data;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require VIEWS_PATH . '/layouts/main.php';
    }
}


