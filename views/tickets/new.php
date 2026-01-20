<?php
/**
 * User: Create ticket
 *
 * @var array<string,mixed> $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
 */
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Новый тикет</h1>
    <div class="text-body-secondary">Опиши проблему — поддержка ответит как можно скорее.</div>
  </div>
  <div>
    <a href="/tickets" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>К списку</a>
  </div>
</div>

<div class="card card-glass">
  <div class="card-body p-4">
    <form method="post" action="/tickets/create" class="row g-3" data-no-loader>
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

      <div class="col-12">
        <label class="form-label">Тема</label>
        <input type="text" name="subject" class="form-control" maxlength="255" placeholder="Например: Не создаётся сервер" required>
      </div>

      <div class="col-12">
        <label class="form-label">Сообщение</label>
        <textarea name="message" class="form-control" rows="6" placeholder="Опиши проблему подробнее: что нажал, что ожидал, что получил..." required></textarea>
        <div class="form-text text-body-secondary">Не указывай пароль. Для проверки достаточно логов/ошибок.</div>
      </div>

      <div class="col-12">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-1"></i>Отправить
        </button>
      </div>
    </form>
  </div>
</div>
