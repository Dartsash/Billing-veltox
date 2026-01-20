<?php

declare(strict_types=1);

final class Flash
{
    private const KEY = '__flash__';

    public function add(string $type, string $message): void
    {
        if (!isset($_SESSION[self::KEY]) || !is_array($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = [];
        }
        $_SESSION[self::KEY][] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array<int, array{type:string,message:string}>
     */
    public function consume(): array
    {
        $items = $_SESSION[self::KEY] ?? [];
        unset($_SESSION[self::KEY]);
        if (!is_array($items)) {
            return [];
        }
        return array_values(array_filter($items, fn($i) => is_array($i) && isset($i['type'], $i['message'])));
    }
}
