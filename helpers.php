<?php

declare(strict_types=1);

namespace Signclave;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Signclave\Support\Config;

/**
 * Optional injectable Config for tests; null = read PHP files directly.
 */
function set_config( ?Config $config ): void {
	Config::setRuntime( $config );
}

/**
 * Read a config value with dot notation (e.g. app.textdomain).
 *
 * Delegates to an injected Config instance when set via set_config().
 *
 * @param mixed $default Default when the key is missing.
 * @return mixed
 */
function config( string $key, mixed $default = null ): mixed {
	$runtime = Config::runtime();
	if ( $runtime instanceof Config ) {
		return $runtime->get( $key, $default );
	}

	static $cache = array();

	$parts = explode( '.', $key, 2 );
	$file  = $parts[0];
	$item  = $parts[1] ?? null;
	$path  = __DIR__ . "/config/{$file}.php";

	if ( ! isset( $cache[ $file ] ) ) {
		$cache[ $file ] = file_exists( $path ) ? require $path : array();
	}

	if ( null === $item ) {
		return $cache[ $file ];
	}

	return $cache[ $file ][ $item ] ?? $default;
}
