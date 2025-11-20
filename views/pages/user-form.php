<?php
use Helpers\Validator;
use Helpers\Auth;

$isEdit = isset($user) && $user !== null;
$pageTitle = $isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur';
?>
<div class="row">
    <div class="col-12 col-md-8 col-lg-6 mx-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><?= $pageTitle ?></h2>
            <a href="?action=users" class="btn btn-outline-secondary">
                ← Retour
            </a>
        </div>

        <?php if (isset($flash)): ?>
            <?php require VIEWS_PATH . '/partials/flash.php'; ?>
        <?php endif; ?>

        <div class="card glass">
            <div class="card-body">
                <form method="post" action="?action=<?= $isEdit ? 'user-edit' : 'user-create' ?>">
                    <input type="hidden" name="csrf" value="<?= Validator::csrfToken() ?>">
                    
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="username" class="form-label">
                            Nom d'utilisateur <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?= Validator::escape($user['username'] ?? '') ?>" 
                               required
                               minlength="3"
                               pattern="[a-zA-Z0-9_]+"
                               title="Lettres, chiffres et underscores uniquement">
                        <div class="form-text">
                            Minimum 3 caractères. Lettres, chiffres et underscores uniquement.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= Validator::escape($user['email'] ?? '') ?>">
                        <div class="form-text">Optionnel</div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            Mot de passe <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               <?= $isEdit ? '' : 'required' ?>
                               minlength="6">
                        <div class="form-text">
                            <?php if ($isEdit): ?>
                                Laisser vide pour ne pas modifier le mot de passe.
                            <?php endif; ?>
                            Minimum 6 caractères.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Rôle</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="user" <?= ($user['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>
                                Utilisateur
                            </option>
                            <option value="admin" <?= ($user['role'] ?? 'user') === 'admin' ? 'selected' : '' ?>>
                                Administrateur
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Compte actif
                            </label>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $isEdit ? 'Mettre à jour' : 'Créer l\'utilisateur' ?>
                        </button>
                        <a href="?action=users" class="btn btn-outline-secondary">
                            Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

