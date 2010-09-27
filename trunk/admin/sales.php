<?php 

/**
 * Sales page
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


//update screen options
if( !empty( $_POST['screen_options'] ) ){
	update_user_meta( $user_ID, $_POST['screen_options']['option'], $_POST['screen_options']['value'] );
	wp_redirect( $pagenowurl );	
};


//bulk actions
if( !empty( $_GET['doaction'] ) ){
	check_admin_referer( 'ims_orders' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_orders( );
			break;
		default:
		ims_change_status( );
	}
}

// empty trash
if( isset( $_GET['deleteall'] ) ){
	check_admin_referer( 'ims_orders' );
	empty_orders_trash( );
}


$sym 		= $this->opts['symbol']; 
$loc 		= $this->opts['clocal'];	
$date_format= get_option( 'date_format' );
$orders 	= get_ims_orders( $this->per_page );
$columns 	= get_column_headers( 'image-store_page_ims-sales' );	
$format 	= array( '', "$sym%s", "$sym %s", "%s$sym", "%s $sym"); 
$is_trash	= ( isset( $_GET['status'] ) ) && ( $_GET['status'] == 'trash' );
$hidden 	= implode( '|', get_hidden_columns( 'image-store_page_ims-sales' ) ) ;

$message[1] 	= __( 'Trash emptied', ImStore::domain );
$message[2] 	= __( 'Order deleted.', ImStore::domain );
$message[3] 	= __( 'Order status updated.', ImStore::domain );
$message[4] 	= __( 'Order moved to trash.', ImStore::domain );
$message[5] 	= sprintf( __( '%d orders deleted.', ImStore::domain ), $_GET['c'] );
$message[6] 	= sprintf( __( 'Status updated on %d orders.', ImStore::domain ), $_GET['c'] );
$message[7] 	= sprintf( __( '%d orders moved to trash.', ImStore::domain ), $_GET['c'] );

?>

<div class="wrap imstore">
	<?php screen_icon( 'sales' )?>
	<h2><?php _e( 'Sales', ImStore::domain )?>
	<?php if ( !empty( $_GET['s'] ) )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', ImStore::domain) . '</span>', esc_html( $_GET['s'] ) )?>
	</h2>
	
	<div id="poststuff" class="metabox-holder">
	
	<?php if( !empty($_GET['ms']) ){ ?>
	<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>

	<!--<div id="dashboard-widgets" class="metabox-holder">
	<?php if( !empty($_GET['ms']) ){ ?>
		<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
	
	<div id="dashboard_right_now" class="postbox">
		<div class="handlediv" ><br /></div>
		<h3 class='hndle'><span><?php _e( 'Current Month Overview' , ImStore::domain ) ?></span></h3> 
		<div class="inside">
			<div class="table">
				<p class="sub"><?php echo __( 'Monthly Gross Imcome: $', ImStore::domain ) . number_format( $amount, 2) ?></p>
				<table class="ims-table"> 
					<tr class="first">
						<td class="first b"><a><?php echo 0 ?></a></td>
						<td class="t"><?php _e( 'Photos', ImStore::domain )?></td>
						<td class="b"><a><?php echo 0?></a></td>
						<td class="last"><?php _e( 'Transactions', ImStore::domain )?></td>
					</tr>
					<tr>
						<td class="b"><a><?php echo count( array_unique( (array)$galleries ) )?></a></td>
						<td class="t"><?php _e( 'Galleries', ImStore::domain )?></td>
						<td class="b"><a><?php echo count( $pending )?></a></td>
						<td class="last t waiting"><?php _e( 'Pending Transactions', ImStore::domain )?></td>
					</tr>
					<tr>
						<td class="b"><a><?php echo count( array_unique( (array)$customers ) )?></a></td>
						<td class="t"><?php _e( 'Unique Customers', ImStore::domain )?></td>
						<td class="b"><a><?php echo count( $complete )?></a></td>
						<td class="last t approved"><?php _e( 'Closed Transactions', ImStore::domain )?></td>
					</tr>
				</table>
			</div>
		</div>
	</div>
	</div>-->
	
	<!-- MANAGE ORDERS -->
	
		
		<ul class="subsubsub"><?php $count = ims_order_count_links( )?></ul>
		
		<form method="get" action="<?php echo $pagenowurl ?>">
		
		<input type="hidden" name="page" value="<?php echo $_GET['page']?>" />
		<?php wp_nonce_field( 'ims_orders' )?>
		
		
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option value="">Bulk Actions</option>
					<?php if ( $is_trash ):?>
					<option value="pending"><?php _e( 'Restore', ImStore::domain )?></option> 
					<option value="delete"><?php _e( 'Delete Permanently', ImStore::domain )?></option>
					<?php else:?>
					<option value="pending"><?php _e( 'Pending', ImStore::domain )?></option>
					<option value="shipped"><?php _e( 'Order Shipped', ImStore::domain )?></option>
					<option value="closed"><?php _e( 'Closed Order', ImStore::domain )?></option>
					<option value="trash"><?php _e( 'Move to Trash', ImStore::domain )?></option>
					<?php endif?>
				</select>
				<input type="submit" value="Apply" name="doaction" id="doaction" class="button-secondary action" />
				
				<select name='m'>
					<option value='0'><?php _e( 'Select date created', ImStore::domain )?></option>
					<?php foreach( ims_order_archive( ) as $archive ): $date = strtotime( $archive->y .'-'. $archive->m ) ?>
					<option value="<?php echo date( 'Ym', $date )?>" <?php selected( date( 'Ym', $date ), $_GET['m'] )?> >
					<?php echo date_i18n( $date_format , $date )?></option>
					<?php endforeach?>
				</select>
				
				<input type="submit" value="<?php _e( 'Filter', ImStore::domain )?>" class="button" />
				<?php if ( $is_trash ):?>
				<input type="submit" name="deleteall" value="<?php _e( 'Empty Trash', ImStore::domain )?>" class="button" /> |
				<?php endif?>
				 <a href="<?php echo IMSTORE_ADMIN_URL ?>sales-csv.php" class="button"><?php _e( 'Download CSV' ); ?></a>
			</div>
			
			<p class="search-box">
			<input type="text" id="media-search-input" name="s" value="<?php echo esc_attr( $_GET['s'] )?>" />
			<input type="submit" value="<?php _e( 'Search Orders', ImStore::domain )?>" class="button" />
			</p>

		</div>
		
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr><?php print_column_headers( 'image-store_page_ims-sales' )?></tr>
			</thead>
			<tbody> 
			<?php 
			$counter = 0; foreach( $orders as $order ): $id = $order->ID;
			$data = get_post_meta( $id, '_response_data', true ); 
			?>
			<tr>
				<?php foreach( $columns as $key => $column ): ?>
				<?php if( $hidden ) $class = ( preg_match( "/($hidden)/i", $key ) )? ' hidden' : '';?>
				<?php switch( $key ){
					
					case 'cb':?>
						<th scope="row" class="column-<?php echo $key . $class?> check-column"> <input type="checkbox" name="orders[]" value="<?php echo $id?>" />
						</th>
					<?php break;
					
					case 'ordernum':?>
						<td class="column-<?php echo $key . $class?>" >
						<?php 
							if( !$is_trash ) echo '<a href="' . $pagenowurl . "&amp;details=1&amp;id=$id" .'">'. $data['txn_id'] .'</a>';
							else echo $data['txn_id'];
						?>
						</td>
					<?php break;
					
					case 'orderdate':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo date_i18n( $date_format, strtotime( $order->post_date ) )?></td>
					<?php break;
					
					case 'amount':?>
						<td class="column-<?php echo $key . $class?>" ><?php printf( $format[$loc], $data['payment_gross'] )?></td>
					<?php break;
					
					case 'customer':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $data['last_name'] . ' ' . $data['first_name']?></td>
					<?php break;
					
					case 'images':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $data['num_cart_items']?></td>
					<?php break;
					
					case 'paystatus':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $data['payment_status'] ?></td>
					<?php break;
						
					case 'orderstat':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $order->post_status?></td>
					<?php break;
					
					default:?>
						<td class="column-<?php echo $key . $class?>" >&nbsp;</td>
				<?php }?>
				<?php endforeach?>
			</tr>
			<?php endforeach?>
			</tbody>
		</table>
		
		<div class="tablenav"><?php $this->imstore_paging( $this->per_page, $count )?></div>
		
		</form>
	</div>
</div>


<?php 

/**
 * Get all orders
 *
 * @param unit $perpage 
 * @since 0.5.0
 * return array
 */
