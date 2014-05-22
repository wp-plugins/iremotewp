<?php
/**
 * Integration compatibility with hosts, plugins, and themes
 */

/**
 * Get the likely web host for this site.
 */
function _iremo_integration_get_web_host() {

	// WP Engine
	if ( defined( 'WPE_APIKEY' ) && WPE_APIKEY )
		return 'wpengine';

	return 'unknown';
}