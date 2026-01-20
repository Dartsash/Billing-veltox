<?php

declare(strict_types=1);

require __DIR__ . '/../src/Config.php';
require __DIR__ . '/../src/Database.php';
require __DIR__ . '/../src/Helpers.php';
require __DIR__ . '/../src/Repositories/PlanRepo.php';

$baseDir = dirname(__DIR__);
$config = Config::load($baseDir);
$pdo = Database::connect($config);
$repo = new PlanRepo($pdo);

$repo->truncate();


$plans = [
    [
        'name' => 'Starter (512MB)',
        'description' => 'Для тестов',
        'price_kopeks' => 9900,
        'ptero_egg_id' => 1,
        'docker_image' => null,
        'startup' => null,
        'environment_json' => json_encode([
            'SERVER_JARFILE' => 'server.jar',
        ], JSON_UNESCAPED_UNICODE),
        'limits_json' => json_encode([
            'memory' => 512,
            'swap' => 0,
            'disk' => 1024,
            'io' => 500,
            'cpu' => 50,
            'oom_disabled' => false,
        ], JSON_UNESCAPED_UNICODE),
        'feature_limits_json' => json_encode([
            'databases' => 0,
            'allocations' => 1,
            'backups' => 1,
        ], JSON_UNESCAPED_UNICODE),
        'deploy_locations_json' => json_encode([1], JSON_UNESCAPED_UNICODE),
    ],
    [
        'name' => 'Pro (2GB)',
        'description' => 'Универсальный тариф: больше RAM/CPU.',
        'price_kopeks' => 29900,
        'ptero_egg_id' => 1,
        'docker_image' => null,
        'startup' => null,
        'environment_json' => json_encode([
            'SERVER_JARFILE' => 'server.jar',
        ], JSON_UNESCAPED_UNICODE),
        'limits_json' => json_encode([
            'memory' => 2048,
            'swap' => 0,
            'disk' => 5120,
            'io' => 500,
            'cpu' => 100,
            'oom_disabled' => false,
        ], JSON_UNESCAPED_UNICODE),
        'feature_limits_json' => json_encode([
            'databases' => 1,
            'allocations' => 1,
            'backups' => 3,
        ], JSON_UNESCAPED_UNICODE),
        'deploy_locations_json' => json_encode([1], JSON_UNESCAPED_UNICODE),
    ],
];

foreach ($plans as $p) {
    $repo->create($p);
}

echo "OK: seeded " . count($plans) . " plans\n";
