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
		<div class="ims-table" role="grid">
			<div class="ims-subrows ims-header">
				<span class="ims-preview">' . esc_html__( 'Image', 'ims' ) . '</span>
				<div class="ims-clear-row">
					<span class="ims-quantity">' . esc_html__( 'Quantity', 'ims' ) . '</span>
					<span class="ims-size">' . esc_html__( 'Size', 'ims' ) . '</span>
					<span class="ims-color">' . esc_html__( 'Color', 'ims' ) . '</span>
					<span class="ims-fisnish">' . esc_html__( 'Finish', 'ims' ) . '</span>
					<span class="ims-price">' . esc_html__( 'Unit Price', 'ims' ) . '</span>
					<span class="ims-subtotal">' .esc_html__( 'Subtotal', 'ims' ) . '</span>
					<span class="ims-delete">' . esc_html__( 'Delete', 'ims' ) . '</span>
				</div>
			</div><!--.ims-header-->';
			
		$row = 1;
		
		//image loop
		foreach ( $this->cart['images'] as $imageid => $sizes )
			$output .= $ImStoreCart->image_row( $imageid, $sizes, $row );
		
		$output .= apply_filters( 'ims_cart_image_list', '', $this );
		$output .= '<div class="ims-footer">'; //start tfoot
		
		//display subtotal
		$output .= '<div role="row" class="ims-subrows subtotal-row">
			<span role="gridcell" class="ims-empty">&nbsp;</span>
			<span role="gridcell" class="ims-th"><label>' . __( 'Item subtotal', 'ims' ) . '</label></span>
			<span role="gridcell" class="subtotal">' . $this->format_price( $this->cart['subtotal'] ) . '</span>
		</div>';
		
		//promotional code
		$output .= '<div role="row" class="ims-subrows promo-row">
			<span role="gridcell" class="ims-empty">&nbsp;</span>
			<span role="gridcell" class="ims-th"><label for="ims-promo-code">' . __( 'Promotional code', 'ims' ) . '</label></span>
			<span role="gridcell" class="total promo-code">
			<input name="promocode" id="ims-promo-code" type="text" value="' . esc_attr( $this->cart['promo']['code'] ) . '" />
			<span class="ims-break"></span> <small>' . __( 'Update cart to apply promotional code.', 'ims' ) . '</small></span>
		</div>';
		
		//display discounted data
		if ( $this->cart['promo']['discount'] )
			$output .= '<div role="row" class="ims-subrows discount-row">
			<span role="gridcell" class="ims-empty">&nbsp;</span>
			<span role="gridcell" class="ims-th">' . __( 'Discount', 'ims' ) . '</span>
			<span role="gridcell" class="discount">' . $this->format_price( $this->cart['promo']['discount'], true, ' - ' ) . 
		'</span></div>';
		
		//shipping charge
		if( $this->cart['shippingcost'] )
			$output .= '<div role="row" class="ims-subrows shipping-row">
			<span role="gridcell" class="ims-empty">&nbsp;</span>
			<span role="gridcell" class="ims-th"><label for="shipping">' . __( 'Shipping', 'ims' ) . '</label></span>
			<span role="gridcell" class="shipping">' . $ImStoreCart->shipping_options( ) . 
		'</span></div>';
		
		//display tax fields
		if ( $this->cart['tax'] ) 
			$output .= '<div role="row" class="ims-subrows tax-row">
			<span role="gridcell" class="ims-empty">&nbsp;</span>
			<span role="gridcell" class="ims-th">' . __( 'Tax', 'ims' ) . '</span>
			<span role="gridcell" class="tax">' . $this->format_price( $this->cart['tax'], true, ' + ' ) . 
			'<input type="hidden" name="tax_cart" data-value-ims="' . $this->format_price( $this->cart['tax'], false ) . '"/> </span></div>';
		
		//display total
		$output .= '<div role="row" class="ims-subrows total-row">
		<span role="gridcell" class="ims-empty">&nbsp;</span> 
		<span role="gridcell" class="ims-th"><label>' . __( 'Total', 'ims' ) . '</label></span>
		<span role="gridcell" class="total">' . $this->format_price( $this->cart['total'] ) . ' </span></div>';
		
		//display notification
		$output .= '<div role="row" class="ims-subrows">
		<span role="gridcell" class="ims-empty">&nbsp;</span><span role="gridcell"><label>' . __( 'Additional Instructions', 'ims' ) . '<br />
		<textarea name="instructions" class="ims-instructions">' . esc_html( $this->cart['instructions'] ) . '</textarea></label></span></div>';
		
		//display buttons
		$output .= '<div role="row" class="ims-checkout-fileds"><span role="gridcell" class="ims-empty">&nbsp;</span><span role="gridcell">';
		$output .= '<input name="ims-apply-changes" type="submit" value="' . esc_attr__( 'Update Cart', 'ims' ) . '" class="secondary" />';
		
		$output .= '<span class="ims-bk"></span>';
		$output .= '<span class="ims-cart-actions"><span class="ims-checkout-label">' . __( 'Checkout using:', 'ims' ) . ' </span>';
		$output .= apply_filters( 'ims_store_cart_actions', '', $this->cart ) . '</span></span></div>';
		
		$output .= '</div><!--.ims-footer-->
		</div><!--.ims-table-->'; //end table*/
	
		//terms and conditions
		$output .= '<div class="ims-terms-condtitions">' .  $this->opts['termsconds'] . '</div>';
		
		$output .= apply_filters( 'ims_cart_hidden_fields', '', $this->cart );
		$output .= '<input type="hidden" name="_xmvdata" data-value-ims="' . esc_attr( $this->cart['total'] ) . '" />';
		$output .= '<input type="hidden" name="_wpnonce" data-value-ims="' . wp_create_nonce( "ims_submit_order" ) . '" />';
	
	endif;

	$output .= '</form><!--.ims-cart-form-->'; //endform
