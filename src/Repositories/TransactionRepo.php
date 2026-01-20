<?php

declare(strict_types=1);

final class TransactionRepo
{
    public function __construct(private readonly PDO $pdo) {}

    public function create(int $userId, string $type, int $amountKopeks, ?array $meta = null): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO transactions (user_id, type, amount_kopeks, meta_json, created_at)
             VALUES (:user_id, :type, :amount, :meta_json, :created_at)'
        );

        $metaJson = $meta === null ? null : json_encode($meta, JSON_UNESCAPED_UNICODE);

        $stmt->execute([
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amountKopeks,
            'meta_json' => $metaJson,
            'created_at' => now_iso(),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /** @return array<int, array<string,mixed>> */
    public function listByUserId(int $userId, int $limit = 15): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM transactions WHERE user_id = :user_id ORDER BY id DESC LIMIT :lim'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}
