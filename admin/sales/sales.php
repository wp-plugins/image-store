<?php

	/**
	 * Image Store - Sales Report List
	 *
	 * @file sales.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/sales/sales.php
	 * @since 0.5.0
	 */
	 
	 if ( !current_user_can( 'ims_read_sales' ) )
		die( );
	
	$integrety = '';
	$css = ' alternate';
	$page = empty( $_GET['p'] ) ? 1 : ( int ) $_GET['p'];
	$nonce = '_wpnonce=' . wp_create_nonce( 'ims_manage_sales' );
	
	$order_status = apply_filters( 'ims_order_status', array(
		'pending' => __( 'Pending', 'ims' ),
		'closed' => __( 'Closed', 'ims' ),
		'shipped' => __( 'Shipped', 'ims' ),
		'cancelled' => __( 'Cancelled', 'ims' ),
		'trash' => __( 'Trash', 'ims' ),
		'delete' => __( 'Delete Permanently', 'ims' ),
	) );
	
	$payment_status = apply_filters( 'ims_payment_status', array(
		'void' => __( 'Void', 'ims' ),
		'failed' => __( 'Failed', 'ims' ),
		'expired' => __( 'Expired', 'ims' ),
		'denied' => __( 'Denied', 'ims' ),
		'pending' => __( 'Pending', 'ims' ),
		'denided' => __( 'Denied', 'ims' ),
		'refunded' => __( 'Refunded', 'ims' ),
		'reviewing' => __( 'Reviewing', 'ims' ),
		'processed' => __( 'Processed', 'ims' ),
		'completed' => __( 'Completed', 'ims' ),
		'in_progress' => __( 'In Progress', 'ims' ),
	) );
	
	$args = apply_filters( 'ims_pre_get_sales', array(
		'paged' => $page,
		'post_type' => 'ims_order',
		'post_status' => $this->status,
		'year' => substr( $this->cdate, 0, 4 ),
		'monthnum' => substr( $this->cdate, -2 ),
		'posts_per_page' => $this->per_page,
		'meta_query' => array( array(
			'compare' => 'LIKE',
			'key' => '_response_data', 
			'value' => $this->payment_status,
		) )
	) );
	
	$sales = new WP_Query( $args );
	$start = ($page - 1) * $this->per_page;
	
	$page_links = paginate_links( array(
		'prev_text' => __('&laquo;'),
		'next_text' => __('&raquo;'),
		'total' => $sales->max_num_pages,
		'base' => $this->pageurl . '%_%#ims_images_box',
		'format' => "&m=$this->cdate&status=$this->status&p=%#%",
		'current' => $page,
	) );
	
	?>
    
    <div class="filter">
        <form id="list-filter" action="" method="get">
            <ul class="subsubsub">
                <?php $this->count_links( $order_status, array( 'type' => 'order', 'active' => $this->status, 'all' => true ) ) ?>
            </ul>
        </form>
    </div><!--.filter-->
    
    
    <form id="posts-filter" action="<?php echo $this->pageurl ?>" method="get">
    
    	<div class="tablenav">
            <div class="alignleft actions">
                <select name="order-action">
                    <option value=""><?php esc_attr_e( 'Order Status', 'ims' ) ?></option>
                    <?php
                    foreach ( $order_status as $key => $label ){ 
						if ( !$this->is_trash || $key != 'trash' )
                        echo '<option value="', esc_attr( $key ), '" ' . selected( $this->status, $key, false) . ' >', $label, '</option>';
					}
                    ?>
                </select>
                
                <?php if ( !$this->is_trash ) { ?>
                <select name="payment-action">
					<option value=""><?php esc_attr_e( 'Payment Status', 'ims' ) ?></option>
                    <?php
					foreach ( $payment_status as $key => $label )
						echo '<option value="', esc_attr( $key ), '" ' . selected( $this->payment_status, $key, false) . ' >', $label, '</option>';
					?>
                </select>
                <?php } ?>
                
                <input type="submit" name="doaction" value="<?php esc_attr_e( 'Apply', 'ims' ) ?>" class="button-secondary action" />
                
                <select name="m">
					<option value=""><?php esc_attr_e( 'Select order date', 'ims' ) ?></option>
                    <?php foreach ( $this->order_archive( ) as $archive ){
						$val = date( 'Ym', $archive->t );
						echo '<option value="', esc_attr( $val ), '"', selected( $val, $this->cdate, false ), '>', date_i18n( 'F Y', $archive->t ), '</option>';
					}?>
                </select>
                
                <input type="submit" value="<?php _e( 'Filter', 'ims' ) ?>" class="button" />
                <a href="<?php echo IMSTORE_ADMIN_URL . "/sales/sales-csv.php?$nonce"?>" class="button"><?php _e( 'Download CSV', 'ims' ) ?></a>

             </div><!--.actions-->
         </div><!--.tablenav-->
         
         
    
        <table class="widefat post fixed imstore-table">
            <thead>
                <tr class="thead">
                <?php print_column_headers('ims_gallery_page_ims-sales') ?>
                </tr>
            </thead>
            <tbody id="sales" class="list:sales sales-list">
            <?php foreach ( $sales->posts as $sale ) {
				
				$css = ( ' alternate' == $css ) ? '' : ' alternate';
				$data = get_post_meta( $sale->ID, '_response_data', true );
				$cart = get_post_meta( $sale->ID, '_ims_order_data', true );
				
				if( empty( $data['txn_id']  ) ) $data['txn_id']  = __( 'No order id', 'ims' );
				$integrety = ( empty( $data['data_integrity'] ) && $sale->post_status == 'pending' ) ? ' not-verified' : '';
				$payment = ( isset( $data['payment_status'] ) ) ? trim( strtolower( $data['payment_status'] ) ) : 'pending';
				
				$r = "<tr id='order-$sale->ID' class='order-edit{$css}{$integrety}'>";
					foreach ( $this->columns as $columnid => $column_name ) {
						
						$hide = ( $this->in_array( $columnid, $this->hidden ) ) ? ' hidden' : '';
						switch ( $columnid ) {
							case 'cb':
							$r .= "<th scope='row' class='check-column'><input type='checkbox' name='orders[]' value='" . esc_attr( $sale->ID ) . "' /></th>";
							break;
						case 'ordernum':
							$r .= "<td class='column-{$columnid}{$hide}'>" .
							( ( $this->is_trash ) ? $data['txn_id'] : '<a href="' . $this->sales_link( $sale->ID ) . '">' . $data['txn_id'] . '</a>' ) . "</td>";
							break;
						case 'orderdate':
							$r .= "<td class='column-{$columnid}{$hide}'>" . date_i18n( $this->dformat, strtotime( $sale->post_date ) ) . "</td>";
							break;
						case 'amount':
							$r .= "<td class='column-{$columnid}{$hide}'>" . ( isset( $data['payment_gross'] ) ? $this->format_price($data['payment_gross'] ) : '' ) . "</td>";
							break;
						case 'customer':
							$r .= "<td class='column-{$columnid}{$hide}'>" . 
							( isset( $data['last_name'] ) ? $data['last_name'] : '' ) . ' ' . ( isset( $data['first_name'] ) ? $data['first_name'] : '' ) . "</td>";
							break;
						case 'images':
							$r .= "<td class='column-{$columnid}{$hide}'>" . ( isset( $cart['items'] ) ? $cart['items'] : '' ) . "</td>";
							break;
						case 'paystatus':
							$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $payment_status[$payment] ) ? '' : $payment_status[$payment] ) . "</td>";
							break;
						case 'orderstat':
							$r .= "<td class='column-{$columnid}{$hide}'>" . ( isset( $sale->post_status ) ? $order_status[$sale->post_status] : '' ) . "</td>";
							break;
						}
					}
					
				echo $r .= "</tr>";
           	} ?>
            </tbody>
        </table><!--.imstore-table-->
        
        
        
         <div class="tablenav">
            <div class="tablenav-pages">
            <?php if ( $page_links )  echo sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s', 
				number_format_i18n( $start + 1 ), number_format_i18n( min( $page * $this->per_page, $sales->found_posts ) ), 
				'<span class="total-type-count">' . number_format_i18n( $sales->found_posts ) . '</span>', $page_links
			);?>
            </div><!--.tablenav-pages-->
        </div><!--.tablenav-pages-->
        
        
        <?php wp_nonce_field( 'ims_orders' ) ?>
        
        
		<input type="hidden" name="post_type" value="ims_gallery" />
		<input type="hidden" name="page" value="<?php echo esc_attr( $this->page ) ?>" />
        <input type="hidden" name="status" value="<?php echo esc_attr( $this->status ) ?>" />
        
	</form>