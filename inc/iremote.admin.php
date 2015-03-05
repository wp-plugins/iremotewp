<?php
/**
 * Register the irem_verify_key settings
 *
 * @return null
 */


function iremo_setup_admin() {
	register_setting( 'irem-settings', 'irem_verify_key' );
}

add_action( 'admin_menu', 'iremo_setup_admin' );
add_action( 'admin_menu', 'iRemoteWPMenu');

//admin_url("options-general.php?page=iRemoteWP");

/** Step 1. */
function iRemoteWPMenu() {
	add_options_page( 'iRemoteWP Settings', 'iRemoteWP Settings', 'manage_options', 'iRemoteWP', 'iRemoteWPOptions' );
}

/** Step 3. */
function iRemoteWPOptions() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}



	echo '<div id="iremotewp_settings">';
?>

           <div>

			<h2><a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"><?php echo '<img src="' . plugins_url( 'assets/img/iremotewp-settings.png' , dirname(__FILE__) ) . '" alt=""> '; ?></a></h2>

			<p>

				<?php _e( 'iRemoteWP is almost ready. you need to enter your ','iremotewp' ); ?> <a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"><?php _e( 'iRemoteWP site key','iremotewp' ); ?></a> <?php _e( 'below. ','iremotewp' ); ?> <?php _e( 'Once you added your key, you can start to manage your site on iremotewp.com. ','iremotewp' ); ?> <?php _e( 'If you didn\'t create any site key for this site ','iremotewp' ); ?> <a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"> <?php _e( 'Click here and get your site key free.','iremotewp' ); ?></a> <?php _e( 'If you are using multisite WordPress, you need to enter site key for only main site.','iremotewp' ); ?>

			</p>

			</div>


		<br />
		<br />

        <div class="iremotewp_settings">

		<h3><?php _e( 'Enter Your Site Key', 'iremotewp' ); ?></h3>

		<form method="post" action="options.php">

				<strong><?php _e( 'Site Key', 'iremotewp' ); ?>:</strong>

				<input type="text" style="margin-left: 5px; margin-right: 5px; width:40%" value="<?php echo get_option( 'irem_verify_key' ); ?>" class="code regular-text" id="irem_verify_key" name="irem_verify_key" />

				<input type="submit" value="<?php _e( 'Save My Key','iremotewp' ); ?>" class="button-primary" />



			<style>#message { display : none; }</style>

			<?php settings_fields( 'irem-settings' );

			// Output any sections defined for page sl-settings
			do_settings_sections( 'irem-settings' ); ?>

		</form>

	<?
	echo '</div>';
}

/**
 * Add API Key form
 *
 * Only shown if no API Key
 *
 * @return null
 */
function iremo_add_api_key_admin_notice() { ?>

	<div id="iremote_info" class="error">

		<form method="post" action="options.php">

              	<h2><a href="<?php echo admin_url("options-general.php?page=iRemoteWP"); ?>" target="_self"><?php echo '<img src="' . plugins_url( 'assets/img/iremotewp-settings.png' , dirname(__FILE__) ) . '" alt=""> '; ?></a></h2>

				<?php _e( 'Congratulations iRemoteWP is ready! but you should enter your site key before to use.', 'iremotewp' ); ?> <br /> <?php _e( 'Please goto the iRemoteWP settings page and enter your site key to continue', 'iremotewp' );?> <strong><a href="<?php echo admin_url("options-general.php?page=iRemoteWP"); ?>" target="_self"> <?php _e( 'iRemoteWP settings page', 'iremotewp' );?></a></strong> <?php _e( 'and enter your site key to continue.', 'iremotewp' );?>

			<p>

				<?php _e( 'FOR USE IN THE SETTINGS PAGE YOU CAN GET YOUR KEY AT ','iremotewp' ); ?> <strong><a href="<?php echo esc_url( iremo_get_irem_url() ); ?>" target="_blank"><?php _e( 'REGISTER AND GET SITE KEY','iremotewp' ); ?></a></strong>

			</p>

			<style>#message { display : none; }</style>

			<?php settings_fields( 'irem-settings' );

			// Output any sections defined for page sl-settings
			do_settings_sections( 'irem-settings' ); ?>

		</form>

	</div>


<?php }

if ( ! iremo_get_site_keys() && $_GET['page'] <> 'iRemoteWP')
	add_action( 'admin_notices', 'iremo_add_api_key_admin_notice' );

/**
 * Success message for a newly added API Key
 *
 * @return null
 */

function iremo_api_key_added_admin_notice() {

	if ( function_exists( 'get_current_screen' ) && get_current_screen()->base != 'plugins' || empty( $_GET['settings-updated'] ) || ! iremo_get_site_keys() )
		return; ?>

	<div id="iremote_info" class="updated">
		<p><strong><?php _e( 'iRemoteWP API Key successfully added' ); ?></strong>, close this window to go back to <a href="<?php echo esc_url( iremo_get_irem_url( '/system/' ) ); ?>"><?php _e( 'iRemoteWP','iremotewp' ); ?></a>.</p>
	</div>

<?php }
add_action( 'admin_notices', 'iremo_api_key_added_admin_notice' );

/**
 * Delete the API key on activate and deactivate
 *
 * @return null
 */
function iremo_deactivate() {
	delete_option( 'irem_verify_key' );
	$sitekey_url = site_url();
	$find_h = '#^http(s)?://#';
	$replace = '';
	$sitekey_url = rtrim(preg_replace( $find_h, $replace, $sitekey_url ), '/').'/';
	$sitekey_new = @file_get_contents(IREM_API_URL.'sitekey/?siteurl='.$sitekey_url);

	if($sitekey_new){
		add_option( 'irem_verify_key', $sitekey_new, '', 'yes' );
	}
}

// Plugin activation and deactivation
add_action( 'activate_' . IREMOTE_PLUGIN_SLUG . '/plugin.php', 'iremo_deactivate' );
add_action( 'deactivate_' . IREMOTE_PLUGIN_SLUG . '/plugin.php', 'iremo_deactivate' );