<?php

/**
 * Image Store - Pago Seguro 
 *
 * @file pagseguro.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/gateways/pagseguro.php
 * @since 3.2.5
 */
 
class ImStoreCartPagSeguro {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.5
	 */
	function ImStoreCartPagSeguro( ) {
		add_filter( 'ims_store_cart_actions', array( &$this, 'cart_actions' ), 50, 1 );
		add_filter( 'ims_cart_hidden_fields', array( &$this, 'cart_hidden_fields' ), 20, 2 );
		add_filter( 'ims_cart_item_hidden_fields', array( &$this, 'item_hidden_fields' ), 20, 8 );
	}
	
	/**
	 * Process IPN call
	 *
	 * @return void
	 * @since 3.2.5
	 */
	function process_notice( ){
	
		if ( empty( $_POST['TransacaoID'] ) 
		|| empty( $_POST['Reference'] )
		|| empty( $_POST['VendedorEmail'] ) )
			return;
		
		global $ImStore;
		
		$postdata = '';
		foreach ( $_POST as $key => $value ) 
			$postdata .= $key . '=' . urlencode( $value ) . '&';
		$postdata .= 'Comando=validar&Token='.$ImStore->opts['pagsegurotoken'];
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://pagseguro.uol.com.br/pagseguro-ws/checkout/NPI.jhtml");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30 );
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = trim(curl_exec($curl));
		curl_close($curl);
		
		if ( $result != "VERIFICADO" ){
			
			$file = IMSTORE_ABSPATH . "/pagseguro_log.txt";
			$log = array( 'REQUEST_TIME', 'REMOTE_ADDR', 'REQUEST_METHOD', 'HTTP_USER_AGENT', 'REMOTE_PORT' );
			
			foreach ( $log as $key )
				$postdata .= $key . '=' . $_SERVER[$key] . ',';
			$postdata .= "\n_________________\n";
			
			$hd = fopen( $file, 'a' );
			fwrite( $hd, $postdata );
			fclose( $hd );
			
			return;
			
		}else{
			
			$cartid = trim( $ImStore->url_decrypt($_POST['Reference'] ) );
			
			if( ! is_numeric( $cartid ) )
				return;
			
			global $ImStoreCart;
			$ImStoreCart->setup_cart( $cartid );
			
			do_action( 'ims_before_pagseguro_ipn', false, $cartid );
			
			$response_data  = array( 
				'ValorFrete' => 'payment_gross', 
				'TransacaoID' => 'txn_id', 
				'Annotation' => 'instructions',
				'StatusTransacao' => 'payment_status', 
				'CliEmail' => 'payer_email',
				'CliCidade' => 'address_city',
				'CliTelefone' => 'ims_phone',
				'CliEstado' => 'address_state',
				'CliComplemento' => 'address_street',
				'CliCEP' => 'address_zip',
				'CliNome' => 'first_name',
				'Parcelas' => 'num_cart_items',
				'buyer-shipping-address_country-code' => 'address_country',
			); 
			
			foreach( $response_data as $key => $reponse ){
				if( isset( $_POST[ $key ] ) )
					$ImStoreCart->data[ $reponse ] =  $_POST[$key];
			} 
			
			$ImStoreCart->data['method'] =  'PagSeguro';
			$ImStoreCart->data['mc_currency'] = $ImStore->opts['currency'];
			$ImStoreCart->data['instructions'] = $ImStoreCart->cart['instructions'];
			$ImStoreCart->data['user_email'] = $ImStoreCart->data['payer_email'];
			$ImStoreCart->data['mc_gross'] = $ImStoreCart->data['payment_gross'];
			
			$ImStoreCart->checkout( );
			do_action( 'ims_after_pagseguro_ipn', $cartid, $ImStoreCart->data );
		}
	}
	
