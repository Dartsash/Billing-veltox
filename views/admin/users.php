<?php
/**
 *
 *
 * @var array<string,mixed>|null $user
 * @var string $csrf
 * @var array<int, array{type:string,message:string}>|null $flash
 * @var array<int, array<string,mixed>> $users_list
 */

$meId = (int)($user['id'] ?? 0);

// Мини-статы
$totalUsers   = count($users_list);
$totalAdmins  = 0;
$totalBalance = 0;
$totalServers = 0;

foreach ($users_list as $row) {
    $totalBalance += (int)($row['balance_kopeks'] ?? 0);
    $totalServers += (int)($row['servers_count'] ?? 0);

    $role = strtolower(trim((string)($row['role'] ?? 'member')));
    if (function_exists('is_admin') && is_admin($row)) {
        $totalAdmins++;
    } elseif ($role === 'admin') {
        $totalAdmins++;
    }
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h1 class="h4 fw-bold m-0">Пользователи</h1>
        <div class="text-body-secondary">Список зарегистрированных аккаунтов биллинга.</div>
    </div>

    <?php if ($totalUsers > 0): ?>
        <div class="d-flex flex-wrap gap-2">
            <div class="badge badge-soft px-3 py-2">
                <span class="text-body-secondary small">Всего</span>
                <span class="ms-1 fw-semibold"><?= (int)$totalUsers ?></span>
            </div>
            <div class="badge badge-soft px-3 py-2">
                <span class="text-body-secondary small">Админов</span>
                <span class="ms-1 fw-semibold"><?= (int)$totalAdmins ?></span>
            </div>
            <div class="badge badge-soft px-3 py-2">
                <span class="text-body-secondary small">Серверов</span>
                <span class="ms-1 fw-semibold"><?= (int)$totalServers ?></span>
            </div>
            <div class="badge badge-soft px-3 py-2">
                <span class="text-body-secondary small">Баланс суммарно</span>
                <span class="ms-1 fw-semibold"><?= format_rub($totalBalance) ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

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
        ?>
        <div class="alert alert-glass alert-<?= e($kind) ?> mb-3" role="alert">
            <?= e($msg) ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="card card-glass rounded-4 border-0">
    <div class="card-body p-3 p-md-4">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="text-body-secondary small">
                Управляй балансом, ролями и аккаунтами. Удаление пользователя также удалит его серверы.
            </div>
            <div class="ms-auto" style="min-width: 220px; max-width: 320px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-transparent border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input
                        type="text"
                        class="form-control border-start-0"
                        id="userSearch"
                        placeholder="Поиск по нику или email..."
                        autocomplete="off"
                    >
                </div>
            </div>
        </div>

        <div class="bg-soft rounded-4 p-2 p-md-3">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="small text-uppercase text-body-secondary">
                    <tr>
                        <th style="width:70px">ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th style="width:120px">Роль</th>
                        <th style="width:140px">Баланс</th>
                        <th style="width:110px">Серверы</th>
                        <th style="width:190px">Создан</th>
                        <th class="text-end" style="width:220px">Действия</th>
                    </tr>
                    </thead>
                    <tbody id="usersTableBody">
                    <?php if (empty($users_list)): ?>
                        <tr>
                            <td colspan="8" class="text-body-secondary text-center py-4">
                                Пользователей пока нет.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users_list as $uRow): ?>
                            <?php
                            $id           = (int)($uRow['id'] ?? 0);
                            $isMe         = ($id === $meId);
                            $serversCount = (int)($uRow['servers_count'] ?? 0);
                            $created      = (string)($uRow['created_at'] ?? '');
                            $roleRaw      = strtolower(trim((string)($uRow['role'] ?? 'member')));
                            if ($roleRaw === '') {
                                $roleRaw = 'member';
                            }
                            $effectiveAdmin = (function_exists('is_admin') && is_admin($uRow));
                            $roleLabel = $effectiveAdmin ? 'admin' : $roleRaw;

                            $username = (string)($uRow['username'] ?? '');
                            $email    = (string)($uRow['email'] ?? '');
                            ?>
                            <tr
                                data-search-text="<?= e(mb_strtolower($username . ' ' . $email)) ?>"
                            >
                                <td class="text-body-secondary"><?= e((string)$id) ?></td>

                                <td class="fw-semibold">
                                    <div class="d-flex align-items-center gap-2">
                                        <span><?= e($username) ?></span>
                                        <?php if ($isMe): ?>
                                            <span class="badge text-bg-secondary border-0">вы</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="text-truncate" style="max-width: 320px"
                                         title="<?= e($email) ?>">
                                        <?= e($email) ?>
                                    </div>
                                </td>

                                <td>
                                    <?php if ($roleLabel === 'admin'): ?>
                                        <span class="badge bg-primary rounded-pill px-3">admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill px-3">
                                            <?= e($roleLabel) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="fw-semibold">
                                    <?= e(format_rub((int)($uRow['balance_kopeks'] ?? 0))) ?>
                                </td>

                                <td>
                                    <span class="badge badge-soft px-2">
                                        <?= e((string)$serversCount) ?>
                                    </span>
                                </td>

                                <td class="text-body-secondary small">
                                    <?= e($created !== '' ? $created : '-') ?>
                                </td>

                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <a class="btn btn-sm btn-outline-light"
                                           href="/admin/users/edit?id=<?= e((string)$id) ?>">
                                            <i class="bi bi-pencil-square me-1"></i>Редактировать
                                        </a>
                                        <form method="post" action="/admin/users/delete" class="d-inline">
                                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                            <input type="hidden" name="user_id" value="<?= e((string)$id) ?>">
                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-outline-danger"
                                                data-confirm="1"
                                                data-username="<?= e($username) ?>"
                                                <?= $isMe ? 'title="Удалишь себя — разлогинит"' : '' ?>
                                            >
                                                <i class="bi bi-trash me-1"></i>Удалить
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
        const btn = e.target.closest('button[data-confirm="1"]');
        if (!btn) return;
        const name = btn.getAttribute('data-username') || 'пользователя';
        if (!confirm('Удалить пользователя "' + name + '"? Это удалит и его серверы/операции.')) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    (function () {
        const input = document.getElementById('userSearch');
        if (!input) return;
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;

        input.addEventListener('input', function () {
            const q = this.value.trim().toLowerCase();
            const rows = tbody.querySelectorAll('tr[data-search-text]');
            rows.forEach(function (row) {
                const hay = row.getAttribute('data-search-text') || '';
                if (!q || hay.indexOf(q) !== -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    })();
</script>
