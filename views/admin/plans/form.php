<?php
/**
 * Admin: Plan form (create/edit)
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
 * @var array<string,mixed>|null $plan
 */

$isEdit = is_array($plan);
$id = $isEdit ? (int)($plan['id'] ?? 0) : 0;
$title = $isEdit ? 'Редактирование тарифа' : 'Новый тариф';
$action = $isEdit ? '/admin/plans/update' : '/admin/plans/create';

$val = static function(?array $plan, string $key, string $default = ''): string {
    if (!is_array($plan) || !array_key_exists($key, $plan) || $plan[$key] === null) {
        return $default;
    }
    return (string)$plan[$key];
};

$priceRub = '';
if ($isEdit) {
    $kopeks = (int)($plan['price_kopeks'] ?? 0);
    $priceRub = number_format($kopeks / 100, 2, '.', '');
}

$prettyJson = static function(string $raw, string $fallback): string {
    $s = trim($raw);
    if ($s === '') {
        $s = $fallback;
    }
    $decoded = json_decode($s, true);
    if ($decoded === null && $s !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
        // keep as-is if invalid
        return $s;
    }
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $s;
};

$envJson = $prettyJson($val($plan, 'environment_json', '{}'), '{}');
$limitsJson = $prettyJson($val($plan, 'limits_json', '{}'), '{}');
$featuresJson = $prettyJson($val($plan, 'feature_limits_json', '{}'), '{}');
$deployJson = $prettyJson($val($plan, 'deploy_locations_json', '[]'), '[]');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0"><?= e($title) ?></h1>
    <div class="text-body-secondary">Egg/лимиты/окружение — это прямые JSON поля Pterodactyl.</div>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/plans" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>К списку</a>
  </div>
</div>

<div class="card card-glass">
  <div class="card-body p-4">
    <form method="post" action="<?= e($action) ?>" class="row g-3">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e((string)$id) ?>">
      <?php endif; ?>

      <div class="col-12 col-lg-6">
        <label class="form-label">Название</label>
        <input type="text" name="name" class="form-control" value="<?= e($val($plan, 'name')) ?>" required>
      </div>

      <div class="col-12 col-lg-3">
        <label class="form-label">Цена (₽)</label>
        <input type="text" name="price_rub" class="form-control" placeholder="100" value="<?= e($priceRub) ?>" required>
        <div class="form-text text-body-secondary">Можно с копейками: 99.99</div>
      </div>

      <div class="col-12 col-lg-3">
        <label class="form-label">Ptero Egg ID</label>
        <input type="number" name="ptero_egg_id" class="form-control" value="<?= e($val($plan, 'ptero_egg_id')) ?>" required>
      </div>

      <div class="col-12">
        <label class="form-label">Описание (опционально)</label>
        <textarea name="description" class="form-control" rows="3" placeholder="Описание тарифа..."><?= e($val($plan, 'description')) ?></textarea>
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Docker image (опционально)</label>
        <input type="text" name="docker_image" class="form-control" value="<?= e($val($plan, 'docker_image')) ?>" placeholder="ghcr.io/...">
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Startup (опционально)</label>
        <input type="text" name="startup" class="form-control" value="<?= e($val($plan, 'startup')) ?>" placeholder="java -Xms...">
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Environment JSON</label>
        <textarea name="environment_json" class="form-control" rows="10" spellcheck="false"><?= e($envJson) ?></textarea>
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Limits JSON</label>
        <textarea name="limits_json" class="form-control" rows="10" spellcheck="false"><?= e($limitsJson) ?></textarea>
        <div class="form-text text-body-secondary">Например: memory, swap, disk, io, cpu</div>
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Feature limits JSON</label>
        <textarea name="feature_limits_json" class="form-control" rows="10" spellcheck="false"><?= e($featuresJson) ?></textarea>
        <div class="form-text text-body-secondary">Например: databases, allocations, backups</div>
      </div>

      <div class="col-12 col-lg-6">
        <label class="form-label">Deploy locations JSON</label>
        <textarea name="deploy_locations_json" class="form-control" rows="10" spellcheck="false"><?= e($deployJson) ?></textarea>
        <div class="form-text text-body-secondary">Обычно массив ID локаций: [1,2]</div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check2-circle me-1"></i>Сохранить
        </button>
        <a href="/admin/plans" class="btn btn-outline-light ms-2">Отмена</a>
      </div>
    </form>
  </div>
</div>
