<?php
use Helpers\Validator;
use Helpers\TimeHelper;
?>

<div class="row g-3">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h2>Audit de sécurité</h2>
            <p class="text-muted mb-0">Surveillez les connexions et les demandes sensibles.</p>
        </div>
        <a href="/" class="btn btn-outline-secondary">← Retour</a>
    </div>

    <?php if (isset($flash) && is_array($flash)): ?>
        <div class="col-12">
            <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        </div>
    <?php endif; ?>

    <div class="col-12 col-lg-6">
        <div class="card glass h-100">
            <div class="card-body">
                <h3 class="h5 mb-3">Tentatives de connexion</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>IP</th>
                                <th>Statut</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($loginAttempts)): ?>
                                <tr><td colspan="4" class="text-center text-muted">Aucune donnée</td></tr>
                            <?php else: ?>
                                <?php foreach ($loginAttempts as $attempt): ?>
                                    <tr>
                                        <td><?= Validator::escape($attempt['username']) ?></td>
                                        <td><?= Validator::escape($attempt['ip_address'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge <?= $attempt['success'] ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $attempt['success'] ? 'Succès' : 'Échec' ?>
                                            </span>
                                        </td>
                                        <td><?= Validator::escape($attempt['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card glass h-100">
            <div class="card-body">
                <h3 class="h5 mb-3">Demandes de réinitialisation</h3>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Demandé</th>
                                <th>Expiration</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($passwordResets)): ?>
                                <tr><td colspan="5" class="text-center text-muted">Aucune donnée</td></tr>
                            <?php else: ?>
                                <?php foreach ($passwordResets as $reset): ?>
                                    <tr>
                                        <td><?= Validator::escape($reset['username']) ?></td>
                                        <td><?= Validator::escape($reset['email'] ?? '-') ?></td>
                                        <td><?= Validator::escape($reset['created_at']) ?></td>
                                        <td><?= Validator::escape($reset['expires_at']) ?></td>
                                        <td>
                                            <?php if ($reset['used_at']): ?>
                                                <span class="badge bg-success">Utilisé</span>
                                            <?php elseif ($reset['expires_at'] < date('Y-m-d H:i:s')): ?>
                                                <span class="badge bg-secondary">Expiré</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Actif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card glass">
            <div class="card-body">
                <h3 class="h5 mb-3">Logs récents</h3>
                <pre class="bg-dark text-light p-3 rounded small" style="max-height:300px; overflow:auto;"><?php
foreach ($fileLogs as $line) {
    echo Validator::escape($line) . "\n";
}
?></pre>
            </div>
        </div>
    </div>
</div>

