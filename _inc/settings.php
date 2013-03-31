<?php

/**
 * Image Store - admin settings
 *
 * @file settings.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/settings.php
 * @since 3.2.1
 */
 
class ImStoreSet extends ImStoreAdmin {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreSet( $page, $action ) {
		
		$this->ImStoreAdmin( $page, $action );
		
		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
		
		add_filter( 'ims_settings_tabs', array( &$this, 'settings_tabs' ), 2 );
		
		add_action( 'admin_init', array( &$this, 'save_settings' ), 10 );
		add_action( 'ims_settings', array( &$this, 'watermark_location'), 2 );
		add_action( 'ims_setting_fields', array( &$this, 'show_user_caps' ), 11 );
		add_action( 'ims_setting_fields', array( &$this, 'add_gateway_fields' ), 10 );
		
		//script styles
		add_action( 'admin_print_styles', array( &$this, 'load_settings_styles' ), 1 );
	}
	
	/**
	 * Load admin styles
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function load_settings_styles( ) {
		wp_enqueue_style( 'ims-settings', IMSTORE_URL . '/_css/settings.css', false, $this->version, 'all' );
	}
	
	/**
	 * Disable tabs if store is disable
	 *
	 * @return array
	 * @since 3.2.1
	 */
	function settings_tabs( $tabs ){
	
		if( !$this->opts['disablestore'] )
			return $tabs;
		
		foreach(array( 'payment', 'checkout', ) as $name )
			unset( $tabs[$name] );
			
		return $tabs;
	}
	
	/**
	 * Add watermark location option
	 *
	 * @return void
	 * @since 3.0.3
	 */
	function watermark_location($boxid) {
		if ( $boxid != 'image' )
			return;

		$option = get_option('ims_wlocal');
		$wlocal = empty($option) ? 5 : $option;

		echo '<tr class="row-wlocal" valign="top"><td><label>' . __('Watermark location', 'ims') . '</label></td><td>';
		echo '<div class="row">
			<label><input name="wlocal" type="radio" value="1" ' . checked(1, $wlocal, false) . ' /></label>
			<label><input name="wlocal" type="radio" value="2" ' . checked(2, $wlocal, false) . '/></label>
			<label><input name="wlocal" type="radio" value="3" ' . checked(3, $wlocal, false) . '/></label>
			</div>';
		echo '<div class="row">
			<label><input name="wlocal" type="radio" value="4" ' . checked(4, $wlocal, false) . '/></label>
			<label><input name="wlocal" type="radio" value="5" ' . checked(5, $wlocal, false) . '/></label>
			<label><input name="wlocal" type="radio" value="6" ' . checked(6, $wlocal, false) . '/></label>
			</div>';
		echo '<div class="row">
			<label><input name="wlocal" type="radio" value="7" ' . checked(7, $wlocal, false) . '/></label>
			<label><input name="wlocal" type="radio" value="8" ' . checked(8, $wlocal, false) . '/></label>
			<label><input name="wlocal" type="radio" value="9" ' . checked(9, $wlocal, false) . '/></label>
			</div>';
		echo '</td></tr>';
	}
	
