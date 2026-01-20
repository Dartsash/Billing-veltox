<?php

declare(strict_types=1);

final class Csrf
{
    private const KEY = '__csrf__';

    public function token(): string
    {
        if (empty($_SESSION[self::KEY]) || !is_string($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public function check(?string $token): bool
    {
        $sessionToken = $_SESSION[self::KEY] ?? null;
        if (!is_string($sessionToken) || $sessionToken === '') {
            return false;
        }
        if (!is_string($token) || $token === '') {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }
}