	/**
	 * Add button (actions) to cart
	 *
	 * @param string $output
	 * @return string
	 * @since 3.2.5
	 */
	function cart_actions( $output ){
		global $ImStore;
		
		 if( $ImStore->opts['gateway']['pagsegurosand'] )
		 	$output .= '<input name="ims-pagsegurosand" type="submit" value="' . esc_attr( $ImStore->gateways['pagsegurosand']['name'] ) . 
			'" class="primary ims-pagsegurosand" data-submit-url="' . esc_attr( urlencode( $ImStore->opts['pagsegurotesturl'] ) ) . '" /> ';
		
		if( $ImStore->opts['gateway']['pagseguroprod'] )
		 	$output .= '<input name="ims-pagseguroprod" type="submit" value="' . esc_attr( $ImStore->gateways['pagseguroprod']['name'] ) . 
			'" class="primary ims-pagseguroprod" data-submit-url="' . esc_attr( urlencode( $ImStore->gateways['pagseguroprod']['url'] ) ) . '" /> ';
		
		return $output;
	}
	
	/**
	 * Add PagSeguro varables to cart
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
	 * @since 3.2.5
	 */
	function item_hidden_fields( $output, $id, $item, $color, $enc, $row = 0, $title = '', $size = '' ){
		
		global $ImStore;
		$color = ( $item['color_name'] )  ? $item['color_name'] : '' ;
		$output .= '<input type="hidden" name="itemDescription' . $row . '" data-value-ims="' . esc_attr(  "$title: $size " . $item['unit'] . $color  ) . '"/>';
		$output .= '<input type="hidden" name="itemId' . $row . '" data-value-ims="' . esc_attr( $enc ) . '"/>';
		$output .= '<input type="hidden" name="itemQuantity' . $row . '" data-value-ims="' . esc_attr( $item['quantity'] ) . '"/>';
		$output .= '<input type="hidden" name="itemAmount' . $row . '" data-value-ims="' . 
		esc_attr( $ImStore->format_price( $item['price'] + $item['color'] + $item['finish'], false ) ) . '" />';
		
		return $output;
	}
	
	/**
	 * Add additional variable to process
	 * PagSeguro cart
	 *
	 * @param string $output
	 * @param array $cart
	 * @return string
	 * @since 3.2.5
	 */
	function cart_hidden_fields( $output, $cart ){
		
		global $ImStore, $ImStoreCart;
		
		$output .= '
		<input type="hidden" name="currency" data-value-ims="' . esc_attr( $ImStore->opts['currency'] ) . '" />
		<input type="hidden" name="receiverEmail" data-value-ims="' . esc_attr( $ImStore->opts['pagseguroemail'] ) . '" />
		<input type="hidden" name="reference" data-value-ims="' . esc_attr( $ImStore->url_encrypt( $ImStoreCart->orderid )  ) . '" />
		
		<input type="hidden" name="page_style" data-value-ims="' . get_bloginfo( 'name' ) . '" />
		<input type="hidden" name="return" data-value-ims="' . $ImStore->get_permalink( 'receipt' ) . '" />
		<input type="hidden" name="notify_url" data-value-ims="' . $ImStore->get_permalink( $ImStore->imspage ) . '" />
		<input type="hidden" name="discount_amount_cart" data-value-ims="' . esc_attr( $cart['promo']['discount'] )  . '" />
		<input type="hidden" name="cancel_return" data-value-ims="' . $ImStore->get_permalink( $ImStore->imspage ) . '" />
		<input type="hidden" name="cbt" data-value-ims="' . esc_attr( sprintf( __( 'Return to %s', 'ims' ), get_bloginfo( 'name' ) ) ) . '" />';
		
		if( $cart['promo']['discount'] ) 
			$output .= '<input type="hidden" name="extraAmount" data-value-ims="-' . esc_attr( $cart['promo']['discount'] )  . '" />';
		
		if ( $cart['shippingcost'] )
			$output .= 	'<input type="hidden" name="itemShippingCost1"  data-value-ims="' . esc_attr( $cart['shipping'] )  . '" />';
		
		return $output = apply_filters( 'ims_cart_pagseguro_hidden_fields', $output, $cart );
	}
}