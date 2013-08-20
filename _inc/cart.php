<?php

/**
 * Image Store - Cart
 *
 * @file cart.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/cart.php
 * @since 3.2.1
 */
 
class ImStoreCart {
	
	public $error = false;
	public $status = false;
	public $orderid = false;
	public $validated = false;
	public $gallery_id = false;
	public $substitutions = false;
	public $download_links = false;
	
	public $cart = array( );
	public $data = array( );
	public $sizes = array( );
	public $listmeta = array( );
	
	/**
	 * Setup cart data
	 *
	 * @param unit $orderid
	 * @return array
	 * @since 3.2.1
	 */
	function setup_cart( $orderid = false ){
		
		$this->setup_defaults( );
		
		if ( isset( $_COOKIE['ims_orderid_' . COOKIEHASH] ) )
			$this->orderid = $_COOKIE[ 'ims_orderid_' . COOKIEHASH ];
		else if ( $orderid )	
			$this->orderid = $orderid;
		else  return $this->cart;
		
		$this->status = get_post_status( $this->orderid );
		
		if ( $cart = get_post_meta( $this->orderid, '_ims_order_data', true ) )
			$this->cart = wp_parse_args( $cart, $this->cart );
		
		if( $data = get_post_meta( $this->orderid, '_response_data', true ) )
			$this->data = wp_parse_args( $data, $this->data );
		
		return $this->cart;
	}
	
	/**
	 * Setup default data
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function setup_defaults( ){
			
		$this->data['custom'] = false;
		$this->data['gallery_id'] = false;
		
		$this->data['method'] = false;
		$this->data['ims_phone'] = false;
		$this->data['data_integrity'] = false;
		$this->data['num_cart_items'] = false;
		
		$this->data['last_name'] = false;
		$this->data['ims_phone'] = false;
		$this->data['instructions'] = false;
		$this->data['first_name'] = false;
		$this->data['user_email'] =false;
		$this->data['payer_email'] =false;
		
		$this->data['txn_id'] = false;
		$this->data['mc_gross'] = false;
		$this->data['mc_currency'] = false;
		$this->data['payment_gross'] = false;
		$this->data['payment_status'] = false;

		$this->data['address_city'] = false;
		$this->data['address_zip'] = false;
		$this->data['address_state'] = false;
		$this->data['address_street'] = false;
		$this->data['address_country'] = false;
		
		$this->cart['total'] = 0;
		$this->cart['items'] = 0;
		$this->cart['tax'] = false;
		$this->cart['shipping'] = 0;
		$this->cart['taxtype'] = false;
		$this->cart['images'] = false;
		$this->cart['tracking'] = false;
		$this->cart['subtotal'] = false;
						
		$this->cart['currency'] = false;
		$this->cart['gallery_id'] = false;
		$this->cart['shipping_type'] = 0;
		$this->cart['discounted'] = false;
		$this->cart['instructions'] = false;
		$this->cart['shippingcost'] = false;
		
		$this->cart['promo'] = array( 
			'code' => false,
			'discount' => false,
			'promo_id' => false,
			'promo_type' => false,
		);
	}
	
	/**
	 * Verify check out data request
	 *
	 * @param array $request
	 * @return bool
	 * @since 3.2.1
	 */
	function verify_request( $request ){
		
		if ( empty( $request['_wpnonce'] ) || !wp_verify_nonce( $request['_wpnonce'], "ims_add_to_cart" )  )
			wp_die( 'Security check failed. Try refreshing the page.' );
		
		if ( empty( $request['ims-quantity']) ||  !is_numeric( $request['ims-quantity'] )  )
			$this->error = __( 'Please, enter a valid image quantity', 'ims' );
		
		if ( empty( $request['ims-image-size'] ) )
			$this->error = __( 'Please, select an image size.', 'ims' );
		
		if ( empty( $request['ims-to-cart-ids'] ) || empty( $this->sizes ) )
			$this->error = __( 'There was a problem adding the images to the cart.', 'ims' );
		
		if ( !empty( $this->error ) )
			return false;
		
		return $this->validated = true;
	}
	