function get_ims_orders( $perpage ){
	global $wpdb; 

	$search = $wpdb->escape( $_GET['s'] );	
	$month 	= intval( substr( $_GET['m'], 4 ) );
	$year 	= intval( substr( $_GET['m'], 0, 4 ) );
	
	$page	= ( empty( $_GET['p'] ) ) ? '1' : $wpdb->escape( $_GET['p'] );
	$limit	= ( $_GET['p'] ) ? ( ( $_GET['p'] - 1 ) * $perpage ) : 0;
	$status = ( empty( $_GET['status'] ) ) ? ' != "trash" ' : ' = "' . $wpdb->escape( $_GET['status'] ) . '" ';
	$datef 	= ( !empty( $_GET['m'] ) )? " AND YEAR( post_date ) = '$year' AND MONTH( post_date ) = '$month'" : '';
	$join	= ( $search )? " JOIN $wpdb->postmeta AS pm ON ( p.ID = pm.post_id ) " : '';
	$srch	= ( $search )? " AND ( post_title LIKE '%$search%' OR post_excerpt LIKE '%$search%' OR pm.meta_value LIKE '%$search%' ) " : '';
	
	 $wpdb->show_errors( );
	 
	$r = $wpdb->get_results(
		"SELECT ID, post_title, 
		post_status, post_date
		FROM $wpdb->posts AS p $join
		WHERE post_type = 'ims_order' 
		AND post_status $status
		AND post_status != 'draft'
		$datef $srch
		GROUP BY ID
		ORDER BY post_date DESC 
	 LIMIT $limit, $perpage"
	);
	
	if( empty( $r ) )
		return $r;
	
	foreach( $r as $post ){
		$custom_fields = get_post_custom( $post->ID );
		foreach ( $custom_fields as $key => $value )
			$post->$key = $value[0];
		$orders[] = $post;
	}
	
	return $orders;
	
}


