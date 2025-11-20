<?php
use Helpers\Validator;
?>
<footer class="mt-4 py-4 border-top">
    <div class="container">
        <div class="text-center text-secondary small">
            &copy; <?= date('Y') ?> — <?= Validator::escape(APP_TITLE) ?> v<?= Validator::escape(APP_VERSION) ?>
            · Stockage SQLite local
        </div>
    </div>
</footer>
