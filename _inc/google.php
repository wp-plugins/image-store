<?php

/**
 * Image Store - Google Checkout / Pay 
 *
 * @file google.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/google.php
 * @since 3.2.1
 */
 
class ImStoreCartGoogle {
	
	public $row = 0;
	public $downlinks = false;
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreCartGoogle( ) {
		add_filter( 'ims_store_cart_actions', array( &$this, 'cart_actions' ), 30, 1 );
		add_filter( 'ims_cart_hidden_fields', array( &$this, 'cart_hidden_fields' ), 30, 2 );
		add_filter( 'ims_cart_item_hidden_fields', array( &$this, 'item_hidden_fields' ), 30, 8 );
	}
	
	/**
	 * Process google notice call
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function process_notice(  ){
		
		global $ImStore;
		
		if( empty( $ImStore->opts['googleid'] ) || empty( $ImStore->opts['googlekey'] ) ||
		$_POST['_type'] != 'new-order-notification' || !is_numeric( $_POST['shopping-cart_merchant-private-data'] ) )
			return;
		
		$cartid = intval( trim( $_POST['shopping-cart_merchant-private-data'] ) );
		
		global $ImStoreCart;
		if( !class_exists( 'ImStoreCart' ) ){
			include_once( IMSTORE_ABSPATH . '/_inc/cart.php' );
			$ImStoreCart = new ImStoreCart( );
		}
		
		$ImStoreCart->setup_cart( $cartid );
		do_action( 'ims_before_google_notice', false, $cartid );
		
		$response_data  = array( 
			'order-total' => 'payment_gross', 
			'google-order-number' => 'txn_id', 
			'financial-order-state' => 'mc_currency', 
			'buyer-billing-address_email' => 'payer_email',
			'buyer-shipping-address_city' => 'address_city',
			'buyer-shipping-address_phone' => 'ims_phone',
			'buyer-shipping-address_region' => 'address_state',
			'buyer-shipping-address_address1' => 'address_street',
			'buyer-shipping-address_postal-code' => 'address_zip',
			'buyer-billing-address_contact-name' => 'first_name',
			'buyer-shipping-address_country-code' => 'address_country',
		); 
		
		foreach( $response_data as $key => $reponse ){
			if( isset( $_POST[ $key ] ) )
				$ImStoreCart->data[ $reponse ] =  $_POST[$key];
		} 
			
		$ImStoreCart->data['method'] =  'Google Checkout';
		$ImStoreCart->data['num_cart_items'] = $ImStoreCart->cart['items'];
		$ImStoreCart->data['mc_gross'] = $ImStoreCart->data['payment_gross'];

		$ImStoreCart->checkout( );
		do_action( 'ims_after_google_notice', $cartid, $ImStoreCart->data );
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
		
		 if( $ImStore->opts['gateway']['googlesand'] )
		 	$output .= '<input name="ims-googlesand" type="submit" value="' . esc_attr( $ImStore->gateways['googlesand']['name'] ) . 
			'" class="primary ims-googlesand" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['googlesand']['url'] ) ) . '" /> ';
		
		if( $ImStore->opts['gateway']['googleprod'] )
		 	$output .= '<input name="ims-googleprod" type="submit" value="' . esc_attr( $ImStore->gateways['googleprod']['name'] ) . 
			'" class="primary ims-googleprod" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['googleprod']['url'] ) ) . '" /> ';
		
		return $output;
	}
	
	/**
	 * Add google varables to cart
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
	function item_hidden_fields( $output, $id, $item, $color, $enc, $row =0, $title = '', $size = '' ){
		
		global $ImStore;
		$this->row = $row;
		
		$output .= '<input type="hidden" name="item_merchant_id_' . $row . '" data-value-ims="' . esc_attr( $enc ) . '" />';
		$output .= '<input type="hidden" name="item_quantity_' . $row . '" data-value-ims="' . esc_attr( $item['quantity'] ) . '" />';
		$output .= '<input type="hidden" name="item_name_' . $row . '" data-value-ims="' . esc_attr( $title ) . '" />';
		$output .= '<input type="hidden" name="item_currency_' . $row. '" data-value-ims="' . esc_attr( $ImStore->opts['currency'] ) . '" />';
		$output .= '<input type="hidden" name="item_description_' . $row . '" data-value-ims="' . esc_attr( "$size " . $item['unit'] . ' ' . $item['color_name'] ) . '" />';
		$output .= '<input type="hidden" name="item_price_' . $row . '" data-value-ims="' . esc_attr( $ImStore->format_price( $item['price'] + $item['color'] + $item['finish'], false ) ) . '"/>';
		
		return $output;
	}
	
	/**
	 * Add additional variable to process
	 * google checkout cart
	 *
	 * @param string $output
	 * @param array $cart
	 * @return string
	 * @since 3.2.1
	 */
	function cart_hidden_fields( $output, $cart ){
		
		global $ImStore;
		$this->row++;
		
		$output .= 
		'<input type="hidden" name="edit-cart-url"  data-value-ims="' . esc_attr( $ImStore->get_permalink( ) ) . '" />
		<input type="hidden" name="tax_country"  data-value-ims="' . esc_attr( $ImStore->opts['taxcountry'] )  . '" />
		<input type="hidden" name="tax_rate"  data-value-ims="' . esc_attr( $ImStore->opts['taxamount'] / 100 ) . '" />
		<input type="hidden" name="shopping-cart.merchant-private-data"  data-value-ims="' . esc_attr( $ImStore->orderid ) . '" />';
		
		$output .=
		 '<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.edit-cart-url"  data-value-ims="' . esc_attr( $ImStore->get_permalink( $ImStore->imspage ) ) . '" />
		<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.continue-shopping-url"  data-value-ims="' . esc_attr( $ImStore->get_permalink( 'receipt' ) ) . '" />
		<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.shipping-taxed" data-value-ims="true"/>';
		
		if ( $this->downlinks )
			$output .= '<input type="hidden" name="shopping-cart.items.item-1.digital-content.description"
			 data-value-ims="' . "&lt;p&gt;" . esc_attr__( "downloads:", 'ims' ) . "&lt;/p&gt; {$this->downlinks}" . '" />';
			 
		if ( $cart['shippingcost'] ) {
			$output .= 
			'<input type="hidden" name="ship_method_name_1"  data-value-ims="' . esc_attr__( "shipping", 'ims' ) . '" />
			<input type="hidden" name="ship_method_price_1"  data-value-ims="' . esc_attr( $cart['shipping'] ) . '" />
			<input type="hidden" name="ship_method_currency_1"  data-value-ims="' . esc_attr( $ImStore->opts['currency'] ) . '" />';
		}
		
		if ( $cart['promo']['discount'] ) {
			$output .=
			 '<input type="hidden" name="item_quantity_' . $this->row . '" data-value-ims="1" />
			<input type="hidden" name="item_name_' . $this->row . '"  data-value-ims="' . esc_attr__( "discount", 'ims' ) . '" />
			<input type="hidden" name="item_currency_' . $this->row . '"  data-value-ims="' . esc_attr( $ImStore->opts['currency'] ) . '" />
			<input type="hidden" name="item_merchant_id_' . $this->row . '"  data-value-ims="' . esc_attr( $cart['promo']['code'] ) . '" />
			<input type="hidden" name="item_price_' . $this->row. '"  data-value-ims="' . "-" . esc_attr( $cart['promo']['discount'] ) . '" />
			<input type="hidden" name="item_description_' . $this->row . '"  data-value-ims="' . esc_attr__( "promotion code", 'ims' ) . '" />';
		}
		
		return $output = apply_filters( 'ims_cart_google_hidden_fields', $output, $cart );
	}
}