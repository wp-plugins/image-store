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
		$postdata = array();

		//dont change array order
		$this->subtitutions = array(
			$_POST['order-total'], $_POST['financial-order-state'],
			get_the_title($_POST['shopping-cart_merchant-private-data']),
			$_POST['order-adjustment_shipping_flat-rate-shipping-adjustment_shipping-cost'],
			$_POST['google-order-number'], $_POST['buyer-billing-address_contact-name'], '', $_POST['buyer-billing-address_email'],
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

		$_POST['data_integrity'] = false;

		$cartid = (int) $_POST['shopping-cart_merchant-private-data'];
		$cart = get_post_meta($cartid, '_ims_order_data', true);

		if ($cartid && $_POST['order-total_currency'] == $this->opts['currency'] &&
		abs($_POST['order-total'] - $ImStore->format_price($cart['total'], false)) < 0.00001)
			$_POST['data_integrity'] = true;

		$_POST['method'] = 'Google Checkout';
		$_POST['num_cart_items'] = $cart['items'];
		$_POST['mc_gross'] = $_POST['order-total'];
		$_POST['payment_gross'] = $_POST['order-total'];
		$_POST['txn_id'] = $_POST['google-order-number'];
		$_POST['payment_status'] = $_POST['financial-order-state'];
		$_POST['payer_email'] = $_POST['buyer-billing-address_email'];
		$_POST['address_city'] = $_POST['buyer-shipping-address_city'];
		$_POST['ims_phone'] = $_POST['buyer-shipping-address_phone'];
		$_POST['address_state'] = $_POST['buyer-shipping-address_region'];
		$_POST['address_street'] = $_POST['buyer-shipping-address_address1'];
		$_POST['address_zip'] = $_POST['buyer-shipping-address_postal-code'];
		$_POST['first_name'] = $_POST['buyer-billing-address_contact-name'];
		$_POST['address_country'] = $_POST['buyer-shipping-address_country-code'];

		wp_update_post(array(
			'post_expire' => '0',
			'ID' => $cartid,
			'post_status' => 'pending',
			'post_date' => current_time('timestamp'),
		));

		update_post_meta($cartid, '_response_data', $_POST);
		$this->subtitutions[] = $cart['instructions'];

		do_action('ims_after_google_notice', $cartid, $cart);

		$message = preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['notifymssg']);
		$headers = 'From: "' . $this->opts['receiptname'] . '" <' . $this->opts['receiptemail'] . ">\r\n";

		wp_mail($this->opts['notifyemail'], $this->opts['notifysubj'], $message, $headers);
		setcookie('ims_orderid_' . COOKIEHASH, false, (time() - 315360000), COOKIEPATH, COOKIE_DOMAIN);

		if (empty($this->opts['emailreceipt']))
			die();

		//notify buyers
		if (isset($_POST['buyer-billing-address_email']) && is_email($_POST['buyer-billing-address_email'])
			&& !get_post_meta($cartid, '_ims_email_sent', true) && $_POST['data_integrity']) {
			
			$message = make_clickable(wpautop(stripslashes(preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['thankyoureceipt']))));
			$message .= $ImStore->get_download_links($cart, $_POST['mc_gross'],$_POST['data_integrity']);
		
			$headers .= "Content-type: text/html; charset=utf8\r\n";
			wp_mail($_POST['buyer-billing-address_email'], sprintf(__('%s receipt.', $this->domain), get_bloginfo('blogname')), $message, $headers);
			update_post_meta($cartid, '_ims_email_sent', 1);
		}
		die();
	}
}

new ImStoreGoogleNotice( );