	/**
	 * Add items to cart
	 *
	 * @param array $request
	 * @return array
	 * @since 3.2.1
	 */
	function add_to_cart( $request ){
	
		if( 	!$this->validated )
			return false;
		
		$color = $finish = 0;
		
		if ( isset( $request['imstore-color'] ) )
			$color = $request['imstore-color'];

		if ( isset( $request['imstore-finish'] ) )
			$finish = $request['imstore-finish'];
		
		$images = explode( ',', $request['ims-to-cart-ids'] );
		
		do_action( 'ims_berofe_add_to_cart', $this->cart );
			
		global $ImStore;		
		foreach ( $images as $id ){
			
			$id = $ImStore->url_decrypt( $id );
			foreach ( $request['ims-image-size'] as $size_name ) {
			
				$size = str_replace( array( '|','\\','.',' ' ), '', $size_name );
				$price = $this->get_image_size_price( $size );
				
				if(  $price === false )
					continue;
				
				$quantity = intval( $request['ims-quantity'] );
				$this->cart['images'][$id][$size][$color] = $this->process_image( $id, $size, $price, $finish, $color, $quantity );
				
				$this->cart['items'] += $quantity;
				$this->cart['images'][$id][$size][$color]['size'] = $size_name;
				$this->cart['subtotal'] += $this->cart['images'][$id][$size][$color]['subtotal'];
			}
		}

		$this->save_cart( 'add' );
		return $this->cart;
	}
	
	/**
	 * Update cart data
	 *
	 * @param array $request
	 * @return void
	 * @since 3.2.1
	 */
	function update_cart( $request ){
		
		if ( empty( $request['_wpnonce'] ) || !wp_verify_nonce( $request["_wpnonce"], "ims_submit_order" ) )
			wp_die( 'Security check failed. Try refreshing the page.' );
			
		global $ImStore;
		do_action( 'ims_before_update_cart', $this );
		
		$this->cart['promo'] = array( 'code' => false,'discount' => false, 'promo_id' => false, 'promo_type' => false );
		
		if( isset( $_POST['promocode'] ) )
			$this->cart['promo']['code'] = $_POST['promocode'];
		
		if ( isset( $request['ims-remove'] ) ) {
			foreach ( ( array) $request['ims-remove'] as $delete ) {
				
				$val = explode( '|', $delete, 3 );
			  $val[0] = $ImStore->url_decrypt( $val[0] );
				
				unset( $this->cart['images'][ $val[0] ][ $val[1] ][ $val[2] ] );
				
				if ( empty( $this->cart['images'][ $val[0] ][ $val[1] ] ) )
				  unset( $this->cart['images'][ $val[0] ][ $val[1] ] );
				
				if ( empty( $this->cart['images'][ $val[0] ] ) )
				  unset( $this->cart['images'][ $val[0] ] );
			}
		}
		
		//if cart is empty save and return
		if ( empty( $this->cart['images'] ) ){
			update_post_meta( $this->orderid, '_ims_order_data', false );
			return;
		}
		
		//save instructions
		if(  isset( $_POST['instructions'] ) )
			$this->cart['instructions'] = esc_html( $_POST['instructions'] );
		else $this->cart['instructions'] = false;
		
		
		$this->cart['shippingcost'] = false;
		if( isset( $request['shipping'] ) )
			$this->cart['shipping_type'] = intval( $request['shipping']);
		
		$this->cart['items'] = $this->cart['subtotal'] = 0;
		
		foreach ( $this->cart['images'] as $id => $sizes ){
			foreach ( $sizes as $size => $colors ) {
				foreach ( $colors as $color => $values ) {
					
					$enc = $ImStore->url_encrypt( $id );
					
					if( isset( $request['ims-quantity'][$enc][$size][$color] ) && 
					$request['ims-quantity'][$enc][$size][$color] < 1 ){
						unset( $this->cart['images'][$id] );
						continue;
					}
					
					//check for downloadable images
					if ( !$colors[$color]['download'] && $ImStore->opts['shipping'] )
						$this->cart['shippingcost'] = true;
					
					$this->cart['images'][$id][$size][$color]['quantity'] = $request['ims-quantity'][$enc][$size][$color];
					
					$this->cart['images'][$id][$size][$color]['subtotal'] = ((
						$this->cart['images'][$id][$size][$color]['price'] +
						$this->cart['images'][$id][$size][$color]['color'] +
						$this->cart['images'][$id][$size][$color]['finish']) *
						$this->cart['images'][$id][$size][$color]['quantity'] 
					);

					$this->cart['items'] += $this->cart['images'][$id][$size][$color]['quantity'];
					$this->cart['subtotal'] += $this->cart['images'][$id][$size][$color]['subtotal'];
				}
			}
		}
		
		$this->save_cart( 'update' );
	}
	
