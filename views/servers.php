<?php
/** @var array<string,mixed> $user */
/** @var array<int, array<string,mixed>> $servers */
/** @var string $panel_url */
/** @var string $csrf */
?>
<div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-4">
    <div>
        <div class="text-uppercase small text-muted fw-semibold mb-1">Управление</div>
        <h1 class="h4 fw-bold mb-1">Мои серверы</h1>
        <div class="text-muted">Создавай, продлевай и управляй своими серверами</div>
    </div>
    <a href="/servers/new" class="btn btn-primary btn-lg rounded-4 px-4">
        <i class="bi bi-plus-circle me-2"></i>
        Новый сервер
    </a>
</div>

<?php if (empty($servers)): ?>
    <div class="card card-glass rounded-4 border-0">
        <div class="card-body p-5 text-center">
            <div class="icon-bubble icon-bubble-lg mb-3">
                <i class="bi bi-hdd-network"></i>
            </div>
            <h2 class="h4 fw-bold mb-2">У тебя пока нет серверов</h2>
            <p class="text-muted mb-4">
                Нажми «Новый сервер», выбери локацию и тариф — мы автоматически создадим сервер
            </p>
            <a href="/servers/new" class="btn btn-primary btn-lg rounded-4 px-4">
                <i class="bi bi-plus-circle me-2"></i>
                Создать первый сервер
            </a>
        </div>
    </div>
<?php else: ?>

    <div class="row g-3">
        <?php foreach ($servers as $srv): ?>
            <?php
            $panelLink = null;
            if (!empty($panel_url) && !empty($srv['ptero_identifier'])) {
                $panelLink = rtrim($panel_url, '/') . '/server/' . $srv['ptero_identifier'];
            }

            $badgeClass = 'secondary';
            if (!empty($srv['status_class'])) {
                $badgeClass = (string)$srv['status_class'];
            }
            $label = (string)($srv['status_label'] ?? 'Неизвестно');

            $expiresAt = !empty($srv['expires_at']) ? (string)$srv['expires_at'] : null;
            $isExpired = !empty($srv['is_expired']);
            ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card card-glass rounded-4 border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column">

                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="text-muted small mb-1">Сервер</div>
                                <div class="fw-bold"><?= e((string)$srv['name']) ?></div>
                                <div class="text-muted small">
                                    ID в Pterodactyl: <span class="fw-semibold"><?= (int)$srv['ptero_server_id'] ?></span>
                                </div>
                            </div>
                            <span class="badge bg-<?= e($badgeClass) ?> rounded-pill px-3 py-2">
                                <?= e($label) ?>
                            </span>
                        </div>

                        <div class="mt-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Тариф</span>
                                <span class="fw-semibold small">
                                    <?= e((string)($srv['plan_name'] ?? 'Неизвестно')) ?>
                                </span>
                            </div>
                            <?php if (isset($srv['plan_price_kopeks'])): ?>
                                <div class="d-flex justify-content-between align-items-center small">
                                    <span class="text-muted">Стоимость</span>
                                    <span class="fw-semibold">
                                        <?= format_rub((int)$srv['plan_price_kopeks']) ?>/мес
                                    </span>
                                </div>
                            <?php endif; ?>

                            <hr class="my-3 border-light opacity-25">

                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="text-muted">Действует до</span>
                                <?php if ($expiresAt): ?>
                                    <span class="fw-semibold<?= $isExpired ? ' text-danger' : '' ?>">
                                        <?= e($expiresAt) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($isExpired && $label === 'Заморожен'): ?>
                                <div class="small text-warning mt-1">
                                    Сервер заморожен из-за окончания оплаченного срока.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto pt-2">
                            <div class="d-flex flex-wrap gap-2">
                                <?php if ($panelLink): ?>
                                    <a href="<?= e($panelLink) ?>" class="btn btn-outline-light btn-sm flex-grow-1"
                                       target="_blank" rel="noopener">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>
                                        Панель
                                    </a>
                                    <a href="<?= e($panelLink) ?>" class="btn btn-outline-success btn-sm flex-fill"
                                       target="_blank" rel="noopener">
                                        <i class="bi bi-play-fill me-1"></i>Запуск
                                    </a>
                                    <a href="<?= e($panelLink) ?>" class="btn btn-outline-warning btn-sm flex-fill"
                                       target="_blank" rel="noopener">
                                        <i class="bi bi-stop-fill me-1"></i>Стоп
                                    </a>
                                <?php endif; ?>

                                <form method="post" action="/servers/delete" class="flex-grow-1" data-del-form>
                                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="server_id" value="<?= (int)$srv['id'] ?>">
                                    <button
                                        class="btn btn-outline-danger btn-sm w-100"
                                        type="button"
                                        data-del-btn
                                        data-server-id="<?= (int)$srv['id'] ?>"
                                        data-server-name="<?= e((string)$srv['name']) ?>"
                                    >
                                        <i class="bi bi-trash me-1"></i>Удалить
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-muted small mt-3">
        Статус «Заморожен» выставляется автоматически через 30 дней после создания сервера.
    </div>
