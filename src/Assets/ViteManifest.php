<?php

declare(strict_types=1);

namespace SamlLdapSso\Assets;

if (!defined('ABSPATH')) {
    exit;
}

use function SamlLdapSso\config;

/**
 * Reads Vite build manifest from dist/.vite/manifest.json.
 */
final class ViteManifest
{
    private string $manifestPath;

    /** @var array<string, mixed>|null */
    private ?array $manifest = null;

    /**
     * @param string|null $manifestPath Optional override path to manifest.json.
     */
    public function __construct(?string $manifestPath = null)
    {
        $this->manifestPath = $manifestPath
            ?? (string) config('paths.plugin_path') . 'dist/.vite/manifest.json';
    }

    /**
     * Load and return the parsed manifest array.
     *
     * @return array<string, mixed>
     */
    public function getManifest(): array
    {
        if (null === $this->manifest) {
            if (!file_exists($this->manifestPath)) {
                $this->manifest = [];

                return $this->manifest;
            }
            $content = file_get_contents($this->manifestPath);
            $this->manifest = is_string($content) ? (json_decode($content, true) ?? []) : [];
        }

        return $this->manifest;
    }

    /**
     * Return the hashed output file path for a manifest entry name.
     *
     * @param string $name Manifest entry key (e.g. source path).
     */
    public function getAsset(string $name): ?string
    {
        $manifest = $this->getManifest();

        return isset($manifest[$name]['file']) ? (string) $manifest[$name]['file'] : null;
    }

    /**
     * Return CSS file paths associated with a manifest entry.
     *
     * @param string $name Manifest entry key (e.g. source path).
     *
     * @return list<string>
     */
    public function getCss(string $name): array
    {
        $manifest = $this->getManifest();
        $css = $manifest[$name]['css'] ?? [];

        return is_array($css) ? array_values(array_map('strval', $css)) : [];
    }
}
