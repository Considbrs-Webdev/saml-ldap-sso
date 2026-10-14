<?php

declare(strict_types=1);

namespace Signclave\Support;

if (!defined('ABSPATH')) {
    exit;
}

use function Signclave\config;

/**
 * Renders PHP views under resources/views/.
 */
final class View
{
    /**
     * Render a view template and echo the output.
     *
     * @param string               $template Template path relative to resources/views.
     * @param array<string, mixed> $args     Variables extracted into the template scope.
     */
    public function render(string $template, array $args = []): void
    {
        $path = $this->path($template);
        if (!is_file($path)) {
            return;
        }

        $render = static function (string $__file, array $__args): void {
            extract($__args, EXTR_SKIP);
            unset($__args);
            include $__file;
        };

        $render($path, $args);
    }

    /**
     * Render a view template and return the captured output.
     *
     * @param string               $template Template path relative to resources/views.
     * @param array<string, mixed> $args     Variables extracted into the template scope.
     */
    public function get(string $template, array $args = []): string
    {
        ob_start();
        $this->render($template, $args);

        return (string) ob_get_clean();
    }

    /**
     * Return whether a view template file exists.
     *
     * @param string $template Template path relative to resources/views.
     */
    public function exists(string $template): bool
    {
        return is_file($this->path($template));
    }

    /**
     * Resolve the absolute filesystem path for a view template.
     *
     * @param string $template Template path relative to resources/views.
     */
    public function path(string $template): string
    {
        $base = (string) config('paths.plugin_path');

        return $base . 'resources/views/' . ltrim($template, '/') . '.php';
    }
}
