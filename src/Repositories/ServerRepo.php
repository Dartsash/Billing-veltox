<?php

declare(strict_types=1);

final class ServerRepo
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO servers (user_id, plan_id, ptero_server_id, ptero_identifier, name, status, created_at)
             VALUES (:user_id, :plan_id, :ptero_server_id, :ptero_identifier, :name, :status, :created_at)'
        );
        $stmt->execute([
            'user_id'          => (int)$data['user_id'],
            'plan_id'          => (int)$data['plan_id'],
            'ptero_server_id'  => (int)$data['ptero_server_id'],
            'ptero_identifier' => $data['ptero_identifier'] ?? null,
            'name'             => (string)$data['name'],
            'status'           => $data['status'] ?? null,
            'created_at'       => now_iso(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<int, array<string,mixed>> */
    public function listByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, p.name AS plan_name, p.price_kopeks AS plan_price_kopeks
             FROM servers s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.user_id = :user_id
             ORDER BY s.id DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE servers SET status = :status WHERE id = :id');
        $stmt->execute([
            'status' => $status,
            'id'     => $id,
        ]);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, p.name AS plan_name, p.price_kopeks AS plan_price_kopeks
             FROM servers s
             JOIN plans p ON p.id = s.plan_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteById(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM servers WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
