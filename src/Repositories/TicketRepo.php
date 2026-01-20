<?php

declare(strict_types=1);

final class TicketRepo
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(int $userId, string $subject): int
    {
        $now = now_iso();
        $stmt = $this->pdo->prepare(
            'INSERT INTO tickets (user_id, subject, status, created_at, updated_at)
             VALUES (:user_id, :subject, :status, :created_at, :updated_at)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'subject' => $subject,
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $ticketId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $ticketId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function listByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE user_id = :uid ORDER BY updated_at DESC, id DESC');
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function listAll(?string $status = null, int $limit = 200, int $offset = 0): array
    {
        $limit = max(1, min(1000, $limit));
        $offset = max(0, $offset);

        $where = '';
        $params = [];
        if ($status !== null && $status !== '') {
            $where = 'WHERE t.status = :status';
            $params['status'] = $status;
        }

        $sql = "
            SELECT
              t.*,
              u.username AS user_username,
              u.email AS user_email
            FROM tickets t
            JOIN users u ON u.id = t.user_id
            $where
            ORDER BY t.updated_at DESC, t.id DESC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function touch(int $ticketId): void
    {
        $stmt = $this->pdo->prepare('UPDATE tickets SET updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'updated_at' => now_iso(),
            'id' => $ticketId,
        ]);
    }

    public function setStatus(int $ticketId, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE tickets SET status = :status, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'updated_at' => now_iso(),
            'id' => $ticketId,
        ]);
    }

    public function deleteById(int $ticketId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM tickets WHERE id = :id');
        $stmt->execute(['id' => $ticketId]);
    }
}
