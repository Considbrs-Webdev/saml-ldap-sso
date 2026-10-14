<?php

declare(strict_types=1);

namespace Signclave\Cli;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\AuthFailureReason;
use Signclave\Auth\PasswordBypass;
use Signclave\Ldap\ClientInterface;
use Signclave\Ldap\Connection;
use Signclave\Ldap\UserLookup;
use Signclave\Saml\SignatureVerifier;
use Signclave\Settings\Options;
use Signclave\Support\Logger;
use WP_CLI;

/**
 * WP-CLI commands: `wp signclave …`.
 */
final class Commands
{
    /**
     * @param Options           $options         Plugin options.
     * @param Logger            $logger          Audit logger.
     * @param PasswordBypass    $passwordBypass  Emergency login URL helper.
     * @param Connection        $connection      LDAP connection helper.
     * @param ClientInterface   $ldap            LDAP client.
     * @param UserLookup        $lookup          Directory user lookup.
     * @param SignatureVerifier $crypto          SAML signature verifier.
     */
    public function __construct(
        private readonly Options $options,
        private readonly Logger $logger,
        private readonly PasswordBypass $passwordBypass,
        private readonly Connection $connection,
        private readonly ClientInterface $ldap,
        private readonly UserLookup $lookup,
        private readonly SignatureVerifier $crypto
    ) {
    }

    /**
     * Enable or disable automatic SAML redirect on wp-login.php.
     *
     * ## OPTIONS
     *
     * <action>
     * : `enable` or `disable`.
     *
     * ## EXAMPLES
     *
     *     wp signclave sso disable
     *     wp signclave sso enable
     *
     * @param array<int, string> $args Positional arguments.
     */
    public function sso(array $args): void
    {
        $action = strtolower((string) ($args[0] ?? ''));
        if (!in_array($action, ['enable', 'disable'], true)) {
            WP_CLI::error('Usage: wp signclave sso <enable|disable>');
        }

        $all = $this->options->getAll();
        $all['general']['auto_sso'] = 'enable' === $action;
        $this->options->update($all);
        $this->logger->security('SSO auto-redirect ' . $action . 'd via WP-CLI.');

        WP_CLI::success('Automatic SSO is now ' . ('enable' === $action ? 'enabled' : 'disabled') . '.');
    }

    /**
     * Generate a new emergency password-bypass key and print the login URL.
     *
     * ## EXAMPLES
     *
     *     wp signclave rotate-key
     */
    public function rotate_key(): void
    {
        $all = $this->options->getAll();
        $all['general']['password_bypass_key'] = wp_generate_password(32, false, false);
        $this->options->update($all);
        $this->logger->security('Emergency login key rotated via WP-CLI.');

        $url = $this->passwordBypass->loginUrl();
        WP_CLI::success('Emergency login key rotated.');
        WP_CLI::line($url);
    }

