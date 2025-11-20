<?php
use Helpers\Validator;
use Helpers\TimeHelper;
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
        <div class="card glass mt-3">
            <div class="card-body">
                <h2 class="h6">Filtrer les entrées</h2>
                <form class="row g-2" method="get">
                    <div class="col-6">
                        <label class="form-label">Du</label>
                        <input type="date" class="form-control" name="from" value="<?= Validator::escape($filterFrom) ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Au</label>
                        <input type="date" class="form-control" name="to" value="<?= Validator::escape($filterTo) ?>">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-light w-100" type="submit">
                            Appliquer les filtres
                        </button>
                        <?php if ($filterFrom || $filterTo): ?>
                            <a href="/" class="btn btn-outline-secondary w-100 mt-2">
                                Réinitialiser
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bouton export -->
        <div class="card glass mt-3">
            <div class="card-body">
                <a href="?action=export-csv" class="btn btn-outline-success w-100">
                    📥 Exporter en CSV
                </a>
            </div>
        </div>
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