	/**
	 * Save cart data
	 *
	 * @param string $action
	 * @return void
	 * @since 3.2.1
	 */
	function save_cart( $action ){
		
		global $ImStore;
		
		$this->cart['total'] = $this->cart['subtotal'];
		$this->cart['currency'] = $ImStore->opts['currency'];
		$this->shipping_opts = $ImStore->get_option( 'ims_shipping_options' );
		
		if( $this->validate_code( ) ){
			
			switch ( $this->cart['promo']['promo_type'] ) {
				
				case 2: $this->cart['promo']['discount'];
					break;
					
				case 3: $this->cart['promo']['discount'] = $this->shipping_opts[ $this->cart['shipping_type'] ]['price'];
					break;
					
				case 1: $this->cart['promo']['discount'] = ( $this->cart['subtotal'] * ( $this->cart['promo']['discount'] / 100 ) );
					break;
			}
			
			$this->cart['total'] = $this->cart['subtotal'] - $this->cart['promo']['discount'];
		}
		
		if ( $this->cart['shippingcost'] && isset( $this->shipping_opts[ $this->cart['shipping_type'] ]['price'] ) )
			$this->cart['total'] += $this->cart['shipping'] = $this->shipping_opts[ $this->cart['shipping_type'] ]['price'];
		
		
		if ( $ImStore->opts['taxamount'] && $ImStore->opts['taxtype'] ) {
			
			if ( $ImStore->opts['taxtype'] == 'percent' )
				$this->cart['tax'] = ( $this->cart['total'] * ( $ImStore->opts['taxamount'] / 100 ) );
			else $this->cart['tax'] = $ImStore->opts['taxamount'];
			
			$this->cart['total'] += $this->cart['tax'];
		}
		
		//if discount is more than total zeroout cart
		if( $this->cart['total'] < 0 )
			 $this->cart['total'] = 0;
		
		do_action( 'ims_before_save_cart', $this->cart );
		do_action( "ims_before_save_cart_{$action}", $this->cart );
		
		if ( !$this->orderid || $this->status != 'draft' ) {
			
			$order = array(
				'ping_status' => 'close',
				'post_status' => 'draft',
				'post_type' => 'ims_order',
				'comment_status' => 'close',
				'post_expire' => date( 'Y-m-d H:i', current_time( 'timestamp' ) + 86400 ),
				'post_title' => 'Ims Order - ' . date( 'Y-m-d H:i', current_time( 'timestamp' ) ),
			);
			
			if ( $orderid = wp_insert_post( apply_filters( 'ims_new_order', $order, $this->cart ) ) ){
				add_post_meta( $orderid, '_ims_order_data', $this->cart );
				setcookie( 'ims_orderid_' . COOKIEHASH, $orderid, time(  ) + 31536000, COOKIEPATH, COOKIE_DOMAIN );
			}
			
		} else update_post_meta( $this->orderid, '_ims_order_data', $this->cart );
		
		
		do_action( 'ims_after_add_to_cart', $this->cart );
		do_action( "ims_after_add_to_cart_{$action}", $this->cart );
	}
	
