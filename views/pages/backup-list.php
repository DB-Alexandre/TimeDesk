<?php
use Helpers\Validator;
use Helpers\TimeFormatter;
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 mb-0">💾 Sauvegardes</h2>
            <a href="?action=backup-create" class="btn btn-primary">
                ➕ Créer une sauvegarde
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <div class="alert alert-info">
                        Aucune sauvegarde disponible. Créez-en une pour commencer.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fichier</th>
                                    <th>Taille</th>
                                    <th>Date de création</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $backup): ?>
                                    <tr>
                                        <td>
                                            <code><?= Validator::escape($backup['file']) ?></code>
                                        </td>
                                        <td>
                                            <?php
                                            $size = $backup['size'];
                                            $units = ['B', 'KB', 'MB', 'GB'];
                                            $unitIndex = 0;
                                            while ($size >= 1024 && $unitIndex < count($units) - 1) {
                                                $size /= 1024;
                                                $unitIndex++;
                                            }
                                            echo number_format($size, 2) . ' ' . $units[$unitIndex];
                                            ?>
                                        </td>
                                        <td>
                                            <?= Validator::escape($backup['date']) ?>
                                        </td>
                                        <td>
                                            <a href="?action=backup-download&file=<?= urlencode($backup['file']) ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                📥 Télécharger
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="alert alert-warning mt-4">
            <strong>⚠️ Important :</strong> Les sauvegardes sont stockées localement. 
            Pensez à les télécharger régulièrement et à les stocker dans un endroit sûr.
        </div>
    </div>
</div>

