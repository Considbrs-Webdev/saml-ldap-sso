<?php

declare(strict_types=1);

use SamlLdapSso\Container;

it('returns the same singleton instance twice', function () {
    $container = new Container();
    $container->singleton(stdClass::class, static fn () => new stdClass());

    $first = $container->make(stdClass::class);
    $second = $container->make(stdClass::class);

    expect($first)->toBe($second);
});

it('throws when resolving an unbound id', function () {
    $container = new Container();
    $container->make(stdClass::class);
})->throws(RuntimeException::class);
