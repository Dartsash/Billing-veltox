<?php
/**
 * @var array<string,mixed> $user
 * @var string $csrf
 * @var array<int,array<string,mixed>> $plans
 * @var array<int,array<string,mixed>> $all_locations
 * @var int $selected_location_id
 */

$EGG_LABELS = [
  2 => 'Paper',
  3 => 'Vanilla',
  4 => 'Forge',
  5 => 'Fabric',
];

$SOFTWARES = [
  'minecraft' => 'Minecraft',
];

$eggIds = [];
foreach ($plans as $p) {
  $eid = (int)($p['ptero_egg_id'] ?? 0);
  if ($eid > 0) $eggIds[$eid] = true;
}
$eggIds = array_keys($eggIds);
sort($eggIds);
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
  <div>
    <div class="text-uppercase small text-muted fw-semibold mb-1">Новый сервер</div>
    <h1 class="h4 fw-bold mb-1">Создать игровой сервер</h1>
    <div class="text-muted">Заполни имя → выбери игру → характеристики → локацию → тариф.</div>
  </div>
</div>

<div class="card card-glass rounded-4 border-0 mb-3">
  <div class="card-body p-4">

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <div class="badge bg-soft rounded-pill px-3 py-2">
        <span class="small text-muted">Баланс</span>
        <span class="fw-semibold ms-1"><?= format_rub((int)$user['balance_kopeks']) ?></span>
      </div>
      <div class="text-muted small">
        Оплата за 1 месяц спишется сразу.
      </div>
    </div>

    <div class="mb-3">
      <div class="text-muted small mb-1">1. Имя сервера</div>
      <input class="form-control form-control-lg" id="serverName" placeholder="Server Name" autocomplete="off">
      <div class="form-text">Например: Server</div>
    </div>

    <div class="mb-3">
      <div class="text-muted small mb-1">2. Программное обеспечение / Игры</div>
      <select class="form-select form-select-lg" id="softwareSelect">
        <option value="" selected disabled>Пожалуйста, выберите программное обеспечение…</option>
        <?php foreach ($SOFTWARES as $key => $label): ?>
          <option value="<?= e($key) ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="mb-2 d-none" id="eggStep">
      <div class="text-muted small mb-1">3. Характеристики</div>
      <select class="form-select form-select-lg" id="eggSelect" disabled>
        <option value="" selected disabled>—</option>
        <?php foreach ($eggIds as $eid): ?>
          <?php
            $label = $EGG_LABELS[$eid] ?? ('Egg #' . $eid);
          ?>
          <option value="<?= (int)$eid ?>"><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Например: Paper (рекомендуется), Vanilla, Forge…</div>
    </div>

  </div>
</div>

<div class="mb-4 d-none" id="locationStep">
  <div class="d-flex flex-wrap align-items-center gap-2">
    <div class="text-muted small me-1">4. Локация:</div>

    <?php if (empty($all_locations)): ?>
      <span class="badge bg-danger rounded-pill">Локации не настроены</span>
    <?php else: ?>
      <?php foreach ($all_locations as $locId => $loc): ?>
        <button
          type="button"
          class="btn btn-sm rounded-pill btn-outline-light"
          data-location-pill
          data-location-id="<?= (int)$locId ?>"
        >
          <i class="bi bi-geo-alt-fill me-1"></i>
          <?= e((string)$loc['short']) ?>
          <?php if (!empty($loc['long'])): ?>
            <span class="text-muted small ms-1"><?= e((string)$loc['long']) ?></span>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="text-muted small mt-2" id="locationHint"></div>
</div>

