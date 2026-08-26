<?php

declare(strict_types=1);

use SamlLdapSso\Tests\Support\HandlerFactory;

it('shows a Log in with SSO button on wp-login when auto-SSO is off', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => false]]);
    ob_start();
    $factory->make()->renderLoginSsoButton();
    $html = (string) ob_get_clean();

    expect($html)->toContain('sls-sso-login')
        ->and($html)->toContain('button-primary')
        ->and($html)->toContain('sls_saml=login')
        ->and($html)->toContain('Log in with SSO');
});

it('does not show the SSO button when auto-SSO is on', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => true]]);
    ob_start();
    $factory->make()->renderLoginSsoButton();

    expect((string) ob_get_clean())->toBe('');
});

it('does not show the SSO button when the IdP is not configured', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions([
        'general' => ['auto_sso' => false],
        'saml' => ['primary_sso_url' => '', 'primary_x509_cert' => ''],
    ]);
    ob_start();
    $factory->make()->renderLoginSsoButton();

    expect((string) ob_get_clean())->toBe('');
});

it('does not show the SSO button on lost-password', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => false]]);
    $_REQUEST['action'] = 'lostpassword';
    ob_start();
    $factory->make()->renderLoginSsoButton();

    expect((string) ob_get_clean())->toBe('');
});

it('enqueues login styles and script when the SSO button is shown', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => false]]);
    $factory->make()->enqueueLoginStyles();

    expect($GLOBALS['sls_test_inline_styles']['login'][0] ?? '')->toContain('.sls-sso-login')
        ->and($GLOBALS['sls_test_inline_scripts']['sls-login-sso'][0] ?? '')->toContain('loginform');
});

it('redirects wp-login to the IdP when auto-SSO is on', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => true]]);
    try {
        $factory->make()->autoRedirectToSso();
        expect(false)->toBeTrue('Expected IdP redirect');
    } catch (SamlLdapSso\Tests\Support\RedirectException $e) {
        expect($e->location)->toContain('sls_saml=login');
    }
});

it('does not auto-redirect lost-password, reset, or register', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions(['general' => ['auto_sso' => true]]);
    $handler = $factory->make();
    foreach (['lostpassword', 'rp', 'resetpass', 'register'] as $action) {
        $_REQUEST['action'] = $action;
        $handler->autoRedirectToSso();
    }
    expect(true)->toBeTrue();
});

it('does not auto-redirect an emergency password bypass request', function () {
    $factory = new HandlerFactory();
    $factory->seedOptions([
        'general' => [
            'auto_sso' => true,
            'password_bypass_key' => 'bypass-secret-key',
        ],
    ]);
    $_GET['password'] = '1';
    $_GET['sls_key'] = 'bypass-secret-key';
    $factory->make()->autoRedirectToSso();
    expect(true)->toBeTrue();
});
