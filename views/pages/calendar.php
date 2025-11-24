<?php
use Helpers\Validator;
use Helpers\TimeHelper;
use Helpers\Auth;

$monthLabel = ucfirst($currentMonth->format('F Y'));
$typeMap = [
    'work' => ['label' => 'Travail', 'class' => 'calendar-entry--work'],
    'break' => ['label' => 'Pause', 'class' => 'calendar-entry--break'],
    'course' => ['label' => 'Cours', 'class' => 'calendar-entry--course'],
];
?>

<div class="row g-3">
    <div class="col-12 d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
            <h2>Calendrier - <?= Validator::escape($monthLabel) ?></h2>
            <?php if (Auth::isAdmin() && $selectedUser): ?>
                <p class="text-muted mb-0">Filtré sur l'utilisateur #<?= (int)$selectedUser ?></p>
            <?php endif; ?>
        </div>
        <div class="btn-group">
            <a class="btn btn-outline-secondary" href="?action=calendar&month=<?= $prevMonth ?><?= $selectedUser ? '&user=' . (int)$selectedUser : '' ?>">← Mois précédent</a>
            <a class="btn btn-outline-light" href="?action=calendar">Aujourd'hui</a>
            <a class="btn btn-outline-secondary" href="?action=calendar&month=<?= $nextMonth ?><?= $selectedUser ? '&user=' . (int)$selectedUser : '' ?>">Mois suivant →</a>
        </div>
    </div>

    <div class="col-12">
        <form class="row g-2 align-items-end">
            <input type="hidden" name="action" value="calendar">
            <div class="col-md-3">
                <label class="form-label">Mois</label>
                <input type="month" class="form-control" name="month" value="<?= $currentMonth->format('Y-m') ?>">
            </div>
            <?php if (Auth::isAdmin()): ?>
                <div class="col-md-3">
                    <label class="form-label">Utilisateur</label>
                    <select name="user" class="form-select">
                        <option value="">Tous</option>
                        <?php foreach ($usersList as $user): ?>
                            <option value="<?= (int)$user['id'] ?>" <?= (string)$selectedUser === (string)$user['id'] ? 'selected' : '' ?>>
                                <?= Validator::escape($user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">Mettre à jour</button>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="/" class="btn btn-outline-secondary">← Retour au tableau de bord</a>
            </div>
        </form>
    </div>

    <div class="col-12">
        <div class="calendar-grid d-grid">
            <div class="calendar-header">Lun</div>
            <div class="calendar-header">Mar</div>
            <div class="calendar-header">Mer</div>
            <div class="calendar-header">Jeu</div>
            <div class="calendar-header">Ven</div>
            <div class="calendar-header">Sam</div>
            <div class="calendar-header">Dim</div>

            <?php foreach ($weeks as $week): ?>
                <?php foreach ($week as $day): ?>
                    <?php
                    $classes = ['calendar-cell'];
                    if (!$day['isCurrentMonth']) {
                        $classes[] = 'calendar-cell--muted';
                    }
                    if ($day['isToday']) {
                        $classes[] = 'calendar-cell--today';
                    }
                    $workLabel = $day['work_minutes'] > 0 ? TimeHelper::formatMinutes($day['work_minutes']) : null;
                    ?>
                    <div class="<?= implode(' ', $classes) ?>">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold"><?= $day['day'] ?></span>
                            <?php if ($workLabel): ?>
                                <span class="badge bg-success-subtle text-success"><?= $workLabel ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="calendar-cell__body">
                            <?php if (!empty($day['entries'])): ?>
                                <?php foreach ($day['entries'] as $entry): ?>
                                    <?php $typeInfo = $typeMap[$entry['type']] ?? $typeMap['work']; ?>
                                    <div class="calendar-entry <?= $typeInfo['class'] ?>">
                                        <strong><?= $typeInfo['label'] ?>:</strong>
                                        <?= Validator::escape($entry['start_time']) ?>-<?= Validator::escape($entry['end_time']) ?>
                                        <?php if (!empty($entry['description'])): ?>
                                            <span class="text-muted small"><?= Validator::escape(mb_strimwidth($entry['description'], 0, 20, '…')) ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .75rem;
}
.calendar-header {
    text-align: center;
    font-weight: 600;
    text-transform: uppercase;
    font-size: .85rem;
    color: #adb5bd;
}
.calendar-cell {
    min-height: 120px;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: .75rem;
    padding: .65rem;
    background: rgba(255,255,255,.02);
}
.calendar-cell--muted {
    opacity: 0.5;
}
.calendar-cell--today {
    border-color: rgba(13,110,253,.7);
    box-shadow: 0 0 0 1px rgba(13,110,253,.2);
}
.calendar-entry {
    font-size: .85rem;
}
.calendar-entry--work {
    color: #20c997;
}
.calendar-entry--break {
    color: #ffc107;
}
.calendar-entry--course {
    color: #0dcaf0;
}
</style>

