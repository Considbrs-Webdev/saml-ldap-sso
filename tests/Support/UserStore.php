<?php

declare(strict_types=1);

namespace SamlLdapSso\Tests\Support;

use WP_User;

/**
 * In-memory WordPress users and meta for unit tests.
 */
final class UserStore
{
    /** @var array<int, WP_User> */
    private static array $byId = [];

    /** @var array<string, int> */
    private static array $byLogin = [];

    /** @var array<int, array<string, mixed>> */
    private static array $meta = [];

    private static int $nextId = 1;

    public static function reset(): void
    {
        self::$byId = [];
        self::$byLogin = [];
        self::$meta = [];
        self::$nextId = 1;
    }

    public static function put(WP_User $user): WP_User
    {
        if ($user->ID < 1) {
            $user->ID = self::$nextId++;
        } else {
            self::$nextId = max(self::$nextId, $user->ID + 1);
        }
        self::$byId[$user->ID] = $user;
        self::$byLogin[strtolower($user->user_login)] = $user->ID;

        return $user;
    }

    public static function byLogin(string $login): ?WP_User
    {
        $id = self::$byLogin[strtolower($login)] ?? null;

        return null !== $id ? (self::$byId[$id] ?? null) : null;
    }

    public static function byId(int $id): ?WP_User
    {
        return self::$byId[$id] ?? null;
    }

    /**
     * @return list<WP_User>
     */
    public static function all(): array
    {
        return array_values(self::$byId);
    }

    public static function getMeta(int $id, string $key): mixed
    {
        return self::$meta[$id][$key] ?? '';
    }

    public static function setMeta(int $id, string $key, mixed $value): void
    {
        self::$meta[$id][$key] = $value;
    }
}
