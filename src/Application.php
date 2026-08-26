<?php

declare(strict_types=1);

namespace SamlLdapSso;

if (!defined('ABSPATH')) {
    exit;
}

use SamlLdapSso\Providers\ServiceProvider;

/**
 * Boots service providers against an instance container.
 */
final class Application
{
    /** @var list<class-string<ServiceProvider>> */
    private array $providers = [];

    private Container $container;

    /**
     * Initialize a new application with an empty container.
     */
    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Create a new application instance for fluent configuration.
     */
    public static function configure(): self
    {
        return new self();
    }

    /**
     * Register the list of service provider classes to boot.
     *
     * @param list<class-string<ServiceProvider>> $providers
     */
    public function withProviders(array $providers): self
    {
        $this->providers = $providers;

        return $this;
    }

    /**
     * Register and boot all configured service providers.
     */
    public function boot(): void
    {
        $instances = [];

        foreach ($this->providers as $providerClass) {
            $provider = new $providerClass();
            if (!$provider instanceof ServiceProvider) {
                throw new \InvalidArgumentException(
                    sprintf('Provider %s must implement ServiceProvider.', $providerClass)
                );
            }
            $provider->register($this->container);
            $instances[] = $provider;
        }

        foreach ($instances as $provider) {
            $provider->boot($this->container);
        }
    }

    /**
     * Return the service container instance.
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}
