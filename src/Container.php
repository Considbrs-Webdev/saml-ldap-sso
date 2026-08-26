<?php

declare(strict_types=1);

namespace SamlLdapSso;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Instance-based service container. No static bindings, no autowiring.
 */
final class Container
{
    /** @var array<string, array{factory: callable, shared: bool}> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /**
     * Register a shared singleton factory for the given service id.
     *
     * @param callable(self): object $factory
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->bindings[$id] = [
            'factory' => $factory,
            'shared' => true,
        ];
    }

    /**
     * Register a transient factory for the given service id.
     *
     * @param callable(self): object $factory
     */
    public function bind(string $id, callable $factory): void
    {
        $this->bindings[$id] = [
            'factory' => $factory,
            'shared' => false,
        ];
    }

    /**
     * Resolve a service by id, invoking its factory when needed.
     *
     * @template T of object
     * @param class-string<T> $id
     *
     * @return T
     */
    public function make(string $id): object
    {
        if (isset($this->instances[$id])) {
            $cached = $this->instances[$id];
            if (!$cached instanceof $id) {
                throw new \RuntimeException(sprintf('Cached instance for %s has the wrong type.', $id));
            }

            return $cached;
        }

        if (!isset($this->bindings[$id])) {
            throw new \RuntimeException(sprintf('Service not registered: %s', $id));
        }

        $instance = ($this->bindings[$id]['factory'])($this);

        if (!$instance instanceof $id) {
            throw new \RuntimeException(sprintf('Factory for %s must return an instance of that type.', $id));
        }

        if ($this->bindings[$id]['shared']) {
            $this->instances[$id] = $instance;
        }

        return $instance;
    }

    /**
     * Return whether a service id has been registered or resolved.
     */
    public function bound(string $id): bool
    {
        return isset($this->bindings[$id]) || isset($this->instances[$id]);
    }

    /**
     * Return whether a shared service has already been instantiated.
     */
    public function resolved(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}
