<?php
use Helpers\Validator;
use Core\Session;

// Variables attendues: $today, $defaultStartTime
?>
<div class="card glass">
    <div class="card-body">
        <h2 class="h5 mb-3">Ajouter / Modifier une entrée</h2>
        <form method="post" class="row g-2" id="entryForm" action="?action=create">
            <input type="hidden" name="csrf" value="<?= Validator::escape(Session::getCsrfToken()) ?>">
            <input type="hidden" name="id" value="" id="formId">

            <div class="col-6">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" name="date" required value="<?= $today->format('Y-m-d') ?>" id="dateInput">
            </div>
            <div class="col-3">
                <label class="form-label">Début</label>
                <input type="time" class="form-control" name="start_time" required value="<?= Validator::escape($defaultStartTime ?? '') ?>" id="startTimeInput">
            </div>
            <div class="col-3">
                <label class="form-label">Fin</label>
                <input type="time" class="form-control" name="end_time" required id="endTimeInput">
            </div>

            <div class="col-12">
                <label class="form-label">Type</label>
                <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="type" id="typeWork" value="work" checked>
                    <label class="btn btn-outline-success" for="typeWork">Travail</label>
                    <input type="radio" class="btn-check" name="type" id="typeBreak" value="break">
                    <label class="btn btn-outline-warning" for="typeBreak">Pause</label>
                    <input type="radio" class="btn-check" name="type" id="typeCourse" value="course">
                    <label class="btn btn-outline-info" for="typeCourse">Cours</label>
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="description" maxlength="<?= MAX_DESCRIPTION_LENGTH ?>" placeholder="Ex: Support client, réunion..." id="descriptionInput">
            </div>

            <div class="col-12 d-flex gap-2 mt-2">
                <button class="btn btn-primary" type="submit">Enregistrer</button>
                <button class="btn btn-outline-secondary" type="button" id="btnReset">
                    Réinitialiser
                </button>
            </div>
        </form>
    </div>
</div>
