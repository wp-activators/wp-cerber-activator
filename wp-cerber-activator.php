<?php

/**
 * @wordpress-plugin
 * Plugin Name:       WP Cerber Security Activator
 * Plugin URI:        https://github.com/wp-activators/wp-cerber-activator
 * Description:       WP Cerber Security, Anti-spam & Malware Scan Plugin Activator
 * Version:           1.2.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            mohamedhk2
 * Author URI:        https://github.com/mohamedhk2
 **/

defined( 'ABSPATH' ) || exit;
$WP_CERBER_ACTIVATOR_NAME   = 'WP Cerber Security Activator';
$WP_CERBER_ACTIVATOR_DOMAIN = 'wp-cerber-activator';
$functions                  = require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php';
extract( $functions );
if (
	$activator_admin_notice_ignored()
	|| $activator_admin_notice_plugin_install( 'wp-cerber/wp-cerber.php', null, 'WP Cerber Security, Anti-spam & Malware Scan', $WP_CERBER_ACTIVATOR_NAME, $WP_CERBER_ACTIVATOR_DOMAIN )
	|| $activator_admin_notice_plugin_activate( 'wp-cerber/wp-cerber.php', $WP_CERBER_ACTIVATOR_NAME, $WP_CERBER_ACTIVATOR_DOMAIN )
) {
	return;
}

add_action( 'plugins_loaded', function () {
	$length = defined( 'LAB_KEY_LENGTH' ) ? LAB_KEY_LENGTH : 32;
	$lic    = str_repeat( 'FREE4ALL', ceil( $length / strlen( 'FREE4ALL' ) ) );
	$lic    = substr( $lic, 0, $length );
	lab_update_key( $lic, strtotime( '+1000 year' ) );
} );

if ( ! file_exists( $patcher_file = __DIR__ . DIRECTORY_SEPARATOR . 'Patcher.php' ) ) {
	$patcher = file_get_contents( $githubusercontent = 'https://raw.githubusercontent.com/mohamedhk2/php-patcher/v1.0.6/src/Patcher.php' );
	if ( $patcher ) {
		put_content:
		$put_contents = file_put_contents( $patcher_file, $patcher );
		if ( $put_contents === false ) {
			unlink( $patcher_file );

			return;
		}
	} else {
		$res = wp_remote_get( $githubusercontent );
		if ( ! is_wp_error( $res ) && ( $res['response']['code'] == 200 ) ) {
			$patcher = $res['body'];
			goto put_content;
		} else {
			return;
		}
	}
}
if ( ! class_exists( 'Mohamedhk2\PhpPatcher\Patcher' ) ) {
	require_once $patcher_file;
}

use \Mohamedhk2\PhpPatcher\Patcher;

try {
	$patcher = new Patcher( $path = WP_PLUGIN_DIR . '/wp-cerber/cerber-lab.php' );
	$patcher->setSearch( '/function\s*lab_validate_lic\s*\(\s*\$lic\s*=\s*\'\',\s*&\$msg\s*=\s*\'\'\s*,\s*&\$site_ip\s*=\s*\'\'\s*\)\s*\{/' )
	        ->setCheck( '/\$msg=date_i18n\(get_option\(\'date_format\',false\),get_option\(\'gmt_offset\',false\)\*3600\+strtotime\(\'\+1000 year\'\)\);return true;/' )
	        ->setAfter( '$msg=date_i18n(get_option(\'date_format\',false),get_option(\'gmt_offset\',false)*3600+strtotime(\'+1000 year\'));return true;' )
	        ->setEol( null );
	if ( $patcher->canModified() && ! $patcher->isModified() ) {
		$new_content = $patcher->makeChange( WP_PLUGIN_DIR . '/wp-cerber/cerber-lab-' . date( 'YmdHis' ) . '.php' );
		if ( $patcher->isSuccessful() && $new_content ) {
			$file_put_contents = file_put_contents( $path, $new_content );
		}
	}
} catch ( \Exception $ex ) {
}
