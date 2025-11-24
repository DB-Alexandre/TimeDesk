<?php
use Helpers\Validator;
use Helpers\Auth;
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h2 class="h4 mb-0">📊 Créer un rapport personnalisé</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="?action=report-create">
                    <?= Validator::csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Titre du rapport</label>
                        <input type="text" class="form-control" id="title" name="title" value="Rapport TimeDesk" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_from" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_from" name="date_from">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_to" class="form-label">Date de fin</label>
                            <input type="date" class="form-control" id="date_to" name="date_to">
                        </div>
                    </div>

                    <?php if (Auth::isAdmin() && !empty($usersList)): ?>
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Utilisateur</label>
                            <select class="form-select" id="user_id" name="user_id">
                                <option value="">Tous les utilisateurs</option>
                                <?php foreach ($usersList as $user): ?>
                                    <option value="<?= $user['id'] ?>">
                                        <?= Validator::escape($user['username']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="format" class="form-label">Format</label>
                        <select class="form-select" id="format" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_stats" name="include_stats" checked>
                            <label class="form-check-label" for="include_stats">
                                Inclure les statistiques
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="include_chart" name="include_chart">
                            <label class="form-check-label" for="include_chart">
                                Inclure un graphique (PDF uniquement)
                            </label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            📊 Générer le rapport
                        </button>
                        <a href="/" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

