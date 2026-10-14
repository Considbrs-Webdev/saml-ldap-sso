<?php

declare(strict_types=1);

namespace Signclave\Ldap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shared LDAP socket options (protocol, timeouts, StartTLS).
 */
final class Connection
{
    /**
     * @param ClientInterface $ldap LDAP client implementation.
     */
    public function __construct(
        private readonly ClientInterface $ldap
    ) {
    }

    /**
     * Split a comma-separated host setting into individual host URIs.
     *
     * @param string $hosts Comma-separated host URIs.
     *
     * @return list<string>
     */
    public function parseHosts(string $hosts): array
    {
        $list = array_filter(array_map('trim', explode(',', $hosts)), static function (string $host): bool {
            return '' !== $host;
        });

        return array_values(array_unique($list));
    }

    /**
     * Connect to the first reachable host in the configured host list.
     *
     * @param array<string, mixed> $ldapData     LDAP settings array.
     * @param bool                 $withTimeouts Whether to apply network/time limits.
     *
     * @return array{conn:mixed,host:string,tried:list<string>} Connection result; `conn` is false when every host failed.
     */
    public function connectFirstAvailable(array $ldapData, bool $withTimeouts = true): array
    {
        $tried = [];
        foreach ($this->parseHosts((string) ($ldapData['hosts'] ?? '')) as $host) {
            $tried[] = $host;
            $conn = $this->connect($host, $ldapData, $withTimeouts);
            if ($conn) {
                return [
                    'conn' => $conn,
                    'host' => $host,
                    'tried' => $tried,
                ];
            }
        }

        return [
            'conn' => false,
            'host' => '',
            'tried' => $tried,
        ];
    }

    /**
     * Connect to LDAP and apply standard connection options.
     *
     * @param string               $host         LDAP host URI.
     * @param array<string, mixed> $ldapData     LDAP settings array.
     * @param bool                 $withTimeouts Whether to apply network/time limits.
     *
     * @return mixed LDAP link or false.
     */
    public function connect(string $host, array $ldapData, bool $withTimeouts = true): mixed
    {
        $conn = $this->ldap->connect($host);
        if (!$conn) {
            return false;
        }
        $this->applyOptions($conn, $ldapData, $withTimeouts);

        return $conn;
    }

    /**
     * Apply protocol, referral, timeout, and TLS certificate options.
     *
     * @param mixed                $conn         LDAP link.
     * @param array<string, mixed> $ldapData     LDAP settings array.
     * @param bool                 $withTimeouts Whether to apply network/time limits.
     */
    public function applyOptions(mixed $conn, array $ldapData, bool $withTimeouts = true): void
    {
        $this->ldap->setOption($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->ldap->setOption($conn, LDAP_OPT_REFERRALS, 'never' === ($ldapData['referrals'] ?? 'never') ? 0 : 1);
        if ($withTimeouts) {
            $this->ldap->setOption($conn, LDAP_OPT_NETWORK_TIMEOUT, (int) ($ldapData['network_timeout'] ?? 5));
            $this->ldap->setOption($conn, LDAP_OPT_TIMELIMIT, (int) ($ldapData['time_limit'] ?? 10));
        }
        if (defined('LDAP_OPT_X_TLS_REQUIRE_CERT') && defined('LDAP_OPT_X_TLS_DEMAND')) {
            $this->ldap->setOption($conn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_DEMAND);
        }
    }

    /**
     * Start TLS on ldap:// connections unless insecure LDAP is explicitly allowed.
     *
     * @param mixed                $conn     LDAP link.
     * @param string               $host     LDAP host URI used for the connection.
     * @param array<string, mixed> $ldapData LDAP settings array.
     */
    public function startTlsIfNeeded(mixed $conn, string $host, array $ldapData): bool
    {
        if (! str_starts_with($host, 'ldap://')) {
            return true;
        }
        if (!empty($ldapData['allow_insecure_ldap'])) {
            return true;
        }

        return $this->ldap->startTls($conn);
    }
}
