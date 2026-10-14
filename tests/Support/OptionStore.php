<?php

declare(strict_types=1);

namespace Signclave\Tests\Support;

/**
 * In-memory WordPress option store for unit tests.
 */
final class OptionStore
{
    /** @var array<string, mixed> */
    private static array $options = [];

    /**
     * @param array<string, mixed> $options
     */
    public static function seed(array $options): void
    {
        self::$options = $options;
    }

    public static function reset(): void
    {
        self::$options = [];
    }

    public static function get(string $key, mixed $default = false): mixed
    {
        return self::$options[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$options[$key] = $value;
    }

    public static function delete(string $key): void
    {
        unset(self::$options[$key]);
    }
}
