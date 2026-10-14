<?php

declare(strict_types=1);

namespace Signclave\Tests\Support;

use Signclave\Ldap\ClientInterface;

/**
 * In-memory LDAP client shaped like eDirectory for unit tests.
 */
final class FakeLdapClient implements ClientInterface
{
    public bool $available = true;

    public bool $startTlsSucceeds = true;

    public bool $startTlsCalled = false;

    public bool $serviceBindSucceeds = true;

    /** @var list<string> */
    public array $connectHosts = [];

    /** @var list<string> */
    public array $failedHosts = [];

    public ?string $lastFilter = null;

    public mixed $lastConnection = null;

    /** @var array<string, array{dn:string,password:string,attrs:array<string, mixed>}> */
    private array $users = [];

    private string $serviceDn = 'cn=ldap-service,ou=ldap,ou=system,o=example';

    private string $servicePassword = 'bind-secret';

    /**
     * @param array<string, array{dn:string,password:string,attrs:array<string, mixed>}> $users
     */
    public function seedDirectory(array $users, string $serviceDn = '', string $servicePassword = ''): void
    {
        $this->users = $users;
        if ('' !== $serviceDn) {
            $this->serviceDn = $serviceDn;
        }
        if ('' !== $servicePassword) {
            $this->servicePassword = $servicePassword;
        }
    }

    public function failHost(string $host): void
    {
        $this->failedHosts[] = $host;
    }

    public function connect(string $host): mixed
    {
        $this->connectHosts[] = $host;
        if (in_array($host, $this->failedHosts, true)) {
            return false;
        }
        $this->lastConnection = (object) ['host' => $host];

        return $this->lastConnection;
    }

    public function setOption(mixed $connection, int $option, mixed $value): bool
    {
        unset($connection, $option, $value);

        return true;
    }

    public function startTls(mixed $connection): bool
    {
        unset($connection);
        $this->startTlsCalled = true;

        return $this->startTlsSucceeds;
    }

    public function bind(mixed $connection, ?string $dn = null, #[\SensitiveParameter] ?string $password = null): bool
    {
        unset($connection);
        if (null === $dn || '' === $dn) {
            return false;
        }
        if ('' === (string) $password) {
            return false;
        }
        if ($dn === $this->serviceDn) {
            return $this->serviceBindSucceeds && hash_equals($this->servicePassword, (string) $password);
        }
        foreach ($this->users as $user) {
            if ($user['dn'] === $dn) {
                return hash_equals($user['password'], (string) $password);
            }
        }

        return false;
    }

    public function search(mixed $connection, string $baseDn, string $filter, array $attributes = []): mixed
    {
        unset($connection, $baseDn, $attributes);
        $this->lastFilter = $filter;

        return (object) ['filter' => $filter];
    }

    public function getEntries(mixed $connection, mixed $searchResult): array
    {
        unset($connection, $searchResult);
        foreach ($this->users as $username => $user) {
            $needle = '(cn=' . $this->escape((string) $username, '', 1) . ')';
            if (is_string($this->lastFilter) && str_contains($this->lastFilter, $needle)) {
                return [
                    'count' => 1,
                    0 => array_merge(
                        ['dn' => $user['dn'], 'count' => 1],
                        $user['attrs']
                    ),
                ];
            }
        }

        return ['count' => 0];
    }

    public function countEntries(mixed $connection, mixed $searchResult): int
    {
        $entries = $this->getEntries($connection, $searchResult);

        return (int) ($entries['count'] ?? 0);
    }

    public function close(mixed $connection): void
    {
        unset($connection);
    }

    public function error(mixed $connection): string
    {
        unset($connection);

        return 'fake ldap error';
    }

    public function escape(string $value, string $ignore = '', int $flags = 0): string
    {
        unset($flags);
        $map = [
            '\\' => '\\5c',
            '*' => '\\2a',
            '(' => '\\28',
            ')' => '\\29',
            "\0" => '\\00',
        ];
        $out = '';
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $ch = $value[$i];
            if ('' !== $ignore && str_contains($ignore, $ch)) {
                $out .= $ch;
                continue;
            }
            $out .= $map[$ch] ?? $ch;
        }

        return $out;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }
}
