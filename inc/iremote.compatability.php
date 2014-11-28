<?php
/**
 * Function which takes active plugins and foreaches them though our list of security plugins
 * @return array
 */
function iremo_get_incompatible_plugins() {

	// Plugins to check for.
	$security_plugins = array(
		__( 'BulletProof Security', 'iremotewp' ),
		__( 'Wordfence Security', 'iremotewp' ),
		__( 'Better WP Security', 'iremotewp' ),
		__( 'Wordpress Firewall 2', 'iremotewp' )
	);

	$active_plugins = get_option( 'active_plugins', array() );
	$dismissed_plugins = get_option( 'dismissed-plugins', array() );

	$plugin_matches = array();

	// foreach through activated plugins and split the string to have one name to check results against.
	foreach ( $active_plugins as $active_plugin ) {

		$plugin = get_plugin_data( WP_PLUGIN_DIR . '/' . $active_plugin );

		if ( in_array( $plugin['Name'], $security_plugins ) && ! in_array( $active_plugin, $dismissed_plugins ) )
			$plugin_matches[$active_plugin] = $plugin['Name'];

	}

	return $plugin_matches;
}

/**
 * foreach through array of matched plugins and for each print the notice.
 */
function iremo_security_admin_notice() {

	if ( ! current_user_can( 'install_plugins' ) )
		return;

	foreach ( iremo_get_incompatible_plugins() as $plugin_path => $plugin_name ) :

		?>

		<div class="error">

			<a class="close-button button" style="float: right; margin-top: 4px; color: inherit; text-decoration: none; " href="<?php echo add_query_arg( 'irem_dismiss_plugin_warning', $plugin_path ); ?>"><?php _e( 'Don\'t show me again','iremotewp' ); ?></a>

			<p>

				<?php _e( 'The plugin', 'iremotewp' );?> <strong><?php echo esc_attr( $plugin_name ); ?></strong> <?php _e( 'may cause issues with iRemoteWP.', 'iremotewp' ); ?>

				<a href="http://iremotewp.com/support/?s=<?php echo esc_attr( $plugin_name ); ?>" title="iRemoteWP Support"> <?php _e( 'Click here for instructions on how to resolve this issue', 'iremotewp' ); ?> </a>

			</p>

		</div>

	<?php endforeach;

}

add_action( 'admin_notices', 'iremo_security_admin_notice' );

/**
 * Function which checks to see if the plugin was dismissed.
 */
function iremo_dismissed_plugin_notice_check() {

	if ( current_user_can( 'install_plugins' ) && ! empty( $_GET['irem_dismiss_plugin_warning'] ) ) {

		$dismissed = get_option( 'dismissed-plugins', array() );
		$dismissed[] = sanitize_text_field( $_GET['irem_dismiss_plugin_warning'] );

		update_option( 'dismissed-plugins', $dismissed );

		wp_safe_redirect( remove_query_arg( 'irem_dismiss_plugin_warning' ) );
		exit;

	}
}
add_action( 'admin_init', 'iremo_dismissed_plugin_notice_check' );