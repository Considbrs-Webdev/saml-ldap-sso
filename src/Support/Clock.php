<?php

declare(strict_types=1);

namespace SamlLdapSso\Support;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Injectable clock for testable time operations.
 */
interface Clock
{
    /**
     * Return the current Unix timestamp.
     */
    public function now(): int;

    /**
     * Format a timestamp in GMT using gmdate().
     *
     * @param string   $format    Date format string.
     * @param int|null $timestamp Optional timestamp; defaults to now().
     */
    public function formatGmt(string $format, ?int $timestamp = null): string;
}
