<?php

	/**
	 * Image Store - Thank You / Receipt Page
	 *
	 * @file cart.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/_store/receipt.php
	 * @since 0.5.0
	 */

	// Stop direct access of the file
	if ( !defined( 'ABSPATH' ) )
		die( );
	
	$user = wp_get_current_user( );
	global $ImStoreCart;
	
	
	//redirect empty data
	if( empty( $ImStoreCart->orderid ) || empty( $ImStoreCart->data ) ){
		wp_redirect( get_permalink( ) );
		die( );
	}
		
	if( empty( $ImStoreCart->substitutions ) )
		$ImStoreCart->get_download_links( );
	
	$output .= '<div class="ims-innerbox">
	 <div class="thank-you-message">' .
		(  make_clickable( wpautop( stripslashes( preg_replace( $this->opts['tags'], $ImStoreCart->substitutions, $this->opts['thankyoureceipt'] )) ) ) )
		 . '</div>
	</div>';
		
	$ImStoreCart->get_download_links( );
	$output .= $ImStoreCart->download_links;
	$output .= '<div class="cl"></div>';
	
	$output  .= '<a href="' . $this->get_permalink( 'photos' ) . '" class="button return-shopping" rel="prev">' . __( 'Continue Shopping' , 'ims') . '</a>';
	 
	// Display registration form 
	if( ! is_user_logged_in( ) && $this->opts['loginform'] ){
				
		$active = 'login';
		$user_login = $message = '';
		$user_email = $ImStoreCart->data['payer_email'];
		
		if( isset ( $_POST['ims-submit-login'] ) || isset ( $_POST['ims-submit-register'] ) )	
			extract( $ImStoreCart->validate_access_forms( )) ;
		
		$output .= apply_filters( 'ims_access_forms',  
		'<p class="ims-regis-info">' . __( 'Log in or register for easy access to your purchased images.', 'ims' ) . '</p>' . $message .
		'<ul class="ims-login-tags" data-active="' . $active . '">
		<li class="form-tab-login">' . __( 'Log in', 'ims' ) . '</li><li class="form-tab-register">' . __( 'Register', 'ims' ) . '</li></ul>' .
		'<form class="ims-form" name="ims-login-form" id="ims-login-form" action="' . esc_url( $this->get_permalink( 'receipt' ) . '#ims-login-form' ) . '" method="post">
			<label for="user_login">' . __( 'Username', 'ims' ) . '
			<input type="text" name="user_login" id="user_login" class="input" value="' . esc_attr( $user_login ) . '" size="20" /></label>
			<label for="user_pass">' . __( 'Password', 'ims' ) . '
			<input type="password" name="user_pass" id="user_pass" class="input" value="" size="20" /></label>
			<p class="submit"><input type="submit" name="ims-submit-login" class="button button-primary" value="' . esc_attr__( 'Log in' ) . '" /></p>			
		</form>'.
		'<form class="ims-form" name="ims-register-form" id="ims-register-form" action="' . esc_url( $this->get_permalink( 'receipt' ) . '#ims-register-form' ) . '" method="post">
			<div>
				<label for="user_login">' . __( 'Username', 'ims' ) . '
				<input type="text" name="user_login" id="user_login" class="input" value="'. esc_attr( $user_login ) . '" size="20" /></label>
				<label for="user_email">' . __( 'E-mail', 'ims' ) . '
				<input type="text" name="user_email" id="user_email" class="input" value="' . esc_attr( $user_email ) . '" size="25" /></label><br />
				<span class="reg-passmail">' . __( 'A password will be e-mailed to you.' ) . '</span>
			</div>
			<p class="submit"><input type="submit" name="ims-submit-register" class="button button-primary" value="' . esc_attr__( 'Register' ) . '" />	</p>		
		</form>', $user_login, $user_email, $message );
		

	} else {
		
		//save purchased images
		if( $user_images = get_user_meta( $user->ID, "_ims_user_{$user->ID}_images", true ) ){
			if( $images = $ImStoreCart->merge_recursive( $user_images, $ImStoreCart->cart['images'] ) )
				update_user_meta( $user->ID, "_ims_user_{$user->ID}_images", $images );
		}else update_user_meta( $user->ID, "_ims_user_{$user->ID}_images", $ImStoreCart->cart['images'] );
		
		setcookie( 'ims_orderid_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );
	}
	
