<?php

	/**
	 * Image Store - Cart Page
	 *
	 * @file cart.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/_store/cart.php
	 * @since 0.5.0
	 */

	// Stop direct access of the file
	if ( !defined( 'ABSPATH' ) )
		die( );
	
	$output .= '<form method="' . esc_attr( $this->opts['gateway_method'] ) . '" class="ims-cart-form" action="#' . apply_filters( 'ims_cart_action', '', $this) . '" >';
	
	//if empty show error
	if ( empty( $this->cart['images'] ) && apply_filters( 'ims_empty_car', true, $this->cart ) ) :
	
		$error = new WP_Error( );
		$error->add( 'empty', __( 'Your shopping cart is empty.', 'ims' ) );
		$output .= $this->error_message( $error, true );
	
	else:
		
		$output .=
		'<noscript><div class="ims-message ims-error">' . __( 'Please enable Javascript, it is required to submit payment.' ) . '</div></noscript>
			<table class="ims-table" role="grid">
			<thead>
				<tr>
					<th scope="col" class="ims-preview">&nbsp;</th>
					<th colspan="2" class="ims-subrows" >
						<span class="ims-quantity">' . __( 'Quantity', 'ims' ) . '</span>
						<span class="ims-size">' . __( 'Size', 'ims' ) . '</span>
						<span class="ims-color">' . __( 'Color', 'ims' ) . '</span>
						<span class="ims-fisnish">' . __( 'Finish', 'ims' ) . '</span>
						<span class="ims-price">' . __( 'Unit Price', 'ims' ) . '</span>
						<span class="ims-subtotal">' . __( 'Subtotal', 'ims' ) . '</span>
						<span class="ims-delete">' . __( 'Delete', 'ims' ) . '</span>
					</th>
				</tr>
			</thead>';
		$output .= '<tbody>';
		
		$row = 1;
		
		//image loop
		foreach ( $this->cart['images'] as $imageid => $sizes )
			$output .= $ImStoreCart->image_row( $imageid, $sizes, $row );
		
		$output .= apply_filters( 'ims_cart_image_list', '', $this );
		$output .= '</tbody><tfoot>'; //end tbody - start tfoot
		
		//display subtotal
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell"><label>' . __( 'Item subtotal', 'ims' ) . '</label></td>
		<td role="gridcell" class="total">' . $this->format_price( $this->cart['subtotal'] ) . '</td></tr>';
		
		//promotional code
		$output .= '<tr role="row">
		<td role="gridcell" >&nbsp;</td><td role="gridcell"><label for="ims-promo-code">' . __( 'Promotional code', 'ims' ) . '</label></td>
		<td role="gridcell" class="total promo-code">
		<input name="promocode" id="ims-promo-code" type="text" value="' . esc_attr( $this->cart['promo']['code'] ) . '" />
		<span class="ims-break"></span> <small>' . __( 'Update cart to apply promotional code.', 'ims' ) . '</small></td>
		</tr>';
		
		//display discounted data
		if ( $this->cart['promo']['discount'] )
			$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell">' . __( 'Discount', 'ims' ) . '</td>
			<td role="gridcell" class="discount">' . $this->format_price( $this->cart['promo']['discount'], true, ' - ' ) . '</td></tr>';
		
		//shipping charge
		if( $this->cart['shippingcost'] )
			$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell"><label for="shipping">' . __( 'Shipping', 'ims' ) . '</label></td>
			<td role="gridcell" class="shipping">' . $ImStoreCart->shipping_options( ) . '</td></tr>';
		
		//display tax fields
		if ( $this->cart['tax'] ) 
			$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell">' . __( 'Tax', 'ims' ) . '</td><td role="gridcell" class="tax">' .
			$this->format_price( $this->cart['tax'], true, ' + ' ) . '<input type="hidden" name="tax_cart" data-value-ims="' . 
			$this->format_price( $this->cart['tax'], false ) . '"/> </td></tr>';
		
		//display total
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td> <td role="gridcell"><label>' . __( 'Total', 'ims' ) . '</label></td>
		<td role="gridcell" class="total">' . $this->format_price( $this->cart['total'] ) . ' </td></tr>';
		
		//display notification
		$output .= '<tr role="row"><td role="gridcell">&nbsp;</td><td role="gridcell" colspan="2"><label>' . __( 'Additional Instructions', 'ims' ) . '<br />
		<textarea name="instructions" class="ims-instructions">' . esc_html( $this->cart['instructions'] ) . '</textarea></label></td></tr>';
		
		$output .= '<tr role="row" class="ims-checkout-fileds"><td role="gridcell">&nbsp;</td><td role="gridcell" colspan="2">';
		$output .= '<input name="ims-apply-changes" type="submit" value="' . esc_attr__( 'Update Cart', 'ims' ) . '" class="secondary" />';
		
		$output .= '<span class="ims-bk"></span>';
		$output .= '<div class="ims-cart-actions"> <span class="ims-checkout-label">' . __( 'Checkout using:', 'ims' ) . ' </span>';
		
		$output .= apply_filters( 'ims_store_cart_actions', '', $this->cart ) . '</div></td></tr>';

		$output .= '</tfoot>
		</table><!--.ims-table-->'; //end table
		
		//terms and conditions
		$output .= '<div class="ims-terms-condtitions">' . esc_html( $this->opts['termsconds'] ) . '</div>';
		
		$output .= apply_filters( 'ims_cart_hidden_fields', '', $this->cart );
		$output .= '<input type="hidden" name="_xmvdata" data-value-ims="' . esc_attr( $this->cart['total'] ) . '" />';
		$output .= '<input type="hidden" name="_wpnonce" data-value-ims="' . wp_create_nonce( "ims_submit_order" ) . '" />';
	
	endif;

	$output .= '</form><!--.ims-cart-form-->'; //endform
