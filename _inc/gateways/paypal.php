<?php

/**
 * Image Store - Paypal 
 *
 * @file paypal.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/gateways/paypal.php
 * @since 3.2.1
 */
 
class ImStoreCartPayPal {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreCartPayPal( ) {
		add_filter( 'ims_store_cart_actions', array( &$this, 'cart_actions' ), 20, 1 );
		add_filter( 'ims_cart_hidden_fields', array( &$this, 'cart_hidden_fields' ), 20, 2 );
		add_filter( 'ims_cart_item_hidden_fields', array( &$this, 'item_hidden_fields' ), 20, 8 );
		add_action( 'ims_after_post_actions', array( &$this, 'process_notice' ), 30, 11 );
	}
	
	/**
	 * Process PayPal IPN call
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function process_notice( ){
		
		if ( empty( $_POST['txn_id'] ) || empty( $_POST['custom'] ) || !is_numeric( $_POST['custom'] ) )
			return;
		
		global $ImStore;
		if( empty( $ImStore->opts['paypalname'] ))
			return;
			
		$postdata = '';
		$info = array( );
		
		if( $ImStore->opts['gateway']['paypalsand'] )
			$url =  $ImStore->gateways['paypalsand']['url'] ;
		else  $url = $ImStore->gateways['paypalprod']['url'] ;
				
		foreach ( $_POST as $i => $v )
			$postdata .= $i . '=' . urlencode( $v ) . '&';
		$postdata .= 'cmd=_notify-validate';
		
		$web = parse_url( $url );
		if ( $web['scheme'] == 'https' ||
			strpos( $url, 'sandbox') !== false ) {
			$web['port'] = 443;
			$ssl = 'ssl://';
		} else {
			$web['port'] = 80;
			$ssl = '';
		}
		
		if( !$fp = fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30) )
			return;
		
		fputs($fp, "POST " . $web['path'] . " HTTP/1.1\r\n");
		fputs($fp, "Host: " . $web['host'] . "\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: " . strlen($postdata) . "\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $postdata . "\r\n\r\n");

		while ( !feof( $fp ) )
			$info[] = @fgets( $fp, 1024 );
		fclose( $fp );
		
		$info = implode( ',', $info );
		
		if ( !preg_match( '/(VERIFIED)/', $info ) ) {
			
			$file = IMSTORE_ABSPATH . "/ipn_log.txt";
			$log = array( 'REQUEST_TIME', 'REMOTE_ADDR', 'REQUEST_METHOD', 'HTTP_USER_AGENT', 'REMOTE_PORT' );
			
			foreach ( $log as $key )
				$postdata .= $key . '=' . $_SERVER[$key] . ',';
			$postdata .= "\n$url\n_________________\n";
			
			if( $hd = fopen( $file, 'a' ) ){
				fwrite( $hd, $web['host'] . "," . $postdata );
				fclose( $hd );
			}
			
			return;
			
		} else {
			
			$cartid = intval( trim( $_POST['custom'] ) );
			
			global $ImStoreCart;
			$ImStoreCart->setup_cart( $cartid );
			
			do_action( 'ims_before_paypal_ipn', false, $cartid );
			
			$ImStoreCart->data = wp_parse_args( $_POST, $ImStoreCart->data );
			
			$ImStoreCart->data['method'] =  'PayPal';
			$ImStoreCart->data['num_cart_items'] = $ImStoreCart->cart['items'];
			$ImStoreCart->data['instructions'] = $ImStoreCart->cart['instructions'];
			
			$ImStoreCart->checkout( );
			do_action( 'ims_after_paypal_ipn', $cartid, $ImStoreCart->data );
		}
	}
	
	/**
	 * Add button (actions) to cart
	 *
	 * @param string $output
	 * @return string
	 * @since 3.2.1
	 */
	function cart_actions( $output ){
		
		global $ImStore;
		
		 if( $ImStore->opts['gateway']['paypalsand'] )
		 	$output .= '<input name="ims-paypalsand" type="submit" value="' . esc_attr( $ImStore->gateways['paypalsand']['name'] ) . 
			'" class="primary ims-paypalsand" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['paypalsand']['url'] ) ) . '" /> ';
		
		if( $ImStore->opts['gateway']['paypalprod'] )
		 	$output .= '<input name="ims-paypalprod" type="submit" value="' . esc_attr( $ImStore->gateways['paypalprod']['name'] ) . 
			'" class="primary ims-paypalprod" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['paypalprod']['url'] ) ) . '" /> ';
		
		return $output;
	}
	
