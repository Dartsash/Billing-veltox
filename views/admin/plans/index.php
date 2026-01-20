<?php
/**
 * Admin: Plans management
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
 * @var array<int, array<string,mixed>> $plans_list
 */

$totalPlans = is_array($plans_list) ? count($plans_list) : 0;
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h1 class="h4 fw-bold m-0">Тарифы</h1>
    <div class="text-body-secondary">Управление тарифами: добавление, редактирование, удаление.</div>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-2">
    <span class="badge badge-soft px-3 py-2">
      <span class="text-body-secondary small">Всего</span>
      <span class="ms-1 fw-semibold"><?= (int)$totalPlans ?></span>
    </span>
    <a href="/admin/plans/new" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>Добавить тариф
    </a>
  </div>
</div>

<div class="card card-glass rounded-4 border-0">
  <div class="card-body p-3 p-md-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div class="text-body-secondary small">
        Эти тарифы отображаются пользователям при создании сервера. Для каждого тарифа должен быть указан корректный Egg ID.
      </div>
    </div>

    <div class="bg-soft rounded-4 p-2 p-md-3">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="small text-uppercase text-body-secondary">
          <tr>
            <th style="width:80px">ID</th>
            <th>Тариф</th>
            <th style="width:160px">Цена</th>
            <th style="width:140px">Egg</th>
            <th style="width:220px">Обновлён</th>
            <th class="text-end" style="width:240px">Действия</th>
          </tr>
          </thead>

          <tbody>
          <?php if (empty($plans_list)): ?>
            <tr>
              <td colspan="6" class="text-body-secondary text-center py-4">Тарифов пока нет.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($plans_list as $p): ?>
              <?php
                $id = (int)($p['id'] ?? 0);
                $name = (string)($p['name'] ?? '');
                $desc = (string)($p['description'] ?? '');
                $price = format_rub((int)($p['price_kopeks'] ?? 0));
                $egg = (int)($p['ptero_egg_id'] ?? 0);
                $updated = (string)($p['updated_at'] ?? $p['created_at'] ?? '');
              ?>
              <tr>
                <td class="text-body-secondary"><?= e((string)$id) ?></td>

                <td>
                  <div class="fw-semibold"><?= e($name) ?></div>
                  <?php if ($desc !== ''): ?>
                    <div class="text-body-secondary small"><?= e($desc) ?></div>
                  <?php endif; ?>
                </td>

                <td class="fw-semibold"><?= e($price) ?></td>

                <td>
                  <span class="badge badge-soft px-2">#<?= e((string)$egg) ?></span>
                </td>

                <td class="text-body-secondary small"><?= e($updated !== '' ? $updated : '-') ?></td>

                <td class="text-end">
                  <div class="d-flex justify-content-end gap-2">
                    <a class="btn btn-sm btn-outline-light" href="/admin/plans/edit?id=<?= e((string)$id) ?>">
                      <i class="bi bi-pencil-square me-1"></i>Редактировать
                    </a>
                    <form method="post" action="/admin/plans/delete" class="d-inline">
                      <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                      <input type="hidden" name="id" value="<?= e((string)$id) ?>">
                      <button
                        type="submit"
                        class="btn btn-sm btn-outline-danger"
                        data-confirm-plan-delete
                        data-plan-name="<?= e($name) ?>"
                        title="Удалить"
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
    const btn = e.target.closest('button[data-confirm-plan-delete]');
    if (!btn) return;
    const name = btn.getAttribute('data-plan-name') || 'тариф';
    if (!confirm('Удалить тариф "' + name + '"?')) {
      e.preventDefault();
      e.stopPropagation();
    }
  });
</script>