<div class="d-none" id="plansStep">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div class="text-muted small">5. Выбери тариф</div>
    <div class="text-muted small" id="plansHint"></div>
  </div>

  <?php if (empty($plans)): ?>
    <div class="card card-glass rounded-4 border-0">
      <div class="card-body p-4 text-center text-muted">
        Тарифы не настроены. Добавь их в админке /admin/plans.
      </div>
    </div>
  <?php else: ?>

    <div class="row g-3" id="plans-grid">
      <?php foreach ($plans as $plan): ?>
        <?php
          $locIds = $plan['location_ids'] ?? [];
          if (!is_array($locIds)) $locIds = [];
          $locIds = array_map('intval', $locIds);

          $limits = json_decode((string)($plan['limits_json'] ?? '{}'), true);
          if (!is_array($limits)) $limits = [];
          $memoryMb = (int)($limits['memory'] ?? 0);
          $diskMb   = (int)($limits['disk'] ?? 0);
          $cpu      = (int)($limits['cpu'] ?? 0);

          $features = json_decode((string)($plan['feature_limits_json'] ?? '{}'), true);
          if (!is_array($features)) $features = [];
          $backups    = isset($features['backups']) ? (int)$features['backups'] : null;
          $portsLimit = isset($features['allocations']) ? (int)$features['allocations'] : null;

          $locationsAttr = implode(',', $locIds);
          $planId = (int)$plan['id'];

          $eggId = (int)($plan['ptero_egg_id'] ?? 0);

          $baseHref = '/buy?plan_id=' . $planId;
        ?>

        <div class="col-12 col-md-6 col-xl-4 plan-card"
             data-plan
             data-plan-egg-id="<?= (int)$eggId ?>"
             data-plan-locations="<?= e($locationsAttr) ?>">
          <div class="card card-glass rounded-4 border-0 h-100">
            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                  <div class="text-muted small">Тариф</div>
                  <div class="fw-bold mb-1"><?= e((string)$plan['name']) ?></div>
                  <?php if (!empty($plan['description'])): ?>
                    <div class="text-muted small"><?= e((string)$plan['description']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="text-end">
                  <div class="fw-bold"><?= format_rub((int)$plan['price_kopeks']) ?></div>
                  <div class="text-muted small">/ мес</div>
                </div>
              </div>

              <div class="bg-soft rounded-4 p-3 small mt-2 mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">CPU</span>
                  <span class="fw-semibold"><?= $cpu ?>%</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">RAM</span>
                  <span class="fw-semibold">
                    <?= $memoryMb >= 1024 ? number_format($memoryMb / 1024, 1) . ' GB' : $memoryMb . ' MB' ?>
                  </span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">SSD</span>
                  <span class="fw-semibold">
                    <?= $diskMb >= 1024 ? number_format($diskMb / 1024, 1) . ' GB' : $diskMb . ' MB' ?>
                  </span>
                </div>
                <?php if ($backups !== null): ?>
                  <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Бэкапы</span>
                    <span class="fw-semibold"><?= $backups ?></span>
                  </div>
                <?php endif; ?>
                <?php if ($portsLimit !== null): ?>
                  <div class="d-flex justify-content-between">
                    <span class="text-muted">Порты</span>
                    <span class="fw-semibold"><?= $portsLimit ?></span>
                  </div>
                <?php endif; ?>
              </div>

              <div class="mt-auto">
                <a href="<?= e($baseHref) ?>"
                   data-buy-link
                   data-base-href="<?= e($baseHref) ?>"
                   data-plan-id="<?= (int)$planId ?>"
                   data-plan-name="<?= e((string)$plan['name']) ?>"
                   data-plan-price="<?= e(format_rub((int)$plan['price_kopeks'])) ?>"
                   class="btn btn-primary w-100 rounded-4 mb-2">
                  <i class="bi bi-lightning-charge me-1"></i>
                  Выбрать тариф
                </a>
                <div class="text-muted small text-center">
                  Локации:
                  <?php
                    $locLabels = [];
                    foreach ($locIds as $lid) {
                      $loc = $all_locations[$lid] ?? null;
                      if ($loc) $locLabels[] = $loc['short'];
                    }
                    echo e($locLabels ? implode(', ', $locLabels) : 'не настроены');
                  ?>
                </div>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  <?php endif; ?>
</div>


<form id="quickBuyForm" method="post" action="/buy" class="d-none">
  <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
  <input type="hidden" name="plan_id" id="qPlanId" value="">
  <input type="hidden" name="server_name" id="qServerName" value="">
  <input type="hidden" name="location_id" id="qLocationId" value="">
</form>

<div class="modal fade" id="confirmCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-glass rounded-4 border-0">
      <div class="modal-body p-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="icon-bubble"><i class="bi bi-lightning-charge"></i></div>
          <div class="min-w-0">
            <div class="fw-bold">Подтверди создание</div>
            <div class="text-muted small">С баланса спишется стоимость тарифа. Сервер появится в разделе «Мои серверы».</div>
          </div>
        </div>

        <div class="bg-soft rounded-4 p-3 small mt-3">
          <div class="d-flex justify-content-between gap-3">
            <span class="text-muted">Сервер</span>
            <span class="fw-semibold text-end value-break" id="mServerName">—</span>
          </div>
          <div class="d-flex justify-content-between gap-3 mt-1">
            <span class="text-muted">Локация</span>
            <span class="fw-semibold text-end" id="mLocation">—</span>
          </div>
          <div class="d-flex justify-content-between gap-3 mt-1">
            <span class="text-muted">Тариф</span>
            <span class="fw-semibold text-end" id="mPlanName">—</span>
          </div>
          <div class="d-flex justify-content-between gap-3 mt-1">
            <span class="text-muted">Списание</span>
            <span class="fw-semibold text-warning text-end" id="mPlanPrice">—</span>
          </div>
        </div>

        <div class="d-flex gap-2 mt-3">
          <button type="button" class="btn btn-outline-light flex-fill" data-bs-dismiss="modal">
            Отмена
          </button>
          <button type="button" class="btn btn-primary flex-fill" id="mConfirmBtn">
            <i class="bi bi-check2-circle me-1"></i>
            Оплатить и создать
          </button>
        </div>

        <div class="text-muted small mt-3">
          Если передумал — нажми «Отмена». Деньги не спишутся.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const serverName = document.getElementById('serverName');
  const softwareSelect = document.getElementById('softwareSelect');

  const eggStep = document.getElementById('eggStep');
  const eggSelect = document.getElementById('eggSelect');

  const locationStep = document.getElementById('locationStep');
  const locationHint = document.getElementById('locationHint');
  const pills = document.querySelectorAll('[data-location-pill]');

  const plansStep = document.getElementById('plansStep');
  const plansHint = document.getElementById('plansHint');

  const cards = document.querySelectorAll('.plan-card');
  const buyLinks = document.querySelectorAll('[data-buy-link]');

  const quickForm = document.getElementById('quickBuyForm');
  const qPlanId = document.getElementById('qPlanId');
  const qServerName = document.getElementById('qServerName');
  const qLocationId = document.getElementById('qLocationId');

  const modalEl = document.getElementById('confirmCreateModal');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }
  const modal = (modalEl && window.bootstrap) ? new bootstrap.Modal(modalEl) : null;

  const mServerName = document.getElementById('mServerName');
  const mLocation = document.getElementById('mLocation');
  const mPlanName = document.getElementById('mPlanName');
  const mPlanPrice = document.getElementById('mPlanPrice');
  const mConfirmBtn = document.getElementById('mConfirmBtn');

  let pendingPlanId = 0;
  let pendingPlanName = '';
  let pendingPlanPrice = '';

  let selectedEggId = 0;
  let selectedLocationId = 0;

  function esc(s){ return encodeURIComponent(String(s || '')); }

  function getPlanMeta(card){
    const eggId = parseInt(card.getAttribute('data-plan-egg-id') || '0', 10);
    const locStr = card.getAttribute('data-plan-locations') || '';
    const locIds = locStr.split(',').filter(Boolean).map(n => parseInt(n,10)).filter(n => n>0);
    return { eggId, locIds };
  }

  function nameOk(){
    const v = (serverName.value || '').trim();
    return v.length >= 3;
  }

  function updateBuyLinks(){
    const n = (serverName.value || '').trim();
    buyLinks.forEach(link => {
      const baseHref = link.getAttribute('data-base-href') || '#';
      const qs = [];
      if (selectedLocationId) qs.push('location_id=' + esc(selectedLocationId));
      if (selectedEggId) qs.push('egg_id=' + esc(selectedEggId));
      if (n) qs.push('server_name=' + esc(n));
      link.href = baseHref + (qs.length ? ('&' + qs.join('&')) : '');
    });
  }

  function reveal(el){
    if (!el) return;
    el.classList.remove('d-none');
    el.classList.add('animate-fade-up');
    setTimeout(() => el.classList.remove('animate-fade-up'), 480);
  }

  function showEggStep(){
    reveal(eggStep);
    eggSelect.disabled = false;
  }

  function showLocationStep(){
    reveal(locationStep);
  }

  function showPlansStep(){
    reveal(plansStep);
  }

  function hideLocationAndPlans(){
    locationStep.classList.add('d-none');
    plansStep.classList.add('d-none');
    selectedLocationId = 0;

    pills.forEach(p => {
      p.classList.remove('btn-primary');
      p.classList.add('btn-outline-light');
      p.disabled = false;
      p.style.opacity = '';
    });

    cards.forEach(c => c.style.display = 'none');
    plansHint.textContent = '';
    locationHint.textContent = '';
    updateBuyLinks();
  }

  function filterLocationsByEgg(){
    const allowed = new Set();
    cards.forEach(card => {
      const meta = getPlanMeta(card);
      if (selectedEggId && meta.eggId === selectedEggId) {
        meta.locIds.forEach(id => allowed.add(id));
      }
    });

    let any = false;
    pills.forEach(btn => {
      const id = parseInt(btn.getAttribute('data-location-id') || '0', 10);
      const ok = allowed.has(id);

      btn.disabled = !ok;
      btn.style.opacity = ok ? '' : '.35';

      if (ok) any = true;

      btn.classList.remove('btn-primary');
      btn.classList.add('btn-outline-light');
    });

    locationHint.textContent = any
      ? 'Выбери локацию — после этого появятся тарифы.'
      : 'Нет тарифов под выбранные характеристики (egg).';

    selectedLocationId = 0;
    plansStep.classList.add('d-none');
    cards.forEach(c => c.style.display = 'none');
    plansHint.textContent = '';
    updateBuyLinks();
  }

  function filterPlans(){
    let visibleCount = 0;

    cards.forEach(card => {
      const meta = getPlanMeta(card);

      const okEgg = selectedEggId ? (meta.eggId === selectedEggId) : true;
      const okLoc = selectedLocationId ? meta.locIds.includes(selectedLocationId) : false;

      const visible = okEgg && okLoc;
      card.style.display = visible ? '' : 'none';
      if (visible) {
        visibleCount++;
        card.classList.add('animate-fade-up');
        setTimeout(() => card.classList.remove('animate-fade-up'), 480);
      }
    });

    plansHint.textContent = visibleCount ? ('Показано тарифов: ' + visibleCount) : 'Тарифов нет под выбранные параметры.';
    updateBuyLinks();
  }

  softwareSelect.addEventListener('change', () => {
    showEggStep();
    hideLocationAndPlans();
  });

  eggSelect.addEventListener('change', () => {
    selectedEggId = parseInt(eggSelect.value || '0', 10) || 0;
    if (!selectedEggId) return;

    showLocationStep();
    filterLocationsByEgg();
    updateBuyLinks();
  });

  pills.forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.disabled) return;

      const id = parseInt(btn.getAttribute('data-location-id') || '0', 10);
      selectedLocationId = id;

      pills.forEach(p => {
        p.classList.remove('btn-primary');
        p.classList.add('btn-outline-light');
      });

      btn.classList.remove('btn-outline-light');
      btn.classList.add('btn-primary');

      showPlansStep();
      filterPlans();
    });
  });

  serverName.addEventListener('input', () => {
    updateBuyLinks();
  });

  function getSelectedLocationLabel(){
    const btn = document.querySelector('[data-location-pill].btn-primary');
    if (btn) {
      const t = (btn.textContent || '').replace(/\s+/g,' ').trim();
      return t || ('LOC ' + selectedLocationId);
    }
    return selectedLocationId ? ('LOC ' + selectedLocationId) : '—';
  }

  buyLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      const n = (serverName.value || '').trim();

      if (!nameOk()) {
        e.preventDefault();
        serverName.focus();
        alert('Укажи имя сервера (минимум 3 символа).');
        return;
      }
      if (!selectedEggId || !selectedLocationId) {
        e.preventDefault();
        alert('Выбери характеристики (egg) и локацию.');
        return;
      }

      if (!modal || !quickForm || !qPlanId || !qServerName || !qLocationId) {
        return;
      }

      e.preventDefault();

      pendingPlanId = parseInt(link.getAttribute('data-plan-id') || '0', 10) || 0;
      pendingPlanName = link.getAttribute('data-plan-name') || '';
      pendingPlanPrice = link.getAttribute('data-plan-price') || '';

      if (!pendingPlanId) {
        alert('Не удалось определить тариф.');
        return;
      }

      if (mServerName) mServerName.textContent = n;
      if (mLocation) mLocation.textContent = getSelectedLocationLabel();
      if (mPlanName) mPlanName.textContent = pendingPlanName || ('#' + pendingPlanId);
      if (mPlanPrice) mPlanPrice.textContent = pendingPlanPrice ? ('-' + pendingPlanPrice) : '';
      if (mConfirmBtn) mConfirmBtn.disabled = false;

      modal.show();
    });
  });

  if (mConfirmBtn) {
    const originalHtml = mConfirmBtn.innerHTML;

    mConfirmBtn.addEventListener('click', () => {
      const n = (serverName.value || '').trim();
      if (!pendingPlanId || !n || !selectedLocationId) return;

      if (mConfirmBtn.disabled) return;

      qPlanId.value = String(pendingPlanId);
      qServerName.value = n;
      qLocationId.value = String(selectedLocationId);

      mConfirmBtn.disabled = true;
      mConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Создаём…';

      setTimeout(() => {
        try { modal && modal.hide(); } catch (e) {}
        quickForm.submit();
        mConfirmBtn.innerHTML = originalHtml;
      }, 3000);
    });
  }

  updateBuyLinks();
});
</script>

