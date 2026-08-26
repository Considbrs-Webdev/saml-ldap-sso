<?php

declare(strict_types=1);

/**
 * PHPStan stubs for optional WordPress CLI.
 */
class WP_CLI
{
    /**
     * @return never
     */
    public static function error(string $message): never
    {
        throw new RuntimeException($message);
    }

    public static function success(string $message): void
    {
    }

    public static function line(string $message): void
    {
    }

    public static function log(string $message): void
    {
    }

    public static function prompt(string $message): string
    {
        return '';
    }

    public static function add_command(string $name, object $callable): void
    {
    }
}