	/**
	 * Add PayPal varables to cart
	 * via hidden fields
	 *
	 * @param string $output
	 * @param unit $id
	 * @param array $item
	 * @param string $color
	 * @param string $enc
	 * @param unit $row
	 * @param string $title
	 * @param string $size
	 * @return string
	 * @since 3.2.1
	 */
	function item_hidden_fields( $output, $id, $item, $color, $enc, $row = 0, $title = '', $size = '' ){
		
		global $ImStore;
		
		if( $item['color_name'] )
			$output .= '<input type="hidden" name="os0_' . $row . '" data-value-ims="' . esc_attr ( $item['color_name'] ). '"/>';
		
		$output .= '<input type="hidden" name="on0_' . $row . '" data-value-ims="' . esc_attr( "$size " . $item['unit'] ) . '"/>';
		$output .= '<input type="hidden" name="item_number_' . $row . '" data-value-ims="' . esc_attr( $enc ) . '"/>';
		$output .= '<input type="hidden" name="quantity_' . $row . '" data-value-ims="' . esc_attr( $item['quantity'] ) . '"/>';
		$output .= '<input type="hidden" name="item_name_' . $row . '" data-value-ims="' . esc_attr( $title ) . '"/>';
		$output .= '<input type="hidden" name="amount_' . $row . '" data-value-ims="' . 
		esc_attr( $ImStore->format_price( $item['price'] + $item['color'] + $item['finish'], false ) ) . '" />';
		
		return $output;
	}
	
	/**
	 * Add additional variable to process
	 * PayPal cart
	 *
	 * @param string $output
	 * @param array $cart
	 * @return string
	 * @since 3.2.1
	 */
	function cart_hidden_fields( $output, $cart ){
		
		global $ImStore, $ImStoreCart;
		
		$output .= '
		<input type="hidden" readonly="readonly" name="rm" data-value-ims="2" />
		<input type="hidden" name="upload" data-value-ims="1" />
		<input type="hidden" name="cmd" data-value-ims="_cart" />
		<input type="hidden" name="lc" data-value-ims="' . esc_attr( get_bloginfo( 'language' ) ) . '" />
		<input type="hidden" name="shipping_1" data-value-ims="' . esc_attr( $cart['shipping'] ) . '" />
		<input type="hidden" name="page_style" data-value-ims="' . get_bloginfo( 'name' ) . '" />
		<input type="hidden" name="custom" data-value-ims="' . esc_attr( $ImStoreCart->orderid  ) . '" />
		<input type="hidden" name="return" data-value-ims="' . $ImStore->get_permalink( 'receipt' ) . '" />
		<input type="hidden" name="business" data-value-ims="' . esc_attr( $ImStore->opts['paypalname'] ) . '" />
		<input type="hidden" name="currency_code" data-value-ims="' . esc_attr( $ImStore->opts['currency'] ) . '" />
		<input type="hidden" name="notify_url" data-value-ims="' . $ImStore->get_permalink( $ImStore->imspage ) . '" />
		<input type="hidden" name="discount_amount_cart" data-value-ims="' . esc_attr( $cart['promo']['discount'] )  . '" />
		<input type="hidden" name="cancel_return" data-value-ims="' . $ImStore->get_permalink( $ImStore->imspage ) . '" />
		<input type="hidden" name="cbt" data-value-ims="' . esc_attr( sprintf( __( 'Return to %s', 'ims' ), get_bloginfo( 'name' ) ) ) . '" />';
		
		return $output = apply_filters( 'ims_cart_paypal_hidden_fields', $output, $cart );
	}
	
	
}