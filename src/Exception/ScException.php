<?php

declare(strict_types=1);

namespace Signclave\Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base runtime exception for plugin-specific failures.
 */
class ScException extends \RuntimeException
{
}
