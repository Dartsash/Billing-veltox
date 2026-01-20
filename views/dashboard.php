<?php
/**
 * @var array<string,mixed> $user
 * @var array<int, array<string,mixed>> $transactions
 * @var array<int, array<string,mixed>> $servers
 */

$balance = (int)($user['balance_kopeks'] ?? 0);
$serverCount = is_array($servers) ? count($servers) : 0;

$lastTx = null;
if (!empty($transactions) && is_array($transactions)) {
    $lastTx = $transactions[0] ?? null;
}

$txLabel = static function(?string $type): string {
    return match ($type) {
        'topup_demo' => 'Пополнение (demo)',
        'purchase_server' => 'Покупка сервера',
        default => $type ? ('Операция: ' . $type) : 'Операция',
    };
};

$txAmountClass = static function(int $amount): string {
    return $amount < 0 ? 'text-warning' : 'text-success';
};
?>

<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Панель управления</h1>
        <div class="text-muted">Привет, <span class="fw-semibold">@<?= e((string)($user['username'] ?? '')) ?></span>. Быстрый обзор аккаунта.</div>
    </div>

    <div class="d-flex gap-2">
        <a href="/plans" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Новый сервер</a>
        <a href="/topup" class="btn btn-outline-light"><i class="bi bi-wallet2 me-1"></i>Пополнить</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-muted small">Баланс</div>
                        <div class="h3 fw-bold mb-0"><?= format_rub($balance) ?></div>
                    </div>
                    <div class="icon-bubble"><i class="bi bi-wallet2"></i></div>
                </div>
                <div class="text-muted small mt-3">Пополни и покупай тарифы.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-muted small">Серверы</div>
                        <div class="h3 fw-bold mb-0"><?= (int)$serverCount ?></div>
                    </div>
                    <div class="icon-bubble"><i class="bi bi-hdd-stack"></i></div>
                </div>
                <div class="text-muted small mt-3">Все созданные через биллинг.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div class="min-w-0 flex-grow-1">
                        <div class="text-muted small">Email</div>
                        <?php $email = (string)($user['email'] ?? ''); ?>
                        <div class="fw-bold value-break" style="font-size: 18px; line-height: 1.2;" title="<?= e($email) ?>">
                            <?= e($email) ?>
                        </div>
                    </div>
                    <div class="icon-bubble"><i class="bi bi-envelope"></i></div>
                </div>
                <div class="text-muted small mt-3">Данные аккаунта.</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-muted small">Последняя операция</div>
                        <?php if (is_array($lastTx)):
                            $amount = (int)($lastTx['amount_kopeks'] ?? 0);
                        ?>
                            <div class="fw-bold mb-1" style="font-size: 18px; line-height: 1.2;">
                                <?= e($txLabel((string)($lastTx['type'] ?? ''))) ?>
                            </div>
                            <div class="<?= e($txAmountClass($amount)) ?> fw-bold">
                                <?= $amount < 0 ? '-' : '+' ?><?= format_rub(abs($amount)) ?>
                            </div>
                        <?php else: ?>
                            <div class="fw-semibold">Пока нет</div>
                            <div class="text-muted small">Сделай demo-пополнение или покупку тарифа.</div>
                        <?php endif; ?>
                    </div>
                    <div class="icon-bubble"><i class="bi bi-receipt"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12 col-lg-7">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-bubble"><i class="bi bi-hdd-stack"></i></div>
                        <h2 class="h5 mb-0 fw-bold">Последние серверы</h2>
                    </div>
                    <a class="btn btn-sm btn-outline-light" href="/servers"><i class="bi bi-arrow-right me-1"></i>Все серверы</a>
                </div>

                <?php
                    $svStatusMeta = static function(?string $status): array {
                        $s = strtolower(trim((string)$status));
                        return match ($s) {
                            'frozen' => ['label' => 'Заморожен', 'class' => 'warning'],
                            'creating' => ['label' => 'Создаётся', 'class' => 'info'],
                            default => ['label' => 'Активен', 'class' => 'success'],
                        };
                    };
                ?>

                <?php if (empty($servers)): ?>
                    <div class="text-muted">У тебя пока нет серверов.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mt-3">
                        <?php foreach (array_slice($servers, 0, 5) as $s): ?>
                            <?php
                                $name = (string)($s['name'] ?? '');
                                $planName = (string)($s['plan_name'] ?? '');
                                $created = (string)($s['created_at'] ?? '');
                                $meta = $svStatusMeta((string)($s['status'] ?? 'active'));
                            ?>
                            <div class="tx-item">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="fw-semibold text-truncate"><?= e($name) ?></div>
                                        <span class="badge text-bg-<?= e((string)$meta['class']) ?>"><?= e((string)$meta['label']) ?></span>
                                    </div>
                                    <div class="text-muted small text-truncate"><?= e($planName) ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="text-muted small"><?= e($created !== '' ? $created : '-') ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="icon-bubble"><i class="bi bi-arrow-left-right"></i></div>
                        <h2 class="h5 mb-0 fw-bold">Операции</h2>
                    </div>
                    <span class="badge badge-soft">последние 10</span>
                </div>

                <?php if (empty($transactions)): ?>
                    <div class="text-muted">Операций пока нет.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-2 mt-3">
                        <?php foreach ($transactions as $t): ?>
                            <?php
                                $amount = (int)($t['amount_kopeks'] ?? 0);
                                $type = (string)($t['type'] ?? '');
                                $when = (string)($t['created_at'] ?? '');
                            ?>
                            <div class="tx-item">
                                <div>
                                    <div class="fw-semibold"><?= e($txLabel($type)) ?></div>
                                    <div class="text-muted small"><?= e($when) ?></div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold <?= e($txAmountClass($amount)) ?>">
                                        <?= $amount < 0 ? '-' : '+' ?><?= format_rub(abs($amount)) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