	/**
	 * Check out and send emails
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function checkout(  ){
		
		if( empty( $this->orderid ) || empty( $this->data) || $this->status != "draft" )
			return;
		
		global $ImStore;
		$total = $this->cart['discounted']  ?  $this->cart['discounted'] :  $this->cart['total'] ;
		
		if ( $this->cart['items'] && $this->data['mc_currency'] == $this->cart['currency'] &&
			abs( $this->data['mc_gross'] - $ImStore->format_price( $total, false ) ) < 0.00001 )
				$this->data['data_integrity'] = true;
		
		sleep( 1 ); 
		$ImStore->imspage = 'receipt';
			
		wp_update_post( array(
			'post_expire' => '0', 'ID' => $this->orderid,
			'post_status' => 'pending', 'post_date' => current_time( 'timestamp' )
		) );
		
	 	update_post_meta( $this->orderid, '_response_data', $this->data );
	 
		//update promotional count
		if( $this->cart['promo']['promo_id'] ){
			update_post_meta( $this->cart['promo']['promo_id'], '_ims_promo_count', 
				( int ) get_post_meta( $this->cart['promo']['promo_id'], '_ims_promo_count', true ) +1 
			);
		}
		
		do_action( 'ims_after_checkout', $this->cart );
		
		// Create/update customer
		if( $ImStore->user_id && current_user_can( 'customer' ) ){
			
			if( !function_exists( 'wp_update_user' ) )
				require_once( ABSPATH . WPINC . '/registration.php');

			wp_update_user( array( 
				'ID' => $ImStore->user_id,
				'last_name' => $this->data['last_name'],
				'first_name' => $this->data['first_name'], 
				'user_email' => $this->data['payer_email'], 
			) );
			
			update_user_meta( $ImStore->user_id, 'user_email', $this->data['payer_email'] );
			
			foreach ( $ImStore->opts['checkoutfields'] as $key => $label ) {
				if ( isset ( $this->data[$key] ) ) 
					update_user_meta( $ImStore->user_id, $key, $this->data[$key] );
			}
		}
		
		//send emails 
		$this->get_download_links( );
		$message = preg_replace( $ImStore->opts['tags'], $this->substitutions, $ImStore->opts['notifymssg'] );
		
		$headers = 'From: "' . esc_attr( $ImStore->opts['receiptname'] ). '" <' . esc_attr( $ImStore->opts['receiptemail'] ) . ">\r\n";
		$headers .= "Content-type: text/html; charset=utf8\r\n";
		
		$headers = apply_filters( 'ims_email_headers', $headers, $ImStore->opts['tags'], $this->substitutions );
		$message = apply_filters( 'ims_admin_message', $message, $ImStore->opts['tags'], $this->substitutions );
		
		wp_mail( $ImStore->opts['notifyemail'], $ImStore->opts['notifysubj'], $message . $this->download_links , $headers );
		
		if ( empty( $ImStore->opts['emailreceipt'] ) )
			return;
	
		//notify buyers
		if ( is_email( $this->data['payer_email'] ) && !get_post_meta( $this->orderid, '_ims_email_sent', true ) ){
			$message = make_clickable( wpautop( 
				stripslashes( preg_replace( $ImStore->opts['tags'], $this->substitutions, $ImStore->opts['thankyoureceipt'] ) ) 
			) );
			
			$message = apply_filters( 'ims_customer_message', $message, $ImStore->opts['tags'], $this->substitutions );
			if( wp_mail( $this->data['payer_email'], sprintf( __('%s receipt.', 'ims' ), get_bloginfo( 'blogname' ) ), $message . $this->download_links, $headers ) )
				update_post_meta( $this->orderid, '_ims_email_sent', 1 );
		}
	}
	
	/**
	 * Get download links and 
	 * setup data substitution
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function get_download_links( ) {
		
		global $ImStore;
				
		// Dont change array order
		$this->substitutions = apply_filters( 'ims_substitutions', array(
			str_replace( $ImStore->sym, '\\' . $ImStore->sym, $ImStore->format_price( $this->data['mc_gross'] ) ), 
			$this->data['payment_status'], 	get_the_title( $this->orderid ), 
			str_replace( $ImStore->sym, '\\' . $ImStore->sym, $ImStore->format_price( $this->cart['shipping'] ) ),
			$this->data['txn_id'], $this->data['last_name'], $this->data['first_name'], $this->data['payer_email'], $this->data['instructions'], $this->cart['items'] 
		) );
	
		if ( $this->cart['total'] === false || !$this->data['data_integrity']  )
			return false;
		
		if ( $this->download_links !== false )
			return $this->download_links;
		
		//normalize nonce field
		wp_set_current_user( 0 );
		$nonce = "_wpnonce=" . wp_create_nonce( "ims_download_img" );
		
		$downlinks = array( );
		
		foreach ( $this->cart['images'] as $id => $sizes ) {
			$enc = $ImStore->url_encrypt( $id );
			foreach ( $sizes as $size => $colors ) {
				foreach ($colors as $color => $item) {
					if ( $item['download'] )
						$downlinks[] = '<a href="' . esc_attr( IMSTORE_ADMIN_URL ) . "/download.php?$nonce&amp;img=" .
						$enc . "&amp;sz=$size&amp;c=" . $item['color_code'] . '" class="ims-download">' .
						esc_html( get_the_title( $id ) . " " . $item['color_name'] ) . "</a>";
				}
			}
		}
		
		if ( empty( $downlinks ) )
			return;
		
		$output = '<div class="imgs-downloads">';
		$output .= '<h4 class="title">' . __( 'Downloads', 'ims' ) . '</h4>';
		$output .= '<ul role="list" class="download-links">';
		foreach ( $downlinks as $link )
			$output .= "<li>$link</li>\n";
		$output .= "</ul>\n</div>";
		
		$this->download_links = $output;
	}
	
	/**
	 * Proces cart image 
	 *
	 * @param unit $id
	 * @param string $size
	 * @param unit $price
	 * @param string $finish
	 * @param string $color
	 * @param unit $quantity
	 * @return array
	 * @since 3.2.1
	 */
	function process_image( $id, $size, $price, $finish, $color, $quantity ){
		
		$values = array(
			'unit' => 'in',
			
			'finish' => 0,
			'finish_name' => false,
			
			'color' => 0,
			'subtotal' => 0,
			'color_code' => false,
			'color_name' => false,
			'download' => false,
			
			'price' => $price,
			'quantity' => $quantity,
			'gallery' => $this->gallery_id,
		);
		
		if( isset( $this->cart['images'][$id][$size][$color]['quantity'] )  )
			$values['quantity'] = $this->cart['images'][$id][$size][$color]['quantity'] + $quantity;
		
		if( isset( $this->listmeta['finishes'][$finish]['type'] ) ){
						
			if( $this->listmeta['finishes'][$finish]['type'] == 'percent' )
				$values['finish'] = ( $price * ( $this->listmeta['finishes'][$finish]['price'] / 100 ) );
				
			else $values['finish'] = $this->listmeta['finishes'][$finish]['price'];
		}
		
		global $ImStore;
			
		if( isset( $this->listmeta['finishes'][$finish]['name'] ) )
			$values['finish_name'] = $this->listmeta['finishes'][$finish]['name'];
		
		if( isset( $this->listmeta['colors'][$color]['price'] ) )
			 $values['color'] = $this->listmeta['colors'][$color]['price'];
			 
		if( isset( $this->listmeta['colors'][$color]['code'] ) )
			 $values['color_code'] = $this->listmeta['colors'][$color]['code'];
		
		if( isset( $this->listmeta['colors'][$color]['name'] ) )
			 $values['color_name'] = $this->listmeta['colors'][$color]['name'];
		
		if ( !empty( $this->sizes[$size]['download'] ) )
			$values['download'] = 1;
			
		if( $ImStore->opts['shipping'] && !$values['download'] ) 
			$this->cart['shippingcost'] = 1;
			
		$values['subtotal'] = ( ( $price + $values['color'] + $values['finish'] ) *  $values['quantity'] );
		return $values;
	}
	
