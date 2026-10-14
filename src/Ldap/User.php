<?php

declare(strict_types=1);

namespace Signclave\Ldap;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Directory user resolved by service bind + search.
 *
 * @phpstan-type GroupList list<string>
 */
final readonly class User
{
    /**
     * @param string       $dn        Distinguished name.
     * @param string       $username  Resolved login name.
     * @param string       $email     Primary email address.
     * @param string       $firstName Given name attribute value.
     * @param string       $lastName  Surname attribute value.
     * @param list<string> $groups    Group memberships.
     */
    public function __construct(
        public string $dn,
        public string $username,
        public string $email,
        public string $firstName,
        public string $lastName,
        public array $groups
    ) {
    }
}