    /**
     * Test LDAP connectivity (service bind, optional user lookup/bind).
     *
     * ## OPTIONS
     *
     * [--user=<username>]
     * : Directory username to look up (and bind as when --password is given).
     *
     * [--password=<password>]
     * : User bind password. Prompted when --user is set and this flag is omitted.
     *
     * ## EXAMPLES
     *
     *     wp signclave test-ldap
     *     wp signclave test-ldap --user=jdoe --password=secret
     *
     * @param array<int, string>    $args       Unused positional arguments.
     * @param array<string, string> $assoc_args Named arguments.
     */
    public function test_ldap(array $args, array $assoc_args): void
    {
        unset($args);

        if (!$this->ldap->isAvailable()) {
            WP_CLI::error('PHP LDAP extension is not loaded.');
        }

        $ldapData = $this->options->getAll()['ldap'];
        $hosts = $this->connection->parseHosts((string) ($ldapData['hosts'] ?? ''));
        if ([] === $hosts) {
            WP_CLI::error('No LDAP host configured.');
        }

        WP_CLI::log('Hosts: ' . implode(', ', $hosts));

        $attempt = $this->connection->connectFirstAvailable($ldapData, true);
        $conn = $attempt['conn'];
        $host = $attempt['host'];
        if (!$conn) {
            WP_CLI::error('Unable to connect (' . implode(', ', $attempt['tried']) . ').');
        }

        WP_CLI::log('Connected: ' . $host);

        if (!$this->connection->startTlsIfNeeded($conn, $host, $ldapData)) {
            $err = $this->ldap->error($conn);
            $this->ldap->close($conn);
            WP_CLI::error('StartTLS failed: ' . $err);
        }

        $pass = $this->options->decryptSecret((string) ($ldapData['bind_password_encrypted'] ?? ''));
        if (false === $pass) {
            $this->ldap->close($conn);
            WP_CLI::error($this->options->secretDecryptFailureMessage());
        }

        $bindUser = (string) ($ldapData['bind_user'] ?? ($ldapData['bind_dn'] ?? ''));
        if ('' === $pass || '' === $bindUser) {
            $this->ldap->close($conn);
            WP_CLI::error('Bind user or bind password is missing in settings.');
        }

        if (!$this->ldap->bind($conn, $bindUser, $pass)) {
            $err = $this->ldap->error($conn);
            $this->ldap->close($conn);
            WP_CLI::error('Service bind failed: ' . $err);
        }

        $this->ldap->close($conn);
        WP_CLI::log('Service bind: ok');

        $username = isset($assoc_args['user']) ? (string) $assoc_args['user'] : '';
        if ('' === $username) {
            WP_CLI::success('LDAP quick test completed (connection + service bind).');

            return;
        }

        $found = $this->lookup->findUser($username);
        if ($found instanceof AuthFailureReason) {
            WP_CLI::error('User lookup failed: ' . $found->logCode());
        }

        WP_CLI::log('User DN: ' . $found->dn);

        $password = isset($assoc_args['password']) ? (string) $assoc_args['password'] : '';
        if ('' === $password) {
            $password = (string) WP_CLI::prompt('LDAP user password');
        }
        if ('' === $password) {
            WP_CLI::success('User found (bind skipped; empty password).');

            return;
        }

        $userConn = $this->connection->connect($host, $ldapData, false);
        if (!$userConn) {
            WP_CLI::error('Could not reconnect for user bind.');
        }
        if (!$this->connection->startTlsIfNeeded($userConn, $host, $ldapData)) {
            $err = $this->ldap->error($userConn);
            $this->ldap->close($userConn);
            WP_CLI::error('StartTLS failed on user connection: ' . $err);
        }
        if (!$this->ldap->bind($userConn, $found->dn, $password)) {
            $err = $this->ldap->error($userConn);
            $this->ldap->close($userConn);
            WP_CLI::error('User bind failed: ' . $err);
        }
        $this->ldap->close($userConn);

        WP_CLI::success('LDAP user bind succeeded for ' . $username . '.');
    }

    /**
     * Verify a SAML Response XML (or base64) against the configured IdP certificates.
     *
     * Does not create a session.
     *
     * ## OPTIONS
     *
     * [--file=<path>]
     * : Path to a file containing raw XML or a base64-encoded SAMLResponse.
     *
     * ## EXAMPLES
     *
     *     wp signclave test-saml --file=/tmp/response.xml
     *
     * @param array<int, string>    $args       Unused positional arguments.
     * @param array<string, string> $assoc_args Named arguments.
     */
    public function test_saml(array $args, array $assoc_args): void
    {
        unset($args);

        $path = isset($assoc_args['file']) ? (string) $assoc_args['file'] : '';
        if ('' === $path || !is_readable($path)) {
            WP_CLI::error('Pass a readable --file= containing XML or base64 SAMLResponse.');
        }

        $raw = (string) file_get_contents($path);
        $trimmed = ltrim($raw);
        if (str_starts_with($trimmed, '<')) {
            $xml = $raw;
        } else {
            $decoded = base64_decode(preg_replace('/\s+/', '', $raw) ?? $raw, true);
            if (false === $decoded) {
                WP_CLI::error('File is neither XML nor valid base64.');
            }
            $xml = $decoded;
        }

        $saml = $this->options->getAll()['saml'];
        $check = $this->crypto->verifySignedXml(
            $xml,
            SignatureVerifier::normalizeCertPem((string) ($saml['primary_x509_cert'] ?? '')),
            SignatureVerifier::normalizeCertPem((string) ($saml['secondary_x509_cert'] ?? ''))
        );

        if (!$check->ok) {
            WP_CLI::error('Signature verification failed: ' . $check->error);
        }

        WP_CLI::success(
            sprintf(
                'Signature valid (signed %s id=%s).',
                $check->signedNodeName,
                $check->signedId
            )
        );
    }

