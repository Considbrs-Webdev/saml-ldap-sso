<?php
/**
 * Uninstall: delete sls_options and upload log files.
 *
 * @package SAML_LDAP_SSO
 */

defined( 'ABSPATH' ) || exit;
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

( static function (): void {
	wp_clear_scheduled_hook( 'sls_prune_logs' );

	$sls_options = get_option( 'sls_options', array() );
	$sls_general = is_array( $sls_options ) && isset( $sls_options['general'] ) && is_array( $sls_options['general'] )
		? $sls_options['general']
		: array();

	delete_option( 'sls_options' );

	global $wpdb;
	if ( $wpdb instanceof wpdb ) {
		$like_transient = $wpdb->esc_like( '_transient_sls_' ) . '%';
		$like_timeout   = $wpdb->esc_like( '_transient_timeout_sls_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- uninstall must drop all plugin transients.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_transient,
				$like_timeout
			)
		);
	}

	$upload = wp_upload_dir();
	if ( empty( $upload['basedir'] ) ) {
		return;
	}

	$dir = trailingslashit( $upload['basedir'] ) . 'saml-ldap-sso';
	if ( ! is_dir( $dir ) ) {
		return;
	}

	$files = array( 'sls.log', 'sls-audit.log', 'index.php', '.htaccess' );

	// Randomized log filenames configured by the plugin.
	foreach ( array( 'log_file', 'audit_log_file' ) as $sls_key ) {
		$sls_name = isset( $sls_general[ $sls_key ] ) ? basename( (string) $sls_general[ $sls_key ] ) : '';
		if ( '' !== $sls_name && preg_match( '/^sls[a-z0-9._-]*\.log$/i', $sls_name ) ) {
			$files[] = $sls_name;
		}
	}

	// Any stale randomized logs left behind by earlier filename rotations.
	$sls_stale = glob( trailingslashit( $dir ) . 'sls*.log' );
	if ( is_array( $sls_stale ) ) {
		foreach ( $sls_stale as $sls_path ) {
			$files[] = basename( $sls_path );
		}
	}

	foreach ( array_unique( $files ) as $file ) {
		$path = trailingslashit( $dir ) . $file;
		if ( is_file( $path ) ) {
			wp_delete_file( $path );
		}
	}

	if ( function_exists( 'rmdir' ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- empty log directory after wp_delete_file().
		rmdir( $dir );
	}
} )();
