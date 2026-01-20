<?php
/** @var array<string,mixed> $user */
/** @var array<string,mixed> $plan */
/** @var string $csrf */
$limits = json_decode((string)($plan['limits_json'] ?? '{}'), true);
if (!is_array($limits)) { $limits = []; }
$memory = (int)($limits['memory'] ?? 0);
$disk = (int)($limits['disk'] ?? 0);
$cpu = (int)($limits['cpu'] ?? 0);
?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="text-muted small">Тариф</div>
                        <div class="h4 fw-bold mb-1"><?= e((string)$plan['name']) ?></div>
                        <div class="text-muted"><?= e((string)($plan['description'] ?? '')) ?></div>
                    </div>
                    <div class="badge badge-soft">#<?= (int)$plan['id'] ?></div>
                </div>

                <hr class="border-light opacity-25 my-4">

                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small">Цена</div>
                        <div class="display-6 fw-bold mb-0"><?= format_rub((int)$plan['price_kopeks']) ?></div>
                    </div>
                    <div class="icon-bubble icon-bubble-lg"><i class="bi bi-cart-check"></i></div>
                </div>

                <div class="bg-soft rounded-4 p-3 small mt-4">
                    <div class="d-flex justify-content-between"><span class="text-muted">RAM</span><span class="fw-semibold"><?= $memory ?> MB</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">Disk</span><span class="fw-semibold"><?= $disk ?> MB</span></div>
                    <div class="d-flex justify-content-between"><span class="text-muted">CPU</span><span class="fw-semibold"><?= $cpu ?>%</span></div>
                </div>

                <div class="text-muted small mt-3">Важно: параметры Egg/Environment/Locations задаются в <code>plans</code> (seed).</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-7">
        <div class="card card-glass rounded-4 border-0">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <div class="icon-bubble"><i class="bi bi-hdd"></i></div>
                    <h1 class="h4 mb-0 fw-bold">Создать сервер</h1>
                </div>
                <p class="text-muted mb-4">Оплата спишется с баланса. Потом сервер появится в Pterodactyl.</p>

                <form method="post" action="/buy" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                    <?php if (!empty($location_id)): ?>
                        <input type="hidden" name="location_id" value="<?= (int)$location_id ?>">
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label">Имя сервера</label>
                        <input class="form-control form-control-lg" name="server_name" required placeholder="My Minecraft Server" value="<?= e((string)($server_name ?? '')) ?>">
                        <div class="form-text">Это имя будет видно в Pterodactyl.</div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 rounded-4 bg-soft">
                            <div class="d-flex justify-content-between"><span class="text-muted">Твой баланс</span><span class="fw-semibold"><?= format_rub((int)$user['balance_kopeks']) ?></span></div>
                            <div class="d-flex justify-content-between"><span class="text-muted">Списание</span><span class="fw-semibold text-warning">-<?= format_rub((int)$plan['price_kopeks']) ?></span></div>
                        </div>
                    </div>

                    <div class="col-12 d-grid mt-2">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="bi bi-lightning-charge me-1"></i>
                            Оплатить и создать
                        </button>
                    </div>

                    <div class="col-12">
                        <a href="/plans" class="btn btn-outline-light w-100"><i class="bi bi-arrow-left me-1"></i>Назад к тарифам</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
