<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'       => 'saml-ldap-sso',
	'textdomain' => 'saml-ldap-sso',
	'version'    => defined( 'SLS_VERSION' ) ? SLS_VERSION : '0.0.0',
	'option_key' => 'sls_options',
);