<?php endif; ?>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-glass rounded-4 border-0">
      <div class="modal-body p-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="icon-bubble"><i class="bi bi-trash"></i></div>
          <div class="min-w-0">
            <div class="fw-bold">Подтверди удаление</div>
            <div class="text-muted small">При удалении сервера ВСЕ файлы будут удалены, а деньги на баланс не вернутся.</div>
          </div>
        </div>

        <div class="bg-soft rounded-4 p-3 small mt-3">
          <div class="d-flex justify-content-between gap-3">
            <span class="text-muted">Сервер</span>
            <span class="fw-semibold text-end value-break" id="dServerName">—</span>
          </div>
          <div class="d-flex justify-content-between gap-3 mt-1">
            <span class="text-muted">ID</span>
            <span class="fw-semibold text-end" id="dServerId">—</span>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-light flex-fill" data-bs-dismiss="modal">
            Отмена
          </button>
          <button type="button" class="btn btn-danger flex-fill" id="dConfirmBtn">
            <i class="bi bi-trash me-1"></i>
            Да, удалить
          </button>
        </div>

        <div class="text-muted small mt-3">
          Если передумал — нажми «Отмена». Сервер не удалится.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modalEl = document.getElementById('confirmDeleteModal');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }
  const modal = (modalEl && window.bootstrap) ? new bootstrap.Modal(modalEl) : null;

  const dServerName = document.getElementById('dServerName');
  const dServerId = document.getElementById('dServerId');
  const dConfirmBtn = document.getElementById('dConfirmBtn');

  const delBtns = document.querySelectorAll('[data-del-btn]');

  let pendingForm = null;
  let pendingBtn = null;

  if (!modal || !dConfirmBtn) return;

  delBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const form = btn.closest('form');
      if (!form) return;

      pendingForm = form;
      pendingBtn = btn;

      const sid = btn.getAttribute('data-server-id') || '';
      const sname = btn.getAttribute('data-server-name') || '';

      if (dServerId) dServerId.textContent = sid ? String(sid) : '—';
      if (dServerName) dServerName.textContent = sname ? String(sname) : '—';

      dConfirmBtn.disabled = false;
      dConfirmBtn.innerHTML = '<i class="bi bi-trash me-1"></i>Да, удалить';

      modal.show();
    });
  });

  const originalHtml = dConfirmBtn.innerHTML;

  dConfirmBtn.addEventListener('click', () => {
    if (!pendingForm || dConfirmBtn.disabled) return;

    dConfirmBtn.disabled = true;
    dConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Удаляем…';

    if (pendingBtn) {
      pendingBtn.disabled = true;
      pendingBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Удаляем…';
    }

    setTimeout(() => {
      try { modal.hide(); } catch (e) {}
      pendingForm.submit();
      dConfirmBtn.innerHTML = originalHtml;
    }, 3000);
  });
});
</script>
