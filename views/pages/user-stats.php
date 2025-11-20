<?php
use Helpers\Validator;
use Helpers\TimeHelper;
use Helpers\Auth;
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Statistiques détaillées</h2>
                <p class="text-muted mb-0">
                    Utilisateur : <strong><?= Validator::escape($user['username']) ?></strong>
                    <?php if ($user['email']): ?>
                        (<?= Validator::escape($user['email']) ?>)
                    <?php endif; ?>
                </p>
            </div>
            <a href="?action=users" class="btn btn-outline-secondary">
                ← Retour à la liste
            </a>
        </div>

        <?php if (isset($flash)): ?>
            <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        <?php endif; ?>

        <!-- Vue d'ensemble -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card glass">
                    <div class="card-body text-center">
                        <h3 class="h5 text-muted">Total entrées</h3>
                        <div class="h2"><?= $totalEntries ?></div>
                        <small class="text-muted">
                            <?= $totalWorkEntries ?> travail, <?= $totalBreakEntries ?> pauses
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass">
                    <div class="card-body text-center">
                        <h3 class="h5 text-muted">Temps total</h3>
                        <div class="h2"><?= TimeHelper::formatMinutes($allTimeStats['net_minutes']) ?></div>
                        <small class="text-muted">heures travaillées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass">
                    <div class="card-body text-center">
                        <h3 class="h5 text-muted">Moyenne journalière</h3>
                        <div class="h2"><?= TimeHelper::formatMinutes((int)$avgDaily) ?></div>
                        <small class="text-muted">heures/jour</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card glass">
                    <div class="card-body text-center">
                        <h3 class="h5 text-muted">Période</h3>
                        <div class="h6">
                            <?php if ($firstEntry): ?>
                                <?= TimeHelper::formatDate($firstEntry['date'], 'd/m/Y') ?>
                            <?php else: ?>
                                Aucune entrée
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">
                            <?php if ($firstEntry && $lastEntry): ?>
                                → <?= TimeHelper::formatDate($lastEntry['date'], 'd/m/Y') ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistiques par période -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <h3 class="h5 mb-3">Statistiques actuelles</h3>
            </div>
            
            <!-- Aujourd'hui -->
            <div class="col-md-6 col-lg-3">
                <?php 
                $stats = $dailyStats;
                $title = 'Aujourd\'hui';
                $target = 'Cible ≈ ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Semaine -->
            <div class="col-md-6 col-lg-3">
                <?php 
                $stats = $weeklyStats;
                $title = 'Semaine';
                $target = 'Cible ' . (int)CONTRACT_WEEKLY_HOURS . 'h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Mois -->
            <div class="col-md-6 col-lg-3">
                <?php 
                $stats = $monthlyStats;
                $title = 'Mois';
                $target = 'Cible ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>

            <!-- Année -->
            <div class="col-md-6 col-lg-3">
                <?php 
                $stats = $yearlyStats;
                $title = 'Année';
                $target = 'Cible ' . TimeHelper::formatMinutes($stats['target_minutes']) . ' h';
                require VIEWS_PATH . '/components/stats-card.php'; 
                ?>
            </div>
        </div>

        <!-- Détails par mois -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card glass">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Évolution mensuelle (12 derniers mois)</h3>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Mois</th>
                                        <th class="text-end">Travail</th>
                                        <th class="text-end">Pauses</th>
                                        <th class="text-end">Net</th>
                                        <th class="text-end">Cible</th>
                                        <th class="text-end">Écart</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthlyBreakdown as $month): ?>
                                        <?php
                                        $delta = $month['net_minutes'] - $month['target_minutes'];
                                        $percentage = $month['target_minutes'] > 0 
                                            ? min(100, round($month['net_minutes'] / $month['target_minutes'] * 100)) 
                                            : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?= Validator::escape($month['label']) ?></strong></td>
                                            <td class="text-end"><?= TimeHelper::formatMinutes($month['work_minutes']) ?></td>
                                            <td class="text-end"><?= TimeHelper::formatMinutes($month['break_minutes']) ?></td>
                                            <td class="text-end"><strong><?= TimeHelper::formatMinutes($month['net_minutes']) ?></strong></td>
                                            <td class="text-end text-muted"><?= TimeHelper::formatMinutes($month['target_minutes']) ?></td>
                                            <td class="text-end <?= $delta >= 0 ? 'text-success' : 'text-warning' ?>">
                                                <?= $delta >= 0 ? '+' : '' ?><?= TimeHelper::formatMinutes($delta) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $percentage >= 100 ? 'bg-success' : ($percentage >= 80 ? 'bg-warning' : 'bg-danger') ?>">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails par semaine -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card glass">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Évolution hebdomadaire (12 dernières semaines)</h3>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Semaine</th>
                                        <th class="text-end">Travail</th>
                                        <th class="text-end">Pauses</th>
                                        <th class="text-end">Net</th>
                                        <th class="text-end">Cible</th>
                                        <th class="text-end">Écart</th>
                                        <th class="text-center">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($weeklyBreakdown as $week): ?>
                                        <?php
                                        $delta = $week['net_minutes'] - $week['target_minutes'];
                                        $percentage = $week['target_minutes'] > 0 
                                            ? min(100, round($week['net_minutes'] / $week['target_minutes'] * 100)) 
                                            : 0;
                                        ?>
                                        <tr>
                                            <td><strong><?= Validator::escape($week['label']) ?></strong></td>
                                            <td class="text-end"><?= TimeHelper::formatMinutes($week['work_minutes']) ?></td>
                                            <td class="text-end"><?= TimeHelper::formatMinutes($week['break_minutes']) ?></td>
                                            <td class="text-end"><strong><?= TimeHelper::formatMinutes($week['net_minutes']) ?></strong></td>
                                            <td class="text-end text-muted"><?= TimeHelper::formatMinutes($week['target_minutes']) ?></td>
                                            <td class="text-end <?= $delta >= 0 ? 'text-success' : 'text-warning' ?>">
                                                <?= $delta >= 0 ? '+' : '' ?><?= TimeHelper::formatMinutes($delta) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $percentage >= 100 ? 'bg-success' : ($percentage >= 80 ? 'bg-warning' : 'bg-danger') ?>">
                                                    <?= $percentage ?>%
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Détails supplémentaires -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card glass">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Informations générales</h3>
                        <dl class="row mb-0">
                            <dt class="col-sm-6">Première entrée</dt>
                            <dd class="col-sm-6">
                                <?php if ($firstEntry): ?>
                                    <?= TimeHelper::formatDate($firstEntry['date'], 'd/m/Y') ?>
                                    à <?= Validator::escape($firstEntry['start_time']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Dernière entrée</dt>
                            <dd class="col-sm-6">
                                <?php if ($lastEntry): ?>
                                    <?= TimeHelper::formatDate($lastEntry['date'], 'd/m/Y') ?>
                                    à <?= Validator::escape($lastEntry['start_time']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Aucune</span>
                                <?php endif; ?>
                            </dd>
                            
                            <dt class="col-sm-6">Total heures travail</dt>
                            <dd class="col-sm-6">
                                <strong><?= TimeHelper::formatMinutes($allTimeStats['work_minutes']) ?></strong>
                            </dd>
                            
                            <dt class="col-sm-6">Total heures pauses</dt>
                            <dd class="col-sm-6">
                                <?= TimeHelper::formatMinutes($allTimeStats['break_minutes']) ?>
                            </dd>
                            
                            <dt class="col-sm-6">Moyenne par jour</dt>
                            <dd class="col-sm-6">
                                <strong><?= TimeHelper::formatMinutes((int)$avgDaily) ?></strong>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card glass">
                    <div class="card-body">
                        <h3 class="h5 mb-3">Répartition</h3>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Entrées de travail</span>
                                <strong><?= $totalWorkEntries ?></strong>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <?php 
                                $workPercent = $totalEntries > 0 ? round($totalWorkEntries / $totalEntries * 100) : 0;
                                ?>
                                <div class="progress-bar bg-success" style="width: <?= $workPercent ?>%">
                                    <?= $workPercent ?>%
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Entrées de pause</span>
                                <strong><?= $totalBreakEntries ?></strong>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <?php 
                                $breakPercent = $totalEntries > 0 ? round($totalBreakEntries / $totalEntries * 100) : 0;
                                ?>
                                <div class="progress-bar bg-warning" style="width: <?= $breakPercent ?>%">
                                    <?= $breakPercent ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dernières entrées -->
        <div class="row g-3">
            <div class="col-12">
                <div class="card glass">
                    <div class="card-body">
                        <h3 class="h5 mb-3">10 dernières entrées</h3>
                        <?php if (empty($recentEntries)): ?>
                            <p class="text-muted text-center py-3">Aucune entrée trouvée</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Début</th>
                                            <th>Fin</th>
                                            <th>Durée</th>
                                            <th>Type</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentEntries as $entry): ?>
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
                                                    <span class="badge <?= $isBreak ? 'bg-warning' : 'bg-success' ?>">
                                                        <?= $isBreak ? 'Pause' : 'Travail' ?>
                                                    </span>
                                                </td>
                                                <td><?= Validator::escape($entry['description']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

