<?php
/**
 *
 *
 * @var array<string,mixed>|null $user
 * @var string|null $csrf
 */

$p = path();
$u = $user ?? [];

$initial = '';
if (!empty($u) && isset($u['username'])) {
    $name = (string)$u['username'];
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
}

$items = [
    ['section' => null, 'rows' => [
        ['/dashboard', 'bi-house', 'Контрольная панель'],
        ['/servers',   'bi-hdd-stack', 'Серверы'],
        ['/plans',     'bi-boxes', 'Тарифы'],
        ['/topup',     'bi-wallet2', 'Баланс'],
        ['/tickets',   'bi-life-preserver', 'Тикеты'],
    ]],
];

$isAdmin = (function_exists('is_admin') && !empty($u) && is_admin($u));

if ($isAdmin) {
    $items[] = ['section' => 'Администрация', 'rows' => [
        ['/admin/users',    'bi-people', 'Пользователи'],
        ['/admin/tickets',  'bi-chat-dots', 'Тикеты'],
        ['/admin/plans',    'bi-boxes', 'Тарифы'],
        ['/admin/settings', 'bi-gear', 'Настройки'],
    ]];
}

$active = static function(string $href) use ($p): string {
    if ($href === '/') {
        return $p === '/' ? 'is-active' : '';
    }

    if ($href !== '/dashboard' && str_starts_with($p, $href)) {
        return 'is-active';
    }
    return $p === $href ? 'is-active' : '';
};
?>

<aside class="b-side" aria-label="Меню" data-full-sidebar>
  <div class="b-brand">
    <button
      type="button"
      class="b-logo b-logo-btn"
      data-bs-toggle="modal"
      data-bs-target="#profileModal"
      aria-label="Профиль"
      title="Профиль"
    ><?= $initial !== '' ? e($initial) : 'V' ?></button>
    <div>
      <div class="b-title">Veltox 2.0</div>
      <div class="b-sub"><?= e($u['username'] ?? 'user') ?></div>
    </div>
  </div>

  <nav class="b-nav">
    <?php foreach ($items as $block): ?>
      <?php if (!empty($block['section'])): ?>
        <div class="b-section"><?= e((string)$block['section']) ?></div>
      <?php endif; ?>

      <?php foreach ($block['rows'] as $row): ?>
        <?php [$href,$icon,$label] = $row; ?>
        <a class="b-item <?= e($active($href)) ?>" href="<?= e($href) ?>">
          <i class="bi <?= e($icon) ?>"></i>
          <span><?= e($label) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="b-spacer"></div>

    <form method="post" action="/logout" class="b-logout">
      <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">
      <button type="submit" class="b-logout-btn">
        <i class="bi bi-power"></i>
        <span>Выйти</span>
      </button>
    </form>
  </nav>
</aside>
