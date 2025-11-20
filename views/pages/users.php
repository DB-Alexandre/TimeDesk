<?php
use Helpers\Validator;
use Helpers\Auth;
?>
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestion des utilisateurs</h2>
            <a href="?action=user-create" class="btn btn-primary">
                ➕ Nouvel utilisateur
            </a>
        </div>

        <?php if (isset($flash)): ?>
            <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        <?php endif; ?>

        <div class="card glass">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        Aucun utilisateur trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= Validator::escape($user['id']) ?></td>
                                        <td>
                                            <strong><?= Validator::escape($user['username']) ?></strong>
                                        </td>
                                        <td><?= Validator::escape($user['email'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-primary">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Utilisateur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['last_login']): ?>
                                                <?= Validator::escape(date('d/m/Y H:i', strtotime($user['last_login']))) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Jamais</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="?action=user-stats&id=<?= $user['id'] ?>" 
                                                   class="btn btn-outline-info"
                                                   title="Voir les statistiques détaillées">
                                                    📊 Stats
                                                </a>
                                                <a href="?action=user-edit&id=<?= $user['id'] ?>" 
                                                   class="btn btn-outline-primary">
                                                    ✏️ Modifier
                                                </a>
                                                <?php if ($user['id'] != Auth::getUserId()): ?>
                                                    <form method="post" 
                                                          action="?action=user-delete" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">
                                                        <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger">
                                                            🗑️ Supprimer
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

        <div class="mt-3">
            <a href="/" class="btn btn-outline-secondary">
                ← Retour au tableau de bord
            </a>
        </div>
    </div>
</div>

