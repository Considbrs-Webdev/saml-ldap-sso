<?php

declare(strict_types=1);

namespace SamlLdapSso\Providers;

if (!defined('ABSPATH')) {
    exit;
}

use SamlLdapSso\Container;

/**
 * Contract for registering and booting plugin services.
 */
interface ServiceProvider
{
    /**
     * Register bindings on the container.
     *
     * @param Container $container Service container.
     */
    public function register(Container $container): void;

    /**
     * Boot services after all providers have registered.
     *
     * @param Container $container Service container.
     */
    public function boot(Container $container): void;
}
