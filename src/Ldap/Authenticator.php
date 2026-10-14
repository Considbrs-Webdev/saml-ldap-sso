<?php

declare(strict_types=1);

namespace Signclave\Ldap;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\AuthFailureReason;
use Signclave\Auth\PasswordBypass;
use Signclave\Settings\Options;
use Signclave\Support\Logger;
use Signclave\Users\UserManager;
use WP_Error;
use WP_User;

/**
 * WordPress authenticate filter: LDAP password bind when SSO is not used.
 */
final class Authenticator
{
    /**
     * @param ClientInterface $ldap           LDAP client implementation.
     * @param Logger          $logger         Plugin logger.
     * @param UserManager     $users          WordPress user provisioning.
     * @param UserLookup      $lookup         Directory user lookup.
     * @param Connection      $connection     Shared LDAP connection helpers.
     * @param Options         $options        Plugin options service.
     * @param PasswordBypass  $passwordBypass Emergency bypass gate.
     */
    public function __construct(
        private readonly ClientInterface $ldap,
        private readonly Logger $logger,
        private readonly UserManager $users,
        private readonly UserLookup $lookup,
        private readonly Connection $connection,
        private readonly Options $options,
        private readonly PasswordBypass $passwordBypass
    ) {
    }

    /**
     * Authenticate via LDAP bind when SSO and bypass are not in play.
     *
     * @param mixed  $user     Current authenticate filter user value.
     * @param string $username Submitted username.
     * @param string $password Submitted password.
     *
     * @return mixed
     */
    public function authenticate(mixed $user, string $username, #[\SensitiveParameter] string $password): mixed
    {
        if ($user instanceof WP_User || $user instanceof WP_Error) {
            return $user;
        }

        if (empty($username) || '' === $password) {
            return $user;
        }

        if ($this->passwordBypass->isPasswordBypassRequest()) {
            return $user;
        }

        if (!$this->options->isLdapUsable()) {
            return $user;
        }

        $ldapUser = $this->lookup->findUser($username);
        if ($ldapUser instanceof AuthFailureReason) {
            if (AuthFailureReason::LdapUserNotFound === $ldapUser) {
                return $user;
            }
            $this->logger->error('LDAP lookup failed during password authentication.', ['code' => $ldapUser->logCode()]);

            return $user;
        }

        $ldapData = $this->options->getAll()['ldap'];
        $attempt = $this->connection->connectFirstAvailable($ldapData, false);
        $userConn = $attempt['conn'];
        $host = $attempt['host'];
        if (!$userConn) {
            return $user;
        }

        if (!$this->connection->startTlsIfNeeded($userConn, $host, $ldapData)) {
            $this->ldap->close($userConn);

            return $user;
        }

        $userBind = $this->ldap->bind($userConn, $ldapUser->dn, $password);
        $this->ldap->close($userConn);
        if (!$userBind) {
            return $user;
        }

        $wpUser = $this->users->findOrCreate(
            $ldapUser->username,
            $ldapUser->email,
            $ldapUser->firstName,
            $ldapUser->lastName,
            'ldap'
        );
        if ($wpUser instanceof WP_Error) {
            return $wpUser;
        }

        $this->users->applyRoleMapping($wpUser, $ldapUser->groups, 'ldap');

        return $wpUser;
    }
}
