<?php
final class Database
{
    private static bool $envLoaded = false;

    private static function loadEnvIfNeeded(): void
    {
        if (self::$envLoaded) return;

        if (getenv('DB_DRIVER')) {
            self::$envLoaded = true;
            return;
        }

        $envPath = __DIR__ . '/../.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) !== 2) continue;
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }

                putenv($key . '=' . $val);
                $_ENV[$key] = $val;
            }
        }

        self::$envLoaded = true;
    }

    public static function connect(): PDO
    {
        self::loadEnvIfNeeded();

        $driver = getenv('DB_DRIVER') ?: 'sqlite';

        if ($driver === 'mysql') {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $name = getenv('DB_NAME') ?: 'billing';
            $user = getenv('DB_USER') ?: 'billing';
            $pass = getenv('DB_PASS') ?: 'StrongPassHere';

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        if ($driver === 'sqlite') {
            if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                throw new RuntimeException(
                    "PDO driver 'sqlite' is not installed. Available drivers: " . implode(',', PDO::getAvailableDrivers())
                );
            }

            $path = getenv('DB_PATH') ?: (__DIR__ . '/../storage/app.sqlite');
            if (!is_dir(dirname($path))) @mkdir(dirname($path), 0777, true);

            return new PDO("sqlite:" . $path, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        throw new RuntimeException("Unknown DB_DRIVER '{$driver}'. Use mysql or sqlite.");
    }

    public static function transaction(...$args)
{

    if (count($args) === 1 && is_callable($args[0])) {
        $pdo = self::connect();
        $fn = $args[0];
    } elseif (count($args) === 2 && $args[0] instanceof PDO && is_callable($args[1])) {
        $pdo = $args[0];
        $fn = $args[1];
    } else {
        throw new InvalidArgumentException("Database::transaction expects (callable) or (PDO, callable).");
    }

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $started = true;
        } else {
            $started = false;
        }

        $result = $fn($pdo);

        if ($started) {
            $pdo->commit();
        }

        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

}
