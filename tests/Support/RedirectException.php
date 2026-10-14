<?php

declare(strict_types=1);

namespace Signclave\Tests\Support;

use RuntimeException;

/**
 * Thrown from the wp_safe_redirect stub so ACS tests can complete without exit.
 */
final class RedirectException extends RuntimeException
{
    public function __construct(public readonly string $location)
    {
        parent::__construct($location);
    }
}
