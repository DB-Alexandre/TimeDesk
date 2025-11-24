<?php
use Helpers\Validator;
use Helpers\Auth;
?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h2 class="h4 mb-0">📥 Importer des données</h2>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Importez vos données depuis un fichier CSV. Le fichier doit contenir les colonnes suivantes :
                    <strong>Date</strong>, <strong>Début</strong>, <strong>Fin</strong>, <strong>Type</strong> (optionnel), <strong>Description</strong> (optionnel).
                </p>

                <form method="POST" enctype="multipart/form-data" action="?action=import">
                    <?= Validator::csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="file" class="form-label">Fichier CSV</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".csv" required>
                        <div class="form-text">Format attendu : CSV avec séparateur point-virgule (;)</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            📥 Importer
                        </button>
                        <a href="/" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>

                <hr class="my-4">

                <div class="alert alert-info">
                    <strong>Format CSV attendu :</strong><br>
                    <code>Date;Début;Fin;Type;Description</code><br>
                    <code>2024-11-24;09:00;12:00;work;Tâche importante</code>
                </div>
            </div>
        </div>
    </div>
</div>

