<?php

/**
 * ImStorePaypalIPN - Paypal Notification
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0 
 */
class ImStorePaypalIPN {

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function ImStorePaypalIPN() {
		global $ImStore;

		$postdata = '';
		$this->opts = $ImStore->opts;
		
		if( $this->opts['gateway']['paypalsand'] )
		$url =  $ImStore->gateway['paypalsand']['url'] ;
		else  $url = $ImStore->gateway['paypalprod']['url'] ;
		
		$log = array('REQUEST_TIME', 'REMOTE_ADDR', 'REQUEST_METHOD', 'HTTP_USER_AGENT', 'REMOTE_PORT');

		foreach ($_POST as $i => $v)
			$postdata .= $i . '=' . urlencode($v) . '&';
		$postdata .= 'cmd=_notify-validate';

		$web = parse_url($url);
		if ($web['scheme'] == 'https' ||
				strpos($url, 'sandbox') !== false) {
			$web['port'] = 443;
			$ssl = 'ssl://';
		} else {
			$web['port'] = 80;
			$ssl = '';
		}
		$fp = fsockopen($ssl . $web['host'], $web['port'], $errnum, $errstr, 30);

		if (!$fp) {
			
			return;
			
		} else {
			fputs($fp, "POST " . $web['path'] . " HTTP/1.1\r\n");
			fputs($fp, "Host: " . $web['host'] . "\r\n");
			fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
			fputs($fp, "Content-length: " . strlen($postdata) . "\r\n");
			fputs($fp, "Connection: close\r\n\r\n");
			fputs($fp, $postdata . "\r\n\r\n");

			while (!feof($fp))
				$info[] = @fgets($fp, 1024);

			fclose($fp);
			$info = implode(',', $info);

			if (eregi('VERIFIED', $info)) {

				do_action('ims_before_paypal_ipn', $postdata);

				// information was verified
				$this->process_paypal_IPN();
				return;
				
			} else {
				
				$logtext = '';
				$file = IMSTORE_ABSPATH . "/ipn_log.txt";
				$hd = fopen($file, 'a');
				
				foreach($_POST as $i => $v)
					$logtext .= $i.'='.$v."\n";
			
				foreach ($log as $key)
					$logtext .= $key . '=' . $_SERVER[$key] . ',';
					
				$logtext .= "\n$url\n_________________\n";
				
				fwrite($hd, $web['host'] . "," . $logtext);
				fclose($hd);
				return;
			}
		}
	}

	/**
	 * Process Paypal IPN
	 *
	 * @return boolean
	 * @since 0.5.0 
	 */
	function process_paypal_IPN() {
		global $ImStore;

		$cartid = (int) $_POST['custom'];
		$cart = get_post_meta($cartid, '_ims_order_data', true);
		$total = (isset($cart['discounted'])) ? $cart['discounted'] : $cart['total'];

		$_POST['data_integrity'] = false;
		
		if ($cartid && $_POST['mc_currency'] == $this->opts['currency']
		&& $_POST['business'] == $this->opts['paypalname'] &&
		abs($_POST['mc_gross'] - $ImStore->format_price($total, false)) < 0.00001)
			$_POST['data_integrity'] = true;

		wp_update_post(array(
			'ID' => $cartid,
			'post_expire' => '0',
			'post_status' => 'pending',
			'post_date' => current_time('timestamp')
		));

		$_POST['method'] = 'PayPal';
		$_POST['num_cart_items'] = $cart['items'];
		$_POST['payment_gross'] = $_POST['mc_gross'];

		update_post_meta($cartid, '_response_data', $_POST);
		$this->subtitutions[] = $cart['instructions'];

		//dont change array order
		$this->subtitutions = array(
			$_POST['mc_gross'], $_POST['payment_status'], get_the_title($cartid),
			$cart['shipping'], $_POST['txn_id'],$_POST['last_name'], $_POST['first_name'], $_POST['payer_email'],
		);

		do_action('ims_after_paypal_ipn', $cartid, $cart);

		$message = preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['notifymssg']);
		$headers = 'From: "' . $this->opts['receiptname'] . '" <' . $this->opts['receiptemail'] . ">\r\n";

		wp_mail($this->opts['notifyemail'], $this->opts['notifysubj'], $message, $headers);
		setcookie('ims_orderid_' . COOKIEHASH, false, (time() - 315360000), COOKIEPATH, COOKIE_DOMAIN);

		if (empty($this->opts['emailreceipt']))
			return;

		//notify buyers
		if (isset($_POST['payer_email']) && is_email($_POST['payer_email'])
		 && !get_post_meta($cartid, '_ims_email_sent', true) && $_POST['data_integrity']) {

			$message = make_clickable(wpautop(stripslashes(preg_replace($this->opts['tags'], $this->subtitutions, $this->opts['thankyoureceipt']))));
			$message .= $ImStore->get_download_links($cart, $_POST['mc_gross'],$_POST['data_integrity']);

			$headers .= "Content-type: text/html; charset=utf8\r\n";
			wp_mail($_POST['payer_email'], sprintf(__('%s receipt.', $ImStore->domain), get_bloginfo('blogname')), $message, $headers);
			update_post_meta($cartid, '_ims_email_sent', 1);
		}
		return;
	}
}

new ImStorePaypalIPN( );