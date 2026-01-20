<?php

declare(strict_types=1);

final class TicketMessageRepo
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(int $ticketId, int $userId, bool $isAdmin, string $message): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO ticket_messages (ticket_id, user_id, is_admin, message, created_at)
             VALUES (:ticket_id, :user_id, :is_admin, :message, :created_at)'
        );

        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'is_admin' => $isAdmin ? 1 : 0,
            'message' => $message,
            'created_at' => now_iso(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    public function listByTicketId(int $ticketId): array
    {
        $sql = '
            SELECT
              m.*,
              u.username AS user_username,
              u.email AS user_email
            FROM ticket_messages m
            JOIN users u ON u.id = m.user_id
            WHERE m.ticket_id = :tid
            ORDER BY m.id ASC
        ';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['tid' => $ticketId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}
