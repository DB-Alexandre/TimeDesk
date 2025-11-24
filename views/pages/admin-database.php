<?php
use Helpers\Validator;

$totalPages = $limit > 0 ? max(1, (int)ceil($totalRows / $limit)) : 1;
$prevPage = max(1, $page - 1);
$nextPage = min($totalPages, $page + 1);
?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card glass">
            <div class="card-body">
                <h2 class="h5 mb-3">Tables SQLite</h2>

                <?php if (empty($tables)): ?>
                    <p class="text-muted mb-0">Aucune table détectée.</p>
                <?php else: ?>
                    <form method="get" class="mb-3">
                        <input type="hidden" name="action" value="admin-db">
                        <label class="form-label small text-uppercase text-secondary">Sélectionner une table</label>
                        <select name="table" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($tables as $table): ?>
                                <option value="<?= Validator::escape($table) ?>" <?= $table === $selectedTable ? 'selected' : '' ?>>
                                    <?= Validator::escape($table) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <?php if ($selectedTable): ?>
                        <ul class="list-unstyled small mb-0">
                            <li><strong>Clé primaire :</strong> <?= $primaryKey ? Validator::escape($primaryKey) : '—' ?></li>
                            <li><strong>Colonnes :</strong> <?= count($columns) ?></li>
                            <li><strong>Lignes :</strong> <?= $totalRows ?></li>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card glass mt-3">
            <div class="card-body">
                <h2 class="h5 mb-3">Console SQL</h2>
                <form method="post" action="<?= Validator::escape($currentUrl) ?>">
                    <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                    <input type="hidden" name="action" value="run-sql">
                    <textarea name="sql" rows="8" class="form-control font-monospace mb-2" placeholder="SELECT * FROM users LIMIT 10;"><?= Validator::escape($sqlDraft ?? '') ?></textarea>
                    <button type="submit" class="btn btn-outline-success w-100">
                        ▶️ Exécuter
                    </button>
                </form>

                <?php if ($queryError): ?>
                    <div class="alert alert-danger mt-3 mb-0"><?= Validator::escape($queryError) ?></div>
                <?php elseif ($queryResult): ?>
                    <?php if ($queryResult['type'] === 'select'): ?>
                        <p class="small text-secondary mt-3 mb-2">
                            <?= $queryResult['rowCount'] ?> ligne(s) renvoyée(s)
                        </p>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <?php foreach ($queryResult['columns'] as $column): ?>
                                            <th><?= Validator::escape($column) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($queryResult['rows'] as $row): ?>
                                        <tr>
                                            <?php foreach ($queryResult['columns'] as $column): ?>
                                                <?php $value = $row[$column] ?? null; ?>
                                                <td><?= $value === null ? '<span class="text-muted">NULL</span>' : Validator::escape((string)$value) ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mt-3 mb-0">
                            Requête exécutée (<?= $queryResult['affectedRows'] ?> ligne(s) impactée(s))
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card glass h-100">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-0">Table <?= $selectedTable ? Validator::escape($selectedTable) : '—' ?></h2>
                        <small class="text-secondary">Page <?= $page ?> / <?= $totalPages ?> — <?= $limit ?> lignes par page</small>
                    </div>
                    <?php if ($selectedTable): ?>
                        <div class="btn-group">
                            <a class="btn btn-outline-light <?= $page === 1 ? 'disabled' : '' ?>"
                               href="<?= Validator::escape($_SERVER['SCRIPT_NAME'] . '?' . http_build_query(['action' => 'admin-db', 'table' => $selectedTable, 'page' => $prevPage, 'limit' => $limit])) ?>">
                                ←
                            </a>
                            <a class="btn btn-outline-light <?= $page === $totalPages ? 'disabled' : '' ?>"
                               href="<?= Validator::escape($_SERVER['SCRIPT_NAME'] . '?' . http_build_query(['action' => 'admin-db', 'table' => $selectedTable, 'page' => $nextPage, 'limit' => $limit])) ?>">
                                →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!$selectedTable): ?>
                    <p class="text-muted mb-0">Choisissez une table pour afficher et modifier ses données.</p>
                <?php else: ?>
                    <form method="post" class="card glass mb-3">
                        <div class="card-body">
                            <h3 class="h6 mb-3">Nouvelle ligne</h3>
                            <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                            <input type="hidden" name="action" value="insert-row">
                            <div class="row g-2">
                                <?php foreach ($columns as $column): ?>
                                    <div class="col-md-6">
                                        <label class="form-label small"><?= Validator::escape($column['name']) ?></label>
                                        <input type="text"
                                               class="form-control form-control-sm"
                                               name="row[<?= Validator::escape($column['name']) ?>]"
                                               placeholder="<?= $column['dflt_value'] !== null ? 'Défaut : ' . $column['dflt_value'] : ((int)$column['notnull'] === 1 ? 'Requis' : 'NULL autorisé') ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm mt-3">
                                ➕ Insérer
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead>
                                <tr>
                                    <?php foreach ($columns as $column): ?>
                                        <th>
                                            <?= Validator::escape($column['name']) ?>
                                            <?php if ((int)$column['pk'] === 1): ?>
                                                <span class="badge bg-secondary">PK</span>
                                            <?php endif; ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <?php if ($primaryKey): ?>
                                        <th class="text-end">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr>
                                        <td colspan="<?= count($columns) + ($primaryKey ? 1 : 0) ?>" class="text-center text-muted">
                                            Aucune donnée sur cette page.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <?php foreach ($columns as $column): ?>
                                                <?php $value = $row[$column['name']] ?? null; ?>
                                                <td><?= $value === null ? '<span class="text-muted">NULL</span>' : Validator::escape((string)$value) ?></td>
                                            <?php endforeach; ?>
                                            <?php if ($primaryKey): ?>
                                                <td class="text-end">
                                                    <details class="d-inline-block">
                                                        <summary class="btn btn-sm btn-outline-light">✏️</summary>
                                                        <div class="card card-body bg-dark text-start mt-2">
                                                            <form method="post" action="<?= Validator::escape($currentUrl) ?>">
                                                                <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                                                                <input type="hidden" name="action" value="update-row">
                                                                <input type="hidden" name="pk_value" value="<?= Validator::escape((string)($row[$primaryKey] ?? '')) ?>">
                                                                <?php foreach ($columns as $column): ?>
                                                                    <?php if ((int)$column['pk'] === 1) {
                                                                        continue;
                                                                    } ?>
                                                                    <label class="form-label small mt-2 mb-0"><?= Validator::escape($column['name']) ?></label>
                                                                    <input type="text"
                                                                           class="form-control form-control-sm"
                                                                           name="row[<?= Validator::escape($column['name']) ?>]"
                                                                           value="<?= Validator::escape((string)($row[$column['name']] ?? '')) ?>">
                                                                <?php endforeach; ?>
                                                                <button type="submit" class="btn btn-primary btn-sm w-100 mt-3">💾 Sauver</button>
                                                            </form>
                                                        </div>
                                                    </details>
                                                    <form method="post"
                                                          class="d-inline"
                                                          action="<?= Validator::escape($currentUrl) ?>"
                                                          onsubmit="return confirm('Supprimer cette ligne ?');">
                                                        <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                                                        <input type="hidden" name="action" value="delete-row">
                                                        <input type="hidden" name="pk_value" value="<?= Validator::escape((string)($row[$primaryKey] ?? '')) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            🗑️
                                                        </button>
                                                    </form>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


