<?php
use Helpers\Validator;
use Helpers\TimeHelper;
use Core\Session;

// Variables attendues: $entries, $filterFrom, $filterTo
?>
<div class="card glass">
    <div class="card-body">
        <h2 class="h5 mb-3">
            Entrées<?= $filterFrom || $filterTo ? ' (filtrées)' : '' ?>
            <span class="badge text-bg-secondary"><?= count($entries) ?></span>
        </h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Début</th>
                        <th>Fin</th>
                        <th>Durée</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">
                                Aucune entrée trouvée
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php
                            $duration = TimeHelper::calculateDuration($entry['start_time'], $entry['end_time']);
                            $isBreak = $entry['type'] === 'break';
                            $entryDate = new DateTimeImmutable($entry['date']);
                            ?>
                            <tr>
                                <td><?= $entryDate->format('d/m/Y') ?></td>
                                <td><?= Validator::escape($entry['start_time']) ?></td>
                                <td><?= Validator::escape($entry['end_time']) ?></td>
                                <td><?= TimeHelper::formatMinutes($duration) ?></td>
                                <td>
                                    <span class="badge <?= $isBreak ? 'text-bg-warning' : 'text-bg-success' ?>">
                                        <?= $isBreak ? 'Pause' : 'Travail' ?>
                                    </span>
                                </td>
                                <td><?= Validator::escape($entry['description']) ?></td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-light" 
                                                onclick='editEntry(<?= json_encode($entry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>
                                            Modifier
                                        </button>
                                        <form method="post" 
                                              action="?action=delete"
                                              onsubmit="return confirm('Supprimer cette entrée ?');"
                                              style="display: inline;">
                                            <input type="hidden" name="csrf" value="<?= Validator::escape(Session::getCsrfToken()) ?>">
                                            <input type="hidden" name="id" value="<?= (int)$entry['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger">
                                                Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
