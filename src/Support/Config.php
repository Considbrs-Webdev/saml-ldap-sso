<?php

declare(strict_types=1);

namespace Signclave\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Injectable config reader (same files as Signclave\config()).
 */
final class Config
{
    private static ?self $runtime = null;

    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    private string $configPath;

    /**
     * @param string|null $configPath Optional override path to config directory.
     */
    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? dirname(__DIR__, 2) . '/config';
    }

    /**
     * Inject a Config used by Signclave\config() (tests).
     */
    public static function setRuntime(?self $config): void
    {
        self::$runtime = $config;
    }

    /**
     * Return the injected runtime Config, if any.
     */
    public static function runtime(): ?self
    {
        return self::$runtime;
    }

    /**
     * Read a dotted config key from cached PHP config files.
     *
     * @param string $key     Dotted key such as "app.version".
     * @param mixed  $default Value when the key is missing.
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        $item = $parts[1] ?? null;

        if (!isset($this->cache[$file])) {
            $path = $this->configPath . '/' . $file . '.php';
            $this->cache[$file] = file_exists($path) ? require $path : [];
        }

        if (null === $item) {
            return $this->cache[$file];
        }

        return $this->cache[$file][$item] ?? $default;
    }
}