	/**
	 * Marge an array into a primary array
	 *
	 * @param array $into
	 * @param array $merge
	 * @return array
	 * @since 3.3.0
	*/
	function merge_recursive( $into, $merge ){
		foreach( $merge as $key => $value ){
			if ( is_array ( $value ) && isset ( $into[$key] )  && is_array ( $into[$key] ) )
				$into[$key] = $this->merge_recursive( $into[$key], $value );
			else $into[$key] = $value;
		}
		return $into;
	}
	
	/**
	 * Handles registering a new user.
	 *
	 * @param string $user_login
	 * @param string $user_email
	 * @return int|WP_Error 
	 * @since 3.3.0
	*/
	function register_new_user( $user_login, $user_email ) {
		
		$errors = new WP_Error();
		$sanitized_user_login = sanitize_user( $user_login );
	
		// Check the username
		if ( $sanitized_user_login == '' ) {
			$errors->add( 'empty_username', __( '<strong>ERROR</strong>: Please enter a username.' ) );
		} elseif ( ! validate_username( $user_login ) ) {
			$errors->add( 'invalid_username', __( '<strong>ERROR</strong>: This username is invalid because it uses illegal characters. Please enter a valid username.' ) );
			$sanitized_user_login = '';
		} elseif ( username_exists( $sanitized_user_login ) ) {
			$errors->add( 'username_exists', __( '<strong>ERROR</strong>: This username is already registered. Please choose another one.' ) );
		}
	
		// Check the e-mail address
		if ( $user_email == '' ) {
			$errors->add( 'empty_email', __( '<strong>ERROR</strong>: Please type your e-mail address.' ) );
		} elseif ( ! is_email( $user_email ) ) {
			$errors->add( 'invalid_email', __( '<strong>ERROR</strong>: The email address isn&#8217;t correct.' ) );
			$user_email = '';
		} elseif ( email_exists( $user_email ) ) {
			$errors->add( 'email_exists', __( '<strong>ERROR</strong>: This email is already registered, please choose another one.' ) );
		}
		
		$errors = apply_filters( 'ims_registration_errors', $errors, $sanitized_user_login, $user_email );
	
		if ( $errors->get_error_code() )
			return $errors;
	
		$user_pass = wp_generate_password( 12, false);
		
		if ( ! $user_id = $this->wp_create_user( $sanitized_user_login, $user_pass, $user_email )) {
			$errors->add( 'registerfail', sprintf( __( '<strong>ERROR</strong>: Couldn&#8217;t register you&hellip; please contact the <a href="mailto:%s">webmaster</a> !' ), get_option( 'admin_email' ) ) );
			return $errors;
		}
	
		update_user_option( $user_id, 'default_password_nag', true, true );
		wp_new_user_notification( $user_id, $user_pass );
	
		return $user_id;
	}
	
