<?php
/*
Plugin Name: iRemoteWP
Plugin URI: https://iremotewp.com/
Description: Manage all of your WordPress based sites from one location on <a href="https://iremotewp.com/">iRemoteWP</a>.
Author: iRemoteWP
Author URI: https://iremotewp.com/
Text Domain: iremotewp
Domain Path: /languages/
Version: 1.4.0
*/

/*  Copyright 2014 iRemoteWP.com  (email : support@iremotewp.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
error_reporting(0);
@ignore_user_abort(true);
@ini_set('memory_limit', '384M');
@ini_set('max_execution_time', 4000);
@set_time_limit(4000);

define( 'IREMOTE_PLUGIN_SLUG', 'iremotewp' );
define( 'IREMOTE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'IREM_URL' ) )
	define( 'IREM_URL', 'https://iremotewp.com/' );

if ( ! defined( 'IREM_API_URL' ) )
	define( 'IREM_API_URL', 'https://iremotewp.com/system/' );

if ( ! defined( 'IREM_LANG_DIR' ) )
	define( 'IREM_LANG_DIR', apply_filters( 'irem_filter_lang_dir', trailingslashit( IREMOTE_PLUGIN_PATH ) . trailingslashit( 'languages' ) ) );

if ( ! defined( 'IPFILTER_IPv4_REGEX' ) )
	define( 'IPFILTER_IPv4_REGEX', '#((\d{1,3}|\*)(\.(\d{1,3}|\*)){1,3}|\*)#' );

// Don't activate on anything less than PHP 5.2.4
if ( version_compare( phpversion(), '5.2.4', '<' ) ) {

	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( IREMOTE_PLUGIN_SLUG . '/plugin.php' );

	if ( isset( $_GET['action'] ) && ( $_GET['action'] == 'activate' || $_GET['action'] == 'error_scrape' ) )
		die( __( 'iRemoteWP requires PHP version 5.2.4 or greater.', 'iremotewp' ) );

}


// iRemoteWP Styles Loaded
function iremotewp_styles() {
    if (is_admin()) {

	wp_enqueue_style('bootstrap-rps', plugin_dir_url( __FILE__ ) . 'assets/css/iremotewp.css');
	}
}
add_action('init', 'iremotewp_styles');

require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.admin.php' );
require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.compatability.php' );
require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.security.php' );

if ( get_option( 'iremo_enable_log' ) )
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.log.php' );



// Backups require 3.1
if ( version_compare( get_bloginfo( 'version' ), '3.1', '>=' ) ) {
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.ir.backup.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.backups.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/lib/DropboxUploader.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/lib/s3/S3.php' );
}

/**
 * Get a needed URL on the iRemoteWP site
 *
 * @param string      $uri     URI for the URL (optional)
 * @return string     $url     Fully-qualified URL to iRemoteWP
 */
function iremo_get_irem_url( $uri = '' ) {

	if ( empty( $uri ) )
		return IREM_URL;

	$url = rtrim( IREM_URL, '/' );
	$uri = trim( $uri, '/' );
	return $url . '/' . $uri . '/';
}

/**
 * Catch the API calls and load the API
 *
 * @return null
 */
function iremo_catch_api_call() {

	if ( empty( $_POST['irem_site_key'] ) )
		return;

	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.integration.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.plugins.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.themes.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.content.php' );
	require_once( IREMOTE_PLUGIN_PATH . '/inc/iremote.api.php' );

		exit;

}
add_action( 'init', 'iremo_catch_api_call', 100 );


/**
 * Check for a signal from the iremote
 *
 * @since 2.7.0
 */
function iremo_check_signal() {

	$signal_key = 'iremo_signal';

	if ( false === get_transient( $signal_key ) ) {

		$signal_url = trailingslashit( IREM_URL ) . 'signal/';
		$response = wp_remote_get( $signal_url );
		$response_body = wp_remote_retrieve_body( $response );
		if ( 'destroy the evidence!' == trim( $response_body ) )
			delete_option( 'irem_verify_key' );

		// One request per day
		set_transient( $signal_key, 'clear', 60 * 60 * 24 );
	}

}
add_action( 'init', 'iremo_check_signal' );

/**
 * Get the stored IREM API key
 *
 * @return mixed
 */
function iremo_get_site_keys() {
	$keys = apply_filters( 'irem_site_keys', get_option( 'irem_verify_key' ) );
	if ( ! empty( $keys ) )
		return (array)$keys;
	else
		return array();
}

function iremo_plugin_update_check() {

	$plugin_data = get_plugin_data( __FILE__ );

	// define the plugin version
	define( 'IREMOTE_VERSION', $plugin_data['Version'] );

	// Fire the update action
	if ( IREMOTE_VERSION !== get_option( 'iremo_plugin_version' ) )
		iremo_update();

}
add_action( 'admin_init', 'iremo_plugin_update_check' );

/**
 * Run any update code and update the current version in the db
 *
 * @access public
 * @return void
 */
