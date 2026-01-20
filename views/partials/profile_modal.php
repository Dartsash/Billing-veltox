<?php
/**
 * Profile modal.
 *
 * @var array<string,mixed>|null $user
 * @var string|null $csrf
 */

$u = $user ?? [];

$initial = '';
if (!empty($u) && isset($u['username'])) {
    $name = (string)$u['username'];
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
}

$isAdmin = (function_exists('is_admin') && !empty($u) && is_admin($u));
$role = $isAdmin ? 'admin' : 'member';
$roleLabel = $isAdmin ? 'Администратор' : 'Member';
$balance = format_rub((int)($u['balance_kopeks'] ?? 0));
?>

<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content card-glass rounded-4 border-0">
      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div class="b-logo" style="width:56px;height:56px;border-radius:18px;">
            <?= $initial !== '' ? e($initial) : 'V' ?>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 18px" id="profileModalLabel">Профиль</div>
            <div class="text-body-secondary small">Аккаунт и безопасность</div>
          </div>
        </div>

        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pt-3">

        <div class="card card-glass rounded-4 border-0 mb-3">
          <div class="card-body p-4">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <div class="text-body-secondary small mb-1">Никнейм</div>
                <div class="fw-semibold" style="font-size: 18px">@<?= e((string)($u['username'] ?? '')) ?></div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-body-secondary small mb-1">Почта</div>
                <?php $email = (string)($u['email'] ?? ''); ?>
                <div class="fw-semibold value-break" style="font-size: 18px" title="<?= e($email) ?>"><?= e($email) ?></div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-body-secondary small mb-1">Баланс</div>
                <div class="fw-bold" style="font-size: 18px"><?= e($balance) ?></div>
              </div>
              <div class="col-12 col-md-6">
                <div class="text-body-secondary small mb-1">Роль</div>
                <div class="fw-bold" style="font-size: 18px">
                  <?= e($roleLabel) ?>
                  <span class="badge text-bg-secondary ms-2"><?= e($role) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card card-glass rounded-4 border-0 mb-3">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-bold">Изменить пароль</div>
                <div class="text-body-secondary small">Пароль изменится и в биллинге, и в Pterodactyl.</div>
              </div>
              <i class="bi bi-shield-lock"></i>
            </div>

            <form method="post" action="/profile/password" class="row g-3 mt-1">
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">

              <div class="col-12 col-md-4">
                <label class="form-label">Текущий пароль</label>
                <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="8" required>
              </div>
              <div class="col-12 col-md-4">
                <label class="form-label">Подтверждение</label>
                <input type="password" name="new_password_confirm" class="form-control" autocomplete="new-password" minlength="8" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check2-circle me-1"></i>Сохранить
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="card card-glass rounded-4 border-0">
          <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
              <div>
                <div class="fw-bold text-danger">Опасная зона</div>
                <div class="text-body-secondary small">Удаление аккаунта нельзя отменить.</div>
              </div>
              <i class="bi bi-exclamation-triangle"></i>
            </div>

            <form method="post" action="/profile/delete" class="mt-3" data-delete-account-form>
              <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
              <button type="submit" class="btn btn-outline-danger">
                <i class="bi bi-trash me-1"></i>Удалить аккаунт
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const form = document.querySelector('form[data-delete-account-form]');
    if (!form) return;
    form.addEventListener('submit', function (e) {
      const ok = confirm('Удалить ваш аккаунт?\n\nАккаунт будет удалён в биллинге и в Pterodactyl. Действие нельзя отменить.');
      if (!ok) {
        e.preventDefault();
        e.stopPropagation();
      }
    });
  })();
</script>
