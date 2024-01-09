<?php
/**
 * @wordpress-plugin
 * Plugin Name:       WP Cerber Security Activ@tor
 * Plugin URI:        https://bit.ly/cbr-act
 * Description:       WP Cerber Security, Anti-spam & Malware Scan Plugin Activ@tor
 * Version:           1.3.0
 * Requires at least: 5.9.0
 * Requires PHP:      7.2
 * Author:            moh@medhk2
 * Author URI:        https://bit.ly/medhk2
 **/

defined( 'ABSPATH' ) || exit;
$PLUGIN_NAME   = 'WP Cerber Security Activ@tor';
$PLUGIN_DOMAIN = 'wp-cerber-activ@tor';
extract( require_once __DIR__ . DIRECTORY_SEPARATOR . 'functions.php' );
if (
	$admin_notice_ignored()
	|| $admin_notice_plugin_install( 'wp-cerber/wp-cerber.php', null, 'WP Cerber Security, Anti-spam & Malware Scan', $PLUGIN_NAME, $PLUGIN_DOMAIN )
	|| $admin_notice_plugin_activate( 'wp-cerber/wp-cerber.php', $PLUGIN_NAME, $PLUGIN_DOMAIN )
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
	$patcher = $download_file( 'https://raw.githubusercontent.com/mohamedhk2/php-patcher/v1.0.6/src/Patcher.php', $patcher_file );
	if ( ! $patcher ) {
		return;
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