	/**
	 * Crea user using image store user role
	 *
	 * @param string $username
	 * @param string $password
	 * @param string $email
	 * @return int|WP_Error
	 * @since 3.3.0
	 */
	function wp_create_user( $username, $password, $email = '' ){
		global $ImStore;
		
		 $user_pass = $password;
		 $user_email = esc_sql( $email );
		 $user_login = esc_sql( $username );
		 $role = esc_sql( $ImStore->customer_role );
		 
		 $userdata = compact( 'user_login', 'user_email', 'user_pass', 'role' );
		 return wp_insert_user( $userdata );
	}
	
	/**
	 * Validate receipt action forms
	 *
	 * @return array()
	 * @since 3.3.0
	 */
	function validate_access_forms(  ){
		
		if( empty( $_POST ) || is_user_logged_in())
			return array( );
		
		$user_id = false;
		$data = array( 
			'user_login' => $_POST['user_login'],
			'user_email' => $this->data['payer_email'],
		);
		
		if( isset( $_POST['ims-submit-register'] ) ){ // user register
		
			$data['active'] = 'register';
			$data['user_email'] = $_POST['user_email'];
			$user_id = $this->register_new_user( $data['user_login'] , $data['user_email']  );

		} else if( isset( $_POST['ims-submit-login'] ) ){ // user login
			
			$user_pass = $_POST['user_pass'];
			$user_id = wp_authenticate( $data['user_login'], $user_pass );
		}
				
		if ( is_wp_error( $user_id ) &&  $user_id->get_error_code( ) ) {
			
			$errors = ''; $message =  '<div class="ims-message ims-error">';
			 foreach ( $user_id->get_error_messages( ) as $error )
				$errors .= '    ' . $error . "<br />\n";
			$data['message'] = $message . apply_filters( 'ims_login_errors', $errors) . "</div>\n";
			
		} else if ( !is_wp_error( $user_id ) ) {
			
			$redirect = site_url( 'wp-login.php?checkemail=registered' );
			
			if( isset( $user_id->ID ) && !empty( $user_pass ) ){
				$redirect = site_url( 'wp-admin' );
				wp_set_auth_cookie( ($user_id = $user_id->ID), false, apply_filters( 'secure_signon_cookie', is_ssl( ), $data ) );
			}
			
			//save purchased images
			if( $user_images = get_user_meta( $user_id, "_ims_user_{$user_id}_images", true ) ){
				if( $images = $this->merge_recursive( $user_images, $this->cart['images'] ) )
					update_user_meta( $user_id, "_ims_user_{$user_id}_images", $images );
			}else update_user_meta( $user_id, "_ims_user_{$user_id}_images", $this->cart['images'] );
				
			setcookie( 'ims_orderid_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );
			wp_safe_redirect( apply_filters( 'ims_register_redirect',  $redirect ) );
			exit( );
		} 
		return $data;
	}
	
	/**
	 * Get shipping option html markup
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function shipping_options( ) {
		
		global $ImStore;
		if ( !$options = $ImStore->get_option( 'ims_shipping_options' ) )
			return;
		
		$select = '<select name="shipping" id="shipping" class="shipping-opt">';
		foreach ( $options as $key => $val )
			$select .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $this->cart['shipping_type'], false ) . '>' .
			esc_attr( $val['name'] ) . ' + ' . $ImStore->format_price( $val['price'] ) . '</option>';
		$select .= '</select>';
		
		return $select;
	}
	
	/**
	 * Get cart buttons ( actions ) 
	 *
	 * @param string $output
	 * @return string
	 * @since 3.2.1
	 */
	function cart_actions( $output ){
		
		global $ImStore;
		
		if( $ImStore->opts['gateway']['custom'] && !empty( $ImStore->opts['data_pair'] ) ){
		
			add_filter( 'ims_cart_hidden_fields', array( &$this, 'cart_hidden_fields' ), 50 );
			
			$output .= '<input name="ims-custom" type="submit" value="' . esc_attr( $ImStore->opts['gateway_name'] ) . 
			'" class="primary ims-custom" data-submit-url="' . esc_attr( urlencode( $ImStore->opts['gateway_url'] ) ) . '" /> ';
		}
			
		 if( $ImStore->opts['gateway']['enotification'] )
		 	$output .= '<input name="ims-enotification" type="submit" value="' . esc_attr( $ImStore->gateways['enotification']['name'] ) . 
			'" class="primary ims-enotification" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['enotification']['url'] ) ) . '" /> ';
			
		return $output;
	}
	
	/**
	 * Add cart hidden fields
	 *
	 * @param string $output
	 * @return string
	 * @since 3.2.1
	 */
	function cart_hidden_fields( $output ){ 
		
		global $ImStore;
		$data_pair = array( );
		
		$cart_replace = array(
			__('%cart_id%', 'ims') => $this->orderid,
			__('%cart_tax%', 'ims') => $this->cart['tax'],
			__('%cart_total%', 'ims') => $this->cart['total'],
			__('%cart_shipping%', 'ims') => $this->cart['shipping'],
			__('%cart_currency%', 'ims') => $ImStore->opts['currency'],
			__('%cart_subtotal%', 'ims') => $this->cart['subtotal'],
			__('%cart_status%', 'ims') => get_post_status( $this->orderid ),
			__('%cart_discount%', 'ims') => $this->cart['promo']['discount'],
			__('%cart_discount_code%', 'ims') => $this->cart['promo']['code'],
			__('%cart_total_items%', 'ims') => $this->cart['items'],
		);
		
		foreach ( explode( ',', $ImStore->opts['data_pair'] ) as $input ) {
			
			$vals = explode( '|', $input );
			
			if ( isset( $vals[1] ) )
				$data_pair[ trim( $vals[0] ) ] = trim( $vals[1] );
		};
		
		foreach ( $data_pair as $key => $sub) {
			if ( isset( $cart_replace[$sub] ) )
				$output .= "\n" . '<input type="hidden" name="' . $key . '" data-value-ims="' . esc_attr( $cart_replace[$sub] ) . '" />';
				
			elseif ( !preg_match('/%image_/', $sub ) )
				$output .= "\n" . '<input type="hidden" name="' . $key . '" data-value-ims="' . esc_attr( $sub ) . '" />';
		}
		
		return $output = apply_filters( 'ims_cart_custom_hidden_fields', $output, $this->cart );
	}
	
	/**
	 * Get image size price
	 *
	 * @param string $size
	 * @return string | bool
	 * @since 3.2.1
	 */
	function get_image_size_price( $size ){
		
		global $ImStore;
		
		if ( isset( $ImStore->sizes[$size]['ID'] ) )
			return get_post_meta( $ImStore->sizes[$size]['ID'], '_ims_price', true );
			
		else if( isset( $ImStore->sizes[$size]['price'] ) )
		 return str_replace( $ImStore->sym, '', $ImStore->sizes[$size]['price'] );
		 
		 return false;
	}
	
	/**
	 * Get image cart row
	 *
	 * @param unit $imageid
	 * @param array $sizes
	 * @param unit $row
	 * @return string
	 * @since 3.2.1
	 */
	function image_row( $imageid, $sizes, &$row ){
		
		global $ImStore;
		
		$ImStore->is_widget = true;
		$enc = $ImStore->url_encrypt( $imageid );
		$imgtitle = ( $title = get_the_title( $imageid ) ) ? $title : $enc;
		$meta = (array) get_post_meta( $imageid, '_wp_attachment_metadata', true );
		
		if( isset( $meta['sizes']['mini'] ) && is_array( $meta['sizes']['mini'] ) )
			$meta += array( 'link' => $ImStore->get_image_url( $imageid ), 'alt' => $imgtitle, 'title' => $imgtitle, 'caption' => $imgtitle );
		
		$output = '<tr role="row"> <td role="gridcell" class="ims-preview">'; //start row
		$output .= $ImStore->image_tag( $imageid, $meta, 3 );
		$output .= '</td>';
		
		$output .= '<td role="gridcell" class="ims-subrows" colspan="2">';
		
		foreach ( $sizes as $size => $colors ) :
			foreach ( $colors as $color => $item ) :
			
			$colorname = ( $item['color_name'] ) ? trim( $item['color_name'], " + ") : false;
			
			$output .= '<div class="ims-clear-row">';
			$output .= apply_filters( 'ims_cart_image_before_list_row', '', $imageid, $item, $color, $enc, $row, $title, $size );
			$output .= 
			'<span class="ims-quantity">
				<input type="text" name="ims-quantity' . "[$enc][$size][$color]" . '" value="' . esc_attr( $item['quantity'] ).'" class="input" />
			</span>';
			
			$output .= '<span class="ims-size">' . esc_html( $item['size'] ) . ' <span class="ims-unit">' . esc_html( $item['unit'] ) . '</span></span>';
			$output .= '<span class="ims-color">' . esc_html( $item['color_name'] ) . ' ' . $ImStore->format_price( $item['color'] ) . '</span>';
			$output .= '<span class="ims-fisnish">' . esc_html( $item['finish_name'] ) . ' ' . $ImStore->format_price( $item['finish'] )  . '</span>';
			$output .= '<span class="ims-price">' . $ImStore->format_price( $item['price'] )  . '</span>';
			$output .= '<span class="ims-subtotal">' . $ImStore->format_price( $item['subtotal'] ) . '</span>';
			$output .= apply_filters( 'ims_cart_image_list_column', '', $imageid, $item, $color, $enc, $row, $title, $size );
			$output .= '<span class="ims-delete"><input name="ims-remove[]" type="checkbox" value="' . esc_attr( "{$enc}|{$size}|{$color}" ) . '" /></span>';
			
			$output .= apply_filters( 'ims_cart_item_hidden_fields', '', $imageid, $item, $color, $enc, $row, $title, $size );
			$output .= '</div><!--.ims-clear-row-->';
			
			$row++;
			endforeach;
		endforeach;
		
		$output .= '</td></tr>'; //end row
		$output .= apply_filters( 'ims_cart_image_list_row', '', $imageid, $item, $color, $enc, $row, $title, $size );
		
		return $output;
	}
	
	/**
	 * Validate promotion code
	 *
	 * @param string $code
	 * @return bool
	 * @since 3.2.1
	 */
	function validate_code( $code = false ) {
		
		if ( !$code && empty( $this->cart['promo']['code'] ) )
			return false;
		
		if( $code ) $this->cart['promo']['code'] = $code;
				
		global $wpdb;
		$promo_id = $wpdb->get_var( $wpdb->prepare (
			"SELECT ID FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE meta_key = '_ims_promo_code'
			AND meta_value = BINARY '%s'
			AND post_status = 'publish'
			AND post_date <= '" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "'
			AND post_expire >= '" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "' "
		, $this->cart['promo']['code'] ) );
		
		if ( empty( $promo_id ) ) {
			$this->error = __( "Invalid promotion code", 'ims' );
			return false;
		}
		
		//check for code limit	
		$data = get_post_meta( $promo_id, '_ims_promo_data', true );
		
		If( !empty( $data['promo_limit'] ) && $data['promo_limit'] <= get_post_meta( $promo_id, '_ims_promo_count', true ) ){
			$this->error = __( "Invalid promotion code", 'ims' );
			return false;
		}
		
		$this->cart['promo']['promo_type'] = $data['promo_type'];
	
		if( isset( $data['discount'] ) ) 
			$this->cart['promo']['discount'] = $data['discount'];
		
		$this->cart['promo']['promo_id'] = $promo_id;
		
		//set promotional cart values
		switch ( $data['rules']['logic'] ) {
			case 'equal':
				if ( $this->cart[ $data['rules']['property'] ] == $data['rules']['value'] )
					return true;
				break;
			case 'more':
				if ( $this->cart[ $data['rules']['property'] ] > $data['rules']['value'] )
					return true;
				break;
			case 'less':
				if ( $this->cart[ $data['rules']['property'] ] < $data['rules']['value'] )
					return true;
				break;
		}
		
		$this->cart['promo'] = array( 'discount' => false, 'promo_id' => false, 'code' => false );
		$this->error = __("Your current purchase doesn't meet the promotion requirements.", 'ims');
		return false;
	}
	
}