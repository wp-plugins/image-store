<?php 

/**
 *Complete - Thank you / Receipt page
 *
 *@package Image Store
 *@author Hafid Trujillo
 *@copyright 20010-2012
 *@since 0.5.0 
*/

// Stop direct access of the file
if (!defined('ABSPATH'))
	die();

//normalize nonce field
wp_set_current_user( 0 );
 
$this->orderid = isset( $_POST['custom'] ) ?  $_POST['custom'] : $this->orderid;
$data = get_post_meta( $this->orderid, '_response_data', true);
$cart = get_post_meta( $this->orderid, '_ims_order_data', true );

//redirect empty data
if(empty($this->orderid) || empty($data)){
	wp_redirect( get_permalink() );
	die();
}

$this->substitutions = array(
	str_replace($this->sym,'\\'.$this->sym, $this->format_price($data['mc_gross'])), 
	$data['payment_status'], 
	get_the_title( $this->orderid ),
	str_replace($this->sym,'\\'.$this->sym, $this->format_price($cart['shipping'])), 
	$data['txn_id'],
	$data['last_name'], 
	$data['first_name'], 
	$data['payer_email'],
	$data['instructions'],
	$cart['items']
);

$output .= '<div class="ims-innerbox">
	 <div class="thank-you-message">' .
		(  make_clickable( wpautop( stripslashes( preg_replace( $this->opts['tags'], $this->substitutions, $this->opts['thankyoureceipt'] )) ) ) )
	 . '</div>
</div>';

$output .= $this->get_download_links($cart, $data['mc_gross'], $data['data_integrity']);
setcookie( 'ims_orderid_' . COOKIEHASH,  false, (time()-315360000), COOKIEPATH, COOKIE_DOMAIN );
$output .= '<div class="cl"></div>';