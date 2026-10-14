<?php

declare(strict_types=1);

namespace Signclave\Ldap;

if (! defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\AuthFailureReason;
use Signclave\Settings\Options;
use Signclave\Support\Logger;

/**
 * Directory search for a user (service bind, then filter).
 */
final class UserLookup
{
    /**
     * @param ClientInterface $ldap       LDAP client.
     * @param Logger          $logger     Logger for lookup failures.
     * @param Connection      $connection LDAP connection helper.
     * @param Options         $options    Plugin options.
     */
    public function __construct(
        private readonly ClientInterface $ldap,
        private readonly Logger $logger,
        private readonly Connection $connection,
        private readonly Options $options
    ) {
    }

    /**
     * @return User|AuthFailureReason
     */
    public function findUser(string $username): User|AuthFailureReason
    {
        $username = trim($username);
        if ('' === $username) {
            return AuthFailureReason::LdapInvalidUsername;
        }

        if (! $this->options->isLdapEnabled()) {
            return AuthFailureReason::LdapDisabled;
        }

        if (preg_match('/[\x00-\x1F\*()\ ]/', $username)) {
            return AuthFailureReason::LdapInvalidUsername;
        }

        if (! $this->ldap->isAvailable()) {
            return AuthFailureReason::LdapUnavailable;
        }

        $ldapData = $this->options->getAll()['ldap'];
        $hosts = $this->connection->parseHosts((string) $ldapData['hosts']);
        if ([] === $hosts) {
            return AuthFailureReason::LdapNotConfigured;
        }

        if (! empty($ldapData['allow_insecure_ldap'])) {
            foreach ($hosts as $candidate) {
                if (str_starts_with($candidate, 'ldap://')) {
                    $this->logger->security(
                        'Allowing plaintext LDAP because allow_insecure_ldap is enabled.',
                        ['host' => $candidate]
                    );
                }
            }
        }

        if (wp_get_environment_type() === 'production' && empty($ldapData['allow_insecure_ldap'])) {
            foreach ($hosts as $candidate) {
                if (str_starts_with($candidate, 'ldap://')) {
                    $this->logger->error('Blocked insecure LDAP in production.');

                    return AuthFailureReason::LdapInsecure;
                }
            }
        }

        $attempt = $this->connection->connectFirstAvailable($ldapData, true);
        $conn = $attempt['conn'];
        $host = $attempt['host'];
        if (! $conn) {
            $this->logger->error('LDAP connect failed on all configured hosts.', ['hosts_tried' => count($attempt['tried'])]);

            return AuthFailureReason::LdapConnectFailed;
        }

        if (! $this->connection->startTlsIfNeeded($conn, $host, $ldapData)) {
            $this->ldap->close($conn);
            $this->logger->error('LDAP StartTLS negotiation failed during user lookup.');

            return AuthFailureReason::LdapStartTlsFailed;
        }

        $bindPassword = $this->options->decryptSecret((string) ($ldapData['bind_password_encrypted'] ?? ''));
        if (false === $bindPassword) {
            $this->ldap->close($conn);
            $this->logger->error($this->options->secretDecryptFailureMessage());

            return AuthFailureReason::SecretDecryptFailed;
        }

        $bindUser = (string) ($ldapData['bind_user'] ?? ($ldapData['bind_dn'] ?? ''));
        if ('' === $bindPassword || '' === $bindUser || ! $this->ldap->bind($conn, $bindUser, $bindPassword)) {
            $this->ldap->close($conn);
            $this->logger->error('LDAP service bind failed during user lookup.');

            return AuthFailureReason::LdapBindFailed;
        }

        $entry = $this->searchUserEntry($conn, $ldapData, $username);
        $this->ldap->close($conn);
        if ($entry instanceof AuthFailureReason) {
            return $entry;
        }

        $groupAttr = 'ad' === $ldapData['directory_type'] ? (string) $ldapData['group_attr_ad'] : (string) $ldapData['group_attr_edir'];
        $resolved = $this->entryValue($entry, (string) $ldapData['username_attr_fallback']);
        if ('' === $resolved) {
            $resolved = $username;
        }

        return new User(
            (string) $entry['dn'],
            $resolved,
            $this->entryValue($entry, (string) $ldapData['mail_attr']),
            $this->entryValue($entry, (string) $ldapData['first_name_attr']),
            $this->entryValue($entry, (string) $ldapData['last_name_attr']),
            $this->collectGroups($entry, $groupAttr)
        );
    }

    /**
     * @param mixed $conn
     * @param array<string, mixed> $ldapData
     * @return array<string, mixed>|AuthFailureReason
     */
    private function searchUserEntry(mixed $conn, array $ldapData, string $username): array|AuthFailureReason
    {
        $template = (string) ($ldapData['user_filter'] ?? '');
        if ('' === $template) {
            $template = 'ad' === $ldapData['directory_type']
                ? '(&(objectClass=user)(sAMAccountName=%s))'
                : '(&(objectClass=inetOrgPerson)(cn=%s))';
        }

        $escaped = $this->ldap->escape($username, '', LDAP_ESCAPE_FILTER);
        if ('' === $escaped && '' !== $username) {
            return AuthFailureReason::LdapUnavailable;
        }

        $filter = str_replace('%s', $escaped, $template);
        $groupAttr = 'ad' === $ldapData['directory_type'] ? (string) $ldapData['group_attr_ad'] : (string) $ldapData['group_attr_edir'];
        $attrs = [
            'dn',
            (string) $ldapData['mail_attr'],
            (string) $ldapData['first_name_attr'],
            (string) $ldapData['last_name_attr'],
            (string) $ldapData['username_attr_fallback'],
            $groupAttr,
        ];

        $search = $this->ldap->search($conn, (string) $ldapData['base_dn'], $filter, $attrs);
        if (! $search) {
            return AuthFailureReason::LdapSearchFailed;
        }

        if (1 !== $this->ldap->countEntries($conn, $search)) {
            return AuthFailureReason::LdapUserNotFound;
        }

        $entries = $this->ldap->getEntries($conn, $search);
        $entry = $entries[0] ?? null;
        if (! is_array($entry) || empty($entry['dn'])) {
            return AuthFailureReason::LdapUserNotFound;
        }

        return $entry;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function entryValue(array $entry, string $attr): string
    {
        $key = strtolower($attr);
        if (! isset($entry[$key]) || ! is_array($entry[$key]) || empty($entry[$key][0])) {
            return '';
        }

        return sanitize_text_field((string) $entry[$key][0]);
    }

    /**
     * @param array<string, mixed> $entry
     * @return list<string>
     */
    private function collectGroups(array $entry, string $groupAttr): array
    {
        $key = strtolower($groupAttr);
        if (! isset($entry[$key]) || ! is_array($entry[$key])) {
            return [];
        }
        $groups = [];
        $count = isset($entry[$key]['count']) ? (int) $entry[$key]['count'] : 0;
        for ($i = 0; $i < $count; $i++) {
            if (! empty($entry[$key][$i])) {
                $groups[] = (string) $entry[$key][$i];
            }
        }

        return $groups;
    }
}
