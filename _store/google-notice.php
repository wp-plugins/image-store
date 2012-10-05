<?php

/**
 * ImStoreGoogleNotice - Google Notification
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 2.0.0
 */
class ImStoreGoogleNotice {

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function ImStoreGoogleNotice() {
		
		global $ImStore;
		$postdata = array();

		//dont change array order
		$this->subtitutions = array(
			$_POST['order-total'], $_POST['financial-order-state'],
			get_the_title($_POST['shopping-cart_merchant-private-data']),
			$ImStore->format_price($_POST['order-adjustment_shipping_flat-rate-shipping-adjustment_shipping-cost']),
			$_POST['google-order-number'], '', $_POST['buyer-billing-address_contact-name'], $_POST['buyer-billing-address_email'],
		);

		foreach ($_POST as $i => $v)
			$postdata .= $i . '=' . $v . "\n";

		if ($_POST['_type'] == 'new-order-notification') {
			do_action('ims_before_google_notice', $postdata);
			$this->process_google_notice();
		}

		die();
	}

	/**
	 * Process Google Notification
	 *
	 * @return boolean
	 * @since 2.0.0
	 */
	function process_google_notice() {

		global $ImStore;
		$this->opts = $ImStore->opts;

		$cartid = (int) $_POST['shopping-cart_merchant-private-data'];
		$cart = get_post_meta($cartid, '_ims_order_data', true);
		
		if(empty($cart)) return;
		
		foreach ($_POST as $key => $value){
			if( is_string($value) || is_numeric($value))
				$data[$key] = trim($value);
		}
		
		if(empty($data)) return;
		$data['data_integrity'] = false;

		if ($cartid && $data['order-total_currency'] == $this->opts['currency'] &&
		abs($data['order-total'] - $ImStore->format_price($cart['total'], false)) < 0.00001)
			$data['data_integrity'] = true;

		$data['last_name'] = '';
		$data['method'] = 'Google Checkout';
		$data['num_cart_items'] = $cart['items'];
		$data['mc_gross'] = $_POST['order-total'];
		$data['payment_gross'] = $_POST['order-total'];
		$data['txn_id'] = $_POST['google-order-number'];
		$data['payment_status'] = $_POST['financial-order-state'];
		$data['payer_email'] = $_POST['buyer-billing-address_email'];
		$data['address_city'] = $_POST['buyer-shipping-address_city'];
		$data['ims_phone'] = $_POST['buyer-shipping-address_phone'];
		$data['address_state'] = $_POST['buyer-shipping-address_region'];
		$data['address_street'] = $_POST['buyer-shipping-address_address1'];
		$data['address_zip'] = $_POST['buyer-shipping-address_postal-code'];
		$data['first_name'] = $_POST['buyer-billing-address_contact-name'];
		$data['address_country'] = $_POST['buyer-shipping-address_country-code'];

		$_POST = array();
		
		wp_update_post(array(
			'post_expire' => '0',
			'ID' => $cartid,
			'post_status' => 'pending',
			'post_date' => current_time('timestamp'),
		));

		update_post_meta($cartid, '_response_data', $data);
		$this->subtitutions[] = $cart['instructions'];

		do_action('ims_after_google_notice', $cartid, $cart);

		$message = preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['notifymssg']);
		$headers = 'From: "' . $this->opts['receiptname'] . '" <' . $this->opts['receiptemail'] . ">\r\n";

		wp_mail($this->opts['notifyemail'], $this->opts['notifysubj'], $message, $headers);
		setcookie('ims_orderid_' . COOKIEHASH, false, (time() - 315360000), COOKIEPATH, COOKIE_DOMAIN);

		if (empty($this->opts['emailreceipt']))
			die();

		//notify buyers
		if (isset($data['buyer-billing-address_email']) && is_email($data['buyer-billing-address_email'])
			&& !get_post_meta($cartid, '_ims_email_sent', true) && $data['data_integrity']) {
			
			$message = make_clickable(wpautop(stripslashes(preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['thankyoureceipt']))));
			$message .= $ImStore->get_download_links($cart, $data['mc_gross'],$data['data_integrity']);
		
			$headers .= "Content-type: text/html; charset=utf8\r\n";
			wp_mail($data['buyer-billing-address_email'], sprintf(__('%s receipt.', 'ims'), get_bloginfo('blogname')), $message, $headers);
			update_post_meta($cartid, '_ims_email_sent', 1);
		}
		die();
	}
}

new ImStoreGoogleNotice( );