	/**
	 * Add fields base on 
	 * wagateway selected
	 *
	 * @param array $settings
	 * @return array
	 * @since 3.2.1
	 */
	function add_gateway_fields( $settings ){
		
		//enotification
		if ( $this->opts['gateway']['enotification'] ) {
			$settings['payment']['shippingmessage'] = array(
				'val' => '',
				'type' => 'textarea',
				'label' => __('Shipping Message', 'ims'),
			);
			$settings['payment']['required_'] = array(
				'multi' => true,
				'label' => __('Required Fields', 'ims'),
			);
			
			foreach ((array) $this->opts['checkoutfields'] as $key => $label)
				$settings['payment']['required_']['opts'][$key] = array( 'val' => 1, 'label' => $label, 'type' => 'checkbox' );
			
			$settings['payment']['currency']['opts'][] = __('---- eNotification only ----', 'ims');
			$settings['payment']['currency']['opts']['ARS'] = __('Argentina Peso', 'ims');
			$settings['payment']['currency']['opts']['CLP'] = __('Chile Peso', 'ims');
			$settings['payment']['currency']['opts']['INR'] = __('Indian Rupee', 'ims');
			$settings['payment']['currency']['opts']['VND'] = __('Vietnam Dong', 'ims');
		}
		
		//wepay
		if ($this->opts['gateway']['wepaystage']
		|| $this->opts['gateway']['wepayprod']) {
			$settings['payment']['wepayclientid'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Client ID', 'ims'),
			);
			$settings['payment']['wepayclientsecret'] = array(
				'val' => '',
				'type' => 'password',
				'label' => __('Client Secret', 'ims'),
			);
			$settings['payment']['wepayaccesstoken'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Access Token', 'ims'),
			);
			$settings['payment']['wepayaccountid'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Account ID', 'ims'),
			);
		}

		//paypal
		if ( $this->opts['gateway']['paypalsand']
		|| $this->opts['gateway']['paypalprod'] ) {
			$settings['payment']['paypalname'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('PayPal Account E-mail', 'ims'),
			);
		}
		
		//google	
		if( $this->opts['gateway']['googlesand'] 
		|| $this->opts['gateway']['googleprod'] ){
			$settings['payment']['taxcountry'] = array(
				'val' => '',
				'type' => 'text',
				'label' => '<a href="http://goes.gsfc.nasa.gov/text/'.
				'web_country_codes.html" target="_blank">' . __( 'Country Code', 'ims' ) . '</a>',
			);
			$settings['payment']['googleid'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Google merchant ID', 'ims'),
			);
			$settings['payment']['googlekey'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Google Merchant key', 'ims'),
			);
		}
		
		//pagseguro	
		if( $this->opts['gateway']['pagsegurosand'] 
		|| $this->opts['gateway']['pagseguroprod'] ){
			
			$settings['payment']['pagseguroemail'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('PagSeguro Seller email', 'ims'),
			);
			$settings['payment']['pagsegurotoken'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('PagSeguro token', 'ims'),
			);
			$settings['payment']['pagsegurotesturl'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('PagSeguro test url', 'ims'),
			);
		}
		
		//custom
		if ($this->opts['gateway']['custom']) {
			$settings['payment']['gateway_name'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Custom Service Name', 'ims'),
			);
			$settings['payment']['gateway_method'] = array(
				'type' => 'radio',
				'label' => __('Custom Method', 'ims'),
				'opts' => array(
					'get' => __('Get', 'ims'),
					'post' => __('Post', 'ims'),
				),
			);
			$settings['payment']['gateway_url'] = array(
				'val' => '',
				'type' => 'text',
				'label' => __('Custom URL', 'ims'),
			);
			$settings['payment']['data_pair'] = array(
				'val' => '',
				'type' => 'textarea',
				'label' => __('Custom Data Pair', 'ims'),
				'desc' => __('Enter key|value should be separated by a pipe, and each data pair by a comma. 
				 ex: key|value,Key|value. <br />
				<strong>Note:</strong> you will have to setup your own notification script ro record sales.<br />
				<strong>Tags:</strong> ', 'ims') . str_replace( '/', '', implode( ', ', $this->opts['carttags'] ) ),
			);
		}		
		return $settings;
	}
	
	/**
	 * Show user capabilities
	 * to provide permission to image store
	 *
	 * @param array $settings
	 * @return array
	 * @since 3.2.1
	 */
	function show_user_caps( $settings ){
		if ( empty( $_GET['userid'] ) )
			return $settings;
		
		$userid = ( int ) $_GET['userid'];
		$settings['permissions']['ims_'] = array(
			'multi' => true,
			'type' => 'checkbox',
			'label' => __('Permissions', 'ims'),
		);
		foreach ( $this->uopts['caplist'] as $cap => $capname )
			$settings['permissions']['ims_']['opts'][$cap] = array( 'val' => 1, 'label' => $capname, 'type' => 'checkbox', 'user' => $userid );
		$this->opts['userid'] = $userid;
		return $settings;
	}
	
	/**
	 * Get all user except customers
	 * and administrators
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function get_users( ) {
		$users = wp_cache_get( 'ims_users', 'ims' );

		if ( false == $users ) {
			global $wpdb;
			$users = $wpdb->get_results( 
				"SELECT ID, user_login name FROM $wpdb->users u 
				JOIN $wpdb->usermeta um ON ( u.ID = um.user_id ) 
				WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value 
				NOT LIKE '%{$this->customer_role}%' AND meta_value NOT LIKE '%administrator%'" 
			);
			wp_cache_set( 'ims_users', $users, 'ims' );
		}

		if ( empty( $users ) )
			return array( '0' => __('No users to manage', 'ims'));

		$list = array();
		$list[0] = __('Select user', 'ims');

		foreach ($users as $user)
			$list[$user->ID] = $user->name;

		return $list;
	}
	
	/**
	 * Return Image Store options
	 *
	 * @parm string $option
	 * @parm unit $userid 
	 * @return string/int
	 * @since 3.0.0
	 */
	function vr( $option, $key = false, $userid = 0 ) {
		if ( $userid ) {
			$usermeta = get_user_meta( $userid, 'ims_user_caps', true );
			if ( isset( $usermeta[$option] ) ) return true;
			return false;
		}
		if ( isset( $this->opts[$option][$key] ) && is_array( $this->opts[$option] ) )
			return stripslashes( $this->opts[$option][$key] );
		elseif ( isset( $this->opts[$option . $key] ) )
			return stripslashes( $this->opts[$option . $key] );
		elseif ( isset( $this->opts[$option] ) && is_string( $this->opts[$option] ) )
			return stripslashes( $this->opts[$option] );
		elseif ( $o = get_option( $option ) )
			return stripslashes( $o );
		elseif ( $ok = get_option( $option . $key ) )
			return stripslashes( $ok );
		return false;
	}
	
	/**
	 * Check if it's a checkbox
	 * or radio box
	 *
	 * @parm string $elem
	 * @return bool
	 * @since 3.0.0
	 */
	function is_checkbox($type) {
		if ( $this->in_array( $type, array( 'checkbox', 'radio' ) ) )
			return true;
		return false;
	}
	
	/**
	 * Display unit sizes
	 *
	 * @return void
	 * @since 1.1.0
	 */
	function dropdown_units($name, $selected) {
		$output = '<select name="' . $name . '" class="unit">';
		foreach ( $this->units as $unit => $label ) {
			$select = ( $selected == $unit ) ? ' selected="selected"' : '';
			$output .= '<option value="' . esc_attr($unit) . '" ' . $select . '>' . $label . '</option>';
		}
		echo $output .= '</select>';
	}
	
	/**
	 * Save settings
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function save_settings( ) {
		
		if( isset( $_REQUEST['flush'] ) )
			flush_rewrite_rules( ); 

		if ( empty( $_POST ) || $this->page != 'ims-settings' )
			return;
		
		check_admin_referer( 'ims_settings' );

		//reset settings
		if ( isset( $_POST['resetsettings'] ) || isset( $_POST['uninstall'] ) ) {

			include_once( IMSTORE_ABSPATH . '/admin/install.php' );

			if ( isset( $_POST['uninstall'] ) )
				ImStoreInstaller::imstore_uninstall( );
			
			ImStoreInstaller::imstore_default_options( );
							
			wp_redirect( $this->pageurl . '&flush=1&ms=3' );
			die( );

		//save options
		} elseif ( isset( $_POST['ims-action'] ) ) {
			
			$action = $_POST['ims-action'];
			include( IMSTORE_ABSPATH . "/admin/settings/settings-fields.php" );

			if ( empty( $action ) || empty( $settings[$action]) ) {
				
				wp_redirect( $this->pageurl );
				die( );
			}

			//clear image cache data
			update_option( 'ims_cache_time', current_time( 'timestamp' ) );

			if ( 'permissions' == $action ) {
				if ( !is_numeric( $_POST['userid'] ) ) {
					
					wp_redirect( $this->pageurl );
					die( );
				}

				$newcaps = array( );
				$userid = (int) $_POST['userid'];
				
				foreach ( $this->uopts['caplist'] as $cap => $label )
					if ( !empty($_POST['ims_'][$cap] ) )
						$newcaps['ims_' . $cap] = 1;
				
				update_user_meta( $userid, 'ims_user_caps', $newcaps);
				do_action( 'ims_user_permissions', $action, $userid, $this->uopts );
				
				wp_redirect( $this->pageurl . "&userid=" . $userid );
				die( );
			}

			foreach ( $settings[$action] as $key => $val ) {
				if ( isset( $val['col'] ) ) {
					foreach ( $val['opts'] as $k2 => $v2) {
						if ( empty($_POST[$k2] ) )
							$this->opts[$k2] = false;
						else $this->opts[$k2] = $_POST[$k2];
					}
				}elseif ( isset( $val['multi'] ) ) {
					foreach ( $val['opts'] as $k2 => $v2 ) {
						if ( get_option( $key . $k2 ) )
							update_option($key . $k2, $_POST[$key][$k2]);
						elseif ( isset( $this->opts[$key] ) && is_array( $this->opts[$key] ) )
							$this->opts[$key][$k2] = isset($_POST[$key][$k2]) ? $_POST[$key][$k2] : false;
						elseif ( !empty( $_POST[$key][$k2] ) )
							$this->opts[$key . $k2] = $_POST[$key][$k2];
						else $this->opts[$key . $k2] = false;
					}
				}elseif( $key == 'galleriespath' && !preg_match('/^\//',$_POST['galleriespath'] ) ){
					$this->opts[$key] = "/" . trim( $_POST['galleriespath'] );
				}elseif ( isset($_POST[$key] ) )
					$this->opts[$key] = $_POST[$key];
				else $this->opts[$key] = false;
			}

			//multisite support
			if ( is_multisite( ) && $this->sync == true )
				switch_to_blog( 1 );

			update_option( $this->optionkey, $this->opts );
			
			if ( isset( $_POST['wlocal'] ) )
				update_option( 'ims_wlocal', $_POST['wlocal'] );

			if ( isset( $_POST['album_template'] ) ) 
				update_option( 'ims_searchable', ( empty( $_POST['ims_searchable'] ) ) ? false : $_POST['ims_searchable'] );
			
			if ( $this->in_array( $action, array( 'taxonomies', 'image', 'gallery' ) )  )
				$this->pageurl .= "&flush=1";
			
			do_action( 'ims_save_settings', $action, $settings );

			wp_redirect( $this->pageurl . '&ms=4' );
			die( );
		}
	}
}