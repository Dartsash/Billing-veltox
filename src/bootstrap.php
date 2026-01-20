<?php

declare(strict_types=1);

require __DIR__ . '/Config.php';
require __DIR__ . '/Database.php';
require __DIR__ . '/Helpers.php';
require __DIR__ . '/Flash.php';
require __DIR__ . '/Csrf.php';
require __DIR__ . '/App.php';
require __DIR__ . '/View.php';
require __DIR__ . '/PterodactylClient.php';
require __DIR__ . '/Turnstile.php';

require __DIR__ . '/Repositories/UserRepo.php';
require __DIR__ . '/Repositories/PlanRepo.php';
require __DIR__ . '/Repositories/ServerRepo.php';
require __DIR__ . '/Repositories/TransactionRepo.php';
require __DIR__ . '/Repositories/TicketRepo.php';
require __DIR__ . '/Repositories/TicketMessageRepo.php';
require __DIR__ . '/Auth.php';

function bootstrap(): App
{
    $baseDir = dirname(__DIR__);
    $config = Config::load($baseDir);

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((int)($_SERVER['SERVER_PORT'] ?? 80) === 443);
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $flash = new Flash();
    $csrf = new Csrf();

    $turnstileSiteKey = (string)($config->get('TURNSTILE_SITE_KEY', '') ?? '');
    $turnstileSecretKey = (string)($config->get('TURNSTILE_SECRET_KEY', '') ?? '');
    $turnstileEnabled = ($turnstileSiteKey !== '' && $turnstileSecretKey !== '');

    $pdo = Database::connect($config);
    $users = new UserRepo($pdo);
    $plans = new PlanRepo($pdo);
    $servers = new ServerRepo($pdo);
    $transactions = new TransactionRepo($pdo);
    $tickets = new TicketRepo($pdo);
    $ticketMessages = new TicketMessageRepo($pdo);

    $auth = new Auth($users);

    $pteroBaseUrl = (string)($config->get('PTERO_BASE_URL', '') ?? '');
    $pteroAppKey = (string)($config->get('PTERO_APP_KEY', '') ?? '');
    $ptero = null;
    if ($pteroBaseUrl !== '' && $pteroAppKey !== '') {
        $ptero = new PterodactylClient($pteroBaseUrl, $pteroAppKey);
    }

    $view = new View($baseDir . '/views');
    $app = new App();

    $requirePtero = function() use ($ptero) : PterodactylClient {
        if (!$ptero) {
            throw new RuntimeException('Pterodactyl не настроен. Заполни PTERO_BASE_URL и PTERO_APP_KEY в .env');
        }
        return $ptero;
    };

    $requireTurnstileOk = function() use ($turnstileEnabled, $flash): void {
        if (!$turnstileEnabled) {
            $flash->add('danger', 'Turnstile не настроен. Добавь TURNSTILE_SITE_KEY и TURNSTILE_SECRET_KEY в .env');
            redirect('/login');
        }
    };

    $app->get('/', function() use ($auth) {
        if ($auth->check()) {
            redirect('/dashboard');
        }
        redirect('/login');
    });

    $app->get('/login', function() use ($auth, $view, $csrf, $flash, $turnstileSiteKey, $turnstileEnabled) {
        if ($auth->check()) {
            redirect('/dashboard');
        }
        $view->render('auth/login', [
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'user' => null,
            'turnstile_site_key' => $turnstileSiteKey,
            'turnstile_enabled' => $turnstileEnabled,
        ]);
    });

    $app->post('/login', function() use ($auth, $csrf, $flash, $turnstileEnabled) {
        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        if (!$turnstileEnabled) {
            $flash->add('danger', 'Turnstile не настроен. Добавь ключи в .env');
            redirect('/login');
        }

        $token = (string)($_POST['cf-turnstile-response'] ?? '');
        if (!Turnstile::verify($token, $_SERVER['REMOTE_ADDR'] ?? null)) {
            $flash->add('danger', 'Подтверди, что ты не бот (Turnstile).');
            redirect('/login');
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $flash->add('danger', 'Введи email и пароль.');
            redirect('/login');
        }

        if ($auth->attempt($email, $password)) {
            $flash->add('success', 'Добро пожаловать!');
            redirect('/dashboard');
        }

        $flash->add('danger', 'Неверный email или пароль.');
        redirect('/login');
    });

    $app->get('/register', function() use ($auth, $view, $csrf, $flash, $turnstileSiteKey, $turnstileEnabled) {
        if ($auth->check()) {
            redirect('/dashboard');
        }
        $view->render('auth/register', [
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'user' => null,
            'turnstile_site_key' => $turnstileSiteKey,
            'turnstile_enabled' => $turnstileEnabled,
        ]);
    });

    $app->post('/register', function() use ($pdo, $users, $auth, $csrf, $flash, $requirePtero, $turnstileEnabled) {
        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        if (!$turnstileEnabled) {
            $flash->add('danger', 'Turnstile не настроен. Добавь ключи в .env');
            redirect('/register');
        }

        $token = (string)($_POST['cf-turnstile-response'] ?? '');
        if (!Turnstile::verify($token, $_SERVER['REMOTE_ADDR'] ?? null)) {
            $flash->add('danger', 'Подтверди, что ты не бот (Turnstile).');
            redirect('/register');
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $password2 = (string)($_POST['password_confirm'] ?? '');

        $firstName = $username;
        $lastName = 'User';

        if ($username === '' || $email === '' || $password === '' || $password2 === '') {
            $flash->add('danger', 'Заполни все поля.');
            redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash->add('danger', 'Некорректный email.');
            redirect('/register');
        }
        if (mb_strlen($username) < 3 || mb_strlen($username) > 32 || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $flash->add('danger', 'Username: 3–32 символа (латиница/цифры/._-).');
            redirect('/register');
        }
        if (strlen($password) < 8) {
            $flash->add('danger', 'Пароль должен быть минимум 8 символов.');
            redirect('/register');
        }
        if ($password !== $password2) {
            $flash->add('danger', 'Пароли не совпадают.');
            redirect('/register');
        }
        if ($users->findByEmail($email)) {
            $flash->add('danger', 'Такой email уже зарегистрирован.');
            redirect('/register');
        }
        if ($users->findByUsername($username)) {
            $flash->add('danger', 'Такой username уже занят.');
            redirect('/register');
        }

        try {
            $newUserId = Database::transaction($pdo, function(PDO $pdo) use ($users, $requirePtero, $firstName, $lastName, $username, $email, $password) {
                $userId = $users->create([
                    'email' => $email,
                    'username' => $username,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'balance_kopeks' => 0,
                ]);

                $ptero = $requirePtero();

                $payload = [
                    'email' => $email,
                    'username' => $username,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => $password,
                    'root_admin' => false,
                    'external_id' => 'billing:' . $userId,
                ];

                $respAttr = null;

                if (method_exists($ptero, 'createOrGetUser')) {
                    /** @phpstan-ignore-next-line */
                    $respAttr = $ptero->createOrGetUser($payload);
                } else {
                    $resp = $ptero->createUser($payload);
                    if (isset($resp['attributes']) && is_array($resp['attributes'])) {
                        $respAttr = $resp['attributes'];
                    } elseif (isset($resp['data']['attributes']) && is_array($resp['data']['attributes'])) {
                        $respAttr = $resp['data']['attributes'];
                    }
                }

                $pteroUserId = is_array($respAttr) && isset($respAttr['id']) ? (int)$respAttr['id'] : 0;
                if ($pteroUserId <= 0) {
                    throw new RuntimeException('Не смогли получить ID пользователя из ответа Pterodactyl.');
                }

                $users->setPteroUserId($userId, $pteroUserId);
                return $userId;
            });

            session_regenerate_id(true);
            $_SESSION['__uid__'] = $newUserId;

            $flash->add('success', 'Аккаунт создан. Пользователь в Pterodactyl тоже создан ✅');
            redirect('/dashboard');

        } catch (PterodactylException $e) {
            $flash->add('danger', 'Ошибка Pterodactyl: ' . $e->getMessage());
            redirect('/register');
        } catch (Throwable $e) {
            $flash->add('danger', 'Ошибка: ' . $e->getMessage());
            redirect('/register');
        }
    });

    $app->post('/logout', function() use ($auth, $csrf) {
        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }
        $auth->logout();
        redirect('/login');
    });

    $app->post('/profile/password', function() use ($auth, $csrf, $flash, $users, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['new_password_confirm'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $flash->add('danger', 'Заполни все поля для смены пароля.');
            redirect_back('/dashboard');
        }
        if (strlen($new) < 8) {
            $flash->add('danger', 'Новый пароль должен быть минимум 8 символов.');
            redirect_back('/dashboard');
        }
        if ($new !== $confirm) {
            $flash->add('danger', 'Новый пароль и подтверждение не совпадают.');
            redirect_back('/dashboard');
        }

        $oldHash = (string)($user['password_hash'] ?? '');
        if ($oldHash === '' || !password_verify($current, $oldHash)) {
            $flash->add('danger', 'Текущий пароль указан неверно.');
            redirect_back('/dashboard');
        }

        $pteroUserId = (int)($user['ptero_user_id'] ?? 0);
        if ($pteroUserId <= 0) {
            $flash->add('danger', 'У аккаунта нет привязки к Pterodactyl (ptero_user_id).');
            redirect_back('/dashboard');
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        try {
            $users->updatePasswordHash((int)$user['id'], $newHash);

            $ptero = $requirePtero();
            $payload = [
                'email' => (string)($user['email'] ?? ''),
                'username' => (string)($user['username'] ?? ''),
                'first_name' => (string)($user['first_name'] ?? ''),
                'last_name' => (string)($user['last_name'] ?? ''),
                'password' => $new,
            ];
            $ptero->updateUser($pteroUserId, $payload);

            $flash->add('success', 'Пароль обновлён в биллинге и Pterodactyl ✅');
            redirect_back('/dashboard');
        } catch (PterodactylException $e) {
            $users->updatePasswordHash((int)$user['id'], $oldHash);
            $flash->add('danger', 'Не удалось обновить пароль в Pterodactyl: ' . $e->getMessage());
            redirect_back('/dashboard');
        } catch (Throwable $e) {
            $users->updatePasswordHash((int)$user['id'], $oldHash);
            $flash->add('danger', 'Ошибка смены пароля: ' . $e->getMessage());
            redirect_back('/dashboard');
        }
    });

    $app->post('/profile/delete', function() use ($auth, $csrf, $flash, $users, $servers, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $pteroUserId = (int)($user['ptero_user_id'] ?? 0);
        if ($pteroUserId <= 0) {
            $flash->add('danger', 'У аккаунта нет привязки к Pterodactyl (ptero_user_id).');
            redirect_back('/dashboard');
        }

        try {
            $ptero = $requirePtero();

            $userServers = $servers->listByUserId((int)$user['id']);
            foreach ($userServers as $s) {
                $pteroServerId = (int)($s['ptero_server_id'] ?? 0);
                if ($pteroServerId <= 0) continue;
                try {
                    $ptero->deleteServer($pteroServerId, true);
                } catch (PterodactylException $e) {
                    if ($e->statusCode !== 404) {
                        throw $e;
                    }
                }
            }

            try {
                $ptero->deleteUser($pteroUserId, true);
            } catch (PterodactylException $e) {
                if ($e->statusCode !== 404) {
                    $msg = mb_strtolower($e->getMessage());
                    $looksLikeHasServers = str_contains($msg, 'active servers')
                        || str_contains($msg, 'servers attached')
                        || str_contains($msg, 'delete their servers');

                    if ($looksLikeHasServers) {
                        $page = 1;
                        $perPage = 100;
                        $maxPages = 50;

                        while (true) {
                            if ($page > $maxPages) break;
                            $resp = $ptero->listServers(['per_page' => $perPage, 'page' => $page]);
                            $rows = $resp['data'] ?? [];
                            if (!is_array($rows) || empty($rows)) break;

                            foreach ($rows as $row) {
                                $attr = $row['attributes'] ?? null;
                                if (!is_array($attr)) continue;
                                $uid = (int)($attr['user'] ?? 0);
                                if ($uid !== $pteroUserId) continue;
                                $sid = (int)($attr['id'] ?? 0);
                                if ($sid <= 0) continue;
                                try {
                                    $ptero->deleteServer($sid, true);
                                } catch (PterodactylException $e2) {
                                    if ($e2->statusCode !== 404) {
                                        throw $e2;
                                    }
                                }
                            }

                            $pagination = $resp['meta']['pagination'] ?? null;
                            if (is_array($pagination)) {
                                $current = (int)($pagination['current_page'] ?? $page);
                                $total = (int)($pagination['total_pages'] ?? $current);
                                if ($current >= $total) break;
                                $page = $current + 1;
                            } else {
                                if (count($rows) < $perPage) break;
                                $page++;
                            }
                        }

                        try {
                            $ptero->deleteUser($pteroUserId, true);
                        } catch (PterodactylException $e3) {
                            if ($e3->statusCode !== 404) {
                                throw $e3;
                            }
                        }
                    } else {
                        throw $e;
                    }
                }
            }

            $users->deleteById((int)$user['id']);
            $auth->logout();
            $flash->add('success', 'Аккаунт удалён.');
            redirect('/login');
        } catch (PterodactylException $e) {
            $flash->add('danger', 'Не удалось удалить аккаунт в Pterodactyl: ' . $e->getMessage());
            redirect_back('/dashboard');
        } catch (Throwable $e) {
            $flash->add('danger', 'Ошибка удаления аккаунта: ' . $e->getMessage());
            redirect_back('/dashboard');
        }
    });

    $app->get('/dashboard', function() use ($auth, $view, $flash, $csrf, $transactions, $servers, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $tx = $transactions->listByUserId((int)$user['id'], 10);
        $svRaw = $servers->listByUserId((int)$user['id']);

        $ptero = null;
        try {
            $ptero = $requirePtero();
        } catch (Throwable) {
            $ptero = null;
        }

        $sv = [];
        $autoRemoved = 0;
        foreach ($svRaw as $row) {
            if ($ptero) {
                try {
                    $ptero->getServer((int)($row['ptero_server_id'] ?? 0));
                } catch (PterodactylException $e) {
                    if ($e->statusCode === 404) {
                        $servers->deleteById((int)($row['id'] ?? 0));
                        $autoRemoved++;
                        continue;
                    }
                } catch (Throwable) {
                }
            }
            $sv[] = $row;
        }

        if ($autoRemoved > 0) {
            $flash->add('info', 'Синхронизация с Pterodactyl: удалено серверов из биллинга — ' . $autoRemoved . '.');
        }

        $view->render('dashboard', [
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'user' => $user,
            'transactions' => $tx,
            'servers' => $sv,
        ]);
    });

    $app->get('/plans', function() use ($auth, $view, $flash, $csrf, $plans) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $view->render('plans', [
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'user' => $user,
            'plans' => $plans->all(),
        ]);
    });

    $app->get('/buy', function() use ($auth, $view, $flash, $csrf, $plans) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $planId = (int)($_GET['plan_id'] ?? 0);
        $plan = $planId ? $plans->findById($planId) : null;
        if (!$plan) {
            http_response_code(404);
            echo 'Тариф не найден';
            return;
        }

        $locationId = (int)($_GET['location_id'] ?? 0);

        $view->render('buy', [
            'csrf'        => $csrf->token(),
            'flash'       => $flash->consume(),
            'user'        => $user,
            'plan'        => $plan,
            'location_id' => $locationId,
        ]);
    });


    $app->post('/buy', function() use ($pdo, $auth, $csrf, $flash, $plans, $users, $transactions, $servers, $requirePtero, $config) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $serverName = trim((string)($_POST['server_name'] ?? ''));
        if ($planId <= 0 || $serverName === '') {
            $flash->add('danger', 'Укажи имя сервера и тариф.');
            redirect('/plans');
        }

        $plan = $plans->findById($planId);
        if (!$plan) {
            $flash->add('danger', 'Тариф не найден.');
            redirect('/plans');
        }

        $eggId = (int)($plan['ptero_egg_id'] ?? 0);
        if ($eggId <= 0) {
            $flash->add('danger', 'В тарифе не указан ptero_egg_id.');
            redirect('/plans');
        }

        $dockerImage = $plan['docker_image'] ?? null;
        $startupCmd = $plan['startup'] ?? null;

        $price = (int)$plan['price_kopeks'];
        $balance = (int)$user['balance_kopeks'];
        if ($balance < $price) {
            $flash->add('danger', 'Недостаточно средств. Пополни баланс.');
            redirect('/topup');
        }

        $pteroUserId = (int)($user['ptero_user_id'] ?? 0);
        if ($pteroUserId <= 0) {
            $flash->add('danger', 'У аккаунта нет привязки к Pterodactyl (ptero_user_id).');
            redirect('/dashboard');
        }

        $env = json_decode((string)$plan['environment_json'], true);
        $limits = json_decode((string)$plan['limits_json'], true);
        $features = json_decode((string)$plan['feature_limits_json'], true);
        $locations = json_decode((string)$plan['deploy_locations_json'], true);
                $locationIdFromUser = (int)($_POST['location_id'] ?? 0);

        if ($locationIdFromUser > 0) {
            $allowed = $locations;
            if (!is_array($allowed)) {
                $allowed = [];
            }
            $allowed = array_map('intval', $allowed);

            if (!in_array($locationIdFromUser, $allowed, true)) {
                $flash->add('danger', 'Выбранная локация недоступна для этого тарифа.');
                redirect('/servers/new');
            }

            $locations = [$locationIdFromUser];
        }

        if (!is_array($env)) { $env = []; }
        if (!is_array($limits)) { $limits = []; }
        if (!is_array($features)) { $features = []; }
        if (!is_array($locations)) { $locations = []; }

        try {
            Database::transaction($pdo, function(PDO $pdo) use ($users, $transactions, $servers, $requirePtero, $user, $planId, $serverName, $price, $pteroUserId, $eggId, $dockerImage, $startupCmd, $env, $limits, $features, $locations) {
                $users->addBalance((int)$user['id'], -$price);

                $transactions->create((int)$user['id'], 'purchase_server', -$price, [
                    'plan_id' => $planId,
                    'server_name' => $serverName,
                ]);

                $payload = [
                    'name' => $serverName,
                    'user' => $pteroUserId,
                    'egg' => $eggId,
                    'environment' => $env,
                    'limits' => $limits,
                    'feature_limits' => $features,
                    'deploy' => [
                        'locations' => $locations,
                        'dedicated_ip' => false,
                        'port_range' => [],
                    ],
                    'start_on_completion' => true,
                ];

                if (is_string($dockerImage) && trim($dockerImage) !== '') {
                    $payload['docker_image'] = trim($dockerImage);
                }
                if (is_string($startupCmd) && trim($startupCmd) !== '') {
                    $payload['startup'] = trim($startupCmd);
                }

                $ptero = $requirePtero();
                $resp = $ptero->createServer($payload);

                $pteroServerId = null;
                $identifier = null;

                if (isset($resp['attributes']['id'])) {
                    $pteroServerId = (int)$resp['attributes']['id'];
                    $identifier = isset($resp['attributes']['identifier']) ? (string)$resp['attributes']['identifier'] : null;
                } elseif (isset($resp['data']['attributes']['id'])) {
                    $pteroServerId = (int)$resp['data']['attributes']['id'];
                    $identifier = isset($resp['data']['attributes']['identifier']) ? (string)$resp['data']['attributes']['identifier'] : null;
                }

                if (!$pteroServerId) {
                    throw new RuntimeException('Не смогли получить ID сервера из ответа Pterodactyl.');
                }

                $servers->create([
                    'user_id'          => (int)$user['id'],
                    'plan_id'          => (int)$planId,
                    'ptero_server_id'  => $pteroServerId,
                    'ptero_identifier' => $identifier,
                    'name'             => $serverName,
                    'status'           => 'active',
                ]);

                return true;
            });

            $flash->add('success', 'Сервер создан и оплачен ✅');
            redirect('/servers');

        } catch (PterodactylException $e) {
            $flash->add('danger', 'Ошибка Pterodactyl: ' . $e->getMessage());
            redirect('/plans');
        } catch (Throwable $e) {
            $flash->add('danger', 'Ошибка: ' . $e->getMessage());
            redirect('/plans');
        }
    });

    $app->post('/servers/delete', function() use ($auth, $csrf, $flash, $servers, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $serverId = (int)($_POST['server_id'] ?? 0);
        if ($serverId <= 0) {
            $flash->add('danger', 'Некорректный сервер.');
            redirect('/servers');
        }

        $server = $servers->findById($serverId);
        if (!$server || (int)$server['user_id'] !== (int)$user['id']) {
            $flash->add('danger', 'Сервер не найден.');
            redirect('/servers');
        }

        $missingInPanel = false;
        try {
            $ptero = $requirePtero();

            try {
                $ptero->deleteServer((int)$server['ptero_server_id'], true);
            } catch (PterodactylException $e) {
                if ($e->statusCode !== 404) {
                    throw $e;
                }
                $missingInPanel = true;
            }
        } catch (Throwable $e) {
            $flash->add('danger', 'Не удалось удалить сервер в Pterodactyl: ' . $e->getMessage());
            redirect('/servers');
        }

        $servers->deleteById($serverId);
        if ($missingInPanel) {
            $flash->add('success', 'Сервер уже был удалён в Pterodactyl. Запись удалена в биллинге.');
        } else {
            $flash->add('success', 'Сервер удалён. Все файлы на ноде также удалены, деньги на баланс не возвращаются.');
        }
        redirect('/servers');
    });

    $app->get('/servers', function() use ($auth, $view, $flash, $csrf, $servers, $config, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $panelUrl = rtrim((string)($config->get('PTERO_PANEL_URL', $config->get('PTERO_BASE_URL', '')) ?? ''), '/');

        $raw = $servers->listByUserId((int)$user['id']);
        $now = new DateTimeImmutable('now');
        $result = [];

        $ptero = null;
        try {
            $ptero = $requirePtero();
        } catch (Throwable) {
            $ptero = null;
        }

        $autoRemoved = 0;

        foreach ($raw as $row) {
            if ($ptero) {
                try {
                    $ptero->getServer((int)($row['ptero_server_id'] ?? 0));
                } catch (PterodactylException $e) {
                    if ($e->statusCode === 404) {
                        $servers->deleteById((int)($row['id'] ?? 0));
                        $autoRemoved++;
                        continue;
                    }
                } catch (Throwable) {
                }
            }

            $createdStr = (string)($row['created_at'] ?? '');
            try {
                $created = new DateTimeImmutable($createdStr ?: 'now');
            } catch (Throwable $e) {
                $created = $now;
            }

            $expires = $created->modify('+30 days');
            $isExpired = $expires <= $now;

            $status = (string)($row['status'] ?? '');
            if ($status === '' || $status === 'null') {
                $status = 'active';
            }

            if ($isExpired && $status !== 'frozen' && $ptero) {
                try {
                    $ptero->suspendServer((int)$row['ptero_server_id']);
                    $servers->updateStatus((int)$row['id'], 'frozen');
                    $status = 'frozen';
                } catch (Throwable) {
                }
            }

            $label = 'Неизвестно';
            $badge = 'secondary';
            switch ($status) {
                case 'active':
                    $label = 'Активен';
                    $badge = 'success';
                    break;
                case 'frozen':
                    $label = 'Заморожен';
                    $badge = 'warning';
                    break;
                case 'creating':
                    $label = 'Скачивается / создаётся';
                    $badge = 'info';
                    break;
            }

            $row['status']        = $status;
            $row['status_label']  = $label;
            $row['status_class']  = $badge;
            $row['expires_at']    = $expires->format('Y-m-d H:i:s');
            $row['is_expired']    = $isExpired;

            $result[] = $row;
        }

        if ($autoRemoved > 0) {
            $flash->add('info', 'Синхронизация с Pterodactyl: удалено серверов из биллинга — ' . $autoRemoved . '.');
        }

        $view->render('servers', [
            'csrf'      => $csrf->token(),
            'flash'     => $flash->consume(),
            'user'      => $user,
            'servers'   => $result,
            'panel_url' => $panelUrl,
        ]);
    });


    $app->get('/servers/new', function() use ($auth, $view, $flash, $csrf, $plans, $config, $requirePtero) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $allPlans = $plans->all();

        $locationsMap = [];
        try {
            $ptero = $requirePtero();
            $resp = $ptero->listLocations();
            $data = is_array($resp['data'] ?? null) ? $resp['data'] : [];
            foreach ($data as $row) {
                if (!is_array($row) || !isset($row['attributes'])) {
                    continue;
                }
                $attr = $row['attributes'];
                $id   = (int)($attr['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $locationsMap[$id] = [
                    'id'    => $id,
                    'short' => (string)($attr['short'] ?? ('LOC ' . $id)),
                    'long'  => (string)($attr['long'] ?? ''),
                ];
            }
        } catch (Throwable $e) {
        }

        $allLocations = [];
        foreach ($allPlans as &$plan) {
            $locIds = json_decode((string)($plan['deploy_locations_json'] ?? '[]'), true);
            if (!is_array($locIds)) {
                $locIds = [];
            }
            $locIds = array_map('intval', $locIds);
            $plan['location_ids'] = $locIds;

            foreach ($locIds as $lid) {
                if (!isset($allLocations[$lid])) {
                    $allLocations[$lid] = $locationsMap[$lid] ?? [
                        'id'    => $lid,
                        'short' => 'LOC ' . $lid,
                        'long'  => '',
                    ];
                }
            }
        }
        unset($plan);

        $selectedLocationId = (int)($_GET['location_id'] ?? 0);
        if ($selectedLocationId <= 0 && !empty($allLocations)) {
            $keys = array_keys($allLocations);
            $selectedLocationId = (int)reset($keys);
        }

        $view->render('servers_new', [
            'csrf'                => $csrf->token(),
            'flash'               => $flash->consume(),
            'user'                => $user,
            'plans'               => $allPlans,
            'all_locations'       => $allLocations,
            'selected_location_id'=> $selectedLocationId,
        ]);
    });


    $app->get('/topup', function() use ($auth, $view, $flash, $csrf) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $view->render('topup', [
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'user' => $user,
        ]);
    });

    $app->post('/topup-demo', function() use ($auth, $csrf, $flash, $users, $transactions) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $amount = rub_to_kopeks((string)($_POST['amount_rub'] ?? '0'));
        if ($amount <= 0) {
            $flash->add('danger', 'Укажи сумму пополнения.');
            redirect('/topup');
        }

        $users->addBalance((int)$user['id'], $amount);
        $transactions->create((int)$user['id'], 'demo_topup', $amount, ['note' => 'Demo пополнение (без платежки)']);

        $flash->add('success', 'Баланс пополнен (demo) ✅');
        redirect('/dashboard');
    });


    $app->get('/tickets', function() use ($auth, $view, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $list = $tickets->listByUserId((int)$user['id']);
        $view->render('tickets/index', [
            'user' => $user,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'tickets_list' => $list,
        ]);
    });

    $app->get('/tickets/new', function() use ($auth, $view, $csrf, $flash) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $view->render('tickets/new', [
            'user' => $user,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
        ]);
    });

    $app->post('/tickets/create', function() use ($auth, $csrf, $flash, $tickets, $ticketMessages) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $subject = trim((string)($_POST['subject'] ?? ''));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($subject === '' || $message === '') {
            $flash->add('danger', 'Укажи тему и сообщение.');
            redirect('/tickets/new');
        }

        if (mb_strlen($subject) > 255) {
            $flash->add('danger', 'Тема слишком длинная (макс. 255 символов).');
            redirect('/tickets/new');
        }

        $ticketId = $tickets->create((int)$user['id'], $subject);
        $ticketMessages->create($ticketId, (int)$user['id'], false, $message);

        $flash->add('success', 'Тикет создан. Поддержка ответит здесь.');
        redirect('/tickets/view?id=' . $ticketId);
    });

    $app->get('/tickets/view', function() use ($auth, $view, $csrf, $flash, $tickets, $ticketMessages) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        $ticketId = (int)($_GET['id'] ?? 0);
        if ($ticketId <= 0) {
            http_response_code(404);
            echo 'Ticket not found';
            return;
        }

        $ticket = $tickets->findById($ticketId);
        if (!$ticket || (int)($ticket['user_id'] ?? 0) !== (int)$user['id']) {
            http_response_code(404);
            echo 'Ticket not found';
            return;
        }

        $messages = $ticketMessages->listByTicketId($ticketId);

        $view->render('tickets/view', [
            'user' => $user,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'ticket' => $ticket,
            'messages' => $messages,
        ]);
    });

    $app->post('/tickets/reply', function() use ($auth, $csrf, $flash, $tickets, $ticketMessages) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));

        if ($ticketId <= 0) {
            $flash->add('danger', 'Некорректный тикет.');
            redirect('/tickets');
        }
        if ($message === '') {
            $flash->add('danger', 'Сообщение не должно быть пустым.');
            redirect('/tickets/view?id=' . $ticketId);
        }

        $ticket = $tickets->findById($ticketId);
        if (!$ticket || (int)($ticket['user_id'] ?? 0) !== (int)$user['id']) {
            $flash->add('danger', 'Тикет не найден.');
            redirect('/tickets');
        }

        if ((string)($ticket['status'] ?? '') !== 'open') {
            $flash->add('danger', 'Тикет закрыт. Нельзя отправлять новые сообщения.');
            redirect('/tickets/view?id=' . $ticketId);
        }

        $ticketMessages->create($ticketId, (int)$user['id'], false, $message);
        $tickets->touch($ticketId);

        redirect('/tickets/view?id=' . $ticketId);
    });

    $app->post('/tickets/close', function() use ($auth, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $user = $auth->user();
        if (!$user) {
            redirect('/login');
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $ticket = $ticketId > 0 ? $tickets->findById($ticketId) : null;
        if (!$ticket || (int)($ticket['user_id'] ?? 0) !== (int)$user['id']) {
            $flash->add('danger', 'Тикет не найден.');
            redirect('/tickets');
        }

        $tickets->setStatus($ticketId, 'closed');
        $flash->add('success', 'Тикет закрыт.');
        redirect('/tickets/view?id=' . $ticketId);
    });



    $app->get('/admin/users', function () use ($auth, $view, $users, $csrf, $flash) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $list = $users->listForAdmin(500, 0);

        $view->render('admin/users', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'users_list' => $list,
        ]);
    });

    $app->get('/admin/users/edit', function () use ($auth, $view, $users, $csrf, $flash) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $target = $users->findById($id);
        if (!$target) {
            http_response_code(404);
            echo 'User not found';
            return;
        }

        $view->render('admin/user_edit', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'target_user' => $target,
        ]);
    });

    $app->post('/admin/users/update', function () use ($auth, $users, $csrf, $flash, $requirePtero) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $id = (int)($_POST['user_id'] ?? 0);
        if ($id <= 0) {
            $flash->add('danger', 'Некорректный пользователь.');
            redirect('/admin/users');
        }

        $target = $users->findById($id);
        if (!$target) {
            $flash->add('danger', 'Пользователь не найден.');
            redirect('/admin/users');
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $balanceRub = trim((string)($_POST['balance_rub'] ?? '0'));
        $role = trim((string)($_POST['role'] ?? 'member'));

        if ($username === '' || $email === '') {
            $flash->add('danger', 'Username и email обязательны.');
            redirect('/admin/users/edit?id=' . $id);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash->add('danger', 'Некорректный email.');
            redirect('/admin/users/edit?id=' . $id);
        }
        if (mb_strlen($username) < 3 || mb_strlen($username) > 32 || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $username)) {
            $flash->add('danger', 'Username: 3–32 символа (латиница/цифры/._-).');
            redirect('/admin/users/edit?id=' . $id);
        }

        $allowedRoles = ['member', 'admin'];
        $role = strtolower($role);
        if (!in_array($role, $allowedRoles, true)) {
            $flash->add('danger', 'Некорректная роль.');
            redirect('/admin/users/edit?id=' . $id);
        }

        $balanceKopeks = rub_to_kopeks($balanceRub);
        if ($balanceKopeks < 0) {
            $flash->add('danger', 'Баланс не может быть отрицательным.');
            redirect('/admin/users/edit?id=' . $id);
        }

        $emailOwner = $users->findByEmail($email);
        if ($emailOwner && (int)($emailOwner['id'] ?? 0) !== $id) {
            $flash->add('danger', 'Такой email уже используется другим пользователем.');
            redirect('/admin/users/edit?id=' . $id);
        }
        $unameOwner = $users->findByUsername($username);
        if ($unameOwner && (int)($unameOwner['id'] ?? 0) !== $id) {
            $flash->add('danger', 'Такой username уже занят.');
            redirect('/admin/users/edit?id=' . $id);
        }

        $oldEmail = (string)($target['email'] ?? '');
        $oldUsername = (string)($target['username'] ?? '');
        $oldBalance = (int)($target['balance_kopeks'] ?? 0);
        $oldRole = (string)($target['role'] ?? 'member');

        try {
            $users->updateAdminFields($id, $email, $username, $balanceKopeks, $role);

            $pteroUserId = (int)($target['ptero_user_id'] ?? 0);
            if ($pteroUserId > 0 && ($email !== $oldEmail || $username !== $oldUsername)) {
                $ptero = $requirePtero();
                $payload = [
                    'email' => $email,
                    'username' => $username,
                    'first_name' => (string)($target['first_name'] ?? $username),
                    'last_name' => (string)($target['last_name'] ?? 'User'),
                ];
                $ptero->updateUser($pteroUserId, $payload);
            }

            $flash->add('success', 'Пользователь обновлён ✅');
            redirect('/admin/users');
        } catch (PterodactylException $e) {
            $users->updateAdminFields($id, $oldEmail, $oldUsername, $oldBalance, $oldRole !== '' ? $oldRole : 'member');
            $flash->add('danger', 'Не удалось обновить пользователя в Pterodactyl: ' . $e->getMessage());
            redirect('/admin/users/edit?id=' . $id);
        } catch (Throwable $e) {
            $users->updateAdminFields($id, $oldEmail, $oldUsername, $oldBalance, $oldRole !== '' ? $oldRole : 'member');
            $flash->add('danger', 'Ошибка сохранения: ' . $e->getMessage());
            redirect('/admin/users/edit?id=' . $id);
        }
    });

    $app->post('/admin/users/delete', function () use ($auth, $users, $servers, $csrf, $flash, $requirePtero) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }


        $id = (int)($_POST['user_id'] ?? 0);
        if ($id <= 0) {
            $flash->add('danger', 'Некорректный пользователь.');
            redirect('/admin/users');
        }

        $target = $users->findById($id);
        if (!$target) {
            $flash->add('danger', 'Пользователь не найден.');
            redirect('/admin/users');
        }

        $pteroUserId = (int)($target['ptero_user_id'] ?? 0);
        if ($pteroUserId > 0) {
            try {
                $ptero = $requirePtero();

                $userServers = $servers->listByUserId($id);
                foreach ($userServers as $s) {
                    $pteroServerId = (int)($s['ptero_server_id'] ?? 0);
                    if ($pteroServerId <= 0) {
                        continue;
                    }
                    try {
                        $ptero->deleteServer($pteroServerId, true);
                    } catch (PterodactylException $e) {
                        if ($e->statusCode !== 404) {
                            throw $e;
                        }
                    }
                }

                try {
                    $ptero->deleteUser($pteroUserId, true);
                } catch (PterodactylException $e) {
                    if ($e->statusCode !== 404) {
                        $msg = mb_strtolower($e->getMessage());
                        $looksLikeHasServers = str_contains($msg, 'active servers')
                            || str_contains($msg, 'servers attached')
                            || str_contains($msg, 'delete their servers');

                        if ($looksLikeHasServers) {
                            $page = 1;
                            $perPage = 100;
                            $maxPages = 50;
                            while (true) {
                                if ($page > $maxPages) {
                                    break;
                                }

                                $resp = $ptero->listServers(['per_page' => $perPage, 'page' => $page]);
                                $rows = $resp['data'] ?? [];
                                if (!is_array($rows) || empty($rows)) {
                                    break;
                                }

                                foreach ($rows as $row) {
                                    $attr = $row['attributes'] ?? null;
                                    if (!is_array($attr)) {
                                        continue;
                                    }
                                    $uid = (int)($attr['user'] ?? 0);
                                    if ($uid !== $pteroUserId) {
                                        continue;
                                    }
                                    $sid = (int)($attr['id'] ?? 0);
                                    if ($sid <= 0) {
                                        continue;
                                    }
                                    try {
                                        $ptero->deleteServer($sid, true);
                                    } catch (PterodactylException $e2) {
                                        if ($e2->statusCode !== 404) {
                                            throw $e2;
                                        }
                                    }
                                }

                                $pagination = $resp['meta']['pagination'] ?? null;
                                if (is_array($pagination)) {
                                    $current = (int)($pagination['current_page'] ?? $page);
                                    $total = (int)($pagination['total_pages'] ?? $current);
                                    if ($current >= $total) {
                                        break;
                                    }
                                    $page = $current + 1;
                                } else {
                                    if (count($rows) < $perPage) {
                                        break;
                                    }
                                    $page++;
                                }
                            }

                            try {
                                $ptero->deleteUser($pteroUserId, true);
                            } catch (PterodactylException $e3) {
                                if ($e3->statusCode !== 404) {
                                    throw $e3;
                                }
                            }
                        } else {
                            throw $e;
                        }
                    }
                }
            } catch (Throwable $e) {
                $flash->add('danger', 'Не удалось удалить пользователя в Pterodactyl: ' . $e->getMessage());
                redirect('/admin/users');
            }
        }

        $users->deleteById($id);

        if ($id === (int)($me['id'] ?? 0)) {
            $auth->logout();
            $flash->add('success', 'Аккаунт удалён. Войдите снова.');
            redirect('/login');
        }

        $flash->add('success', 'Пользователь удалён.');
        redirect('/admin/users');
    });

    $app->get('/admin/tickets', function () use ($auth, $view, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $status = (string)($_GET['status'] ?? 'open');
        if ($status !== 'open' && $status !== 'closed' && $status !== 'all') {
            $status = 'open';
        }

        $list = $tickets->listAll($status === 'all' ? null : $status, 500, 0);

        $view->render('admin/tickets', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'status_filter' => $status,
            'tickets_list' => $list,
        ]);
    });

    $app->get('/admin/tickets/view', function () use ($auth, $view, $csrf, $flash, $tickets, $ticketMessages, $users) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $ticketId = (int)($_GET['id'] ?? 0);
        if ($ticketId <= 0) {
            http_response_code(404);
            echo 'Ticket not found';
            return;
        }

        $ticket = $tickets->findById($ticketId);
        if (!$ticket) {
            http_response_code(404);
            echo 'Ticket not found';
            return;
        }

        $owner = $users->findById((int)($ticket['user_id'] ?? 0));
        $messages = $ticketMessages->listByTicketId($ticketId);

        $view->render('admin/ticket_view', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'ticket' => $ticket,
            'owner' => $owner,
            'messages' => $messages,
        ]);
    });

    $app->post('/admin/tickets/reply', function () use ($auth, $csrf, $flash, $tickets, $ticketMessages) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        $message = trim((string)($_POST['message'] ?? ''));

        if ($ticketId <= 0) {
            $flash->add('danger', 'Некорректный тикет.');
            redirect('/admin/tickets');
        }
        if ($message === '') {
            $flash->add('danger', 'Сообщение не должно быть пустым.');
            redirect('/admin/tickets/view?id=' . $ticketId);
        }

        $ticket = $tickets->findById($ticketId);
        if (!$ticket) {
            $flash->add('danger', 'Тикет не найден.');
            redirect('/admin/tickets');
        }

        $ticketMessages->create($ticketId, (int)$me['id'], true, $message);
        $tickets->touch($ticketId);

        redirect('/admin/tickets/view?id=' . $ticketId);
    });

    $app->post('/admin/tickets/close', function () use ($auth, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $flash->add('danger', 'Некорректный тикет.');
            redirect('/admin/tickets');
        }

        $tickets->setStatus($ticketId, 'closed');
        $flash->add('success', 'Тикет закрыт.');
        redirect('/admin/tickets/view?id=' . $ticketId);
    });

    $app->post('/admin/tickets/reopen', function () use ($auth, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $flash->add('danger', 'Некорректный тикет.');
            redirect('/admin/tickets');
        }

        $tickets->setStatus($ticketId, 'open');
        $flash->add('success', 'Тикет снова открыт.');
        redirect('/admin/tickets/view?id=' . $ticketId);
    });

    $app->post('/admin/tickets/delete', function () use ($auth, $csrf, $flash, $tickets) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $ticketId = (int)($_POST['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            $flash->add('danger', 'Некорректный тикет.');
            redirect('/admin/tickets');
        }

        $ticket = $tickets->findById($ticketId);
        if (!$ticket) {
            $flash->add('danger', 'Тикет не найден.');
            redirect('/admin/tickets');
        }

        $tickets->deleteById($ticketId);
        $flash->add('success', 'Тикет удалён.');
        redirect('/admin/tickets');
    });

    $app->get('/admin/plans', function () use ($auth, $view, $csrf, $flash, $plans) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $view->render('admin/plans/index', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'plans_list' => $plans->all(),
        ]);
    });

    $app->get('/admin/plans/new', function () use ($auth, $view, $csrf, $flash) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $view->render('admin/plans/form', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'plan' => null,
        ]);
    });

    $app->get('/admin/plans/edit', function () use ($auth, $view, $csrf, $flash, $plans) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $plan = $id > 0 ? $plans->findById($id) : null;
        if (!$plan) {
            http_response_code(404);
            echo 'Plan not found';
            return;
        }

        $view->render('admin/plans/form', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
            'plan' => $plan,
        ]);
    });

    $app->post('/admin/plans/create', function () use ($auth, $csrf, $flash, $plans) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'price_kopeks' => rub_to_kopeks((string)($_POST['price_rub'] ?? '0')),
            'ptero_egg_id' => (int)($_POST['ptero_egg_id'] ?? 0),
            'docker_image' => trim((string)($_POST['docker_image'] ?? '')),
            'startup' => trim((string)($_POST['startup'] ?? '')),
            'environment_json' => (string)($_POST['environment_json'] ?? '{}'),
            'limits_json' => (string)($_POST['limits_json'] ?? '{}'),
            'feature_limits_json' => (string)($_POST['feature_limits_json'] ?? '{}'),
            'deploy_locations_json' => (string)($_POST['deploy_locations_json'] ?? '[]'),
        ];

        if ($data['name'] === '' || $data['price_kopeks'] <= 0 || $data['ptero_egg_id'] <= 0) {
            $flash->add('danger', 'Заполни имя, цену и Egg ID.');
            redirect('/admin/plans/new');
        }

        foreach (['environment_json','limits_json','feature_limits_json','deploy_locations_json'] as $k) {
            $decoded = json_decode((string)$data[$k], true);
            if ($decoded === null && trim((string)$data[$k]) !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
                $flash->add('danger', 'Некорректный JSON в поле ' . $k . ': ' . json_last_error_msg());
                redirect('/admin/plans/new');
            }
        }

        if ($data['description'] === '') { $data['description'] = null; }
        if ($data['docker_image'] === '') { $data['docker_image'] = null; }
        if ($data['startup'] === '') { $data['startup'] = null; }

        $plans->create($data);
        $flash->add('success', 'Тариф добавлен.');
        redirect('/admin/plans');
    });

    $app->post('/admin/plans/update', function () use ($auth, $csrf, $flash, $plans) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $flash->add('danger', 'Некорректный тариф.');
            redirect('/admin/plans');
        }

        $data = [
            'name' => trim((string)($_POST['name'] ?? '')),
            'description' => trim((string)($_POST['description'] ?? '')),
            'price_kopeks' => rub_to_kopeks((string)($_POST['price_rub'] ?? '0')),
            'ptero_egg_id' => (int)($_POST['ptero_egg_id'] ?? 0),
            'docker_image' => trim((string)($_POST['docker_image'] ?? '')),
            'startup' => trim((string)($_POST['startup'] ?? '')),
            'environment_json' => (string)($_POST['environment_json'] ?? '{}'),
            'limits_json' => (string)($_POST['limits_json'] ?? '{}'),
            'feature_limits_json' => (string)($_POST['feature_limits_json'] ?? '{}'),
            'deploy_locations_json' => (string)($_POST['deploy_locations_json'] ?? '[]'),
        ];

        if ($data['name'] === '' || $data['price_kopeks'] <= 0 || $data['ptero_egg_id'] <= 0) {
            $flash->add('danger', 'Заполни имя, цену и Egg ID.');
            redirect('/admin/plans/edit?id=' . $id);
        }

        foreach (['environment_json','limits_json','feature_limits_json','deploy_locations_json'] as $k) {
            $decoded = json_decode((string)$data[$k], true);
            if ($decoded === null && trim((string)$data[$k]) !== 'null' && json_last_error() !== JSON_ERROR_NONE) {
                $flash->add('danger', 'Некорректный JSON в поле ' . $k . ': ' . json_last_error_msg());
                redirect('/admin/plans/edit?id=' . $id);
            }
        }

        if ($data['description'] === '') { $data['description'] = null; }
        if ($data['docker_image'] === '') { $data['docker_image'] = null; }
        if ($data['startup'] === '') { $data['startup'] = null; }

        $plans->update($id, $data);
        $flash->add('success', 'Тариф обновлён.');
        redirect('/admin/plans/edit?id=' . $id);
    });

    $app->post('/admin/plans/delete', function () use ($auth, $csrf, $flash, $plans) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        if (!$csrf->check($_POST['_csrf'] ?? null)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $flash->add('danger', 'Некорректный тариф.');
            redirect('/admin/plans');
        }

        try {
            $plans->delete($id);
            $flash->add('success', 'Тариф удалён.');
        } catch (Throwable $e) {
            $flash->add('danger', 'Не удалось удалить тариф (возможно, есть серверы на этом тарифе): ' . $e->getMessage());
        }
        redirect('/admin/plans');
    });

    $app->get('/admin/settings', function () use ($auth, $view, $csrf, $flash) {
        $auth->requireLogin();
        $me = $auth->user();
        if (!$me || !(function_exists('is_admin') && is_admin($me))) {
            http_response_code(403);
            echo 'Forbidden';
            return;
        }

        $view->render('admin/settings', [
            'user' => $me,
            'csrf' => $csrf->token(),
            'flash' => $flash->consume(),
        ]);
    });

    return $app;
}
