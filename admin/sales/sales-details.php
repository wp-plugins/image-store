<?php

	/**
	 * Image Store - Sales Details
	 *
	 * @file sales-details.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/sales/sales-details.php
	 * @since 0.5.0
	 */
	 
	if( !current_user_can( 'ims_read_sales' ) ) 
		die( );
		
	$this->orderid 	= intval ($_GET['id'] );
	$this->sale		= get_post( $this->orderid ); 
	$this->cart 		= get_post_meta( $this->orderid,'_ims_order_data',true );
	$this->data 		= get_post_meta( $this->orderid,'_response_data',true ); 
	
	?>
	
    <form method="get" action="">
    
        <table class="widefat post fixed imstore-table store-detail">
            <thead>
                <tr>
                    <th scope="col" class="column-thumb">
                        <input type="button" onclick="javascript:window.print()" class="print-bt button" value="<?php _e('Print', 'ims')?>"  />
                    </th>
                    <th scope="col" colspan="6">
                        <span class="quantity"><?php _e('Quantity', 'ims')?></span>
                        <span class="size"><?php _e('Size', 'ims')?></span>
                        <span class="color"><?php _e('Color', 'ims')?></span>
                        <span class="fisnish"><?php _e('Finish', 'ims')?></span>
                        <span class="price"><?php _e('Unit Price', 'ims')?></span>
                        <span class="subtotal"><?php _e('Subtotal', 'ims')?></span>
                        <span class="title"><?php _e('Title', 'ims')?></span>
                        <span class="imageid"><?php _e('Image ID', 'ims')?></span>
                        <span class="gallery"><?php _e('Gallery', 'ims')?></span>
                    </th>
                </tr>
            </thead>
            <tbody id="details" class="list:details sales-details">
            <?php
                foreach( $this->cart['images'] as $id => $sizes ){
                    
					$parentid  = $this->get_post_parent_id( $id );
                    $mini  		= array('url' => false, 'width' => false, 'height' => false, 'file' => false);
                    
                    if( $image = (array) get_post_meta( $id, '_wp_attachment_metadata', true )){
						if( isset( $image['sizes']['mini'] ) )
                        	$mini = wp_parse_args( $image['sizes']['mini'], $mini );
					}
                    
                    $r = '<tr><td class="column-thumb">
					<img src="' . esc_attr( $mini['url'] ) . '" width="'. esc_attr( $mini['width'] ) . ' " height="' . esc_attr( $mini ['height'] ) . '" alt="' . esc_attr( $mini['file'] ) .'"/></td>';
                    $r .= '<td colspan="6">';
                    
                    foreach( $sizes as $size => $colors ){
                        foreach( $colors as $color => $item ){
							
							$finishprice = isset( $item['finish'] ) ? $item['finish']: 0;
							$finishname = isset( $item['finish_name'] ) ? $item['finish_name']: '';
							
							if( isset( $item['color_name'] ) )
								$colorname = $item['color_name'];
							else  $colorname = isset( $this->color[$color] ) ? $this->color[$color] : '';
							$colorprice = isset( $item['color'] ) ? $item['color']: 0;
							
                            $r .= '<div class="clear-row">';
                            $r .= '<span class="quantity">' .  $item['quantity'] . '</span>';
                            $r .= '<span class="size">' . ( isset($item['size']) ?$item['size']:$size). '</span>';
                            $r .= '<span class="color">' . $colorname . $this->format_price( $colorprice, true, ' + ' ) . '</span>';
							$r .=  '<span class="fisnish">' . $finishname . $this->format_price( $finishprice,  true, ' + ' ) . '</span>';
                            $r .= '<span class="price">' . $this->format_price( $item['price'] ) . '</span>';
                            $r .= '<span class="subtotal">' . $this->format_price( $item['subtotal'] ) . '</span>';
                            $r .= '<span class="title">' .  get_the_title($id) . '</span>';
                            $r .= '<span class="imageid">' .  sprintf( "%05d", $id ) . '</span>';
                            $r .= '<span class="gallery"><a href="' . esc_attr( get_edit_post_link( $parentid ) ) . '">';
                            $r .= '' .  get_the_title( $parentid ) . '</a></span>';
                            $r .= '</div>';	
                        }
                    }
                    
                    echo $r .= '</td></tr>';
                }
            ?>
            <?php 
            if( empty( $this->data['data_integrity'] ) && $this->sale->post_status == 'pending' ): ?>
            <tr class="not-verified form-invalid error">
                    <td colspan="7"><strong>
                    <?php _e( "Review payment information for this order before shipping items,
                     the data provided by the gateway couldn't be verified. To remove message change the order status" , 'ims')?>
                    </strong></td>
            </tr>
            <?php endif ?>
            <?php do_action( 'ims_before_sales_details', $this->sale, $this->cart, $this->data );?>
            <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Method', 'ims')?></td>
                    <td scope="row" ><?php if( isset( $this->data['method'] ) ) echo wp_strip_all_tags( $this->data['method'] ) ?></td>
                    <td>&nbsp;</td>
                    <td><?php _e('Payment Status', 'ims')?></td>
                    <td scope="row"><?php if( isset( $this->data['payment_status'] ) ) echo wp_strip_all_tags( $this->data['payment_status'] ) ?></td>
                    <td>&nbsp;</td>
            </tr>
            <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Date', 'ims')?></td>
                    <td><?php echo date_i18n( $this->dformat, strtotime( $this->sale->post_date ) )?></td>
                    <td>&nbsp;</td>
                    <td><?php _e('Item subtotal', 'ims')?></td>
                    <td><span class="total"><?php echo $this->format_price( $this->cart['subtotal'] )?></span></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Order number', 'ims')?></td>
                    <td><?php echo $this->data['txn_id']?></td>
                    <td >&nbsp;</td>
                    <td ><?php _e('Promotional code', 'ims')?></td>
                    <td><span class="total promo-code"><?php if( isset( $this->cart['promo']['code'] ) ) echo $this->cart['promo']['code'] ?></span></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Customer', 'ims')?></td>
                    <td><?php echo $this->data['last_name'].' '.$this->data['first_name']?></td>
                    <td>&nbsp;</td>
                    <td><?php _e('Shipping', 'ims')?></td>
                    <td><span class="shipping"><?php echo $this->format_price( $this->cart['shipping'] )?></span></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Shipping Adress', 'ims')?></td>
                    <td><?php 
						if( isset( $this->data['address_street'] ) ) 
							echo $this->data['address_street'];
						else if ( isset( $this->data['ims_address']))
							echo $this->data['ims_address']; 
					?></td>
                    <td>&nbsp;</td>
                    <td><?php _e('Discount', 'ims')?></td>
                    <td> <?php if ( isset($this->cart['promo']['discount']) ) echo $this->format_price( $this->cart['promo']['discount'] ) ?></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Shipping Adress 2', 'ims')?></td>
                    <td colspan="2">
                   
				    <?php
                    foreach( array( 
						'ims_city', 'ims_state', 'ims_zip', 'ims_contry', 
						'address_city', 'address_state', 'address_zip', 'address_country' 
					) as $key ){
                        if( ! empty( $this->data[$key]  ) ) {
							if( in_array( $key, array( 'address_country', 'ims_contry' ) ) )
                            	echo esc_html( $this->data[$key] );
							else  echo esc_html( $this->data[$key] ) . ", ";
						}
                    }
                    ?>
					
                    </td>
                    <td><?php _e( 'Tax', 'ims' )?></td>
        
                    <td><?php if( isset( $this->cart['tax'] ) ) echo $this->format_price( $this->cart['tax'] , true, ' + ' ) ?></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td scope="row">&nbsp;</td>
                    <td><?php _e( 'E-mail', 'ims')?></td>
                    <td colspan="2">
                    <?php
                    if ( isset( $this->data['payer_email'])  ) 
                        echo esc_html( $this->data['payer_email'] );
                    elseif( isset( $this->data['user_email']) ) 
                        echo esc_html( $this->data['user_email'] )
                    ?>
                    </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                  <tr>
                    <td scope="row">&nbsp;</td>
                    <td><?php _e( 'Phone', 'ims' )?></td>
                    <td colspan="2">
                    <?php if( isset( $this->data['ims_phone']) ) echo  $this->data['ims_phone']?>
                    </td>
                    <td>&nbsp; </td>
                    <td>&nbsp;</td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php //_e('Gallery ID', 'ims')?>&nbsp;</td>
                    <td><?php //echo get_post_meta($order->post_parent,'_ims_gallery_id',true)?>&nbsp;</td>
                    <td>&nbsp;</td>
                    <td><?php _e('Total', 'ims')?></td>
                    <td><span class="total"><?php echo $this->format_price( $this->cart['total'] )?></span></td>
                    <td>&nbsp;</td>
                </tr>
                <tr>
                    <td class="column-thumb" scope="row">&nbsp;</td>
                    <td><?php _e('Additional Instructions', 'ims')?></td>
                    <td scope="row" colspan="5">
					<?php if( isset( $this->data['instructions'] ) )
					 echo wp_strip_all_tags( $this->data['instructions'] ) 
					 ?></td>
                </tr>
                <tr><td scope="row" colspan="7">&nbsp;</td></tr>
    			<?php do_action( 'ims_after_sales_details', $this->sale, $this->cart, $this->data );?>
            </tbody>
        </table>
         
    </form>