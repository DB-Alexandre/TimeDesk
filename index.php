<?php
// timesheet_single_page.php — Single‑file PHP Timesheet (SQLite + Bootstrap)
// Author: ChatGPT (GPT‑5 Thinking)
// Requirements: PHP 8.1+, SQLite3 extension, writable folder for the .sqlite file
// ------------------------------------------------------------

declare(strict_types=1);

// ---- CONFIG ----
const APP_TITLE = 'TimeDesk';
const TIMEZONE  = 'Europe/Paris';
const DB_FILE   = __DIR__ . '/timesheet.sqlite';
// Weekly contract hours (e.g., 35h)
const CONTRACT_WEEKLY_HOURS = 35.0;
// Default monthly target in hours (France 35h ≈ 151.67h/month)
const MONTHLY_TARGET_HOURS = 151.67;

// ---- BOOTSTRAP ----
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }

// ---- INIT ----
@date_default_timezone_set(TIMEZONE);
session_start();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$db = new PDO('sqlite:' . DB_FILE);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$db->exec('CREATE TABLE IF NOT EXISTS entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  date TEXT NOT NULL,               -- YYYY-MM-DD
  start_time TEXT NOT NULL,         -- HH:MM
  end_time TEXT NOT NULL,           -- HH:MM
  type TEXT NOT NULL CHECK(type IN ("work","break")),
  description TEXT DEFAULT "",
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
)');

// ---- HELPERS ----
function parseTimeToMinutes(string $hhmm): int {
  [$h,$m] = array_map('intval', explode(':', $hhmm));
  return $h*60 + $m;
}
function minutesToHM(int $min): string {
  $sign = $min < 0 ? '-' : '';
  $min = abs($min);
  $h = intdiv($min, 60);
  $m = $min % 60;
  return sprintf('%s%02d:%02d', $sign, $h, $m);
}
function durationMinutes(string $start, string $end): int {
  // Supports overnight spans (e.g., 22:00 -> 02:00 next day)
  $s = parseTimeToMinutes($start);
  $e = parseTimeToMinutes($end);
  if ($e < $s) { $e += 24*60; }
  return max(0, $e - $s);
}

function validDate(string $d): bool { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d); }
function validTime(string $t): bool { return (bool)preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $t); }
function isCsrfOk(): bool { return isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf']); }

// ---- LIGHTWEIGHT AJAX API ----
// GET ?ajax=lastEnd&date=YYYY-MM-DD  => { lastEnd: "HH:MM" | null }
if (($_GET['ajax'] ?? '') === 'lastEnd' && isset($_GET['date']) && validDate($_GET['date'])) {
  $stmt = $db->prepare('SELECT end_time FROM entries WHERE date = ? ORDER BY end_time DESC LIMIT 1');
  $stmt->execute([$_GET['date']]);
  $last = $stmt->fetch(PDO::FETCH_ASSOC);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['lastEnd' => $last['end_time'] ?? null], JSON_UNESCAPED_UNICODE);
  exit;
}

// ---- ACTIONS ----
$errors = [];
$flash  = null;

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isCsrfOk()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
      $id          = isset($_POST['id']) ? (int)$_POST['id'] : null;
      $date        = trim((string)($_POST['date'] ?? ''));
      $start_time  = trim((string)($_POST['start_time'] ?? ''));
      $end_time    = trim((string)($_POST['end_time'] ?? ''));
      $type        = ($_POST['type'] ?? 'work') === 'break' ? 'break' : 'work';
      $description = trim((string)($_POST['description'] ?? ''));

      if (!validDate($date)) $errors[] = 'Date invalide.';
      if (!validTime($start_time)) $errors[] = "Heure de début invalide.";
      if (!validTime($end_time)) $errors[] = "Heure de fin invalide.";

      if (!$errors) {
        $now = (new DateTimeImmutable('now'))->format('c');
        if ($action === 'create') {
          $stmt = $db->prepare('INSERT INTO entries(date,start_time,end_time,type,description,created_at,updated_at) VALUES(?,?,?,?,?,?,?)');
          $stmt->execute([$date,$start_time,$end_time,$type,$description,$now,$now]);
          $flash = 'Entrée ajoutée.';
        } else { // update
          $stmt = $db->prepare('UPDATE entries SET date=?, start_time=?, end_time=?, type=?, description=?, updated_at=? WHERE id=?');
          $stmt->execute([$date,$start_time,$end_time,$type,$description,$now,$id]);
          $flash = 'Entrée mise à jour.';
        }
      }
    }

    if (($_POST['action'] ?? '') === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $stmt = $db->prepare('DELETE FROM entries WHERE id=?');
      $stmt->execute([$id]);
      $flash = 'Entrée supprimée.';
    }
  }
} catch (Throwable $e) {
  $errors[] = 'Erreur: ' . $e->getMessage();
}

