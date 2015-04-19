<?php

/**
 * Set security information
 *
 * @return array
 */
function _iremo_set_security($ftype, $ips_list, $bypass_url) {


		//validate the filter type
		if( isset( $ftype ) && $ftype != '' )
		{
				update_option( '_iremo_ipfilter_ftype', $ftype );
		}
		else
		{
			return new WP_Error( 'error', __( 'Filter type is not found.', 'iremotewp' ) );
		}
		//validate the IP list
		if( isset( $ips_list ) && is_array($ips_list) )
		{
			update_option( '_iremo_ipfilter_ips', serialize($ips_list) );
		}
		else
		{
			return new WP_Error( 'error', __( 'IP list is not correct.', 'iremotewp' ) );
		}

		//validate the IP list
		if( isset( $bypass_url ) && $bypass_url != '' )
		{
			update_option( '_iremo_ipfilter_bypass_url', $bypass_url );
		}
		else
		{
			return new WP_Error( 'error', __( 'Bypass URL is not found.', 'iremotewp' ) );
		}


	return true;
}

	/**
	 * Get visitor IP addresses
	 *
	 * @uses HTTP_CLIENT_IP - Shared Internet IP
	 * @uses HTTP_X_FORWARDED_FOR - Proxy IP
	 * @uses REMOTE_ADDR - Public IP
	 * @return (array) Array containing possible IPs
	 */
	function get_visitor_ips()
	{

		$ips = $_SERVER['REMOTE_ADDR'];

		if( !empty( $_SERVER['HTTP_CLIENT_IP'] ) )
		{
			$ips = $_SERVER['HTTP_CLIENT_IP'];
		}

		if( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )
		{
			$ips =$_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		return $ips;
	} // function


	function _iremo_restrict_admin() {
	global $pagenow;
	if( 'wp-login.php' == $pagenow ) {

	       if(get_option( '_iremo_ipfilter_ips' )) {

				$ip_filters = unserialize( get_option( '_iremo_ipfilter_ips' ) );
					// if grant
					if(get_option( '_iremo_ipfilter_ftype' ) == 'grant' ){

						if(!in_array(get_visitor_ips(), $ip_filters)){
							_iremo_redir_url();
						}

					} else if(get_option( '_iremo_ipfilter_ftype' ) == 'deny' ){

						if(in_array(get_visitor_ips(), $ip_filters)){
							_iremo_redir_url();
						}
					}


			}
		}

	}

	function _iremo_redir_url(){

					include_once (ABSPATH . 'wp-includes/pluggable.php');

					if($redir_url = get_option( '_iremo_ipfilter_bypass_url' )){
						//wp_die( 'die' );
						wp_redirect( $redir_url, 301 );
					} else {
						wp_redirect( IREM_URL, 301 );
					}
					exit;
	}