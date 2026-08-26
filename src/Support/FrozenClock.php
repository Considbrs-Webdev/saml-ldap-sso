<?php

declare(strict_types=1);

namespace SamlLdapSso\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fixed clock for deterministic SAML time validation in tests.
 */
final class FrozenClock implements Clock
{
    /**
     * @param int $timestamp Unix timestamp returned by now().
     */
    public function __construct(
        private int $timestamp
    ) {
    }

    /**
     * Advance the frozen clock by a number of seconds.
     */
    public function advance(int $seconds): void
    {
        $this->timestamp += $seconds;
    }

    /**
     * Set an absolute Unix timestamp.
     */
    public function set(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @inheritDoc
     */
    public function now(): int
    {
        return $this->timestamp;
    }

    /**
     * @inheritDoc
     */
    public function formatGmt(string $format, ?int $timestamp = null): string
    {
        return gmdate($format, $timestamp ?? $this->timestamp);
    }
}
