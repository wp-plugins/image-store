<?php

	/**
	 * Image Store - Customer purchased images List
	 *
	 * @file customer-images.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/customers/customer-images.php
	 * @since 3.3.0
	 */
	 
	if ( !current_user_can( 'ims_read_galleries' ) )
		die( );
	
	$style = '';
	global $user_ID;
	
	$args  = array(  'post_type' => 'none' );
	$search = isset( $_GET['s'] ) ? $_GET['s'] : NULL;
	$page = empty( $_GET['p'] ) ? 1 : ( int ) $_GET['p'];
	
	$hidden 	= get_hidden_columns( 'ims_gallery_page_ims-images' );
	$nonce 	= "_wpnonce=" . wp_create_nonce( "ims_download_img" );

	if( $user_images = get_user_meta( $user_ID, "_ims_user_{$user_ID}_images", true ) ){
		foreach( $user_images as $imageid => $sizes )
			$imageids[] = $imageid;
		$args = array( 
			'orderby' => 'post__in', 
			'post_type' => 'ims_image', 
			'posts_per_page' => $this->per_page,
			'post__in' => array_reverse($imageids), 
		);
	}

	$images = new WP_Query( apply_filters( 'ims_pre_get_customer_images', $args ) );

	$start = ( $page - 1 ) * $this->per_page;
	$page_links = paginate_links( array(
		'base' => $this->pageurl . '%_%',
		'format' => '&p=%#%',
		'prev_text' => __( '&laquo;', 'ims'),
		'next_text' => __( '&raquo;', 'ims'),
		'total' => $images->max_num_pages,
		'current' => $page,
	) );
	?>
    	
    <div id="poststuff" class="metabox-holder">
        <form method="get" action="<?php echo $this->pageurl ?>#poststuff">
        
            <table class="widefat post fixed imstore-table image-list">
                <thead>
                    <tr class="thead">
                    <?php print_column_headers( 'profile_page_user-images' )?>
                    </tr>
                </thead>
                <tbody>
                	<?php
					foreach( $images->posts as $image ) {
						
						if( get_post_status( $image->post_parent) != 'publish' )
								continue;
							
						$enc = $this->url_encrypt( $image->ID );
						$style = ( ' alternate' == $style ) ? '' : ' alternate';
						$r = "<tr id='image-{$image->ID}' class='image{$style}'>";
						
						$r .= '
						<td  class="column-image"><a href="' . $this->baseurl . $this->url_encrypt( "{$image->ID}:1:1" ). '" class="thickbox" >
							<img role="img" src="' . $this->baseurl . $this->url_encrypt( "{$image->ID}:3" ) . '" title="' . esc_attr( $image->post_title ) . '" alt="' . esc_attr( $image->post_title ) . '" />
						</a></td>';
								
						$r .= '<td role="gridcell" class="ims-subrows" colspan="5">';
						foreach ( $user_images[$image->ID] as $size => $colors ) {
							foreach ( $colors as $color => $item ) {
								
								//backwards compatibilty allow old images to be downloaded by default
								$status = 'completed';	
								
								$downlink = '';
								if( isset( $item['orderid'] ) ){
									$data = get_post_meta( $item['orderid'], '_response_data', true );
									if( isset( $data['payment_status'] ) )
										$status = $data['payment_status'];
								}
								
								if( $item['download'] && $status == 'completed' ){
									$downlink = '<a href="' . esc_attr( IMSTORE_ADMIN_URL ) . "/download.php?$nonce&amp;img=" . 
									$enc . "&amp;sz=$size&amp;usr=1&amp;c=" . $item['color_code'] . '" >' . __( 'Download', 'imgs' ) . "</a>";
								}
								
								$r .= '<div class="ims-clear-row">';
								$r .= '<span class="column-gallery"><a href="'.  get_permalink( $image->post_parent ) .'" target="_blank">' . get_the_title( $image->post_parent ) . '</a></span>';
								$r .= '<span class="column-size">' . esc_html( $size ) . '</span>';
								$r .= '<span class="column-color">' . esc_html( $item['color_name'] ) . '</span>';
								$r .= '<span class="column-fisnish">' . esc_html( $item['finish_name'] ) . '</span>';
								$r .= '<span class="column-download">' . $downlink . '</span>';
								$r .= '</div><!--.ims-clear-row-->';
							}
						}
						echo $r .= "</td></tr>";
					}
					
					?>
                </tbody>
            </table>
            
            <div class="tablenav">
            	<div class="tablenav-pages">
                
                <?php if( $page_links ) echo sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
					number_format_i18n( $start + 1 ),
					number_format_i18n( min( $page * $this->per_page, $images->found_posts ) ),
					'<span class="total-type-count">' . number_format_i18n( $images->found_posts ) . '</span>',
					$page_links
				) ?>
                
                </div><!--.tablenav-pages-->
            </div><!--.tablenav-->
            
        </form>
    </div>
	