<?php

declare(strict_types=1);

require __DIR__ . '/../src/Database.php';

$pdo = Database::connect();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

try {
    if ($driver === 'mysql') {
        $stmts = [
            "CREATE TABLE IF NOT EXISTS users (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL,
                username VARCHAR(64) NOT NULL,
                first_name VARCHAR(120) NOT NULL,
                last_name VARCHAR(120) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                ptero_user_id BIGINT UNSIGNED NULL,
                balance_kopeks BIGINT NOT NULL DEFAULT 0,
                role VARCHAR(32) NOT NULL DEFAULT 'member',
                created_at VARCHAR(32) NOT NULL,
                updated_at VARCHAR(32) NOT NULL,
                UNIQUE KEY uq_users_email (email),
                UNIQUE KEY uq_users_username (username)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS plans (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                description TEXT NULL,
                price_kopeks BIGINT NOT NULL,
                ptero_egg_id BIGINT UNSIGNED NOT NULL,
                docker_image VARCHAR(191) NULL,
                startup TEXT NULL,
                environment_json LONGTEXT NOT NULL,
                limits_json LONGTEXT NOT NULL,
                feature_limits_json LONGTEXT NOT NULL,
                deploy_locations_json LONGTEXT NOT NULL,
                created_at VARCHAR(32) NOT NULL,
                updated_at VARCHAR(32) NOT NULL,
                KEY idx_plans_price (price_kopeks)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS servers (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                plan_id BIGINT UNSIGNED NOT NULL,
                ptero_server_id BIGINT UNSIGNED NOT NULL,
                ptero_identifier VARCHAR(64) NULL,
                name VARCHAR(120) NOT NULL,
                status VARCHAR(32) NULL,
                created_at VARCHAR(32) NOT NULL,
                KEY idx_servers_user_id (user_id),
                KEY idx_servers_plan_id (plan_id),
                CONSTRAINT fk_servers_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_servers_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS transactions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                type VARCHAR(32) NOT NULL,
                amount_kopeks BIGINT NOT NULL,
                meta_json LONGTEXT NULL,
                created_at VARCHAR(32) NOT NULL,
                KEY idx_transactions_user_id (user_id),
                CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS tickets (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                subject VARCHAR(255) NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'open',
                created_at VARCHAR(32) NOT NULL,
                updated_at VARCHAR(32) NOT NULL,
                KEY idx_tickets_user_id (user_id),
                KEY idx_tickets_status (status),
                CONSTRAINT fk_tickets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS ticket_messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ticket_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NOT NULL,
                is_admin TINYINT(1) NOT NULL DEFAULT 0,
                message TEXT NOT NULL,
                created_at VARCHAR(32) NOT NULL,
                KEY idx_ticket_messages_ticket_id (ticket_id),
                KEY idx_ticket_messages_user_id (user_id),
                CONSTRAINT fk_ticket_messages_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                CONSTRAINT fk_ticket_messages_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ];

        foreach ($stmts as $sql) {
            $pdo->exec($sql);
        }

        $roleCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
        if (!$roleCol) {
            $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(32) NOT NULL DEFAULT 'member' AFTER balance_kopeks");
        }

        echo "OK: MySQL migrations applied\n";
        exit(0);
    }

    $sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT NOT NULL UNIQUE,
  username TEXT NOT NULL UNIQUE,
  first_name TEXT NOT NULL,
  last_name TEXT NOT NULL,
  password_hash TEXT NOT NULL,
  ptero_user_id INTEGER,
  balance_kopeks INTEGER NOT NULL DEFAULT 0,
  role TEXT NOT NULL DEFAULT 'member',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS plans (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  description TEXT,
  price_kopeks INTEGER NOT NULL,
  ptero_egg_id INTEGER NOT NULL,
  docker_image TEXT,
  startup TEXT,
  environment_json TEXT NOT NULL,
  limits_json TEXT NOT NULL,
  feature_limits_json TEXT NOT NULL,
  deploy_locations_json TEXT NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS servers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  plan_id INTEGER NOT NULL,
  ptero_server_id INTEGER NOT NULL,
  ptero_identifier TEXT,
  name TEXT NOT NULL,
  status TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  type TEXT NOT NULL,
  amount_kopeks INTEGER NOT NULL,
  meta_json TEXT,
  created_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tickets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  subject TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'open',
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ticket_messages (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  ticket_id INTEGER NOT NULL,
  user_id INTEGER NOT NULL,
  is_admin INTEGER NOT NULL DEFAULT 0,
  message TEXT NOT NULL,
  created_at TEXT NOT NULL,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_servers_user_id ON servers(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_user_id ON tickets(user_id);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);
CREATE INDEX IF NOT EXISTS idx_ticket_messages_ticket_id ON ticket_messages(ticket_id);
SQL;

    $pdo->exec($sql);

    $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
    $hasRole = false;
    if (is_array($cols)) {
        foreach ($cols as $c) {
            if (isset($c['name']) && (string)$c['name'] === 'role') {
                $hasRole = true;
                break;
            }
        }
    }
    if (!$hasRole) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role TEXT NOT NULL DEFAULT 'member'");
    }

    echo "OK: SQLite migrations applied\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}
