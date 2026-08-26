<?php

declare(strict_types=1);

use function SamlLdapSso\config;

it( 'reads app.name from config', function () {
	expect( config( 'app.name' ) )->toBe( 'saml-ldap-sso' );
} );

it( 'returns default for unknown keys', function () {
	expect( config( 'app.missing_key', 'fallback' ) )->toBe( 'fallback' );
} );

it( 'reads option_key', function () {
	expect( config( 'app.option_key' ) )->toBe( 'sls_options' );
} );
