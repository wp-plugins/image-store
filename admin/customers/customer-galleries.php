<?php

	/**
	 * Image Store - Customer Galleries List
	 *
	 * @file customer-galleries.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/customers/customer-galleries.php
	 * @since 3.2.1
	 */
	 
	if ( !current_user_can( 'ims_read_galleries' ) )
		die( );
	
	$style = '';
	global $user_ID, $wpdb; 
	
	$search 	= isset( $_GET['s'] ) ? $_GET['s'] : NULL;
	$page		= empty( $_GET['p'] ) ? 1 : ( int ) $_GET['p'];
	$status		= isset( $_GET['status'] ) ? $_GET['status'] : 'publish';	
	
	$columns	= get_column_headers( 'profile_page_user-galleries' ); 
	$hidden 	= get_hidden_columns( 'ims_gallery_page_ims-customers' );
	
	$args = array(
		'paged' => $page,
		'post_status' => $status,
		'post_type' => 'ims_gallery',
		'post_parent' => $this->galid,
		'posts_per_page' => $this->per_page,
		'meta_query' => array(
			array(
				'compare' => 'LIKE',
				'value' => '"' . trim( $user_ID ) . '"',
				'key' => '_ims_customer',
			)
		)
	);
	
	//backwards compatiblity
	if( version_compare( $this->wp_version , '3.1', '<' ) ){
		function add_meta_values( $where ){
			if( strpos( $where, '_ims_customer' ) === false )
				return $where;
			return str_replace( '.meta_value =', '.meta_value LIKE ', $where );
		}
		$args['meta_key'] = '_ims_customer';
		$args['meta_value'] = '%"' . trim($user_ID) . '"%';
		add_filter( 'posts_where', 'add_meta_values' );
	}
	
	$galleries = new WP_Query( apply_filters( 'ims_pre_get_customer_galleries', $args) );
	
	$start = ( $page - 1 ) * $this->per_page;
	$page_links = paginate_links( array(
		'base' => $this->pageurl . '%_%',
		'format' => '&p=%#%',
		'prev_text' => __( '&laquo;', 'ims'),
		'next_text' => __( '&raquo;', 'ims'),
		'total' => $galleries->max_num_pages,
		'current' => $page,
	) );
	
	?>
	
    <div id="poststuff" class="metabox-holder">
        <form method="get" action="<?php echo $this->pageurl ?>#poststuff">
        
            <div class="tablenav">
                <p class="search-box">
                <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ) ?>" />
                <input type="text" id="media-search-input" name="s" value="<?php echo esc_attr( $search )?>" />
                <input type="submit" value="<?php _e( 'Search Galleries', 'ims' )?>" class="button" />
                </p>
            </div><!--.tablenav-->
            
            <table class="widefat post fixed imstore-table">
                <thead>
                    <tr class="thead">
                    <?php print_column_headers( 'profile_page_user-galleries')?>
                    </tr>
                </thead>
                <tbody id="galleries" class="list:galleries galleries-list">
                	<?php
					foreach( $galleries->posts as $gallery ) {
						
						$style = ( ' alternate' == $style ) ? '' : ' alternate';
						$r = "<tr id='gallery-{$gallery->ID}' class='gallery{$style}'>";
						
						foreach ( $columns as $columnid => $column_name ){
							
							$hide = ( $this->in_array( $columnid, $hidden ) ) ? ' hidden' : '';
							switch( $columnid ){
								case 'gallery':
									$r .= "<td class='column-{$columnid}{$hide}'><strong>" .
									 '<a href="' . get_permalink( $gallery->ID ) . '">' . $gallery->post_title . '</a>' . "</strong></td>";
									break;
								case 'galleryid':
									$r .= "<td class='column-{$columnid}{$hide}'>". get_post_meta( $gallery->ID, '_ims_gallery_id', true ) ."</td>";
									break;
								case 'password':
									$r .= "<td class='column-{$columnid}{$hide}'>". $gallery->post_password ."</td>";
									break;
								case 'expire':
									$r .= "<td class='column-{$columnid}{$hide}'>". (( $expires = get_post_meta( $gallery->ID, '_ims_post_expire', true ) ) ? 
									mysql2date( $this->dformat, $expires, true ) : '' ) ."</td>";
									break;
								case 'images':
									$r .= "<td class='column-{$columnid}{$hide}'>".
									$wpdb->get_var( "SELECT COUNT( * ) FROM $wpdb->posts 
									WHERE post_parent = $gallery->ID AND post_status = 'publish' AND post_type = 'ims_image' " ) 
									. "</td>";
									break;
							}
						}
						
						echo $r .= "</tr>";
					}
					?>
                </tbody>
            </table>
            
            <div class="tablenav">
            	<div class="tablenav-pages">
                
                <?php if( $page_links ) echo sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
					number_format_i18n( $start + 1 ),
					number_format_i18n( min( $page * $this->per_page, $galleries->found_posts ) ),
					'<span class="total-type-count">' . number_format_i18n( $galleries->found_posts ) . '</span>',
					$page_links
				) ?>
                
                </div><!--.tablenav-pages-->
            </div><!--.tablenav-->
            
        </form>
    </div>