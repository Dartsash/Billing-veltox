<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}

function redirect_back(string $fallback = '/dashboard'): void
{
    $ref = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($ref !== '') {
        $u = parse_url($ref);
        $path = (string)($u['path'] ?? '');
        $query = isset($u['query']) && is_string($u['query']) && $u['query'] !== '' ? ('?' . $u['query']) : '';
        if ($path !== '' && str_starts_with($path, '/')) {
            redirect($path . $query);
        }
    }

    redirect($fallback);
}

function path(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH);
    return $path ?: '/';
}

function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    return false;
}

function now_iso(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DateTimeInterface::ATOM);
}

function format_rub(int $kopeks): string
{
    $rub = $kopeks / 100;
    $formatted = number_format($rub, 2, ',', ' ');
    return $formatted . ' ₽';
}

function rub_to_kopeks(string $rubString): int
{
    $clean = str_replace([' ', '\u{00A0}'], '', trim($rubString));
    $clean = str_replace(',', '.', $clean);
    if ($clean === '' || !is_numeric($clean)) {
        return 0;
    }
    $value = (float)$clean;
    return (int) round($value * 100);
}


if (!function_exists('is_admin')) {
    /** @param array<string,mixed>|null $user */
    function is_admin(?array $user): bool
    {
        if (!$user) {
            return false;
        }

        $role = strtolower(trim((string)($user['role'] ?? '')));
        if ($role === 'admin') {
            return true;
        }

        $email = strtolower(trim((string)($user['email'] ?? '')));
        $username = strtolower(trim((string)($user['username'] ?? '')));

        $emailsRaw = (string)(getenv('ADMIN_EMAILS') ?: getenv('ADMIN_EMAIL') ?: '');
        $usernamesRaw = (string)(getenv('ADMIN_USERNAMES') ?: '');

        $emails = preg_split('/[\s,]+/', strtolower($emailsRaw), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $usernames = preg_split('/[\s,]+/', strtolower($usernamesRaw), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($email !== '' && in_array($email, $emails, true)) {
            return true;
        }

        if ($username !== '' && in_array($username, $usernames, true)) {
            return true;
        }

        return false;
    }
}
