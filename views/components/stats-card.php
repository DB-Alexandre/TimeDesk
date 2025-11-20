<?php
use Helpers\Validator;
use Helpers\TimeHelper;

// Variables attendues: $stats, $title, $target
?>
<div class="card glass">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 m-0"><?= Validator::escape($title) ?></h2>
            <span class="badge text-bg-secondary"><?= Validator::escape($target) ?></span>
        </div>
        <div class="kpi h4">
            <?= TimeHelper::formatMinutes($stats['net_minutes']) ?>
            <small class="text-secondary">h travaillées</small>
            <span class="ms-2 small <?= $stats['delta_minutes'] >= 0 ? 'text-success' : 'text-warning' ?>">
                (Δ <?= TimeHelper::formatMinutes($stats['delta_minutes']) ?>)
            </span>
        </div>
        <div class="progress" role="progressbar">
            <div class="progress-bar" style="width: <?= $stats['percentage'] ?>%"></div>
        </div>
    </div>
</div>
