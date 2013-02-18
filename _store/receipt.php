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
	
	//normalize nonce field
	wp_set_current_user( 0 );
	 
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
	
	//remove cookie
	setcookie( 'ims_orderid_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );