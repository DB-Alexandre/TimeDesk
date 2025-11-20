<?php
use Helpers\Validator;

if (isset($flash) && is_array($flash)):
    $type = $flash['type'] ?? 'info';
    $message = $flash['message'] ?? '';
    
    $alertClass = match($type) {
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        default => 'alert-info'
    };
?>
    <div class="alert <?= $alertClass ?> alert-dismissible fade show" role="alert">
        <?= Validator::escape($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
