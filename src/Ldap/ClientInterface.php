<?php

declare(strict_types=1);

namespace Signclave\Ldap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstraction over native PHP LDAP extension calls.
 */
interface ClientInterface
{
    /**
     * Open an LDAP connection to the given host URI.
     *
     * @param string $host LDAP host URI.
     *
     * @return mixed LDAP link or false.
     */
    public function connect(string $host): mixed;

    /**
     * Set an LDAP connection option.
     *
     * @param mixed $connection LDAP link.
     * @param int   $option     LDAP option constant.
     * @param mixed $value      Option value.
     */
    public function setOption(mixed $connection, int $option, mixed $value): bool;

    /**
     * Upgrade a plain LDAP connection with StartTLS.
     *
     * @param mixed $connection LDAP link.
     */
    public function startTls(mixed $connection): bool;

    /**
     * Bind to LDAP with optional DN and password.
     *
     * @param mixed       $connection LDAP link.
     * @param string|null $dn         Bind DN, or null for anonymous bind.
     * @param string|null $password   Bind password.
     */
    public function bind(mixed $connection, ?string $dn = null, #[\SensitiveParameter] ?string $password = null): bool;

    /**
     * Search the directory under a base DN.
     *
     * @param mixed        $connection LDAP link.
     * @param string       $baseDn     Search base DN.
     * @param string       $filter     LDAP filter string.
     * @param list<string> $attributes Attributes to retrieve.
     *
     * @return mixed Search result resource or false.
     */
    public function search(mixed $connection, string $baseDn, string $filter, array $attributes = []): mixed;

    /**
     * Convert a search result into an entries array.
     *
     * @param mixed $connection   LDAP link.
     * @param mixed $searchResult LDAP search result.
     *
     * @return array<int|string, mixed>
     */
    public function getEntries(mixed $connection, mixed $searchResult): array;

    /**
     * Count entries in a search result.
     *
     * @param mixed $connection   LDAP link.
     * @param mixed $searchResult LDAP search result.
     */
    public function countEntries(mixed $connection, mixed $searchResult): int;

    /**
     * Close an LDAP connection.
     *
     * @param mixed $connection LDAP link.
     */
    public function close(mixed $connection): void;

    /**
     * Return the last LDAP error message for a connection.
     *
     * @param mixed $connection LDAP link.
     */
    public function error(mixed $connection): string;

    /**
     * Escape a value for safe use in LDAP filters or DNs.
     *
     * @param string $value  Value to escape.
     * @param string $ignore Characters to leave unescaped.
     * @param int    $flags  ldap_escape() flags.
     */
    public function escape(string $value, string $ignore = '', int $flags = 0): string;

    /**
     * Return whether the LDAP extension is available.
     */
    public function isAvailable(): bool;
}
