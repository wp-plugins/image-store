<?php
/**
 * Image Store - SagePay
 *
 * @file sagepay.php
 * @package Image Store
 * @author Leon Hughes / Xpark Media
 * @copyright 2013
 * @filesource  wp-content/plugins/image-store/_inc/gateways/sagepay.php
 * @since 3.3.4
 */
 
  class ImStoreCartSagePay {
  
  	public $status = false;
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.3.4
	 */
	function ImStoreCartSagePay( ) {
		
		// add button to checkout page
		add_filter( 'ims_store_cart_actions', array( &$this, 'cart_actions' ), 30, 1 );
		
		// add posts for sagepay checkout
		add_action( 'ims_before_post_actions', array( &$this, 'post_actions' ), 30, 10 );
			
		// add fields to checkout form
		add_filter( 'ims_before_checkout_order', array( &$this, 'add_checkout_billing_fieldset'), 30, 1 );
		
		//add aditional fields to user check objecto on checkout form
		add_filter( 'ims_user_checkout_fields', array( &$this, 'user_checkout_fields'), 30, 1 );
	}
	
	
	/**
	 * Add button (actions) to cart
	 *
	 * @param string $output
	 * @return string
	 * @since 3.3.4
	 */
	function cart_actions( $output ){
		
		global $ImStore;
		
 		if( $ImStore->opts['gateway']['sagepaydev'] ){
		 	$output .= '<input name="ims-sagepaydev" type="submit" value="' . esc_attr( $ImStore->gateways['sagepaydev']['name'] ) . 
			'" class="primary ims-sagepaydev" data-submit-url="' . get_permalink( ) . '" /> ';
		}
		
		if( $ImStore->opts['gateway']['sagepay'] ){
		 	$output .= '<input name="ims-sagepay" type="submit" value="' . esc_attr( $ImStore->gateways['sagepay']['name'] ) . 
			'" class="primary ims-sagepay" data-submit-url="' .  get_permalink( ) . '" /> ';
		}
		
		return $output;	
	}


	/**
	 * Post data to checkout page
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function post_actions( ){
				
		global $ImStore, $ImStoreCart;
		
		// redirec sage quest to checkout page
		if ( isset( $_POST['ims-sagepay' ] ) || isset( $_POST['ims-sagepaydev' ] ) ){
			$ImStore->imspage = 'checkout';
			$ImStore->show_comments = false;
		}
		
		// save billing data in current cart 		
		if( ! empty( $_POST['sage-checkout'] ) ){
			$ImStore->checkout = false;

			// add hidden fields only when proceesing sagepay
			add_filter( 'ims_checkout_hidden_fields', array( &$this, 'add_hidden_fields'), 30, 1 );
			
			foreach( array( 'billing_first_name' => 'billing_first_name', 'billing_last_name' => 'billing_last_name',
			 'billing_address' => 'billing_address', 'billing_city' => 'billing_city',  'billing_state' => 'billing_state', 
			 'billing_zip' => 'billing_zip', 'billing_phone' => 'billing_phone' ) as $field => $cart_key ){
				if( ! empty( $_POST[ $field ] ) ){
					$ImStoreCart->data[ $field ] = $_POST[ $field ];
					$ImStoreCart->data[ $cart_key ] = $_POST[ $field ];
				} else $ImStoreCart->data[ $field ] = false;
			}
			update_post_meta( $ImStoreCart->orderid, '_response_data', $ImStoreCart->data );
		}
		
		// process responce from  sagepay
		if( ! empty( $_GET['crypt'] ) )
			$this->process_notice( $_GET['crypt'] );
	}
	
	
	/**
	 * Allow additional data to be
	 * saved in the image store cart
	 *
	 * @param array $fields
	 * @return array
	 * @since 3.4
	 */
	function user_checkout_fields( $fields ){
		if( empty( $_POST['ims-sagepay'] ) )
			return $fields;
		
		return array_merge( $fields, array(
			'billing_first_name', 'billing_last_name', 'billing_address', 'billing_city', 'billing_state', 'billing_zip', 'billing_phone'
		) );
	}
	
	
	/**
	 * Decrypt data coming from sagepay 
	 *
	 * @param string $string
	 * @return array
	 * @since 3.4
	 */
	function decrypt_string( $string, $password ){
		
		$crypt = str_replace(" ","+", $string );
		
		$crypt_length = strlen( $crypt );
		$password_length = strlen( $password );
		
		$n = intval((($crypt_length / $password_length) + 1));
		$extension = str_repeat( $password, $n );
			
		$fields = explode( "&", base64_Decode( $crypt ) ^ $extension );
		
		foreach ( $fields as $lineNum => $line ) {
			list($key, $value) = explode("=", $line);
			$format_fields[$key] = $value;
		}
		
		return $format_fields;
	}
	
	
	/**
	 * Encrypt data to send to sagepay
	 *
	 * @param string $crypt
	 * @return array
	 * @since 3.4
	 */
	function crypt_string( $string, $password ) {
        
		if( empty( $password ) )
			return ;
			
        $output = '';
		$data = array();
		$string = utf8_decode($string);
		
        for ($i = 0; $i < strlen($password); $i++) 
            $data[$i] = ord(substr($password, $i, 1));

        for ($i = 0; $i < strlen($string); $i++) 
			$output .= chr(ord(substr($string, $i, 1)) ^ ($data[$i % strlen($password)]));

		return base64_encode( $output );
    }
	
	
	/**
	 * Process sagepay call
	 *
	 * @param string $crypt
	 * @return void
	 * @since 3.4
	 */
	function process_notice( $crypt ){
		
		global $ImStore, $ImStoreCart;
		
		$crypt_array = $this->decrypt_string( 
			str_replace(" ","+", $crypt ),
			$ImStore->opts['sppassword'] 
		);
		
		//don't display error message possible hack
		if( empty( $crypt_array['VendorTxCode'] ) || empty( $crypt_array['Status']  ) )
			return; 
		
		//  problem with paymet display error message in cart page
		if( $crypt_array['Status'] != 'OK' ){
			$ImStore->imspage = 'shopping-cart';
			$ImStore->error = $this->get_error_message( $crypt_array['Status'] );
			do_action( 'ims_sagepay_notice_error', $ImStore->error, $cartid );
			return;
		}
		
		$cartid = trim( $crypt_array['VendorTxCode'] );

		global $ImStoreCart;
		$ImStoreCart->setup_cart( $cartid );
		
		$crypt_array['Status'] = 'processed';
		
		do_action( 'ims_before_sagepay_notice', false, $cartid );
		
		$response_data  = array( 
			'Currency' => 'mc_currency', 
			'Status' => 'payment_status', 
			'Amount' => 'payment_gross', 
			'CustomerEmail' => 'payer_email',
			'CartItems' => 'num_cart_items',
			'DeliverySurname' => 'last_name',
			'DeliveryFirstnames' => 'first_name',
			'DeliveryCity' => 'address_city',
			'DeliveryPhone' => 'ims_phone',
			'DeliveryState' => 'address_state',
			'DeliveryAddress1' => 'address_street',
			'DeliveryPostCode' => 'address_zip',
			'DeliveryCountry' => 'address_country',
		); 
		
		foreach( $response_data as $key => $reponse ){
			if( isset( $crypt_array[ $key ] ) )
				$ImStoreCart->data[ $reponse ] =  $crypt_array[$key];
		} 
		
		$ImStoreCart->data['method'] =  'SagePay';
		$ImStoreCart->data['instructions'] = $ImStoreCart->cart['instructions'];
		$ImStoreCart->data['user_email'] = $ImStoreCart->data['payer_email'];
		$ImStoreCart->data['txn_id'] = sprintf( "%08d", $ImStoreCart->orderid );
		$ImStoreCart->data['mc_gross'] = $ImStoreCart->data['payment_gross'];
		
		$ImStoreCart->checkout( );
		do_action( 'ims_after_sagepay_notice', $cartid, $ImStoreCart->data );
	}
	
	
	/**
	 * Add billing fieldset to checout page
	 *
	 * @param string $output
	 * @return string
	 * @since 3.4
	 */
	function add_checkout_billing_fieldset( $output ) {
		
		global $ImStore;
		
		if( ( empty( $_POST['ims-sagepay'] ) && empty( $_POST['ims-sagepaydev'] ) ) || empty(  $ImStore->cart ) )
			return $output;
			
		$gateway_url = '';
		
		if( isset( $_POST['ims-sagepay'] ) )
			 $gateway_url = $ImStore->gateways['sagepay']['url'];
			 
		elseif( isset( $_POST['ims-sagepaydev'] ) )
			 $gateway_url = $ImStore->gateways['sagepaydev']['url'];
			 
		$userdata = wp_get_current_user( ); 
		$output .= '<fieldset class="ims-billing"><legend>' . __( 'Billing Information' ) . '</legend>';
		
		$output .= '<div class="ims-p user-info">';
		$output .= '<label for="first_name">' . __( 'First Name', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_first_name" id="billing_first_name" value="' . esc_attr( $userdata->billing_first_name ) . '" class="ims-input" />';
		$output .= '<span class="ims-break"></span>';
		$output .= '<label for="last_name">' . __( 'Last Name', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_last_name" id="billing_last_name" value="' . esc_attr( $userdata->billing_last_name ) . '" class="ims-input"/>';
		$output .= '</div><!--.user-info-->';
		
		$output .= '<div class="ims-p address-info">';
		$output .= '<label for="billing_address">' . __( 'Address', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_address" id="billing_address" value="' . esc_attr( $userdata->billing_address ) . '" class="ims-input" />';
		$output .= '<span class="ims-break"></span>';
		
		$output .= '<label for="billing_city">' . __( 'City', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_city" id="billing_city" value="' . esc_attr( $userdata->billing_city ) . '" class="ims-input" />';
		$output .= '<span class="ims-break"></span>';
		
		$output .= '<label for="billing_state">' . __( 'State', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_state" id="billing_state" value="' . esc_attr( $userdata->billing_state ) . '" class="ims-input" />';
		$output .= '<span class="ims-break"></span>';
		
		$output .= '<label for="billing_zip">' . __( 'Zip', 'ims' ) . ' </label>';
		$output .= '<input type="text" name="billing_zip" id="billing_zip" value="' . esc_attr( $userdata->billing_zip ) . '" class="ims-input" />';
		$output .= '<span class="ims-break"></span>';
			
		$output .= '<label for="billing_phone">' . __( 'Phone', 'ims' )  . ' </label>';
		$output .= '<input type="text" name="billing_phone" id="billing_phone" value="' . esc_attr( $userdata->billing_phone ) . '" class="ims-input" />';
		$output .= '</div>';
		
		$output .= '<input type="hidden" name="ims-sagepay" value="1" /><input type="hidden" name="sage-checkout" value="1" />';
		$output .= '<input type="hidden" name="sagepay_url" value="' . esc_url( $gateway_url ) . '" />';

		return $output .= '</fieldset>';
	}
	
	
	/**
	 * Add additional variable to process checkout 
	 *
	 * @param string $output
	 * @return string
	 * @since 3.4
	 */
	function add_hidden_fields( $output ){
		
		global $ImStoreCart, $ImStore;
		$data = $ImStoreCart->data;
				
		$crypt = 'VendorTxCode=' . $ImStoreCart->orderid . '&';
		$crypt .= 'Amount=' .  $ImStoreCart->cart['total'] . '&';
		$crypt .= 'Currency=' . $ImStore->opts['currency'] .'&';
		$crypt .= 'Description=' . $ImStore->opts['spdescription'] .'&';
		$crypt .= 'SuccessURL=' . $ImStore->get_permalink( 'receipt' ) . '&';
		$crypt .= 'FailureURL=' . $ImStore->get_permalink( 'shopping-cart' ) . '&';
			
		$crypt .= 'CustomerName=' . $data['billing_first_name'] . ' ' . $data['billing_last_name'] . '&';
		$crypt .= 'CustomerEmail=' . $data['payer_email'] . '&';
		$crypt .= 'VendorEmail='. $ImStore->opts['spemail'] . '&';
				
		$crypt .= 'BillingSurname=' . $data['billing_last_name'] . '&';
		$crypt .= 'BillingFirstnames=' . $data['billing_first_name'] . '&';
		$crypt .= 'BillingAddress1=' . $data['billing_address'] . '&';
		$crypt .= 'BillingCity=' . $data['billing_city'] . '&';
		$crypt .= 'BillingState=' . $data['billing_state'] . '&';
		$crypt .= 'BillingPostCode=' . $data['billing_zip'] . '&';
		$crypt .= 'BillingCountry=' . $ImStore->opts['taxcountry']  .'&';
		$crypt .= 'BillingPhone=' . $data['billing_phone'] . '&';
		
		$crypt .= 'DeliverySurname=' . $data['last_name'] . '&';
		$crypt .= 'DeliveryFirstnames=' . $data['first_name'] . '&';
		$crypt .= 'DeliveryAddress1=' . $data['address_street'] . '&';
		$crypt .= 'DeliveryCity=' . $data['address_city'] . '&';
		$crypt .= 'DeliveryState=' . $data['address_state'] . '&';
		$crypt .= 'DeliveryPostCode=' . $data['address_zip'] . '&';
		$crypt .= 'DeliveryCountry=' . $ImStore->opts['taxcountry']  .'&';
		$crypt .= 'DeliveryPhone=' . $data['ims_phone'] . '&';
		//$crypt .= 'CartItems=' . $ImStoreCart->cart['items']  . '&';
		
		//test status
		//$crypt .= 'Status=INVALID&';
		
		$crypt .= 'SendEmail=1&';
		$crypt .= 'AllowGiftAid=0&';
		$crypt .= 'ApplyAVSCV2=0&';
		$crypt .= 'Apply3DSecure=0';
		
		update_post_meta( $ImStoreCart->orderid, '_response_data', $ImStoreCart->data );
		
		$output .= '<div id="sagepay-data" >';
		$output .= '<input type="hidden" name="TxType" value="PAYMENT" />';
		$output .= '<input type="hidden" name="Vendor" value="'. esc_attr( $ImStore->opts['spvendor'] ) . '" />';
		$output .= '<input type="hidden" name="VPSProtocol" value="'. esc_attr( $ImStore->opts['vpsprotocol'] ) . '" />';
		$output .= '<input type="hidden" name="Crypt" value="' . $this->crypt_string( $crypt, $ImStore->opts['sppassword'] ) . '" />';
		return $output .= '</div>';
	}
	
	
	/**
	 * Get error base on sagepay responce
	 *
	 * @param string $error id
	 * @return string
	 * @since 3.4
	 */
	function get_error_message( $error ){
		$output = '';
		switch ( $error ){
			case 'NOTAUTHED':
				$output .= '<p>' . __( 'There was an error with your details given to the sagePay checkout system, or there was insufficient funds.', 'ims' ) . '</p>';
				$output .= '<p>' . __( 'Please ensure that your details are correct and that there are sufficient funds in your account, then try again.', 'ims' ) . '</p>';
				break;
			case 'MALFORMED':
				$output .= '<p>' . __( 'There were missing fields when transferring your details to sagePay. <a href="%s">Please contact us</a>', 'ims' ) . '</a>';
				break;
			case 'INVALID':
				$output .= '<p>' . __( 'Some details sent to sagePay were incorrect. <a href="%s">Please contact us</a>.', 'ims' ) . '</p>';
				break;
			case 'ABORT':
				$output .= '<p>' . __( 'The transaction was aborted at sagePay, or the page was idle for longer than 15 minutes.', 'ims' ) . '</p>';
				$output .= '<p>' . __( 'Please check that the funds were not taken from your account and try again.', 'ims'  ) . '</p>';
				break;
			case 'REJECTED':
				$output .= '<p>' . __( 'The transaction was rejected due to the fraud screen system. <a href="%s">Please contact us</a>.', 'ims' ) . '</p>';
				break;
			case 'ERROR':
				$output .= '<p>' . __( 'There was a problem with the sagePay server.', 'ims'  ) . '</p>';
				$output .= '<p>' . __( 'Please check that the funds were not taken from your account and try again.', 'ims' ) . '</p>';
				break;
			default:
				$output .= '<p>'. __( 'An unknown error occured. <a href="%s">Please contact us</a>.', 'ims' ) .'</p>';
				break;
		}
		return sprintf( $output,  apply_filters( 'ims_sagepay_contact',  "mailto:" . esc_attr( get_option( 'admin_email' )) ) );
	}
  }