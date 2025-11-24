<?php
use Helpers\Validator;
use Helpers\TimeHelper;
use Helpers\Auth;
?>

<div class="row g-3">
    <!-- Statistiques du jour -->
    <div class="col-12">
        <?php 
        $stats = $dailyStats;
        $title = 'Aujourd\'hui (' . $today->format('d/m/Y') . ')';
        $target = 'Cible ≈ ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
        require VIEWS_PATH . '/components/stats-card.php'; 
        ?>
    </div>

    <!-- Colonne gauche: Formulaire et filtres -->
    <div class="col-lg-5">
        <?php require VIEWS_PATH . '/components/entry-form.php'; ?>
        
        <!-- Filtres -->
        <?php
        $baseFilters = $filterQueryArray ?? [];
        $buildFilterLink = function(array $overrides) use ($baseFilters) {
            $params = array_merge(['action' => 'index'], $baseFilters, $overrides);
            return '?' . http_build_query($params);
        };
        $weekStart = $weeklyStats['start_date']->format('Y-m-d');
        $weekEnd = $weeklyStats['end_date']->format('Y-m-d');
        $monthStart = $monthlyStats['start_date']->format('Y-m-d');
        $monthEnd = $monthlyStats['end_date']->format('Y-m-d');
        $yearStart = $yearlyStats['start_date']->format('Y-m-d');
        $yearEnd = $yearlyStats['end_date']->format('Y-m-d');
        ?>
        <div class="card glass mt-3">
            <div class="card-body">
                <h2 class="h6 d-flex justify-content-between align-items-center">
                    <span>Filtrer les entrées</span>
                    <span class="badge text-bg-secondary"><?= (int)$totalEntries ?> résultat<?= $totalEntries > 1 ? 's' : '' ?></span>
                </h2>
                <form class="row g-2" method="get">
                    <input type="hidden" name="action" value="index">
                    <div class="col-6">
                        <label class="form-label">Du</label>
                        <input type="date" class="form-control" name="from" value="<?= Validator::escape($filterFrom) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Au</label>
                        <input type="date" class="form-control" name="to" value="<?= Validator::escape($filterTo) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">Tous</option>
                            <option value="work" <?= $filterType === 'work' ? 'selected' : '' ?>>Travail</option>
                            <option value="break" <?= $filterType === 'break' ? 'selected' : '' ?>>Pause</option>
                            <option value="course" <?= $filterType === 'course' ? 'selected' : '' ?>>Cours</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Résultats / page</label>
                        <select name="perPage" class="form-select">
                            <?php foreach ([10, 25, 50, 100] as $size): ?>
                                <option value="<?= $size ?>" <?= (int)$perPage === $size ? 'selected' : '' ?>><?= $size ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($usersList) && Auth::isAdmin()): ?>
                        <div class="col-12">
                            <label class="form-label">Utilisateur</label>
                            <select name="user" class="form-select">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach ($usersList as $user): ?>
                                    <option value="<?= (int)$user['id'] ?>" <?= (string)$filterUser === (string)$user['id'] ? 'selected' : '' ?>>
                                        <?= Validator::escape($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-12">
                        <label class="form-label">Recherche</label>
                        <input type="text" class="form-control" name="search" placeholder="Description, mot-clé..."
                               value="<?= Validator::escape($filterSearch ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="text-muted small">Raccourcis :</span>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= $buildFilterLink(['from' => $today->format('Y-m-d'), 'to' => $today->format('Y-m-d')]) ?>">Aujourd'hui</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= $buildFilterLink(['from' => $weekStart, 'to' => $weekEnd]) ?>">Semaine</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= $buildFilterLink(['from' => $monthStart, 'to' => $monthEnd]) ?>">Mois</a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= $buildFilterLink(['from' => $yearStart, 'to' => $yearEnd]) ?>">Année</a>
                        </div>
                        <button class="btn btn-outline-light w-100" type="submit">
                            Appliquer les filtres
                        </button>
                        <?php if ($filterFrom || $filterTo || $filterType || $filterSearch || $filterUser): ?>
                            <a href="/" class="btn btn-outline-secondary w-100 mt-2">
                                Réinitialiser
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bouton export -->
        <?php $exportSuffix = $filterQuery ? '&' . $filterQuery : ''; ?>
        <div class="card glass mt-3">
            <div class="card-body">
                <h2 class="h6">Exports</h2>
                <div class="d-grid gap-2">
                    <a href="?action=export-csv<?= $exportSuffix ?>" class="btn btn-outline-success">
                        📄 CSV
                    </a>
                    <a href="?action=export-xlsx<?= $exportSuffix ?>" class="btn btn-outline-primary">
                        📊 Excel
                    </a>
                    <a href="?action=export-pdf<?= $exportSuffix ?>" class="btn btn-outline-light">
                        🧾 PDF
                    </a>
                </div>
            </div>
        </div>

        <?php
        $alerts = [];
        if ($weeklyStats['delta_minutes'] < 0) {
            $alerts[] = 'Retard hebdo de ' . TimeHelper::formatMinutes(abs($weeklyStats['delta_minutes']));
        }
        if ($monthlyStats['delta_minutes'] < 0) {
            $alerts[] = 'Retard mensuel de ' . TimeHelper::formatMinutes(abs($monthlyStats['delta_minutes']));
        }
        ?>
        <?php if (!empty($alerts)): ?>
            <div class="card glass mt-3 border-warning">
                <div class="card-body">
                    <h2 class="h6 text-warning mb-2">Alertes</h2>
                    <ul class="small m-0 ps-3">
                        <?php foreach ($alerts as $alert): ?>
                            <li><?= Validator::escape($alert) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Colonne droite: Statistiques et tableau -->
    <div class="col-lg-7">
        <div class="row g-3">
            <!-- Stats hebdomadaires -->
            <div class="col-12">
                <?php 
                $stats = $weeklyStats;
                $title = 'Semaine en cours (' . $stats['start_date']->format('d/m') . ' → ' . $stats['end_date']->format('d/m') . ')';
                $target = 'Cible ' . (int)CONTRACT_WEEKLY_HOURS . 'h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Stats mensuelles -->
            <div class="col-12">
                <?php 
                $stats = $monthlyStats;
                $title = 'Mois courant (' . $stats['start_date']->format('F Y') . ')';
                $target = 'Cible ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Stats annuelles -->
            <div class="col-12">
                <?php 
                $stats = $yearlyStats;
                $title = 'Année en cours (' . $today->format('Y') . ')';
                $target = 'Cible ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Tableau des entrées -->
            <div class="col-12">
                <?php require VIEWS_PATH . '/components/entry-table.php'; ?>
            </div>
        </div>
    </div>
</div>
