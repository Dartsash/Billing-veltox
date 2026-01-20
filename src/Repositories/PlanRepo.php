<?php

declare(strict_types=1);

final class PlanRepo
{
    public function __construct(private PDO $pdo)
    {
    }

    public function truncate(): void
    {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $this->pdo->exec('DELETE FROM plans');
            $this->pdo->exec("DELETE FROM sqlite_sequence WHERE name='plans'");
            return;
        }

        $this->pdo->exec('DELETE FROM plans');
        $this->pdo->exec('ALTER TABLE plans AUTO_INCREMENT = 1');
    }

    public function create(array $plan): int
    {
        $sql = "INSERT INTO plans
            (name, description, price_kopeks, ptero_egg_id, docker_image, startup,
             environment_json, limits_json, feature_limits_json, deploy_locations_json,
             created_at, updated_at)
            VALUES
            (:name, :description, :price_kopeks, :ptero_egg_id, :docker_image, :startup,
             :environment_json, :limits_json, :feature_limits_json, :deploy_locations_json,
             :created_at, :updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':name' => (string)($plan['name'] ?? ''),
            ':description' => $plan['description'] ?? null,
            ':price_kopeks' => (int)($plan['price_kopeks'] ?? 0),
            ':ptero_egg_id' => (int)($plan['ptero_egg_id'] ?? 0),
            ':docker_image' => $plan['docker_image'] ?? null,
            ':startup' => $plan['startup'] ?? null,
            ':environment_json' => (string)($plan['environment_json'] ?? '{}'),
            ':limits_json' => (string)($plan['limits_json'] ?? '{}'),
            ':feature_limits_json' => (string)($plan['feature_limits_json'] ?? '{}'),
            ':deploy_locations_json' => (string)($plan['deploy_locations_json'] ?? '[]'),
            ':created_at' => (string)($plan['created_at'] ?? date('c')),
            ':updated_at' => (string)($plan['updated_at'] ?? date('c')),
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $plan): void
    {
        $sql = "UPDATE plans SET
            name = :name,
            description = :description,
            price_kopeks = :price_kopeks,
            ptero_egg_id = :ptero_egg_id,
            docker_image = :docker_image,
            startup = :startup,
            environment_json = :environment_json,
            limits_json = :limits_json,
            feature_limits_json = :feature_limits_json,
            deploy_locations_json = :deploy_locations_json,
            updated_at = :updated_at
            WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':name' => (string)($plan['name'] ?? ''),
            ':description' => $plan['description'] ?? null,
            ':price_kopeks' => (int)($plan['price_kopeks'] ?? 0),
            ':ptero_egg_id' => (int)($plan['ptero_egg_id'] ?? 0),
            ':docker_image' => $plan['docker_image'] ?? null,
            ':startup' => $plan['startup'] ?? null,
            ':environment_json' => (string)($plan['environment_json'] ?? '{}'),
            ':limits_json' => (string)($plan['limits_json'] ?? '{}'),
            ':feature_limits_json' => (string)($plan['feature_limits_json'] ?? '{}'),
            ':deploy_locations_json' => (string)($plan['deploy_locations_json'] ?? '[]'),
            ':updated_at' => date('c'),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM plans WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM plans ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM plans WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        return $this->find($id);
    }
}

