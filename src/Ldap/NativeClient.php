<?php

declare(strict_types=1);

namespace SamlLdapSso\Ldap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Native PHP ldap_* implementation.
 */
final class NativeClient implements ClientInterface
{
    /**
     * @inheritDoc
     */
    public function connect(string $host): mixed
    {
        return ldap_connect($host);
    }

    /**
     * @inheritDoc
     */
    public function setOption(mixed $connection, int $option, mixed $value): bool
    {
        return ldap_set_option($connection, $option, $value);
    }

    /**
     * @inheritDoc
     */
    public function startTls(mixed $connection): bool
    {
        return ldap_start_tls($connection);
    }

    /**
     * @inheritDoc
     */
    public function bind(mixed $connection, ?string $dn = null, #[\SensitiveParameter] ?string $password = null): bool
    {
        return ldap_bind($connection, $dn, $password);
    }

    /**
     * @inheritDoc
     */
    public function search(mixed $connection, string $baseDn, string $filter, array $attributes = []): mixed
    {
        return ldap_search($connection, $baseDn, $filter, $attributes);
    }

    /**
     * @inheritDoc
     */
    public function getEntries(mixed $connection, mixed $searchResult): array
    {
        $entries = ldap_get_entries($connection, $searchResult);

        return is_array($entries) ? $entries : [];
    }

    /**
     * @inheritDoc
     */
    public function countEntries(mixed $connection, mixed $searchResult): int
    {
        return (int) ldap_count_entries($connection, $searchResult);
    }

    /**
     * @inheritDoc
     */
    public function close(mixed $connection): void
    {
        ldap_unbind($connection);
    }

    /**
     * @inheritDoc
     */
    public function error(mixed $connection): string
    {
        return ldap_error($connection);
    }

    /**
     * @inheritDoc
     */
    public function escape(string $value, string $ignore = '', int $flags = 0): string
    {
        if (!function_exists('ldap_escape')) {
            return '';
        }
        $flags = 0 !== $flags ? $flags : LDAP_ESCAPE_FILTER;

        return ldap_escape($value, $ignore, $flags);
    }

    /**
     * @inheritDoc
     */
    public function isAvailable(): bool
    {
        return extension_loaded('ldap') && function_exists('ldap_connect') && function_exists('ldap_escape');
    }
}
