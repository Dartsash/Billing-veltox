<?php
/** @var string $csrf */
/** @var array|null $flash */
/** @var string|null $turnstile_site_key */
$tsKey = (string)($turnstile_site_key ?? (getenv('TURNSTILE_SITE_KEY') ?: ''));
?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

<style>
  html, body { height: 100% !important; }
  body {
    overflow: hidden !important;
    margin: 0 !important;
    padding: 0 !important;
  }

  body > .alert,
  body > .container > .alert,
  body > .container-fluid > .alert,
  body > .toast-container,
  body > .flash,
  body > .flash-messages {
    display: none !important;
  }

  .auth-page{
    position: fixed;
    inset: 0;
    overflow: hidden;
    display: grid;
    place-items: center;
    padding: 18px;
    z-index: 50;
  }

  .auth-frame{
    max-height: calc(100vh - 36px);
    overflow: auto;                 
    -webkit-overflow-scrolling: touch;
  }

  .auth-wrap{ max-width: 980px; width: 100%; }
  .auth-frame{
    border-radius: 26px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
    box-shadow: 0 24px 80px rgba(0,0,0,.45);
    backdrop-filter: blur(14px);
  }

  .auth-hero{
    min-height: 520px;
    padding: 28px;
    background:
      radial-gradient(900px 480px at 20% 20%, rgba(124, 77, 255, .45), transparent 60%),
      radial-gradient(900px 480px at 90% 15%, rgba(0, 212, 255, .28), transparent 55%),
      radial-gradient(900px 480px at 60% 95%, rgba(34, 197, 94, .18), transparent 60%),
      rgba(0, 0, 0, .22);
    border-right: 1px solid rgba(255,255,255,.10);
  }

  .auth-badge{
    display:inline-flex; align-items:center; gap:10px;
    padding:10px 12px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(0,0,0,.18);
    color: rgba(234,240,255,.78);
    font-weight: 800;
    letter-spacing: .2px;
  }
  .auth-title{ margin-top:14px; font-size: 30px; font-weight: 900; letter-spacing: .2px; }
  .auth-sub{ margin-top:10px; color: rgba(234,240,255,.70); line-height: 1.55; }

  .auth-pills {
    margin-top: 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }

  .auth-pill {
    padding: 10px 12px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .10);
    background: rgba(255, 255, 255, .06);
    color: rgba(234, 240, 255, .75);
    font-weight: 800;
    font-size: 13.5px;
  }

  .auth-form{ padding: 28px; }
  .auth-form h1{ margin:0 0 8px; font-size: 22px; font-weight: 900; }
  .auth-form p{ margin:0 0 14px; color: rgba(234,240,255,.70); }

  .auth-btn{
    border: 1px solid rgba(124,77,255,.42);
    background: linear-gradient(135deg, rgba(124, 77, 255, .40), rgba(0, 212, 255, .18));
    color: #EAF0FF;
    border-radius: 16px;
    padding: 12px 14px;
    font-weight: 900;
    letter-spacing: .2px;
  }
  .auth-btn:hover{ filter: brightness(1.06); color: #fff; }
  .auth-btn:disabled{ opacity: .55; cursor: not-allowed; }

  .auth-link{ color:#EAF0FF; text-decoration:none; font-weight:900; }
  .auth-link:hover{ text-decoration:underline; }

  .auth-note{ color: rgba(234,240,255,.62); font-size: 14px; }

  .notice {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 12px 14px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(0,0,0,.18);
    color: #EAF0FF;
    position: relative;
  }

  .notice__icon {
    width: 36px;
    height: 36px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
  }

  .notice__title {
    font-weight: 900;
    line-height: 1.15;
    margin: 1px 0 2px;
  }

  .notice__text {
    color: rgba(234,240,255,.78);
    line-height: 1.35;
  }

  .notice__close {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 30px;
    height: 30px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
    color: rgba(234,240,255,.9);
    display: grid;
    place-items: center;
    cursor: pointer;
  }
  .notice__close:hover { filter: brightness(1.08); }

  .notice--danger {
    border-color: rgba(239, 68, 68, .35);
    background: linear-gradient(135deg, rgba(239,68,68,.14), rgba(0,0,0,.18));
  }
  .notice--danger .notice__icon{
    border-color: rgba(239,68,68,.35);
    background: rgba(239,68,68,.14);
  }

  .notice--success {
    border-color: rgba(34, 197, 94, .35);
    background: linear-gradient(135deg, rgba(34,197,94,.12), rgba(0,0,0,.18));
  }
  .notice--success .notice__icon{
    border-color: rgba(34,197,94,.35);
    background: rgba(34,197,94,.14);
  }

  .notice--info {
    border-color: rgba(124,77,255,.35);
    background: linear-gradient(135deg, rgba(124,77,255,.12), rgba(0,0,0,.18));
  }
  .notice--info .notice__icon{
    border-color: rgba(124,77,255,.35);
    background: rgba(124,77,255,.14);
  }

  .auth-form .form-label{
    color: rgba(234, 240, 255, 0.6);
    font-weight: 800;
    margin: 0 0 8px;
  }

  .auth-form .form-control {
    background-color: rgba(0, 0, 0, .25) !important;
    border: 1px solid rgba(255, 255, 255, .12) !important;
    color: #EAF0FF !important;
    border-radius: 16px !important;
    height: calc(3.5rem + 2px);
    font-weight: 500;
    padding: 1rem 1rem;
  }

  .auth-form .form-control::placeholder {
    color: rgba(234, 240, 255, 0.30) !important;
  }

  .auth-form .form-control:focus {
    background-color: rgba(0, 0, 0, .35) !important;
    border-color: rgba(124, 77, 255, 0.6) !important;
    box-shadow: 0 0 0 4px rgba(124, 77, 255, 0.15) !important;
  }

  input:-webkit-autofill,
  input:-webkit-autofill:hover,
  input:-webkit-autofill:focus,
  input:-webkit-autofill:active {
      -webkit-box-shadow: 0 0 0 1000px rgba(0,0,0,.25) inset !important;
      -webkit-text-fill-color: #EAF0FF !important;
      transition: background-color 5000s ease-in-out 0s;
      caret-color: #EAF0FF;
      border-radius: 16px !important;
  }

  @media (max-width: 991px){
    .auth-hero{ min-height: auto; border-right: 0; border-bottom: 1px solid rgba(255,255,255,.10); }
  }

  @media (min-width: 992px) {
    .auth-frame .row > .col-lg-6:first-child { display: flex; }
    .auth-hero { flex: 1 1 auto; height: 100%; }
  }

  @media (max-width: 1020px) {
    .auth-frame .row { flex-direction: column; }
    .auth-frame .col-lg-6 { width: 100%; }

    .auth-hero {
      min-height: auto;
      border-right: 0;
      border-bottom: 1px solid rgba(255,255,255,.10);
    }
  }

  .auth-check {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 14px;
    border-radius: 18px;
    border: 1px solid rgba(255,255,255,.10);
    background: rgba(255,255,255,.06);
    color: rgba(234,240,255,.78);
  }
  .auth-check .form-check-input{
    margin-top: 4px;
    width: 18px; height: 18px;
    border-radius: 6px;
    border: 1px solid rgba(255,255,255,.18);
    background-color: rgba(0,0,0,.25);
  }
  .auth-check .form-check-input:checked{
    background-color: rgba(124,77,255,.55);
    border-color: rgba(124,77,255,.65);
  }
  .auth-check a{ color:#EAF0FF; font-weight: 900; text-decoration: none; }
  .auth-check a:hover{ text-decoration: underline; }

  .alert { display: none !important; }
</style>

<div class="auth-page">
  <div class="auth-wrap">
    <div class="auth-frame">
      <div class="row g-0">
        <div class="col-lg-6">
          <div class="auth-hero">
            <div class="auth-badge">
              <i class="bi bi-sparkles"></i>
              <span>Создание аккаунта</span>
            </div>

            <div class="auth-title">Новый кабинет</div>
            <div class="auth-sub">
              Регистрация займёт минуту.
            </div>

            <div class="auth-pills">
              <div class="auth-pill"><i class="bi bi-check2-circle me-1"></i> Оплата и счета</div>
              <div class="auth-pill"><i class="bi bi-hdd-network me-1"></i> Баланс и тарифы</div>
              <div class="auth-pill"><i class="bi bi-shield-check me-1"></i> Поддержка</div>
            </div>

            <div class="mt-4 auth-note">
              <i class="bi bi-info-circle me-1"></i>
              Уже есть аккаунт? Нажми «Войти»
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="auth-form">
            <h1>Регистрация</h1>
            <p>Заполни поля</p>

            <?php if (!empty($flash) && is_array($flash)): ?>
              <?php foreach ($flash as $f): ?>
                <?php
                  $type = (string)($f['type'] ?? $f['level'] ?? 'info');
                  $msg  = (string)($f['message'] ?? $f['text'] ?? '');
                  if ($msg === '') continue;

                  $kind = 'info';
                  if (in_array($type, ['danger','error'], true)) $kind = 'danger';
                  elseif ($type === 'success') $kind = 'success';
                  elseif ($type === 'warning') $kind = 'danger';

                  $title = ($kind === 'danger') ? 'Ошибка' : (($kind === 'success') ? 'Успешно' : 'Сообщение');
                  $icon  = ($kind === 'danger') ? 'bi-exclamation-triangle' : (($kind === 'success') ? 'bi-check2-circle' : 'bi-info-circle');
                ?>

                <div class="notice notice--<?= e($kind) ?>" role="alert" data-notice>
                  <div class="notice__icon">
                    <i class="bi <?= e($icon) ?>"></i>
                  </div>

                  <div>
                    <div class="notice__title"><?= e($title) ?></div>
                    <div class="notice__text"><?= e($msg) ?></div>
                  </div>

                  <button class="notice__close" type="button" aria-label="Закрыть" data-dismiss-notice>
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" action="/register" class="d-grid gap-3">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

              <div>
                <label class="form-label" for="reg_user">Имя пользователя</label>
                <input
                  class="form-control"
                  type="text"
                  name="username"
                  id="reg_user"
                  placeholder="username"
                  autocomplete="username"
                  required
                >
                <div class="auth-note mt-1">3–32 символа: латиница/цифры/._-</div>
              </div>

              <div>
                <label class="form-label" for="reg_email">Адрес электронной почты</label>
                <input
                  class="form-control"
                  type="email"
                  name="email"
                  id="reg_email"
                  placeholder="you@example.com"
                  autocomplete="email"
                  inputmode="email"
                  required
                >
              </div>

              <div>
                <label class="form-label" for="reg_pass">Пароль</label>
                <input
                  class="form-control"
                  type="password"
                  name="password"
                  id="reg_pass"
                  placeholder="••••••••"
                  autocomplete="new-password"
                  required
                >
                <div class="auth-note mt-1">Минимум 8 символов.</div>
              </div>

              <div>
                <label class="form-label" for="reg_pass2">Подтвердите пароль</label>
                <input
                  class="form-control"
                  type="password"
                  name="password_confirm"
                  id="reg_pass2"
                  placeholder="••••••••"
                  autocomplete="new-password"
                  required
                >
              </div>

              <div class="auth-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="privacy_accept"
                  name="privacy_accept"
                  value="1"
                  required
                >
                <label for="privacy_accept" class="auth-note" style="margin:0;">
                  Я принимаю <a href="https://veltox.xyz/privacy/" target="_blank" rel="noopener">Политику конфиденциальности</a>
                </label>
              </div>

              <div class="d-flex justify-content-center">
                <div class="cf-turnstile" data-sitekey="<?= e($tsKey) ?>"></div>
              </div>

              <button id="reg_submit" class="auth-btn w-100" type="submit">
                <i class="bi bi-stars me-1"></i> Создать аккаунт
              </button>

              <div class="text-center auth-note">
                Уже есть аккаунт? <a class="auth-link" href="/login">Войти</a>
              </div>
            </form>

          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-dismiss-notice]');
    if (!btn) return;
    const notice = btn.closest('[data-notice]');
    if (notice) notice.remove();
  });

  (function () {
    const cb = document.getElementById('privacy_accept');
    const btn = document.getElementById('reg_submit');
    if (!cb || !btn) return;

    function sync() { btn.disabled = !cb.checked; }
    sync();
    cb.addEventListener('change', sync);
  })();
</script>
