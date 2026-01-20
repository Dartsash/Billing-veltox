<?php
/**
 * User: Ticket view
 *
 * @var array<string,mixed> $user
 * @var string $csrf
 * @var array<string,mixed> $ticket
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
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Тикет #<?= e((string)$id) ?> — <?= e($subject) ?></h1>
    <div class="text-body-secondary">
      Статус: <span class="badge text-bg-<?= e($statusBadge) ?>"><?= e($statusLabel) ?></span>
    </div>
  </div>
  <div class="d-flex gap-2">
    <a href="/tickets" class="btn btn-outline-light"><i class="bi bi-arrow-left me-1"></i>К списку</a>
    <?php if ($status === 'open'): ?>
      <form method="post" action="/tickets/close" class="d-inline" data-no-loader>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">
        <button type="submit" class="btn btn-outline-warning" data-close-ticket>
          <i class="bi bi-check2-circle me-1"></i>Закрыть
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
            $who = $isAdminMsg ? 'Поддержка' : ('@' . (string)($m['user_username'] ?? 'вы'));
            $when = (string)($m['created_at'] ?? '');
            $text = (string)($m['message'] ?? '');
          ?>
          <div class="d-flex <?= $isAdminMsg ? 'justify-content-start' : 'justify-content-end' ?>">
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
      <div class="fw-bold mb-2">Ответить</div>
      <form method="post" action="/tickets/reply" class="row g-3" data-no-loader>
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">

        <div class="col-12">
          <textarea name="message" class="form-control" rows="4" placeholder="Напиши сообщение..." required></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Отправить</button>
        </div>
      </form>
    </div>
  </div>
<?php else: ?>
  <div class="text-body-secondary">Тикет закрыт. Если вопрос снова актуален — создай новый тикет.</div>
<?php endif; ?>

<script>
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-close-ticket]');
    if (!btn) return;
    if (!confirm('Закрыть тикет? После закрытия нельзя будет писать новые сообщения.')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
</script>