<?php

declare(strict_types=1);

namespace SamlLdapSso\Exception;

if (!defined('ABSPATH')) {
    exit;
}

use SamlLdapSso\Auth\AuthFailureReason;

/**
 * SAML assertion failed validation or policy checks.
 */
final class InvalidAssertion extends SlsException
{
    /**
     * @param AuthFailureReason    $reason  Mapped authentication failure reason.
     * @param array<string, mixed> $context Optional diagnostic context.
     * @param string               $message Optional override message.
     */
    public function __construct(
        public readonly AuthFailureReason $reason,
        public readonly array $context = [],
        string $message = ''
    ) {
        parent::__construct('' !== $message ? $message : $reason->logCode());
    }
}