/**
 * Return order count by status
 *
 * @since 0.5.0
 * return unit
 */
function ims_order_count_links( ){
	global $wpdb,$pagenowurl; 
	
	$r = $wpdb->get_results(
		"SELECT post_status AS status, count(post_status) AS count 
		FROM $wpdb->posts
		WHERE post_type = 'ims_order'
		AND post_status != 'draft'
		GROUP by post_status"
	);
	
	if( empty($r) )
		return $r;
	
	$labels = array(
		'trash' 	=> __( 'Trash', ImStore::domain ),
		'publish' 	=> __( 'Published', ImStore::domain ),
		'pending' 	=> __( 'Pending', ImStore::domain ),
		'shipped' 	=> __( 'Shipped', ImStore::domain ),
		'closed' 	=> __( 'Closed', ImStore::domain ),
	);
	
	foreach( $r as $obj ){
		$count 	 = ( $obj->status == $_GET['status'] ) ? $obj->count : 0 ;
		$current = ( $obj->status == $_GET['status'] ) ? ' class="current"' : '';
		$links[] = '<li><a href="' . $pagenowurl . '&amp;status=' . $obj->status . '"' . $current . '>' . $labels[$obj->status] . ' <span class="count">(' . $obj->count . ')</span></a></li>';
		if( $obj->status != 'trash')
			$all += $obj->count ;
	}
	
	$style = ( empty($_GET['status']) ) ? ' class="current"' : '';
	if( $all ){
		array_unshift( $links, '<li><a href="' . $pagenowurl . '"' . $style . '>' . __( 'All', ImStore::domain ) . ' <span class="count">(' . $all . ')</span></a></li>' );
		$count = $all; 
	}
	echo implode( ' | ', $links );
	
	if( $s = $_GET['s']){
		$search	= $wpdb->escape( $s );
		$status = ( empty( $_GET['status'] ) ) ? ' != "trash" ' : ' = "' . $wpdb->escape( $_GET['status'] ) . '" ';
		$count = $wpdb->get_var( 
			"SELECT COUNT(ID)
			FROM $wpdb->posts AS p $join
			WHERE post_type = 'ims_order' 
			AND post_status $status
			AND post_status != 'draft'
			GROUP BY ID "
		);
	}
	
	return $count;
	
}


