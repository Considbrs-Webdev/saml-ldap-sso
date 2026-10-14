<?php

declare(strict_types=1);

namespace Signclave\Tests\Support;

/**
 * In-memory WordPress transient store for unit tests.
 */
final class TransientStore
{
    /** @var array<string, mixed> */
    private static array $values = [];

    public static function reset(): void
    {
        self::$values = [];
    }

    public static function get(string $key): mixed
    {
        return self::$values[$key] ?? false;
    }

    public static function set(string $key, mixed $value, int $ttl = 0): bool
    {
        unset($ttl);
        self::$values[$key] = $value;

        return true;
    }

    public static function delete(string $key): bool
    {
        unset(self::$values[$key]);

        return true;
    }
}
