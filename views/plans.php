<?php
/** @var array<string,mixed> $user */
/** @var array<int, array<string,mixed>> $plans */
?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold mb-1">Тарифы</h1>
        <div class="text-muted">Выбирай, оплачивай с баланса — сервер создастся в панели.</div>
    </div>
    <a href="/topup" class="btn btn-outline-light"><i class="bi bi-wallet2 me-1"></i>Пополнить баланс</a>
</div>

<?php if (empty($plans)): ?>
    <div class="card card-glass rounded-4 border-0">
        <div class="card-body p-4">
            <div class="fw-semibold mb-1">Тарифов нет</div>
            <div class="text-muted">Запусти <code>php bin/seed_plans.php</code> и заполни параметры под свои Eggs/Locations.</div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($plans as $plan): ?>
            <?php
                $limits = json_decode((string)($plan['limits_json'] ?? '{}'), true);
                if (!is_array($limits)) { $limits = []; }
                $memory = (int)($limits['memory'] ?? 0);
                $disk = (int)($limits['disk'] ?? 0);
                $cpu = (int)($limits['cpu'] ?? 0);
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-glass rounded-4 border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div>
                                <div class="h5 fw-bold mb-1"><?= e((string)$plan['name']) ?></div>
                                <div class="text-muted small"><?= e((string)($plan['description'] ?? '')) ?></div>
                            </div>
                            <div class="badge badge-soft">ID #<?= (int)$plan['id'] ?></div>
                        </div>

                        <div class="my-3">
                            <div class="display-6 fw-bold mb-0"><?= format_rub((int)$plan['price_kopeks']) ?></div>
                            <div class="text-muted small">за создание сервера</div>
                        </div>

                        <div class="bg-soft rounded-4 p-3 small">
                            <div class="d-flex justify-content-between"><span class="text-muted">RAM</span><span class="fw-semibold"><?= $memory ?> MB</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Disk</span><span class="fw-semibold"><?= $disk ?> MB</span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">CPU</span><span class="fw-semibold"><?= $cpu ?>%</span></div>
                        </div>

                        <div class="mt-4 d-grid gap-2">
                            <a class="btn btn-primary btn-lg" href="/buy?plan_id=<?= (int)$plan['id'] ?>">
                                <i class="bi bi-cart3 me-1"></i>Купить и создать
                            </a>
                            <div class="text-muted small">Списываем с баланса: <?= format_rub((int)$plan['price_kopeks']) ?></div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
