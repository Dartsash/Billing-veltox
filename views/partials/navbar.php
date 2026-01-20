<?php
/** @var array<string,mixed>|null $user */
/** @var string|null $csrf */

$p = path();

$isActive = static function(string $href) use ($p): string {
    return $p === $href ? 'active' : '';
};
?>
<nav class="navbar navbar-expand-lg navbar-glass navbar-dark rounded-4 px-3 py-2">
    <div class="container-fluid px-0">
        <a class="navbar-brand fw-bold" href="/">
            <i class="bi bi-lightning-charge-fill me-1"></i>
            <span class="brand-grad">Billing</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link <?= e($isActive('/dashboard')) ?>" href="/dashboard"><i class="bi bi-grid-1x2 me-1"></i>Панель</a></li>
                    <li class="nav-item"><a class="nav-link <?= e($isActive('/plans')) ?>" href="/plans"><i class="bi bi-boxes me-1"></i>Тарифы</a></li>
                    <li class="nav-item"><a class="nav-link <?= e($isActive('/servers')) ?>" href="/servers"><i class="bi bi-hdd-stack me-1"></i>Серверы</a></li>
                    <li class="nav-item"><a class="nav-link <?= e($isActive('/topup')) ?>" href="/topup"><i class="bi bi-wallet2 me-1"></i>Баланс</a></li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($user): ?>
                    <div class="text-end me-1">
                        <div class="fw-semibold small">@<?= e((string)$user['username']) ?></div>
                        <div class="text-muted small">Баланс: <span class="fw-semibold"><?= format_rub((int)$user['balance_kopeks']) ?></span></div>
                    </div>
                    <form method="post" action="/logout" class="m-0">
                        <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
                        <button class="btn btn-sm btn-outline-light" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Выйти</button>
                    </form>
                <?php else: ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