    /**
     * Print configuration health: certificates, LDAP, HTTPS, log directory.
     *
     * ## EXAMPLES
     *
     *     wp signclave doctor
     */
    public function doctor(): void
    {
        $all = $this->options->getAll();
        $now = time();
        $warnWindow = 30 * 86400;
        $rows = [];

        foreach (
            [
                'primary' => (string) ($all['saml']['primary_x509_cert'] ?? ''),
                'secondary' => (string) ($all['saml']['secondary_x509_cert'] ?? ''),
            ] as $label => $pem
        ) {
            if ('' === trim($pem)) {
                $rows[] = ['IdP cert (' . $label . ')', 'not configured'];
                continue;
            }
            $parsed = openssl_x509_parse($pem);
            if (!is_array($parsed) || !isset($parsed['validTo_time_t'])) {
                $rows[] = ['IdP cert (' . $label . ')', 'unreadable'];
                continue;
            }
            $validTo = (int) $parsed['validTo_time_t'];
            if ($validTo < $now) {
                $status = 'EXPIRED ' . gmdate('Y-m-d', $validTo);
            } elseif ($validTo < $now + $warnWindow) {
                $status = 'expires soon ' . gmdate('Y-m-d', $validTo);
            } else {
                $status = 'ok until ' . gmdate('Y-m-d', $validTo);
            }
            $rows[] = ['IdP cert (' . $label . ')', $status];
        }

        $hosts = $this->connection->parseHosts((string) ($all['ldap']['hosts'] ?? ''));
        $rows[] = ['LDAP hosts', [] === $hosts ? '(none)' : implode(', ', $hosts)];
        $rows[] = ['LDAP enabled', !empty($all['ldap']['enabled']) ? 'yes' : 'no'];
        $rows[] = ['LDAP usable', $this->options->isLdapUsable() ? 'yes' : 'no'];

        $secret = $this->options->decryptSecret((string) ($all['ldap']['bind_password_encrypted'] ?? ''));
        if (false === $secret) {
            $rows[] = ['LDAP bind secret', 'decrypt failed'];
        } elseif ('' === $secret) {
            $rows[] = ['LDAP bind secret', 'empty'];
        } else {
            $rows[] = ['LDAP bind secret', 'ok'];
        }

        $rows[] = ['is_ssl()', is_ssl() ? 'yes' : 'no'];
        $rows[] = ['trust_proxy_headers', !empty($all['general']['trust_proxy_headers']) ? 'yes' : 'no'];
        $rows[] = ['auto_sso', !empty($all['general']['auto_sso']) ? 'yes' : 'no'];

        $upload = wp_upload_dir();
        $base = trailingslashit((string) ($upload['basedir'] ?? ''));
        $logFile = (string) ($all['general']['log_file'] ?? '');
        $auditFile = (string) ($all['general']['audit_log_file'] ?? '');
        $dir = $base . 'signclave-sso';
        $rows[] = ['Log directory', is_dir($dir) ? $dir : 'missing'];
        $rows[] = ['log_file', '' !== $logFile ? $logFile : '(unset)'];
        $rows[] = ['audit_log_file', '' !== $auditFile ? $auditFile : '(unset)'];
        $rows[] = ['log_retention_days', (string) (int) ($all['general']['log_retention_days'] ?? 90)];

        if (function_exists('WP_CLI\\Utils\\format_items')) {
            \WP_CLI\Utils\format_items(
                'table',
                array_map(
                    static fn (array $row): array => ['check' => $row[0], 'status' => $row[1]],
                    $rows
                ),
                ['check', 'status']
            );
        } else {
            foreach ($rows as $row) {
                WP_CLI::log($row[0] . ': ' . $row[1]);
            }
        }
    }
}
