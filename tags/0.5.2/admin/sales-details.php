<?php 

/**
 * Sales details page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_read_sales' ) ) 
	die( );

$order_id 	= intval( $_GET['id'] );
$sym 		= $this->opts['symbol']; 
$loc 		= $this->opts['clocal'];
$date_format= get_option( 'date_format' );
$format 	= array( '', "$sym%s", "$sym %s", "%s$sym", "%s $sym");
$order 	= get_post( $order_id ); 
$data 		= get_post_meta( $order_id, '_response_data', true ); 
$cart  	= get_post_meta( $order_id, '_ims_order_data', true );
$colors_options = array(
	'ims_sepia' => __( 'Sepia + ', ImStore::domain ),	
	'color' 	=> __( 'Full Color', ImStore::domain ),	
	'ims_bw' 	=> __( 'B &amp; W + ', ImStore::domain )
);
?>

<div class="wrap imstore">
	<?php screen_icon( 'sales' )?>
	<h2><?php _e( 'Sales', ImStore::domain )?></h2>
	
		
	<div id="poststuff" class="metabox-holder">
		<form method="get" action="">
			<table class="widefat post fixed imstore-table store-detail">
				<thead>
					<tr>
						<th scope="col" class="column-thumb">&nbsp;</th>
						<th scope="col" colspan="6">
							<span class="quantity"><?php _e( 'Quantity', ImStore::domain )?></span>
							<span class="size"><?php _e( 'Size', ImStore::domain )?></span>
							<span class="color"><?php _e( 'Color', ImStore::domain )?></span>
							<span class="price"><?php _e( 'Unit Price', ImStore::domain )?></span>
							<span class="subtotal"><?php _e( 'Subtotal', ImStore::domain )?></span>
							<span class="imageid"><?php _e( 'Image ID', ImStore::domain )?></span>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php $i=1; foreach( $cart['images'] as $id => $sizes ){?>
					<?php $image = get_post_meta( $id, '_wp_attachment_metadata', true )?>
					<tr>
						<td class="column-thumb">
						<img src="<?php echo $image['sizes']['mini']['url']?>" width="<?php echo $image['sizes']['mini']['width']?>" height="<?php echo $image['sizes']['mini']['height']?>" alt="<?php echo $image['sizes']['mini']['file']?>"/>
						</td>
						<td colspan="6">
						<?php foreach( $sizes as $size => $colors ){?>
							<?php foreach( $colors as $color => $item ){?>
							<div class="clear-row">
								<span class="quantity"><?php echo $item['quantity']?></span>
								<span class="size"><?php echo $size?></span>
								<span class="color"><?php echo $colors_options[$color] . $item['color'] ?></span>
								<span class="price"><?php printf( $format[$loc], number_format( $item['price'], 2 ) )?></span>
								<span class="subtotal"><?php printf( $format[$loc], number_format( $item['subtotal'], 2 ) )?></span>
								<span class="imageid"><?php echo sprintf( "%05d", $id )?></span>
							</div>
							<?php $i++; }?>
						<?php }?>
						</td>
					</tr>
					<?php }?>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Date', ImStore::domain )?></td>
						<td><?php echo date_i18n( $date_format, strtotime( $order->post_date ) )?></td>
						<td>&nbsp;</td>
						<td><?php _e( 'Item subtotal',ImStore::domain )?></td>
						<td><span class="total"><?php printf( $format[$loc], number_format( $cart['subtotal'], 2 ) )?></span></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Order number', ImStore::domain ) ?></td>
						<td><?php echo $data['txn_id']?></td>
						<td >&nbsp;</td>
						<td ><?php _e( 'Promotional code',ImStore::domain )?></td>
						<td><span class="total promo-code"><?php echo $cart['promo']['code']?></span></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Customer', ImStore::domain )?></td>
						<td><?php echo $data['last_name'] . ' ' . $data['first_name']?></td>
						<td>&nbsp;</td>
						<td><?php _e( 'Shipping', ImStore::domain )?></td>
						<td><span class="shipping"><?php echo $cart['shipping'] ?></span></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Shipping Adress', ImStore::domain )?></td>
						<td><?php echo $data['address_street'] ?></td>
						<td>&nbsp;</td>
						<td><?php _e( 'Discount',ImStore::domain ) ?></td>
						<td> <?php echo( $cart['promo']['discount'] ) ? sprintf ( '- ' .$format[$loc], number_format( $cart['promo']['discount'], 2 ) ) : '' ?></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Shipping Adress 2', ImStore::domain )?></td>
						<td colspan="2">
						<?php echo $data['address_city'] . ", ". $data['address_state'] . ", ". $data['address_zip'] . ", ". $data['address_country'] ?>
						</td>
						<td><?php _e( 'Tax',ImStore::domain ) ?></td>
						<td><?php echo( $cart['tax'] ) ? sprintf( '+ ' . $format[$loc], number_format( $cart['tax'], 2 ) ) : '' ?></td>
						<td>&nbsp;</td>
					</tr>
					<tr>
						<td class="column-thumb" scope="row">&nbsp;</td>
						<td><?php _e( 'Gallery ID', ImStore::domain )?></td>
						<td><?php echo get_post_meta( $order->post_parent, '_ims_gallery_id', true ) ?></td>
						<td>&nbsp;</td>
						<td><?php _e( 'Total',ImStore::domain )?></td>
						<td><span class="total"><?php printf( $format[$loc], number_format( $cart['total'], 2 ) ) ?></span></td>
						<td>&nbsp;</td>
					</tr>
					<tr><td scope="row" colspan="7">&nbsp;</td></tr>
				</tbody>
			</table>
		</form>
	</div>
	
	
</div>
