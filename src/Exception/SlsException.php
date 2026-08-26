<?php

declare(strict_types=1);

namespace SamlLdapSso\Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base runtime exception for plugin-specific failures.
 */
class SlsException extends \RuntimeException
{
}
