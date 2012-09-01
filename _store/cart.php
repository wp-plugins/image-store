<?php

/**
 * Shopping cart page
 *
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0
 */
 
// Stop direct access of the file
if (!defined('ABSPATH'))
	die();

$downlinks = '';
global $user_ID;
$userid = $user_ID;

//normalize nonce field
wp_set_current_user(0);
$nonce = wp_create_nonce("ims_download_img");
wp_set_current_user($userid);

//custom cart data
if ($this->opts['gateway']['custom'] && !empty($this->opts['data_pair'])) {
	$data_pair = array();
	foreach (explode(',', $this->opts['data_pair']) as $input) {
		$vals = explode('|', $input);
		if (isset($vals[1]))
			$data_pair[$vals[0]] = $vals[1];
	};
}

//start form output
$output .= '<form method="' . esc_attr($this->opts['gateway_method']) . '" class="ims-cart-form" action="#' . apply_filters('ims_cart_action', '', $this) . '" >';

//if empty show error
if (empty($this->cart['images']) && apply_filters('ims_empty_car', true, $this->cart)):

	$error = new WP_Error( );
	$error->add('empty', __('Your shopping cart is empty.', $this->domain));
	$output .= $this->error_message($error, true);

else: //else show table
	
	if(!is_singular('ims_gallery'))
		$this->imspage = false;
		
	$output .=
	'<noscript><div class="ims-message ims-error">' . __('Please enable Javascript, it is required to submit payment. ') . '</div></noscript>
		<table class="ims-table" role="grid">
		<thead>
			<tr>
				<th scope="col" class="ims-preview">&nbsp;</th>
				<th colspan="2" class="ims-subrows" >
					<span class="ims-quantity">' . __('Quantity', $this->domain) . '</span>
					<span class="ims-size">' . __('Size', $this->domain) . '</span>
					<span class="ims-color">' . __('Color', $this->domain) . '</span>
					<span class="ims-fisnish">' . __('Finish', $this->domain) . '</span>
					<span class="ims-price">' . __('Unit Price', $this->domain) . '</span>
					<span class="ims-subtotal">' . __('Subtotal', $this->domain) . '</span>
					<span class="ims-delete">' . __('Delete', $this->domain) . '</span>
				</th>
			</tr>
		</thead>';
	$output .= '<tbody>';

	$i = 1;
	foreach ((array) $this->cart['images'] as $id => $sizes):

		$image = get_post_meta($id, '_wp_attachment_metadata', true);
		
		if( empty($image) )
			continue;
		
		$mini = $image['sizes']['mini'];
		$size = ' width="' . $mini['width'] . '" height="' . $mini['height'] . '"';

		$output .= '<tr role="row"> <td role="gridcell" class="ims-preview">'; //start row
		$output .= '<img src="' . $this->get_image_url($id, 3) . '" title="' . esc_attr($mini['file']) . '" alt="' . esc_attr($mini['file']) . '"' . $size . ' />';
		$output .= '</td>';

		$output .= '<td role="gridcell" class="ims-subrows" colspan="2">';
		foreach ($sizes as $size => $colors):
			foreach ($colors as $color => $item):
				
				$enc = $this->url_encrypt($id);
				$output .= '<div class="ims-clear-row">';
				$output .= '<span class="ims-quantity"><input type="text" name="ims-quantity'."[$enc][$size][$color]" . '" value="'.esc_attr($item['quantity']).'" class="input" /></span>';
				$output .= '<span class="ims-size">' . $size . ' <span class="ims-unit">' . $item['unit'] . '</span></span>';
				$output .= '<span class="ims-color">' . $item['color_name'] . ' ' . $this->format_price($item['color']) . '</span>';
				$output .= '<span class="ims-fisnish">' . $item['finish_name'] . ' ' . $this->format_price($item['finish'])  . '</span>';
				$output .= '<span class="ims-price">' . $this->format_price($item['price']) . '</span>';
				$output .= '<span class="ims-subtotal">' . $this->format_price($item['subtotal']) . '</span>';
				$output .= apply_filters('ims_cart_image_list_column', '', $id, $item, $color, $enc, $i);
				$output .= '<span class="ims-delete"><input name="ims-remove[]" type="checkbox" value="' . esc_attr("{$enc}|{$size}|{$color}") . '" /></span>';

				//load google checkout
				if ($this->opts['gateway']['googlesand'] || $this->opts['gateway']['googleprod']) :
					$output .= '<input type="hidden" name="item_merchant_id_' . $i . '" data-value-ims="' . esc_attr($enc) . '" />';
					$output .= '<input type="hidden" name="item_quantity_' . $i . '" data-value-ims="' . esc_attr($item['quantity']) . '" />';
					$output .= '<input type="hidden" name="item_name_' . $i . '" data-value-ims="' . get_the_title($id) . '" />';
					$output .= '<input type="hidden" name="item_currency_' . $i . '" data-value-ims="' . esc_attr($this->opts['currency']) . '" />';
					$output .= '<input type="hidden" name="item_description_' . $i . '" data-value-ims="' . esc_attr("$size " . $item['unit'] . ' ' . trim($item['color_name'], " + ")) . '" />';
					$output .= '<input type="hidden" name="item_price_' . $i . '" data-value-ims="' . esc_attr($this->format_price($item['price']+$item['color']+$item['finish'], false)) . '"/>';

					if (isset($item['download']))
						$downlinks .=
								"&lt;p&gt;&lt;a href='" . IMSTORE_ADMIN_URL . "/download.php?_wpnonce=$nonce&amp;img=$enc&amp;sz=$size&amp;c=".$item['color_code']."' &gt;" .
								get_the_title($id) . "&lt;/a&gt;: " . trim($item['color_name'], " + ") . "&lt;/p&gt;";
				endif;

				//load paypal
				if ($this->opts['gateway']['paypalsand'] || $this->opts['gateway']['paypalprod']) :
					$output .= '<input type="hidden" name="on0_' . $i . '" data-value-ims="' . esc_attr("$size " . $item['unit']) . '"/>';
					$output .= '<input type="hidden" name="item_number_' . $i . '" data-value-ims="' . esc_attr($enc) . '"/>';
					$output .= '<input type="hidden" name="quantity_' . $i . '" data-value-ims="' . esc_attr($item['quantity']) . '"/>';
					$output .= '<input type="hidden" name="item_name_' . $i . '" data-value-ims="' . get_the_title($id) . '"/>';
					$output .= '<input type="hidden" name="os0_' . $i . '" data-value-ims="' . trim($item['color_name'] , " + ") . '"/>';
					$output .= '<input type="hidden" name="amount_' . $i . '" data-value-ims="' . esc_attr($this->format_price($item['price']+$item['color']+$item['finish'], false)) . '" />';
				endif;

				//load custom cart
				if ($this->opts['gateway']['custom'] && !empty($this->opts['data_pair'])) :

					$item_replace = array($enc,
						__('%image_id%', $this->domain) => $enc,
						__('%image_name%', $this->domain) => get_the_title($id),
						__('%image_value%', $this->domain) => esc_attr($this->format_price($item['price']+$item['color']+$item['finish'], false)),
						__('%image_color%', $this->domain) => trim($this->color[$color], " + "),
						__('%image_quantity%', $this->domain) => $item['quantity'],
					);

					if (isset($item['download']))
						$item_replace[__('%image_download%', $this->domain)] = $item['download'];

					foreach ($data_pair as $key => $sub) {
						if (isset($item_replace[$sub]))
							$output .= "\n" . '<input type="hidden" name="' . $key . $i . '" data-value-ims="' . esc_attr($item_replace[$sub]) . '" />';
					}
				endif;

				$output .= apply_filters('ims_cart_item_hidden_fields', '', $id, $item, $color, $enc, $i);
				$output .= '</div><!--.ims-clear-row-->';
				$i++;

			endforeach;
		endforeach;

		$output .= '</td></tr>'; //end row
		$output .= apply_filters('ims_cart_image_list_row', '', $id, $item, $color, $enc, $i);

	endforeach; //end image list

	$output .= apply_filters('ims_cart_image_list', '', &$this);
	$output .= '</tbody><tfoot>'; //end tbody - start tfoot
	//display subtotal
	$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell"><label>' . __('Item subtotal', $this->domain) . '</label></td>
	<td role="gridcell" class="total">' . $this->format_price($this->cart['subtotal']) . '</td></tr>';

	//promotional code
	$output .= '<tr role="row">
	<td role="gridcell" >&nbsp;</td><td role="gridcell"><label for="ims-promo-code">' . __('Promotional code', $this->domain) . '</label></td>
	<td role="gridcell" class="total promo-code">
	<input name="promocode" id="ims-promo-code" type="text" value="' . ( isset($this->cart['promo']['code']) ? esc_attr($this->cart['promo']['code']) : '' ) . '" />
	<span class="ims-break"></span> <small>' . __('Update cart to apply promotional code.', $this->domain) . '</small></td>
	</tr>';

	//display discounted data
	if ($this->cart['promo']['discount'])
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell">' . __('Discount', $this->domain) . '</td>
		<td role="gridcell" class="discount">' . $this->format_price($this->cart['promo']['discount'], true, ' - ') . '</td></tr>';
	
	//shipping charge
	if($this->cart['shippingcost'] )
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell"><label for="shipping">' . __('Shipping', $this->domain) . '</label></td>
		<td role="gridcell" class="shipping">' .  $this->shipping_options()  . '</td></tr>';
		
	//display tax fields
	if ($this->cart['tax']) 
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell">' . __('Tax', $this->domain) . '</td><td role="gridcell" class="tax">' .
		$this->format_price($this->cart['tax'], true, ' + ') . '<input type="hidden" name="tax_cart" value="' . $this->cart['tax'] . '"/></td></tr>';

	//display total
	$output .= '<tr role="row"><td role="gridcell">&nbsp;</td> <td role="gridcell"><label>' . __('Total', $this->domain) . '</label></td>
	<td role="gridcell" class="total">' . $this->format_price($this->cart['total']) . ' </td></tr>';

	//display notification
	$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell" colspan="2"><label>' . __('Additional Instructions', $this->domain) . '<br />
	<textarea name="instructions" class="ims-instructions">' . esc_textarea(isset($this->cart['instructions']) ? $this->cart['instructions'] : '' ) . '</textarea></label></td></tr>';

	$output .= '<tr role="row" class="ims-checkout-fileds"><td role="gridcell">&nbsp;</td><td role="gridcell" colspan="2">';

	$output .='<input name="apply-changes" type="submit" value="' . esc_attr__('Update Cart', $this->domain) . '" class="secondary" /><span class="ims-bk"></span>  ';

	$output .= '<span class="ims-checkout-label">' . esc_attr__('Checkout using:', $this->domain) . ' </span>';

	//render button
	foreach ((array)$this->opts['gateway'] as $key => $bol){
		if ($bol)
			$output .='<input name="' . $key . '" type="submit" value="' . esc_attr($this->gateway[$key]['name']) . 
			'" class="primary ims-google-checkout" data-submit-url="' . esc_attr(urlencode($this->gateway[$key]['url'])) . '" /> ';
	}
	
	$output .= apply_filters('ims_store_cart_actions', '', &$this->cart) . '</td></tr>';

	$output .= '</tfoot>
	</table><!--.ims-table-->'; //end table
	
	//terms and conditions
	$output .= '<div class="ims-terms-condtitions">' . esc_html(isset($this->opts['termsconds']) ? $this->opts['termsconds'] : '' ) . '</div>';

	//google cart fileds
	if ($this->opts['gateway']['googlesand'] || $this->opts['gateway']['googleprod']) :

		$output .= '<input type="hidden" name="edit-cart-url"  data-value-ims="' . esc_attr($this->get_permalink()) . '" />
		<input type="hidden" name="tax_country"  data-value-ims="' . ( isset($this->opts['taxcountry']) ? esc_attr($this->opts['taxcountry']) : '' ) . '" />
		<input type="hidden" name="tax_rate"  data-value-ims="' . ( isset($this->opts['taxamount']) ? esc_attr($this->opts['taxamount'] / 100) : 0 ) . '" />
		<input type="hidden" name="shopping-cart.merchant-private-data"  data-value-ims="' . esc_attr($this->orderid) . '" />';

		$output .= '<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.edit-cart-url"  data-value-ims="' . esc_attr($this->get_permalink($this->imspage)) . '" />
		<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.continue-shopping-url"  data-value-ims="' . esc_attr($this->get_permalink('receipt')) . '" />
		<input type="hidden" name="checkout-flow-support.merchant-checkout-flow-support.tax-tables.default-tax-table.tax-rules.default-tax-rule-1.shipping-taxed" data-value-ims="true"/>';

		if ($this->cart['shippingcost']) {
			$output .= '<input type="hidden" name="ship_method_name_1"  data-value-ims="' . esc_attr__("shipping", $this->domain) . '" />
			<input type="hidden" name="ship_method_price_1"  data-value-ims="' . esc_attr($this->cart['shipping']) . '" />
			<input type="hidden" name="ship_method_currency_1"  data-value-ims="' . esc_attr($this->opts['currency']) . '" />';
		}

		if ($downlinks)
			$output .= '<input type="hidden" name="shopping-cart.items.item-1.digital-content.description"
			 data-value-ims="' . "&lt;p&gt;" . esc_attr__("downloads:", $this->domain) . "&lt;/p&gt; $downlinks" . '" />';

		if ($this->cart['promo']['discount']) {
			$output .= '<input type="hidden" name="item_quantity_' . $i . '" data-value-ims="1" />
			<input type="hidden" name="item_name_' . $i . '"  data-value-ims="' . esc_attr__("discount", $this->domain) . '" />
			<input type="hidden" name="item_currency_' . $i . '"  data-value-ims="' . esc_attr($this->opts['currency']) . '" />
			<input type="hidden" name="item_merchant_id_' . $i . '"  data-value-ims="' . esc_attr($this->cart['promo']['code']) . '" />
			<input type="hidden" name="item_price_' . $i . '"  data-value-ims="' . "-" . esc_attr($this->cart['promo']['discount']) . '" />
			<input type="hidden" name="item_description_' . $i . '"  data-value-ims="' . esc_attr__("promotion code", $this->domain) . '" />';
		}

		$output .= apply_filters('ims_cart_google_hidden_fields', '', $this->cart);

	endif;


	//load paypal
	if ($this->opts['gateway']['paypalsand'] || $this->opts['gateway']['paypalprod']) :

		$output .= '
		<input type="hidden" readonly="readonly" name="rm" data-value-ims="2" />
		<input type="hidden" name="upload" data-value-ims="1" />
		<input type="hidden" name="cmd" data-value-ims="_cart" />
		<input type="hidden" name="lc" data-value-ims="' . get_bloginfo('language') . '" />
		<input type="hidden" name="return" data-value-ims="' . $this->get_permalink('receipt') . '" />
		<input type="hidden" name="page_style" data-value-ims="' . get_bloginfo('name') . '" />
		<input type="hidden" name="custom" data-value-ims="' . esc_attr($this->orderid) . '" />
		<input type="hidden" name="notify_url" data-value-ims="' . $this->get_permalink($this->imspage) . '" />
		<input type="hidden" name="currency_code" data-value-ims="' . esc_attr($this->opts['currency']) . '" />
		<input type="hidden" name="cancel_return" data-value-ims="' . $this->get_permalink($this->imspage) . '" />
		<input type="hidden" name="shipping_1" data-value-ims="' . $this->cart['shipping'] . '" />
		<input type="hidden" name="business" data-value-ims="' . ( isset($this->opts['paypalname']) ? esc_attr($this->opts['paypalname']) : '' ) . '" />
		<input type="hidden" name="discount_amount_cart" data-value-ims="' . ( isset($this->cart['promo']['discount']) ? esc_attr($this->cart['promo']['discount']) : '' ) . '" />
		<input type="hidden" name="cbt" data-value-ims="' . esc_attr(sprintf(__('Return to %s', $this->domain), get_bloginfo('name'))) . '" />';

		$output .= apply_filters('ims_cart_paypal_hidden_fields', '', $this->cart);

	endif;

	//custom
	if ($this->opts['gateway']['custom'] && !empty($this->opts['data_pair'])) :

		if (empty($this->cart['tax']))
			$this->cart['tax'] = '';

		if (empty($this->cart['promo']['code']))
			$this->cart['promo']['code'] = '';

		if (empty($this->cart['promo']['discount']))
			$this->cart['promo']['discount'] = '';

		$cart_replace = array(
			__('%cart_id%', $this->domain) => $this->orderid,
			__('%cart_tax%', $this->domain) => $this->cart['tax'],
			__('%cart_total%', $this->domain) => $this->cart['total'],
			__('%cart_shipping%', $this->domain) => $this->cart['shipping'],
			__('%cart_currency%', $this->domain) => $this->opts['currency'],
			__('%cart_subtotal%', $this->domain) => $this->cart['subtotal'],
			__('%cart_status%', $this->domain) => get_post_status($this->orderid),
			__('%cart_discount%', $this->domain) => $this->cart['promo']['discount'],
			__('%cart_discount_code%', $this->domain) => $this->cart['promo']['code'],
			__('%cart_total_items%', $this->domain) => $this->cart['items'],
		);

		foreach ($data_pair as $key => $sub) {
			if (isset($cart_replace[$sub]))
				$output .= "\n" . '<input type="hidden" name="' . $key . '" data-value-ims="' . esc_attr($cart_replace[$sub]) . '" />';
			elseif (!preg_match('/%image_/', $sub))
				$output .= "\n" . '<input type="hidden" name="' . $key . '" data-value-ims="' . esc_attr($sub) . '" />';
		}

	endif;

	$output .= apply_filters('ims_cart_hidden_fields', '', $this->cart);
	$output .= '<input type="hidden" name="_xmvdata" data-value-ims="' . esc_attr( $this->cart['total'] ) . '" />';
	$output .= '<input type="hidden" name="_wpnonce" data-value-ims="' . wp_create_nonce("ims_submit_order") . '" />';

endif; //end if table
$output .= '</form><!--.ims-cart-form-->'; //endform