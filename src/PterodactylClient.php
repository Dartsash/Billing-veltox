<?php

declare(strict_types=1);

final class PterodactylException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly ?array $payload = null
    ) {
        parent::__construct($message, $statusCode);
    }
}

final class PterodactylClient
{
    private string $baseUrl;
    private string $appKey;

    public function __construct(string $baseUrl, string $appKey)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->appKey  = $appKey;
    }

    public function listLocations(array $query = []): array
    {
        return $this->request('GET', '/api/application/locations', null, $query);
    }

    public function deleteServer(int $serverId, bool $force = true): void
    {
        $query = [];
        if ($force) {
            $query['force'] = 'true';
        }
        $this->request('DELETE', '/api/application/servers/' . $serverId, null, $query);
    }

    public function getServer(int $serverId): array
    {
        return $this->request('GET', '/api/application/servers/' . $serverId);
    }

    public function suspendServer(int $serverId): void
    {
        $this->request('POST', '/api/application/servers/' . $serverId . '/suspend');
    }

    public function unsuspendServer(int $serverId): void
    {
        $this->request('POST', '/api/application/servers/' . $serverId . '/unsuspend');
    }

    /**
     * @param array<string,mixed>|null $json
     * @param array<string,mixed> $query
     * @return array<string,mixed>
     */
    public function request(string $method, string $path, ?array $json = null, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Failed to init cURL');
        }

        $headers = [
            'Authorization: Bearer ' . $this->appKey,
            'Accept: Application/vnd.pterodactyl.v1+json',
        ];

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($json !== null) {
            $body = json_encode($json, JSON_UNESCAPED_UNICODE);
            if ($body === false) {
                throw new RuntimeException('Failed to encode JSON');
            }
            $headers[]                 = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        curl_setopt_array($ch, $options);
        $raw    = curl_exec($ch);
        $err    = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('Pterodactyl API request failed: ' . $err);
        }

        $data = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        if ($status >= 400) {
            $msg = 'Pterodactyl API error ' . $status;
            if (isset($data['errors'][0]['detail']) && is_string($data['errors'][0]['detail'])) {
                $msg = $data['errors'][0]['detail'];
            } elseif (isset($data['error']) && is_string($data['error'])) {
                $msg = $data['error'];
            }
            throw new PterodactylException($msg, $status, $data ?: null);
        }

        return $data;
    }

    /** @param array<string,mixed> $userData */
    public function createUser(array $userData): array
    {
        return $this->request('POST', '/api/application/users', $userData);
    }

    /**
     * Update an existing user in Pterodactyl (Application API).
     *
     * @param array<string,mixed> $userData
     * @return array<string,mixed>
     */
    public function updateUser(int $pteroUserId, array $userData): array
    {
        unset($userData['language']);

        return $this->request('PATCH', '/api/application/users/' . $pteroUserId, $userData);
    }

    /**
     *
     *
     * @throws PterodactylException
     */
    public function deleteUser(int $pteroUserId, bool $force = true): void
    {
        $query = [];
        if ($force) {
            $query['force'] = 'true';
        }
        $this->request('DELETE', '/api/application/users/' . $pteroUserId, null, $query);
    }

    /** @param array<string,mixed> $serverData */
    public function createServer(array $serverData): array
    {
        return $this->request('POST', '/api/application/servers', $serverData);
    }

    /** @param array<string,mixed> $query */
    public function listServers(array $query = []): array
    {
        return $this->request('GET', '/api/application/servers', null, $query);
    }

    /** @param array<string,mixed> $query */
    public function listUsers(array $query = []): array
    {
        return $this->request('GET', '/api/application/users', null, $query);
    }

    public function findUserByEmail(string $email): ?array
    {
        $emailLower = mb_strtolower($email);

        $candidates = [
            ['per_page' => 100, 'filter[email]' => $email],
            ['per_page' => 100, 'search'        => $email],
        ];

        foreach ($candidates as $q) {
            $resp = $this->listUsers($q);
            $rows = $resp['data'] ?? [];
            if (!is_array($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                $attr = $row['attributes'] ?? null;
                if (!is_array($attr)) {
                    continue;
                }
                $e = $attr['email'] ?? null;
                if (is_string($e) && mb_strtolower($e) === $emailLower) {
                    return $attr;
                }
            }
        }

        return null;
    }

    /**
     *
     *
     *
     * @param array<string,mixed> $userData
     * @return array<string,mixed>
     */
    public function createOrGetUser(array $userData): array
    {
        unset($userData['language']);

        try {
            $resp = $this->createUser($userData);

            $attr = $resp['attributes'] ?? ($resp['data']['attributes'] ?? null);
            if (is_array($attr) && isset($attr['id'])) {
                return $attr;
            }

            throw new RuntimeException('Не смогли получить attributes из ответа Pterodactyl.');
        } catch (PterodactylException $e) {
            $msg = mb_strtolower($e->getMessage());

            if ($e->statusCode === 422 && str_contains($msg, 'email') && str_contains($msg, 'already')) {
                $email = (string)($userData['email'] ?? '');
                if ($email !== '') {
                    $existing = $this->findUserByEmail($email);
                    if (is_array($existing) && isset($existing['id'])) {
                        return $existing;
                    }
                }
            }

            throw $e;
        }
    }
}
