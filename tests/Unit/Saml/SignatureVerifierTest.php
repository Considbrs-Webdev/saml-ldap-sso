<?php

declare(strict_types=1);

use SamlLdapSso\Saml\SignatureVerifier;

function fixture(string $name): string
{
    return file_get_contents(dirname(__DIR__, 2) . '/fixtures/saml/' . $name);
}

function idpCert(): string
{
    return fixture('idp.crt');
}

function otherCert(): string
{
    return fixture('other.crt');
}

it('accepts a valid assertion signed with the pinned certificate', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('valid-assertion.xml'), idpCert());

    expect($result->ok)->toBeTrue()
        ->and($result->signedNodeName)->toBe('Assertion');
});

it('rejects a tampered attribute after signing', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('tampered-attribute.xml'), idpCert());

    expect($result->ok)->toBeFalse();
});

it('rejects SHA-1 signed assertions', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('sha1-assertion.xml'), idpCert());

    expect($result->ok)->toBeFalse()
        ->and($result->error)->toBe('sha1_rejected');
});

it('rejects the wrong IdP certificate', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('valid-assertion.xml'), otherCert());

    expect($result->ok)->toBeFalse();
});

it('accepts the secondary certificate during rotation', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('valid-assertion.xml'), otherCert(), idpCert());

    expect($result->ok)->toBeTrue()
        ->and($result->signedNodeName)->toBe('Assertion');
});

it('rejects a third unknown certificate when primary and secondary fail', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('valid-assertion.xml'), otherCert(), otherCert());

    expect($result->ok)->toBeFalse();
});

it('rejects a Response-only signature without a signed Assertion', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('response-only.xml'), idpCert());

    expect($result->ok)->toBeFalse()
        ->and($result->error)->toBe('unsigned_assertion');
});

it('accepts an assertion that is also wrapped in a signed Response', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('both-signed.xml'), idpCert());

    expect($result->ok)->toBeTrue()
        ->and($result->signedNodeName)->toBe('Assertion');
});

it('rejects XML without a signature', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('unsigned-response.xml'), idpCert());

    expect($result->ok)->toBeFalse();
});

it('rejects XXE document type declarations', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('xxe.xml'), idpCert());

    expect($result->ok)->toBeFalse();
});

it('rejects billion-laughs entity expansion', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('billion-laughs.xml'), idpCert());

    expect($result->ok)->toBeFalse();
});

it('accepts a response signed with the secondary certificate', function () {
    $verifier = new SignatureVerifier();
    $result = $verifier->verifySignedXml(fixture('signed-by-other.xml'), fixture('idp.crt'), otherCert());

    expect($result->ok)->toBeTrue();
});
