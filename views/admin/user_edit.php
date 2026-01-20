<?php
/**
 *
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
 * @var array<string,mixed> $target_user
 */

$u = $target_user ?? [];
$id = (int)($u['id'] ?? 0);
$username = (string)($u['username'] ?? '');
$email = (string)($u['email'] ?? '');
$balanceKopeks = (int)($u['balance_kopeks'] ?? 0);
$balanceRub = number_format($balanceKopeks / 100, 2, ',', ' ');
$role = (string)($u['role'] ?? 'member');
if ($role === '') { $role = 'member'; }

$roleLabel = static function(string $r): string {
    return match ($r) {
        'admin' => 'Администратор',
        default => 'Пользователь',
    };
};

$pteroId = (int)($u['ptero_user_id'] ?? 0);
$created = (string)($u['created_at'] ?? '');
$updated = (string)($u['updated_at'] ?? '');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h1 class="h4 fw-bold m-0">Редактирование пользователя</h1>
      <span class="badge badge-soft">#<?= e((string)$id) ?></span>
      <span class="badge text-bg-secondary"><?= e($role) ?></span>
    </div>
    <div class="text-body-secondary">Изменения email/username будут синхронизированы с Pterodactyl.</div>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/users" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>К списку</a>
  </div>
</div>

<div class="row g-4">
  <div class="col-12 col-lg-7">
    <div class="card card-glass rounded-4 border-0">
      <div class="card-body p-4">
        <form method="post" action="/admin/users/update" class="row g-3">
          <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
          <input type="hidden" name="user_id" value="<?= e((string)$id) ?>">

          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between gap-2">
              <div>
                <div class="fw-bold">Основные данные</div>
                <div class="text-body-secondary small">Аккуратно: email и username должны быть уникальными.</div>
              </div>
              <div class="icon-bubble"><i class="bi bi-person-gear"></i></div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Username</label>
            <input
              type="text"
              name="username"
              class="form-control"
              value="<?= e($username) ?>"
              minlength="3"
              maxlength="32"
              required
            >
            <div class="form-text text-body-secondary">3–32 символа: латиница/цифры/._-</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Адрес электронной почты</label>
            <input
              type="email"
              name="email"
              class="form-control"
              value="<?= e($email) ?>"
              required
            >
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Баланс (₽)</label>
            <input
              type="text"
              name="balance_rub"
              class="form-control"
              value="<?= e($balanceRub) ?>"
              placeholder="0"
              inputmode="decimal"
            >
            <div class="form-text text-body-secondary">Можно: 1000, 1000.50, 1 000,50</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Должность (роль)</label>
            <select name="role" class="form-select">
              <option value="member" <?= $role === 'member' ? 'selected' : '' ?>>Member — обычный пользователь</option>
              <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin — доступ к админке</option>
            </select>
            <div class="form-text text-body-secondary">
              Роль влияет только на биллинг (Pterodactyl root_admin не меняем).
            </div>
          </div>

          <div class="col-12">
            <div class="d-flex flex-wrap gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i>Сохранить
              </button>
              <a href="/admin/users" class="btn btn-outline-light">Отмена</a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-5">
    <div class="card card-glass rounded-4 border-0">
      <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <div class="icon-bubble"><i class="bi bi-info-circle"></i></div>
            <div>
              <div class="fw-bold">Информация</div>
              <div class="text-body-secondary small">Технические данные пользователя</div>
            </div>
          </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-3">
          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Никнейм</div>
            <div class="fw-semibold">@<?= e($username) ?></div>
          </div>

          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Почта</div>
            <div class="fw-semibold text-break"><?= e($email) ?></div>
          </div>

          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Роль</div>
            <div class="fw-semibold"><?= e($roleLabel($role)) ?> <span class="badge text-bg-secondary ms-2"><?= e($role) ?></span></div>
          </div>

          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Pterodactyl user_id</div>
            <div class="fw-semibold"><?= $pteroId > 0 ? '#' . e((string)$pteroId) : '<span class="text-body-secondary">не привязан</span>' ?></div>
          </div>

          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Создан</div>
            <div class="fw-semibold text-body-secondary small mb-0"><?= e($created !== '' ? $created : '-') ?></div>
          </div>

          <div class="p-3 rounded-4 bg-soft">
            <div class="text-body-secondary small">Обновлён</div>
            <div class="fw-semibold text-body-secondary small mb-0"><?= e($updated !== '' ? $updated : '-') ?></div>
          </div>
        </div>

        <div class="text-body-secondary small mt-3">
          Подсказка: если пользователь потерял доступ — он может сменить пароль через профиль.
        </div>
      </div>
    </div>
  </div>
</div>
