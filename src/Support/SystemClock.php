<?php

declare(strict_types=1);

namespace SamlLdapSso\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * System clock backed by PHP time functions.
 */
final class SystemClock implements Clock
{
    /**
     * @inheritDoc
     */
    public function now(): int
    {
        return time();
    }

    /**
     * @inheritDoc
     */
    public function formatGmt(string $format, ?int $timestamp = null): string
    {
        return gmdate($format, $timestamp ?? $this->now());
    }
}
