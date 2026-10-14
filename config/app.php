<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'name'       => 'signclave-sso',
	'textdomain' => 'signclave-sso',
	'version'    => defined( 'SC_VERSION' ) ? SC_VERSION : '0.0.0',
	'option_key' => 'sc_options',
);