function iremo_update() {

	/**
	 * Remove the old _iremotewp_backups directory
	 */
	$uploads_dir = wp_upload_dir();

	$old_iremotewp_dir = trailingslashit( $uploads_dir['basedir'] ) . '_iremotewp_backups';

	if ( file_exists( $old_iremotewp_dir ) )
		IREMOTE_Backups::rmdir_recursive( $old_iremotewp_dir );

	// If BackUpWordPress isn't installed then lets just delete the whole backups directory
	if ( ! defined( 'HMBKP_PLUGIN_PATH' ) && $path = get_option( 'hmbkp_path' ) ) {

		IREMOTE_Backups::rmdir_recursive( $path );

		delete_option( 'hmbkp_path' );
		delete_option( 'hmbkp_default_path' );
		delete_option( 'hmbkp_plugin_version' );

	}

	// Update the version stored in the db
	if ( get_option( 'iremo_plugin_version' ) !== IREMOTE_VERSION )
		update_option( 'iremo_plugin_version', IREMOTE_VERSION );

}

function _iremo_upgrade_core()  {

	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS )
		return new WP_Error( 'disallow-file-mods', __( "File modification is disabled with the DISALLOW_FILE_MODS constant.", 'iremotewp' ) );

	include_once ( ABSPATH . 'wp-admin/includes/admin.php' );
	include_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
	include_once ( ABSPATH . 'wp-includes/update.php' );
	require_once ( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
	require_once IREMOTE_PLUGIN_PATH . 'inc/class-iremote-core-upgrader-skin.php';

	// check for filesystem access
	if ( ! _irem_check_filesystem_access() )
		return new WP_Error( 'filesystem-not-writable', __( 'The filesystem is not writable with the supplied credentials', 'iremotewp' ) );

	// force refresh
	wp_version_check();

	$updates = get_core_updates();

	if ( is_wp_error( $updates ) || ! $updates )
		return new WP_Error( 'no-update-available' );

	$update = reset( $updates );

	if ( ! $update )
		return new WP_Error( 'no-update-available' );

	$skin = new IREMOTE_Core_Upgrader_Skin();

	$upgrader = new Core_Upgrader( $skin );
	$result = $upgrader->upgrade($update);

	if ( is_wp_error( $result ) )
		return $result;

	global $wp_current_db_version, $wp_db_version;

	// we have to include version.php so $wp_db_version
	// will take the version of the updated version of wordpress
	require( ABSPATH . WPINC . '/version.php' );

	wp_upgrade();

	return true;
}

function _irem_check_filesystem_access() {

	ob_start();
	$success = request_filesystem_credentials( '' );
	ob_end_clean();

	return (bool) $success;
}

function _irem_set_filesystem_credentials( $credentials ) {

	if ( empty( $_POST['filesystem_details'] ) )
		return $credentials;

	$_credentials = array(
		'username' => $_POST['filesystem_details']['credentials']['username'],
		'password' => $_POST['filesystem_details']['credentials']['password'],
		'hostname' => $_POST['filesystem_details']['credentials']['hostname'],
		'connection_type' => $_POST['filesystem_details']['method']
	);

	// check whether the credentials can be used
	if ( ! WP_Filesystem( $_credentials ) ) {
		return $credentials;
	}

	return $_credentials;
}
add_filter( 'request_filesystem_credentials', '_irem_set_filesystem_credentials' );
add_filter( 'https_ssl_verify', '__return_false' );
add_filter( 'https_local_ssl_verify', '__return_false' );
add_action( 'init', '_iremo_restrict_admin');

/**
 *
 */
function iremo_translations_init() {

	if ( is_admin() ) {

		/** Set unique textdomain string */
		$iremo_textdomain = 'iremotewp';

		/** The 'plugin_locale' filter is also used by default in load_plugin_textdomain() */
		$plugin_locale = apply_filters( 'plugin_locale', get_locale(), $iremo_textdomain );

		/** Set filter for WordPress languages directory */
		$iremo_wp_lang_dir = apply_filters(
			'iremo_filter_wp_lang_dir',
				trailingslashit( WP_LANG_DIR ) . trailingslashit( 'iremotewp' ) . $iremo_textdomain . '-' . $plugin_locale . '.mo'
		);

		/** Translations: First, look in WordPress' "languages" folder = custom & update-secure! */
		load_textdomain( $iremo_textdomain, $iremo_wp_lang_dir );

		/** Translations: Secondly, look in plugin's "languages" folder = default */
		load_plugin_textdomain( $iremo_textdomain, FALSE, IREM_LANG_DIR );
	}
}
add_action( 'plugins_loaded', 'iremo_translations_init' );

/**
 * Format a WP User object into a better
 * object for the API
 */
function iremo_format_user_obj( $user_obj ) {
	$new_user_obj = new stdClass;

	foreach( $user_obj->data as $key => $value ) {
		$new_user_obj->$key = $value;
	}

	$new_user_obj->roles = $user_obj->roles;
	$new_user_obj->caps = $user_obj->caps;

	return $new_user_obj;
}
