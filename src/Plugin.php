<?php

declare(strict_types=1);

namespace Signclave;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Settings\Options;

use function Signclave\config;

/**
 * Core plugin lifecycle helpers (textdomain, upgrade, proxy SSL).
 */
final class Plugin
{
    /**
     * @param Options $options Plugin options service.
     */
    public function __construct(
        private readonly Options $options
    ) {
    }

    /**
     * Load plugin translations from resources/languages.
     */
    public function loadTextdomain(): void
    {
        $pluginFile = (string) config('paths.plugin_path') . 'signclave-sso.php';
        load_plugin_textdomain( // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Domain Path is resources/languages; needed off wordpress.org.
            (string) config('app.textdomain', 'signclave-sso'),
            false,
            dirname(plugin_basename($pluginFile)) . '/resources/languages'
        );
    }

    /**
     * Ensure a password bypass key exists after upgrades from older versions.
     */
    public function maybeUpgrade(): void
    {
        $all = $this->options->getAll();
        if ('' !== (string) ($all['general']['password_bypass_key'] ?? '')) {
            return;
        }

        $all['general']['password_bypass_key'] = wp_generate_password(32, false, false);
        $this->options->update($all);
    }

    /**
     * Honor reverse-proxy HTTPS headers when configured.
     */
    public function trustProxySsl(): void
    {
        if (is_ssl()) {
            return;
        }

        $trust = (bool) $this->options->get('general', 'trust_proxy_headers', false);
        $trust = (bool) apply_filters('sc_trust_proxy_headers', $trust);
        if (!$trust) {
            return;
        }

        $proto = isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_X_FORWARDED_PROTO']))
            : '';
        if ('' !== $proto) {
            $token = strtok($proto, ',');
            if (is_string($token) && 'https' === strtolower(trim($token))) {
                $_SERVER['HTTPS'] = 'on';

                return;
            }
        }

        $ssl = isset($_SERVER['HTTP_X_FORWARDED_SSL'])
            ? sanitize_text_field(wp_unslash((string) $_SERVER['HTTP_X_FORWARDED_SSL']))
            : '';
        if ('on' === strtolower($ssl)) {
            $_SERVER['HTTPS'] = 'on';
        }
    }
}
