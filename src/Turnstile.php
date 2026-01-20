<?php

declare(strict_types=1);

final class Turnstile
{
    public static function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = (string)(getenv('TURNSTILE_SECRET_KEY') ?: '');
        if ($secret === '' || $token === '') {
            return false;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        if ($ch === false) {
            return false;
        }

        $payload = [
            'secret' => $secret,
            'response' => $token,
        ];
        if ($remoteIp) {
            $payload['remoteip'] = $remoteIp;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);

        if (!is_string($raw) || $raw === '') {
            return false;
        }

        $data = json_decode($raw, true);
        return is_array($data) && ($data['success'] ?? false) === true;
    }
}
