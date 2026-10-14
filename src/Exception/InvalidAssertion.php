<?php

declare(strict_types=1);

namespace Signclave\Exception;

if (!defined('ABSPATH')) {
    exit;
}

use Signclave\Auth\AuthFailureReason;

/**
 * SAML assertion failed validation or policy checks.
 */
final class InvalidAssertion extends ScException
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
