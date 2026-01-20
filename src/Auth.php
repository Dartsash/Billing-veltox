<?php

declare(strict_types=1);

final class Auth
{
    private const KEY = '__uid__';

    public function __construct(
        private readonly UserRepo $users
    ) {}

    public function id(): ?int
    {
        $val = $_SESSION[self::KEY] ?? null;
        if ($val === null) {
            return null;
        }
        if (is_int($val)) {
            return $val;
        }
        if (is_string($val) && ctype_digit($val)) {
            return (int)$val;
        }
        return null;
    }

    public function check(): bool
    {
        return $this->id() !== null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function user(): ?array
    {
        $id = $this->id();
        if ($id === null) {
            return null;
        }
        return $this->users->findById($id);
    }

    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return false;
        }
        $hash = (string)($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($password, $hash)) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION[self::KEY] = (int)$user['id'];
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION[self::KEY]);
        session_regenerate_id(true);
    }

    public function requireLogin(): void
    {
        if (!$this->check()) {
            redirect('/login');
        }
    }
}
