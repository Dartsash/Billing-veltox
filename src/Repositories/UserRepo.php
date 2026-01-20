<?php

declare(strict_types=1);

final class UserRepo
{
    public function __construct(private readonly PDO $pdo) {}

    private ?bool $hasRoleColumn = null;

    private function usersHasRoleColumn(): bool
    {
        if ($this->hasRoleColumn !== null) {
            return $this->hasRoleColumn;
        }

        $driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        try {
            if ($driver === 'mysql') {
                $row = $this->pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
                $this->hasRoleColumn = (bool)$row;
                return $this->hasRoleColumn;
            }

            $cols = $this->pdo->query("PRAGMA table_info(users)")->fetchAll();
            if (is_array($cols)) {
                foreach ($cols as $c) {
                    if (isset($c['name']) && (string)$c['name'] === 'role') {
                        $this->hasRoleColumn = true;
                        return true;
                    }
                }
            }
        } catch (Throwable) {
        }

        $this->hasRoleColumn = false;
        return false;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $now = now_iso();
        $role = (string)($data['role'] ?? 'member');
        if ($role === '') {
            $role = 'member';
        }
        $sql = 'INSERT INTO users (email, username, first_name, last_name, password_hash, balance_kopeks, created_at, updated_at)
                VALUES (:email, :username, :first_name, :last_name, :password_hash, :balance_kopeks, :created_at, :updated_at)';
        $params = [
            'email' => $data['email'],
            'username' => $data['username'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'password_hash' => $data['password_hash'],
            'balance_kopeks' => (int)($data['balance_kopeks'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if ($this->usersHasRoleColumn()) {
            $sql = 'INSERT INTO users (email, username, first_name, last_name, password_hash, balance_kopeks, role, created_at, updated_at)
                    VALUES (:email, :username, :first_name, :last_name, :password_hash, :balance_kopeks, :role, :created_at, :updated_at)';
            $params['role'] = $role;
        }

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute($params);

        return (int)$this->pdo->lastInsertId();
    }

    public function setPteroUserId(int $id, int $pteroUserId): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET ptero_user_id = :ptero_user_id, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'ptero_user_id' => $pteroUserId,
            'updated_at' => now_iso(),
            'id' => $id,
        ]);
    }

    public function updatePasswordHash(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'password_hash' => $passwordHash,
            'updated_at' => now_iso(),
            'id' => $id,
        ]);
    }

    public function addBalance(int $id, int $deltaKopeks): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET balance_kopeks = balance_kopeks + :delta, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'delta' => $deltaKopeks,
            'updated_at' => now_iso(),
            'id' => $id,
        ]);
    }

    public function setBalance(int $id, int $balanceKopeks): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET balance_kopeks = :balance, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'balance' => $balanceKopeks,
            'updated_at' => now_iso(),
            'id' => $id,
        ]);
    }

    public function updateAdminFields(int $id, string $email, string $username, int $balanceKopeks, string $role): void
    {
        $sql = 'UPDATE users
                SET email = :email,
                    username = :username,
                    balance_kopeks = :balance,
                    updated_at = :updated_at
                WHERE id = :id';

        $params = [
            'email' => $email,
            'username' => $username,
            'balance' => $balanceKopeks,
            'updated_at' => now_iso(),
            'id' => $id,
        ];

        if ($this->usersHasRoleColumn()) {
            $sql = 'UPDATE users
                    SET email = :email,
                        username = :username,
                        balance_kopeks = :balance,
                        role = :role,
                        updated_at = :updated_at
                    WHERE id = :id';
            $params['role'] = $role;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Admin list with quick stats.
     * @return array<int, array<string,mixed>>
     */
    public function listForAdmin(int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(1000, $limit));
        $offset = max(0, $offset);

        $sql = '
            SELECT
              u.*, 
              (SELECT COUNT(*) FROM servers s WHERE s.user_id = u.id) AS servers_count
            FROM users u
            ORDER BY u.id DESC
            LIMIT :limit OFFSET :offset
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
