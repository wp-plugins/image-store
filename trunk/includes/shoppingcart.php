<?php 

/**
 * Shopping cart page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0 
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );


$sym 	= $this->opts['symbol']; 
$loc 	= $this->opts['clocal'];
$format = array( '', "$sym%s", "$sym %s", "%s$sym", "%s $sym"); 
$colors_options = array(
	'ims_sepia' => __( 'Sepia + ', ImStore::domain ),	
	'color' 	=> __( 'Full Color', ImStore::domain ),	
	'ims_bw' 	=> __( 'B &amp; W + ', ImStore::domain )
);

?>
<div id="ims-mainbox" class="shopping-cart">
	
	<div class="ims-nav-box"><?php $this->store_nav( )?></div>
	
	<div class="ims-labels">
		<span class="title"><?php echo $this->gallery->post_title?></span>
		<?php if( $this->gallery->post_expire != '0000-00-00 00:00:00' ){ ?>
		<span class="divider"> | </span>
		<span class="expires"><?php 
			echo __( "Expires: ", ImStore::domain ) . date_i18n( get_option( 'date_format' ), strtotime( $this->gallery->post_expire ))
		?></span>
		<?php }?>
	</div>
	
	
	<div class="ims-message<?php echo $css?>">
		<?php if( $this->error ) echo $this->error?>
		<?php if( $this->message ) echo $this->message?>
	</div>
	
	
	<div class="ims-innerbox">
	
	<?php if( empty( $this->cart['images'] ) ) {?>
	
		<div class="ims-message error"><?php _e( 'Your shopping cart is empty!!', ImStore::domain )?></div>
	
	<?php }else{?>
			<form action="" method="post">
			<table class="ims-table">
				<thead>
					<tr>
						<th scope="col" class="preview">&nbsp;</th>
						<th scope="col" colspan="6" class="subrows">
							<span class="quantity"><?php _e( 'Quantity', ImStore::domain )?></span>
							<span class="size"><?php _e( 'Size', ImStore::domain )?></span>
							<span class="color"><?php _e( 'Color', ImStore::domain )?></span>
							<span class="price"><?php _e( 'Unit Price', ImStore::domain )?></span>
							<span class="subtotal"><?php _e( 'Subtotal', ImStore::domain )?></span>
							<span class="delete"><?php _e( 'Delete', ImStore::domain )?></span>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php $i=1; foreach( $this->cart['images'] as $id => $sizes ){?>
					<?php $image = get_post_meta( $id, '_wp_attachment_metadata', true )?>
					<tr>
						<td scope="row" class="preview"><img src="<?php echo $image['sizes']['mini']['url']?>" width="<?php echo $image['sizes']['mini']['width']?>" height="<?php echo $image['sizes']['mini']['height']?>" alt="<?php echo $image['sizes']['mini']['file']?>"/></td>
						<td colspan="6" class="subrows">
						<?php foreach( $sizes as $size => $colors ){?>
							<?php foreach( $colors as $color => $item ){?>
							<div class="clear-row">
								<span class="quantity"><input type="text" name="ims-quantity<?php echo "[{$id}][{$size}][{$color}]"?>" value="<?php echo $item['quantity']?>" class="input" /></span>
								<span class="size"><?php echo $size . ' ' . $item['unit']?></span>
								<span class="color"><?php echo $colors_options[$color] . $item['color'] ?></span>
								<span class="price"><?php printf( $format[$loc], number_format( $item['price'], 2 ) )?></span>
								<span class="subtotal"><?php printf( $format[$loc], number_format( $item['subtotal'], 2 ) )?></span>
								<span class="delete"><input name="ims-remove[]" type="checkbox" value="<?php echo "{$id}|{$size}|{$color}"?>" /></span>
								<input type="hidden" name="on0_<?php echo $i?>" value="<?php echo $size ?>"/>
								<input type="hidden" name="os0_<?php echo $i?>" value="<?php echo $color?>"/>
								<input type="hidden" name="quantity_<?php echo $i?>" value="<?php echo $item['quantity']?>"/>
								<input type="hidden" name="amount_<?php echo $i?>" value="<?php echo $item['price'] + $item['color'];?>"/>
								<input type="hidden" name="item_name_<?php echo $i ?>" value="image-<?php echo sprintf( "%05d", $id );?>"/>
							</div>
							<?php $i++; }?>
						<?php }?>
						</td>
					</tr>
				<?php }?>
				</tbody>
				<tfoot>
					<tr>
						<td scope="row">&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td>&nbsp;</td>
						<td><?php _e( 'Item subtotal',ImStore::domain )?></td>
						<td class="total" colspan="2"><?php printf( $format[$loc], number_format( $this->cart['subtotal'], 2 ) )?></td>
					</tr>
					<tr>
						<td scope="row"colspan="4">&nbsp;</td>
						<td><?php _e( 'Promotional code',ImStore::domain )?></td>
						<td class="total promo-code" colspan="2">
							<input name="promocode" type="text" value="<?php echo $this->cart['promo']['code']?>" />
							<span class="ims-break"></span>
							<small><?php _e( 'Update cart to apply promotional code.',ImStore::domain )?></small>
						</td>
					</tr>
					<tr>
						<td scope="row" colspan="4">&nbsp;</td>
						<td><?php _e( 'Shipping', ImStore::domain )?></td>
						<td colspan="2" class="shipping">
							<?php $meta = get_post_meta( $this->pricelist_id, '_ims_list_opts', true );?>
							<select name="shipping_1" id="shipping_1" class="shipping-opt">
								<option value="<?php echo $meta['ims_ship_local']?>"<?php $this->selected( $meta['ims_ship_local'], $this->cart['shipping'] )?>><?php echo __( 'Local + ', ImStore::domain ) . sprintf( $format[$loc], $meta['ims_ship_local'] )?></option>
								<option value="<?php echo $meta['ims_ship_inter']?>"<?php $this->selected( $meta['ims_ship_inter'], $this->cart['shipping'] )?>><?php echo __( 'International + ', ImStore::domain ) . sprintf( $format[$loc], $meta['ims_ship_inter'] )?></option>
							</select>
						</td>
					</tr>
					<?php if( $this->cart['discounted'] ){ ?>
					<tr>
						<td scope="row" colspan="4">&nbsp;</td>
						<td><?php _e( 'Discount',ImStore::domain )?></td>
						<td colspan="2" class="discount"><?php printf( '- ' .$format[$loc], number_format( $this->cart['promo']['discount'], 2 ) ) ?></td>
					</tr>
					<?php } ?>
					<?php if( $this->cart['tax'] ){ ?>
					<tr>
						<td scope="row" colspan="4">&nbsp;</td>
						<td><?php _e( 'Tax',ImStore::domain )?></td>
						<td colspan="2" class="tax">
							<?php printf( '+ ' . $format[$loc], number_format( $this->cart['tax'], 2 ) ) ?>
							<input type="hidden" name="tax_cart" value="<?php echo number_format( $this->cart['tax'], 2 ) ?>"/>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td scope="row" colspan="4">&nbsp;</td>
						<td><?php _e( 'Total',ImStore::domain )?></td>
						<td colspan="2" class="total"><?php printf( $format[$loc], number_format( $this->cart['total'], 2 ) ) ?></td>
					</tr>
					<tr>
						<td scope="row" colspan="4">&nbsp;</td>
						<td colspan="3">
							<input name="applychanges" type="submit" value="<?php _e( 'Update Cart', ImStore::domain )?>" class="secondary" />
							<input name="<?php echo ($this->opts['gateway'] == 'notification' ) ? 'enotification' : 'checkout' ?>" type="submit" value="<?php _e( 'Check out', ImStore::domain )?>" class="primary" />
						</td>
					</tr>
				</tfoot>
			</table>
			
			<div class="ims-terms-condtitions"><?php echo $this->opts['termsconds'] ?></div>
			
			<input type="hidden" name="ims-total" value="<?php echo $this->cart['total'] ?>" />

			<input type="hidden" name="rm" value="2" />
			<input type="hidden" name="upload" value="1"/>
			<input type="hidden" name="cmd" value="_cart"/>
			<input type="hidden" name="lc" value="<?php echo $this->opts['currency'] ?>" />
			<input type="hidden" name="page_style" value="<?php bloginfo( 'name' )?>"/>
			<input type="hidden" name="custom" value="<?php echo $this->cart_cookie ?>"/>
			<input type="hidden" name="return" value="<?php echo $this->get_permalink( $this->gallery_id, 8 )?>"/>
			<input type="hidden" name="notify_url" value="<?php echo $this->get_permalink( $this->gallery_id, 6 )?>"/>
			<input type="hidden" name="cancel_return" value="<?php echo $this->get_permalink( $this->gallery_id, 7 )?>"/>
			<input type="hidden" name="business" value="<?php echo $this->opts['paypalname']?>" />
			<input type="hidden" name="currency_code" value="<?php echo $this->opts['currency']?>" />
			<input type="hidden" name="_wpnonce" id="_wpnonce" value="<?php echo wp_create_nonce( "ims_submit_order" )?>" />					
			<input type="hidden" name="cbt" value="<?php printf( __( 'Return to %s', ImStore::domain ), get_bloginfo( 'name' ) )?>" />
			<input type="hidden" name="discount_amount_cart" value="<?php printf( __( 'Return to %s', ImStore::domain ), get_bloginfo( 'name' ) )?>" />
		</form>
	<?php }?>
	</div>
	
</div>