<?php
/**
 * User: Tickets list
 *
 * @var array<string,mixed> $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
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
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Тикеты поддержки</h1>
    <div class="text-body-secondary">Обратись в поддержку — мы ответим прямо здесь.</div>
  </div>
  <div>
    <a href="/tickets/new" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Создать тикет</a>
  </div>
</div>

<div class="card card-glass">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle mb-0">
        <thead>
          <tr>
            <th style="width:80px">ID</th>
            <th>Тема</th>
            <th style="width:120px">Статус</th>
            <th style="width:220px">Обновлён</th>
            <th style="width:140px" class="text-end">Действия</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($tickets_list)): ?>
          <tr>
            <td colspan="5" class="text-body-secondary">Тикетов пока нет. Создай первый.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($tickets_list as $t): ?>
            <?php
              $id = (int)($t['id'] ?? 0);
              $subject = (string)($t['subject'] ?? '');
              $status = (string)($t['status'] ?? 'open');
              $updated = (string)($t['updated_at'] ?? $t['created_at'] ?? '');
            ?>
            <tr>
              <td><?= e((string)$id) ?></td>
              <td class="fw-semibold"><?= e($subject) ?></td>
              <td>
                <span class="badge text-bg-<?= e($statusBadge($status)) ?>">
                  <?= e($statusLabel($status)) ?>
                </span>
              </td>
              <td class="text-body-secondary small"><?= e($updated !== '' ? $updated : '-') ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-light" href="/tickets/view?id=<?= e((string)$id) ?>">
                  <i class="bi bi-chat-left-text"></i> Открыть
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
