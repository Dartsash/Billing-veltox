<?php
/**
 *
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var array<string,mixed> $ticket
 * @var array<string,mixed>|null $owner
 * @var array<int, array<string,mixed>> $messages
 */

$id = (int)($ticket['id'] ?? 0);
$subject = (string)($ticket['subject'] ?? '');
$status = (string)($ticket['status'] ?? 'open');

$statusBadge = match ($status) {
    'open' => 'success',
    'closed' => 'secondary',
    default => 'secondary',
};

$statusLabel = match ($status) {
    'open' => 'Открыт',
    'closed' => 'Закрыт',
    default => $status,
};

$ownerName = (string)($owner['username'] ?? $ticket['user_username'] ?? '');
$ownerEmail = (string)($owner['email'] ?? $ticket['user_email'] ?? '');
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Тикет #<?= e((string)$id) ?> — <?= e($subject) ?></h1>
    <div class="text-body-secondary">
      Пользователь: <span class="fw-semibold">@<?= e($ownerName) ?></span> • <?= e($ownerEmail) ?>
      <span class="mx-2">|</span>
      Статус: <span class="badge text-bg-<?= e($statusBadge) ?>"><?= e($statusLabel) ?></span>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="/admin/tickets" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>К списку</a>

    <form method="post" action="/admin/tickets/delete" class="d-inline">
      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
      <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">
      <button type="submit" class="btn btn-outline-danger" data-delete-ticket data-ticket-id="<?= e((string)$id) ?>" data-ticket-subject="<?= e($subject) ?>">
        <i class="bi bi-trash me-1"></i>Удалить
      </button>
    </form>
    <?php if ($status === 'open'): ?>
      <form method="post" action="/admin/tickets/close" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">
        <button type="submit" class="btn btn-outline-warning" data-close-ticket>
          <i class="bi bi-check2-circle me-1"></i>Закрыть
        </button>
      </form>
    <?php else: ?>
      <form method="post" action="/admin/tickets/reopen" class="d-inline">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">
        <button type="submit" class="btn btn-outline-light">
          <i class="bi bi-arrow-counterclockwise me-1"></i>Открыть снова
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="card card-glass mb-3">
  <div class="card-body p-4">
    <?php if (empty($messages)): ?>
      <div class="text-body-secondary">Сообщений пока нет.</div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($messages as $m): ?>
          <?php
            $isAdminMsg = (int)($m['is_admin'] ?? 0) === 1;
            $who = $isAdminMsg ? 'Вы (поддержка)' : ('@' . (string)($m['user_username'] ?? 'пользователь'));
            $when = (string)($m['created_at'] ?? '');
            $text = (string)($m['message'] ?? '');
          ?>
          <div class="d-flex <?= $isAdminMsg ? 'justify-content-end' : 'justify-content-start' ?>">
            <div class="p-3 rounded-4 bg-soft border" style="max-width: 780px; width: fit-content;">
              <div class="small text-body-secondary mb-1"><?= e($who) ?> • <?= e($when) ?></div>
              <div><?= nl2br(e($text)) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($status === 'open'): ?>
  <div class="card card-glass">
    <div class="card-body p-4">
      <div class="fw-bold mb-2">Ответить пользователю</div>
      <form method="post" action="/admin/tickets/reply" class="row g-3">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">

        <div class="col-12">
          <textarea name="message" class="form-control" rows="4" placeholder="Ответ поддержки..." required></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Отправить</button>
        </div>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="text-body-secondary">Тикет закрыт. Чтобы ответить — открой его снова.</div>
<?php endif; ?>

<script>
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-close-ticket]');
    if (!btn) return;
    if (!confirm('Закрыть тикет?')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-delete-ticket]');
    if (!btn) return;
    const id = btn.getAttribute('data-ticket-id') || '';
    const subject = btn.getAttribute('data-ticket-subject') || 'тикет';
    if (!confirm('Удалить тикет #' + id + ' "' + subject + '"? Это удалит всю переписку.')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
</script>
