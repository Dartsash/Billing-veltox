<?php

declare(strict_types=1);

final class Config
{
    /** @var array<string, string> */
    private array $data;

    /**
     * @param array<string, string> $data
     */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function load(string $baseDir): self
    {
        $data = [];

        $envPath = rtrim($baseDir, '/'). '/.env';
        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }

                    $pos = strpos($line, '=');
                    if ($pos === false) {
                        continue;
                    }

                    $key = trim(substr($line, 0, $pos));
                    $value = trim(substr($line, $pos + 1));

                    if (
                        (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                        (str_starts_with($value, "'") && str_ends_with($value, "'"))
                    ) {
                        $value = substr($value, 1, -1);
                    }

                    if ($key !== '') {
                        $data[$key] = $value;
                        putenv($key . '=' . $value);
                        $_ENV[$key] = $value;
                    }
                }
            }
        }

        foreach ($_ENV as $k => $v) {
            if (is_string($k) && (is_string($v) || is_numeric($v))) {
                $data[$k] = (string) $v;
            }
        }

        return new self($data);
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $val = $this->data[$key] ?? getenv($key);
        if ($val === false || $val === null) {
            return $default;
        }
        return (string) $val;
    }

    public function require(string $key): string
    {
        $val = $this->get($key);
        if ($val === null || $val === '') {
            throw new RuntimeException("Missing required config key: {$key}");
        }
        return $val;
    }
}
