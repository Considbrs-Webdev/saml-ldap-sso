<?php

declare(strict_types=1);

function slsE2eUrl(): string
{
    return rtrim(getenv('SLS_E2E_URL') ?: 'https://saml-ldap-sso.test', '/');
}

function slsE2eFetch(string $path): string|false
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 8,
            'ignore_errors' => true,
            'follow_location' => 1,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);

    return @file_get_contents(slsE2eUrl() . $path, false, $context);
}

it('skips the Keycloak lab round-trip unless SLS_E2E=1', function () {
    if (getenv('SLS_E2E') !== '1') {
        skip('Set SLS_E2E=1 after starting saml-dev and the WordPress lab.');
    }

    expect(slsE2eFetch('/'))->not->toBeFalse();
});

it('serves well-formed SP metadata from the lab', function () {
    if (getenv('SLS_E2E') !== '1') {
        skip('Set SLS_E2E=1 after starting saml-dev and the WordPress lab.');
    }

    $xml = slsE2eFetch('/?sls_saml=metadata');
    expect($xml)->not->toBeFalse()
        ->and($xml)->toContain('EntityDescriptor')
        ->and($xml)->toContain('AssertionConsumerService')
        ->and($xml)->toContain('WantAssertionsSigned="true"');
});

it('starts SSO with an HTTP-POST AuthnRequest form', function () {
    if (getenv('SLS_E2E') !== '1') {
        skip('Set SLS_E2E=1 after starting saml-dev and the WordPress lab.');
    }

    $html = slsE2eFetch('/?sls_saml=login');
    expect($html)->not->toBeFalse()
        ->and(strtolower((string) $html))->toContain('samlrequest')
        ->and(strtolower((string) $html))->toContain('method="post"');
});
