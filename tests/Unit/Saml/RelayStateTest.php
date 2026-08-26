<?php

declare(strict_types=1);

use SamlLdapSso\Saml\Handler;
use SamlLdapSso\Tests\Support\HandlerFactory;

function slsRelayHandler(): array
{
    $factory = new HandlerFactory();
    $factory->seedOptions();
    $handler = $factory->make();

    return [$factory, $handler];
}

it('accepts a signed RelayState blob', function () {
    [$factory, $handler] = slsRelayHandler();
    $_REQUEST['redirect_to'] = 'https://sp.example.test/wp-admin/';
    $blob = $factory->invoke($handler, 'createRelayState');
    $url = $factory->invoke($handler, 'validateRelayState', $blob);
    expect($url)->toContain('sp.example.test');
});

it('rejects a tampered RelayState MAC', function () {
    [$factory, $handler] = slsRelayHandler();
    $_REQUEST['redirect_to'] = 'https://sp.example.test/wp-admin/';
    $blob = $factory->invoke($handler, 'createRelayState');
    $tampered = substr($blob, 0, -4) . 'dead';
    $url = $factory->invoke($handler, 'validateRelayState', $tampered);
    expect($url)->toBe(admin_url());
});

it('rejects an external host in RelayState', function () {
    [$factory, $handler] = slsRelayHandler();
    $url = $factory->invoke($handler, 'sanitizeRedirectTarget', 'https://evil.example/phish');
    expect($url)->toBe(admin_url());
});

it('rejects a protocol-relative URL', function () {
    [$factory, $handler] = slsRelayHandler();
    $url = $factory->invoke($handler, 'sanitizeRedirectTarget', '//evil.example/phish');
    expect($url)->toBe(admin_url());
});

it('rejects javascript and data URLs', function () {
    [$factory, $handler] = slsRelayHandler();
    expect($factory->invoke($handler, 'sanitizeRedirectTarget', 'javascript:alert(1)'))->toBe(admin_url())
        ->and($factory->invoke($handler, 'sanitizeRedirectTarget', 'data:text/html,hi'))->toBe(admin_url());
});

it('rejects an expired RelayState blob', function () {
    [$factory, $handler] = slsRelayHandler();
    $_REQUEST['redirect_to'] = 'https://sp.example.test/wp-admin/profile.php';
    $blob = $factory->invoke($handler, 'createRelayState');
    $factory->clock->advance(601);
    expect($factory->invoke($handler, 'validateRelayState', $blob))->toBe(admin_url());
});