// ---- FILTERS ----
$filterFrom = $_GET['from'] ?? '';
$filterTo   = $_GET['to']   ?? '';
$where = [];$params=[];
if ($filterFrom && validDate($filterFrom)) { $where[] = 'date >= ?'; $params[]=$filterFrom; }
if ($filterTo   && validDate($filterTo))   { $where[] = 'date <= ?'; $params[]=$filterTo; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// ---- FETCH ENTRIES ----
$stmt = $db->prepare("SELECT * FROM entries $whereSql ORDER BY date DESC, start_time DESC, id DESC");
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---- STATISTICS ----
function statsForRange(PDO $db, string $from, string $to): array {
  $stmt = $db->prepare('SELECT date, start_time, end_time, type FROM entries WHERE date BETWEEN ? AND ?');
  $stmt->execute([$from, $to]);
  $totalWork = 0; $totalBreak = 0; $byDay=[];
  foreach ($stmt as $row) {
    $dur = durationMinutes($row['start_time'], $row['end_time']);
    if ($row['type'] === 'break') { $totalBreak += $dur; } else { $totalWork += $dur; }
    $byDay[$row['date']] = ($byDay[$row['date']] ?? 0) + ($row['type']==='break' ? 0 : $dur);
  }
  return [
    'work_min'  => $totalWork,
    'break_min' => $totalBreak,
    'net_min'   => $totalWork, // pauses exclues
    'by_day'    => $byDay,
  ];
}

// Current ISO week (Mon..Sun)
$today = new DateTimeImmutable('today');
$weekStart = $today->modify('monday this week');
$weekEnd   = $weekStart->modify('+6 days');
$weekStats = statsForRange($db, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'));
$weekTargetMin = (int)round(CONTRACT_WEEKLY_HOURS * 60);
$weekDeltaMin  = $weekStats['net_min'] - $weekTargetMin;
$weekPct       = $weekTargetMin > 0 ? min(100, round($weekStats['net_min'] / $weekTargetMin * 100)) : 0;

// Current month
$monthStart = $today->modify('first day of this month');
$monthEnd   = $today->modify('last day of this month');
$monthStats = statsForRange($db, $monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'));
$monthTargetMin = (int)round(MONTHLY_TARGET_HOURS * 60);
$monthDeltaMin  = $monthStats['net_min'] - $monthTargetMin;
$monthPct       = $monthTargetMin > 0 ? min(100, round($monthStats['net_min'] / $monthTargetMin * 100)) : 0;

// ---- DAILY & ANNUAL STATS ----
$dayStart = $today;
$dayStats = statsForRange($db, $dayStart->format('Y-m-d'), $dayStart->format('Y-m-d'));
$dailyTargetMin = (int)round(CONTRACT_WEEKLY_HOURS * 60 / 5);
$dayDeltaMin = $dayStats['net_min'] - $dailyTargetMin;
$dayPct = $dailyTargetMin > 0 ? min(100, round($dayStats['net_min'] / $dailyTargetMin * 100)) : 0;

$yearStart = new DateTimeImmutable($today->format('Y-01-01'));
$yearEnd   = new DateTimeImmutable($today->format('Y-12-31'));
$yearStats = statsForRange($db, $yearStart->format('Y-m-d'), $yearEnd->format('Y-m-d'));
$annualTargetMin = (int)round(MONTHLY_TARGET_HOURS * 12 * 60);
$yearDeltaMin  = $yearStats['net_min'] - $annualTargetMin;
$yearPct       = $annualTargetMin > 0 ? min(100, round($yearStats['net_min'] / $annualTargetMin * 100)) : 0;

// ---- DEFAULT START TIME FOR FORM (last end time of selected date) ----
$defaultDateStr = $today->format('Y-m-d');
$lastEndTimeInitial = null;
$stmtLast = $db->prepare('SELECT end_time FROM entries WHERE date = ? ORDER BY end_time DESC LIMIT 1');
$stmtLast->execute([$defaultDateStr]);
if ($row = $stmtLast->fetch(PDO::FETCH_ASSOC)) { $lastEndTimeInitial = $row['end_time']; }

?>
<!doctype html>
<html lang="fr" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(APP_TITLE) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="container py-4">
  <header class="mb-4 d-flex justify-content-between align-items-center">
    <h1 class="h3 m-0"><span class="accent"><?= h(APP_TITLE) ?></span> <small class="text-secondary">(<?= h(TIMEZONE) ?>)</small></h1>
    <div>
      <button class="btn btn-sm btn-outline-secondary" id="toggleTheme" type="button">🌙/☀️</button>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= h($flash) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="m-0"><?php foreach($errors as $e){echo '<li>'.h($e).'</li>'; } ?></ul></div>
  <?php endif; ?>

  <div class="row g-3">
        <div class="col-12">
          <div class="card glass">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 m-0">Aujourd'hui (<?= h($today->format('d/m/Y')) ?>)</h2>
                <span class="badge text-bg-secondary">Cible ≈ <?= h(minutesToHM($dailyTargetMin)) ?> h</span>
              </div>
              <div class="kpi h4">
                <?= h(minutesToHM($dayStats['net_min'])) ?> <small class="text-secondary">h travaillées</small>
                <span class="ms-2 small <?= $dayDeltaMin>=0?'text-success':'text-warning' ?>">(Δ <?= h(minutesToHM($dayDeltaMin)) ?>)</span>
              </div>
              <div class="progress" role="progressbar" aria-valuenow="<?= $dayPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= $dayPct ?>%"></div>
              </div>
            </div>
          </div>
        </div>
    <div class="col-lg-5">
      <div class="card glass">
        <div class="card-body">
          <h2 class="h5 mb-3">Ajouter / Modifier une entrée</h2>
          <form method="post" class="row g-2" id="entryForm">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
            <input type="hidden" name="action" value="create" id="formAction">
            <input type="hidden" name="id" value="" id="formId">

            <div class="col-6">
              <label class="form-label">Date</label>
              <input type="date" class="form-control" name="date" required value="<?= h($today->format('Y-m-d')) ?>">
            </div>
            <div class="col-3">
              <label class="form-label">Début</label>
              <input type="time" class="form-control" name="start_time" required value="<?= h($lastEndTimeInitial ?? '') ?>">
            </div>
            <div class="col-3">
              <label class="form-label">Fin</label>
              <input type="time" class="form-control" name="end_time" required>
            </div>
            <div class="col-12">
              <label class="form-label">Type</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="type" id="typeWork" value="work" checked>
                <label class="btn btn-outline-success" for="typeWork">Travail</label>
                <input type="radio" class="btn-check" name="type" id="typeBreak" value="break">
                <label class="btn btn-outline-warning" for="typeBreak">Pause</label>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <input type="text" class="form-control" name="description" placeholder="Ex: Support client, réunion, dev backend...">
            </div>
            <div class="col-12 d-flex gap-2 mt-2">
              <button class="btn btn-primary" type="submit">Enregistrer</button>
              <button class="btn btn-outline-secondary" type="reset" id="btnReset">Réinitialiser</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card glass mt-3">
        <div class="card-body">
          <h2 class="h6">Filtrer</h2>
          <form class="row g-2" method="get">
            <div class="col-6">
              <label class="form-label">Du</label>
              <input type="date" class="form-control" name="from" value="<?= h($filterFrom) ?>">
            </div>
            <div class="col-6">
              <label class="form-label">Au</label>
              <input type="date" class="form-control" name="to" value="<?= h($filterTo) ?>">
            </div>
            <div class="col-12">
              <button class="btn btn-outline-light w-100" type="submit">Appliquer</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="row g-3">
        <div class="col-12">
          <div class="card glass">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 m-0">Semaine en cours (<?= h($weekStart->format('d/m')) ?> → <?= h($weekEnd->format('d/m')) ?>)</h2>
                <span class="badge text-bg-secondary">Cible <?= (int)CONTRACT_WEEKLY_HOURS ?>h</span>
              </div>
              <div class="kpi h4">
                <?= h(minutesToHM($weekStats['net_min'])) ?> <small class="text-secondary">h travaillées</small>
                <span class="ms-2 small <?= $weekDeltaMin>=0?'text-success':'text-warning' ?>">(Δ <?= h(minutesToHM($weekDeltaMin)) ?>)</span>
              </div>
              <div class="progress" role="progressbar" aria-label="Progression hebdo" aria-valuenow="<?= $weekPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= $weekPct ?>%"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="card glass">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 m-0">Mois courant (<?= h($monthStart->format('F Y')) ?>)</h2>
                <span class="badge text-bg-secondary">Cible <?= h(minutesToHM($monthTargetMin)) ?> h</span>
              </div>
              <div class="kpi h4">
                <?= h(minutesToHM($monthStats['net_min'])) ?> <small class="text-secondary">h travaillées</small>
                <span class="ms-2 small <?= $monthDeltaMin>=0?'text-success':'text-warning' ?>">(Δ <?= h(minutesToHM($monthDeltaMin)) ?>)</span>
              </div>
              <div class="progress" role="progressbar" aria-valuenow="<?= $monthPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= $monthPct ?>%"></div>
              </div>
            </div>
          </div>

        <div class="col-12">
          <div class="card glass">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h5 m-0">Année en cours (<?= h($today->format('Y')) ?>)</h2>
                <span class="badge text-bg-secondary">Cible <?= h(minutesToHM($annualTargetMin)) ?> h</span>
              </div>
              <div class="kpi h4">
                <?= h(minutesToHM($yearStats['net_min'])) ?> <small class="text-secondary">h travaillées</small>
                <span class="ms-2 small <?= $yearDeltaMin>=0?'text-success':'text-warning' ?>">(Δ <?= h(minutesToHM($yearDeltaMin)) ?>)</span>
              </div>
              <div class="progress" role="progressbar" aria-valuenow="<?= $yearPct ?>" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar" style="width: <?= $yearPct ?>%"></div>
              </div>
            </div>
          </div>
        </div>
        </div>

        <div class="col-12">
          <div class="card glass">
            <div class="card-body">
              <h2 class="h5 mb-3">Entrées<?= $where ? ' (filtrées)' : '' ?></h2>
              <div class="table-responsive" style="max-height: 420px;">
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
                    <?php if (!$entries): ?>
                      <tr><td colspan="7" class="text-center text-secondary">Aucune entrée</td></tr>
                    <?php else: foreach ($entries as $e): 
                      $durMin = durationMinutes($e['start_time'],$e['end_time']);
                      $isBreak = $e['type']==='break';
                    ?>
                      <tr>
                        <td><?= h((new DateTimeImmutable($e['date']))->format('d/m/Y')) ?></td>
                        <td><?= h($e['start_time']) ?></td>
                        <td><?= h($e['end_time']) ?></td>
                        <td><?= h(minutesToHM($durMin)) ?></td>
                        <td>
                          <?php if ($isBreak): ?>
                            <span class="badge text-bg-warning">Pause</span>
                          <?php else: ?>
                            <span class="badge text-bg-success">Travail</span>
                          <?php endif; ?>
                        </td>
                        <td><?= h($e['description']) ?></td>
                        <td class="text-end">
                          <div class="btn-group">
                            <button class="btn btn-sm btn-outline-light" onclick='prefill(<?= json_encode($e, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) ?>)'>Modifier</button>
                            <form method="post" onsubmit="return confirm('Supprimer cette entrée ?');">
                              <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
                              <input type="hidden" name="action" value="delete">
                              <input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
                              <button class="btn btn-sm btn-outline-danger">Supprimer</button>
                            </form>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <footer class="mt-4 text-center text-secondary small">
    <span>&copy; <?= date('Y') ?> — <?= h(APP_TITLE) ?> · Stockage: SQLite local</span>
  </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/script.js"></script>

</body>
</html>
