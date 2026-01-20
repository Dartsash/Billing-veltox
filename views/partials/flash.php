<?php
/** @var array<int, array{type:string,message:string}>|null $flash */
?>
<?php if (!empty($flash)): ?>
    <div class="d-flex flex-column gap-2 mb-3">
        <?php foreach ($flash as $item): ?>
            <?php
                $type = $item['type'] ?? 'info';
                $msg = $item['message'] ?? '';
            ?>
            <div class="alert alert-<?= e($type) ?> alert-glass mb-0" role="alert">
                <?= e($msg) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
