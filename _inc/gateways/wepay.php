<?php

/**
 * Image Store - WePay
 *
 * @file wepay.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/gateways/wepay.php
 * @since 3.2.1
 */
 
class ImStoreCartWePay {
		
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreCartWePay( ) {
		add_filter( 'ims_store_cart_actions', array( &$this, 'cart_actions' ), 40, 1 );
		add_action( 'ims_before_post_actions', array( &$this, 'process_notice' ), 30, 12 );
	}
	
	/**
	 * Process wepay notice request
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function process_notice( ){
		
		if( empty( $_REQUEST['checkout_id'] ) )
			return;
			
		global $ImStore;
		if( !is_numeric( $_REQUEST['checkout_id'] ) || empty( $ImStore->opts['wepayclientid'] ) 
		|| empty( $ImStore->opts['wepayclientsecret'] )  || empty( $ImStore->opts['wepayaccesstoken'] ) )
			return;
		
		$checkout_id = intval( trim( $_REQUEST['checkout_id'] ) );
		include_once( IMSTORE_ABSPATH . '/_inc/gateways/wepaysdk.php' );
		
		$wepay = new WePay(
		  $ImStore->opts['wepayaccesstoken'],
		  $ImStore->opts['wepayclientid'],
		  $ImStore->opts['wepayclientsecret'],
		  (( $ImStore->opts['gateway']['wepayprod'] ) ? true : false )
		);
		
		$checkout = $wepay->request( 'checkout', array( 'checkout_id' => $checkout_id ) );
				
		if( empty($checkout) || empty( $checkout->reference_id ) )
			return ;
		
		$response_data  = array( 
			'payer_name' => 'first_name' , 
			'amount' => 'mc_gross', 
			'gross' => 'payment_gross', 
			'checkout_id' => 'txn_id', 
			'currency' => 'mc_currency',
			'state' => 'payment_status',
			'payer_email' => 'payer_email',
			'shipping_address' => array(
				'city' => 'address_city',
				'zip' => 'address_zip',
				'state' => 'address_state',
				'address1' => 'address_street',
				'address2' => 'address_street',
				'country' => 'address_country',
			),
		); 
		
		$cartid = $checkout->reference_id;
		
		global $ImStoreCart;
		$ImStoreCart->setup_cart( $cartid );
		
		do_action( 'ims_before_wepay_notice', false, $cartid );
		
		foreach( $response_data as $key => $reponse ){
			if( isset( $checkout->$key ) ){
				if( is_array( $reponse ) ){
					foreach( $reponse as $addres => $value ){
						if( isset( $checkout->$key->$addres ) )
							$ImStoreCart->data[ $value ] .= $checkout->$key->$addres;
					}
				} else $ImStoreCart->data[ $reponse ] = $checkout->$key;
			}
		} 
		
		$ImStoreCart->data['method'] =  'WePay';
		$ImStoreCart->data['num_cart_items'] = $ImStoreCart->cart['items'];
		
		$ImStoreCart->checkout( );
		do_action( 'ims_after_wepay_notice', $cartid, $ImStoreCart->data );
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
		
		 if( $ImStore->opts['gateway']['wepaystage'] ){
			$this->button_request( 'wepaystage' );
		 	$output .= '<input name="ims-wepaystage" type="submit" value="' . esc_attr( $ImStore->gateways['wepaystage']['name'] ) . 
			'" class="primary ims-wepaystage" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['wepaystage']['url'] ) ) . '" /> ';
		}
		
		if( $ImStore->opts['gateway']['wepayprod'] ){
			$this->button_request( 'wepayprod' );
		 	$output .= '<input name="ims-wepayprod" type="submit" value="' . esc_attr( $ImStore->gateways['wepayprod']['name'] ) . 
			'" class="primary ims-wepayprod" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['wepayprod']['url'] ) ) . '" /> ';
		}
		
		return $output;
	}

	/**
	 * Request a button from WePay
	 * using WePay API
	 *
	 * @param string $env
	 * @return void
	 * @since 3.2.1
	 */
	function button_request( $env ){
	
		global $ImStore, $ImStoreCart;
		include_once( IMSTORE_ABSPATH . '/_inc/gateways/wepaysdk.php' );

		$data = array(
			'type' => 'GOODS', 
			'amount' => $ImStoreCart->cart['total'],
			'reference_id' => $ImStoreCart->orderid,
			'short_description' => __("Image Purchase"),
			'account_id' => $ImStore->opts['wepayaccountid'],
			'redirect_uri' => $ImStore->get_permalink( 'receipt' ),
			'callback_uri' =>$ImStore->get_permalink( $ImStore->imspage ),
		);
		
		if ( $ImStoreCart->cart['shippingcost']) 
			$data['require_shipping' ] = true;
		
		$wepay = new WePay(
			$ImStore->opts['wepayaccesstoken'],
			$ImStore->opts['wepayclientid'],
			$ImStore->opts['wepayclientsecret'],
			(( $env == 'wepayprod' ) ? true : false )
		);

		try{ $checkout = $wepay->request( 'checkout/create', $data );
		}catch( WePayException $e ){ }
			
		if( !empty( $checkout->checkout_uri ) ) 
			$ImStore->gateways[$env]['url'] = $checkout->checkout_uri;
	}
}