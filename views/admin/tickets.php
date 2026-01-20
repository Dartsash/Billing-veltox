<?php
/**
 *
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var string $status_filter
 * @var array<int, array<string,mixed>> $tickets_list
 */

$statusBadge = static function(string $status): string {
    return match ($status) {
        'open' => 'success',
        'closed' => 'secondary',
        default => 'secondary',
    };
};

$statusLabel = static function(string $status): string {
    return match ($status) {
        'open' => 'Открыт',
        'closed' => 'Закрыт',
        default => $status,
    };
};

$tabClass = static function(string $tab, string $current): string {
    return $tab === $current ? 'btn btn-primary' : 'btn btn-outline-light';
};
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Тикеты поддержки</h1>
    <div class="text-body-secondary">Все обращения пользователей.</div>
  </div>
  <div class="d-flex gap-2">
    <a class="<?= e($tabClass('open', $status_filter)) ?>" href="/admin/tickets?status=open">Открытые</a>
    <a class="<?= e($tabClass('closed', $status_filter)) ?>" href="/admin/tickets?status=closed">Закрытые</a>
    <a class="<?= e($tabClass('all', $status_filter)) ?>" href="/admin/tickets?status=all">Все</a>
  </div>
</div>

<div class="card card-glass rounded-4 border-0">
  <div class="card-body p-3 p-md-4">
    <div class="bg-soft rounded-4 p-2 p-md-3">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="small text-uppercase text-body-secondary">
          <tr>
            <th style="width:80px">ID</th>
            <th>Пользователь</th>
            <th>Тема</th>
            <th style="width:120px">Статус</th>
            <th style="width:220px">Обновлён</th>
            <th style="width:160px" class="text-end">Действия</th>
          </tr>
          </thead>
          <tbody>
        <?php if (empty($tickets_list)): ?>
          <tr>
            <td colspan="6" class="text-body-secondary">Тикетов нет.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($tickets_list as $t): ?>
            <?php
              $id = (int)($t['id'] ?? 0);
              $subject = (string)($t['subject'] ?? '');
              $status = (string)($t['status'] ?? 'open');
              $updated = (string)($t['updated_at'] ?? $t['created_at'] ?? '');
              $uname = (string)($t['user_username'] ?? '');
              $email = (string)($t['user_email'] ?? '');
            ?>
            <tr>
              <td><?= e((string)$id) ?></td>
              <td>
                <div class="fw-semibold">@<?= e($uname) ?></div>
                <div class="text-body-secondary small"><?= e($email) ?></div>
              </td>
              <td class="fw-semibold"><?= e($subject) ?></td>
              <td>
                <span class="badge text-bg-<?= e($statusBadge($status)) ?>"><?= e($statusLabel($status)) ?></span>
              </td>
              <td class="text-body-secondary small"><?= e($updated !== '' ? $updated : '-') ?></td>
              <td class="text-end">
                <div class="d-flex justify-content-end gap-2">
                  <a class="btn btn-sm btn-outline-light" href="/admin/tickets/view?id=<?= e((string)$id) ?>">
                    <i class="bi bi-chat-left-text"></i> Открыть
                  </a>
                  <form method="post" action="/admin/tickets/delete" class="d-inline">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <input type="hidden" name="ticket_id" value="<?= e((string)$id) ?>">
                    <button
                      type="submit"
                      class="btn btn-sm btn-outline-danger"
                      data-confirm-ticket-delete
                      data-ticket-id="<?= e((string)$id) ?>"
                      data-ticket-subject="<?= e($subject) ?>"
                    >
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('button[data-confirm-ticket-delete]');
    if (!btn) return;
    const id = btn.getAttribute('data-ticket-id') || '';
    const subject = btn.getAttribute('data-ticket-subject') || 'тикет';
    if (!confirm('Удалить тикет #' + id + ' "' + subject + '"? Это удалит всю переписку.')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
</script>
