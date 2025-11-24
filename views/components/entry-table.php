<?php
use Helpers\Validator;
use Helpers\TimeHelper;
use Helpers\Auth;
use Core\Session;

$isAdmin = Auth::isAdmin();
$emptyColspan = $isAdmin ? 8 : 7;
$totalEntries = $totalEntries ?? count($entries);
$page = $page ?? 1;
$totalPages = $totalPages ?? 1;
$perPage = $perPage ?? 25;
$filterQueryArray = $filterQueryArray ?? [];
$baseParams = array_merge(['action' => 'index'], $filterQueryArray);
$buildPageUrl = function(int $targetPage) use ($baseParams, $perPage) {
    $params = array_merge($baseParams, [
        'page' => $targetPage,
        'perPage' => $perPage,
    ]);
    return '?' . http_build_query($params);
};

$typeLabels = [
    'work' => ['label' => 'Travail', 'class' => 'text-bg-success'],
    'break' => ['label' => 'Pause', 'class' => 'text-bg-warning'],
    'course' => ['label' => 'Cours', 'class' => 'text-bg-info'],
];

$renderTypeBadge = function (string $type) use ($typeLabels): string {
    $info = $typeLabels[$type] ?? ['label' => ucfirst($type), 'class' => 'text-bg-secondary'];
    return '<span class="badge ' . $info['class'] . '">' . htmlspecialchars($info['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
};
?>
<div class="card glass">
    <div class="card-body">
        <div class="d-flex justify-content-between flex-column flex-md-row gap-2 mb-3">
            <div>
                <h2 class="h5 mb-0">
                    Entrées<?= !empty($filterQuery) ? ' (filtrées)' : '' ?>
                </h2>
                <small class="text-muted">Page <?= $page ?> / <?= $totalPages ?> — <?= $totalEntries ?> entrées</small>
            </div>
            <div class="text-end">
                <?php if (!empty($filterSearch)): ?>
                    <span class="badge text-bg-info">Recherche : <?= Validator::escape($filterSearch) ?></span>
                <?php endif; ?>
                <?php if ($filterType): ?>
                    <span class="badge text-bg-secondary">
                        Type : <?= Validator::escape($typeLabels[$filterType]['label'] ?? ucfirst($filterType)) ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php if ($isAdmin): ?>
                            <th>Utilisateur</th>
                        <?php endif; ?>
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
                            <td colspan="<?= $emptyColspan ?>" class="text-center text-secondary py-4">
                                Aucune entrée trouvée
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entries as $entry): ?>
                            <?php
                            $duration = TimeHelper::calculateDuration($entry['start_time'], $entry['end_time']);
                            $entryDate = new DateTimeImmutable($entry['date']);
                            ?>
                            <tr>
                                <td><?= $entryDate->format('d/m/Y') ?></td>
                                <?php if ($isAdmin): ?>
                                    <td><?= Validator::escape($entry['username'] ?? '-') ?></td>
                                <?php endif; ?>
                                <td><?= Validator::escape($entry['start_time']) ?></td>
                                <td><?= Validator::escape($entry['end_time']) ?></td>
                                <td><?= TimeHelper::formatMinutes($duration) ?></td>
                                <td><?= $renderTypeBadge($entry['type']) ?></td>
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
        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm justify-content-center">
                    <li class="page-item <?= $page === 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page === 1 ? '#' : $buildPageUrl($page - 1) ?>" tabindex="-1">Préc.</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= $buildPageUrl($i) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page >= $totalPages ? '#' : $buildPageUrl($page + 1) ?>">Suiv.</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