/**
 * change status
 *
 * @return void
 * @since 0.5.0
 */
function ims_change_status( ){
	global $wpdb, $pagenowurl;
	
	if( empty( $_GET['orders'] ) ) 
		return;
	
	
	$wpdb->query(
		"UPDATE $wpdb->posts 
		SET post_status = '" . $wpdb->escape( $_GET['action'] ) . "' 
		WHERE ID IN (" . $wpdb->escape( implode( ',' , $_GET['orders'] ) ) . ")"
	);
	
	$count = count( $_GET['orders'] );
	$s = ( $_GET['action'] == 'trash' ) ? 1 : 2 ;
	
	if( $count < 2 && $s == 2 ) $a = 3;
	elseif( $count < 2 && $s == 1 ) $a = 4;
	elseif( $s == 1 ) $a = 7;
	else $a = 6;
	
	wp_redirect( $pagenowurl . "&ms=$a&c=$count" );
	
}


/**
 * Get order archive
 *
 * @return array
 * @since 0.5.0
 */
function ims_order_archive( ){
	global $wpdb;
	
	$status = ( !empty( $_GET['status'] ) ) ? " = '" . $wpdb->escape( $_GET['status'] ) . "' " : " != 'trash' ";
	$r = $wpdb->get_results( "
		SELECT distinct 
		YEAR( post_date ) AS y,
		MONTH( post_date ) AS m
		FROM $wpdb->posts 
		WHERE post_status $status 
		AND post_status != 'draft'
		AND post_type = 'ims_order'
		AND post_date != 0"
	);
	return $r;
	
}



/**
 * Empty trash
 *
 * @param bool $delete_files
 * @return void
 * @since 0.5.0
 */
function empty_orders_trash( ){
	global $wpdb, $pagenowurl;
		
	$wpdb->query( 
		"DELETE p, pm FROM $wpdb->posts p 
		LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) 
		WHERE post_type = 'ims_order'
		AND post_status = 'trash'"
	);
	
	wp_redirect( $pagenowurl . "&ms=1" );
	
}



/**
 * Delete orders
 *
 * @param bool $delete_files
 * @return void
 * @since 0.5.0
 */
function delete_ims_orders( ){
	global $wpdb, $pagenowurl;

	if( empty( $_GET['orders'] ) ) 
		return;

	$orderids = $wpdb->escape( implode( ',', $_GET['orders'] ) );
	
	$wpdb->query( 
		"DELETE p, pm FROM $wpdb->posts p 
		LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) 
		WHERE ID IN ( $orderids )
		AND post_type = 'ims_order'"
	);
	
	$count = count( $_GET['orders'] );
	$a = ( $count < 2 ) ? 2 : 5 ;
	wp_redirect( $pagenowurl . "&ms=$a&c=$count" );

}

?>