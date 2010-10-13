<?php 

/**
 * Customers page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/


// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_manage_customers' ) ) 
	die( );
	
//clear cancel post data
if( isset( $_POST['cancel'] ) )
	wp_redirect( $pagenowurl );	
	
//update screen options
if( !empty( $_POST['screen_options'] ) ){
	update_user_meta( $user_ID, $_POST['screen_options']['option'], $_POST['screen_options']['value'] );
	wp_redirect( $pagenowurl );	
};

//add/update customer
if( isset( $_POST['add_customer'] ) || isset( $_POST['update_customer'] ) ){
	check_admin_referer( 'ims_new_customer' );
	$errors = create_ims_customer( );
}

//view customer information
if( !empty( $_GET['edit'] ) ){
	check_admin_referer( 'ims_link_customer' );
	$_GET['newcustomer'] = 1;
	$_POST = get_object_vars( get_userdata( $_GET['edit'] ) );
}

//update user statuts
if( !empty( $_GET['inactive'] ) ){
	check_admin_referer( 'ims_link_customer' );
	update_user_meta( $_GET['inactive'], 'ims_status', 'inactive' );
	wp_redirect( $pagenowurl . '&ms=3' );	
}

//update user statuts
if( !empty( $_GET['active'] ) ){
	check_admin_referer( 'ims_link_customer' );
	update_user_meta( $_GET['active'], 'ims_status', 'active' );
	wp_redirect( $pagenowurl . '&ms=3' );	
}

//delete single user
if( !empty( $_GET['user_delete'] ) ){
	check_admin_referer( 'ims_link_customer' );
	wp_delete_user( (int) $_GET['user_delete'] );
	wp_redirect( $pagenowurl . '&ms=4' );	
}

//bulk actions
if( !empty( $_GET['doaction'] ) ){
	
	if( empty( $_GET['action'] ) || empty( $_GET['customer'] ) )
		wp_redirect( $pagenowurl );
		
	check_admin_referer( 'ims_customers' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_customers( );
			break;
		default:
			update_ims_customer_status( );
	}
}


$message[1] = __( 'A new customer was added successfully.', ImStore::domain);
$message[2] = __( 'Customer updated.', ImStore::domain );
$message[3] = __( 'Status successfully updated.', ImStore::domain);
$message[4] = __( 'Customer deleted.', ImStore::domain);
$message[5] = sprintf( __( '%d customers updated.', ImStore::domain ), $_GET['c'] );
$message[6] = sprintf( __( '%d customers deleted.', ImStore::domain ), $_GET['c'] );

$customers 	= get_ims_customers( $this->per_page );
$nonce 		= '_wpnonce=' . wp_create_nonce( 'ims_link_customer' );
$hidden 	= implode( '|', get_hidden_columns( 'image-store_page_ims-customers' ) ) ;
$columns 	= get_column_headers( 'image-store_page_ims-customers' );

?>

<div class="wrap imstore">
	<?php screen_icon( 'users' )?>
	<h2>
		<?php _e( 'Manage Customers', ImStore::domain )?>
		<?php if ( !empty( $_GET['s'] ) )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', ImStore::domain) . '</span>', esc_html( $_GET['s'] ) )?>
	</h2>
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error">
		<?php foreach ( $errors->get_error_messages() as $err )
				echo "<p><strong>$err</strong></p>\n"; ?>
	</div>
	<?php endif; ?>
	<div id="poststuff" class="metabox-holder">
		<?php if( !empty($_GET['ms']) ){ ?><div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div> <?php }?>
		
		<!-- New Customer -->
		
		<?php 
		if( !empty( $_GET['newcustomer'] ) || !empty( $_GET['edit'] ) ): 
		$link = ( $_GET['newcustomer'] ) ? '&newcustomer=1' : '&edit='. $_GET['edit'];
		?>
		
		<form method="POST" action="<?php echo $pagenowurl . $link ?>" >
			<?php wp_nonce_field( 'ims_new_customer' )?>
			<div class="postbox" >
				<div class="handlediv"><br /></div>
				<h3 class='hndle'><span><?php if( $_GET['edit'] ) _e( 'Edit Customer', ImStore::domain ); else _e( 'New Customer', ImStore::domain ); ?></span></h3>
				<div class="inside">
					<table class="ims-table">
						<tr><td width="33" colspan="4" scope="row">&nbsp;</td></tr>
						<tr class="imstore-table">
							<td><label for="first_name"><?php _e( 'Name', ImStore::domain )?></label></td>
							<td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $_POST['first_name'] )?>" class="inputxl" /></td>
							<td><label for="last_name"><?php _e( 'Last Name', ImStore::domain )?></label></td>
							<td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $_POST['last_name'] )?>" class="inputxl"/></td>
						</tr>
						<tr class="imstore-table">
							<td><label for="user_email"><?php _e( 'Email', ImStore::domain )?></label></td>
							<td><input type="text" name="user_email" id="user_email" value="<?php echo esc_attr( $_POST['user_email'] )?>" class="inputxl" /></td>
							<td><?php if ( class_exists('MailPress') ):?>
								<label for="ims_enewsletter"><?php _e( 'Add to eNewsletter', ImStore::domain )?></label>
								<?php endif?>
							</td>
							<td><?php if ( class_exists('MailPress') ):?>
								<input type="checkbox" name="ims_enewsletter" id="ims_enewsletter" value="1"<?php checked( 1, $_POST['ims_enewsletter'] )?> />
								<?php endif?>
							</td>
						</tr>
						<tr class="imstore-table">
							<td><label for="ims_address"><?php _e( 'Address', ImStore::domain )?></label></td>
							<td><input type="text" name="ims_address" id="ims_address" value="<?php echo esc_attr( $_POST['ims_address'] )?>" class="inputxl" /></td>
							<td><label for="ims_city"><?php _e( 'City', ImStore::domain )?></label></td>
							<td><input type="text" name="ims_city" id="ims_city" value="<?php echo esc_attr( $_POST['ims_city'] )?>" class="inputmd" /></td>
						</tr>
						<tr>
							<td><label for="ims_state"><?php _e( 'State', ImStore::domain )?></label></td>
							<td><input type="text" name="ims_state" id="ims_state" value="<?php echo esc_attr( $_POST['ims_state'] )?>" class="input" />
								<label for="ims_zip"><?php _e( 'Zip', ImStore::domain )?></label>
								<input type="text" name="ims_zip" id="ims_zip" value="<?php echo esc_attr( $_POST['ims_zip'] )?>" class="inputsm" /></td>
							<td><label for="ims_phone"><?php _e( 'Phone', ImStore::domain )?></label></td>
							<td><input type="text" name="ims_phone" id="ims_phone" value="<?php echo esc_attr( $_POST['ims_phone'] )?>" class="inputxl" /></td>
						</tr>
						<tr>
							<td colspan="4" align="right"><input type="submit" name="cancel" value="<?php _e( 'Cancel', ImStore::domain )?>" class="button" />
								<?php if( empty( $_GET['edit'] ) ):?>
								<input type="submit" name="add_customer" value="<?php _e( 'Add New Customer', ImStore::domain )?>" class="button-primary" />
								<?php else:?>
								<input type="hidden" name="ims_status" id="ims_status" value="<?php echo esc_attr( $_POST['ims_status'] )?>" />
								<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $_POST['ID'] )?>" />
								<input type="submit" name="update_customer" value="<?php _e( 'Update', ImStore::domain )?>" class="button-primary" />
								<?php endif;?></td>
						</tr>
					</table>
				</div>
			</div>
		</form>
		<?php endif;?>
		
		<!-- Customer actions -->
		
		<ul class="subsubsub">
			<?php $count = ims_customers_count_links( ); if( $_GET['s'] ) ?>
		</ul>
		<form method="get" action="<?php echo $pagenowurl?>">
			<?php wp_nonce_field( 'ims_customers' )?>
			<div class="tablenav">
				<div class="alignleft actions">
					<select name="action">
						<option selected="selected"><?php _e( 'Bulk Actions', ImStore::domain )?></option>
						<?php if( $_GET['status'] == 'inactive' ):?>
						<option value="active"><?php _e( 'Active', ImStore::domain )?></option>
						<option value="delete"><?php _e( 'Delete', ImStore::domain )?></option>
						<?php else:?>
						<option value="inactive"><?php _e( 'Inactive', ImStore::domain )?></option>
						<?php endif;?>
					</select>
					<input type="submit" value="<?php _e( 'Apply' ); ?>" name="doaction" class="button-secondary" />
					| <a href="<?php echo IMSTORE_ADMIN_URL ?>customer-csv.php" class="button"><?php _e( 'Download CSV' ); ?></a> 
					<a href="<?php echo $pagenowurl ."&amp;$nonce&amp;newcustomer=1" ?>" class="button"><?php _e( 'New Customer' ); ?></a>
					<input type="hidden" value="<?php echo $_GET['page']?>" name="page" />
				</div>
				<p class="search-box">
					<input type="text" id="media-search-input" name="s" value="<?php echo esc_attr( $_GET['s'] )?>" />
					<input type="submit" value="<?php _e( 'Search Customers', ImStore::domain )?>" class="button" />
				</p>
				<br class="clear" />
			</div>
			
			<!-- Manage Customers -->
			
			<table class="widefat post fixed imstore-table">
				<thead>
					<tr><?php print_column_headers( 'image-store_page_ims-customers' )?></tr>
				</thead>
				<tbody>
					<?php $counter = 0; foreach( $customers as $id ): $customer = get_userdata( $id ); ?>
					<tr id="item-<?php echo $id?>" class="iedit<?php if( ($counter%2) ) echo ' alternate'; $counter++ ?>">
						<?php foreach( $columns as $key => $column ): ?>
						<?php if( $hidden ) $class = ( preg_match( "/($hidden)/i", $key ) )? ' hidden' : '';?>
						<?php switch( $key ){
					
					case 'cb':?>
						<th scope="row" class="column-<?php echo $key . $class?> check-column"> <input type="checkbox" name="customer[]" value="<?php echo $id?>" />
						</th>
						<?php break;
					
					case 'name':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->first_name?>
							<div class="row-actions"> <span><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;edit=$id" ?>" title="<?php _e( "Edit information", ImStore::domain )?>">
								<?php _e( "Edit", ImStore::domain )?>
								</a></span> |
								<?php if( $_GET['status'] == 'inactive' ):?>
								<span><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;active=$id" ?>" title="<?php _e( "Make entry active", ImStore::domain )?>">
								<?php _e( "Active", ImStore::domain )?>
								</a></span> | <span class="delete"><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;user_delete=$id" ?>" title="<?php _e( "Delete entry permanently", ImStore::domain )?>">
								<?php _e( "Delete", ImStore::domain )?>
								</a></span>
								<?php else:?>
								<span><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;inactive=$id" ?>" title="<?php _e( "Make entry inactive", ImStore::domain )?>">
								<?php _e( "Inactive", ImStore::domain )?>
								</a></span>
								<?php endif;?>
							</div></td>
						<?php break;
					
					case 'lastname':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->last_name?></td>
						<?php break;
					
					case 'email':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->user_email?></td>
						<?php break;
					
					case 'phone':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->ims_phone?></td>
						<?php break;
					
					case 'city':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->ims_city?></td>
						<?php break;
					
					case 'state':?>
						<td class="column-<?php echo $key . $class?>" ><?php echo $customer->ims_state?></td>
						<?php break;
					
					case 'enewsletter':?>
						<?php if ( class_exists('MailPress') ):?>
						<td class="column-<?php echo $key . $class?>" ><?php if( $customer->ims_enewsletter ) echo 'Yes'; else echo 'No'; ?></td>
						<?php endif;?>
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
 * Get all customers
 *
 * @param unit $perpage 
 * @since 0.5.0
 * return array
 */
function get_ims_customers( $perpage ){
	global $wpdb; 
	
	$search = $wpdb->escape( $_GET['s'] );	
	$page	= ( empty( $_GET['pagenum'] ) ) ? '1' : intval( $wpdb->escape( $_GET['pagenum'] ) );
	$limit	= ( $_GET['p'] ) ? ( ( $_GET['p'] - 1 ) * $perpage ) : 0;
	$status = ( empty( $_GET['status'] ) ) ? 'active' : $wpdb->escape( $_GET['status'] );
	$srch	= ( $search )? " AND ( user_login LIKE '%$search%' OR user_email LIKE '%$search%' OR um.meta_value LIKE '%$search%' ) " : '';
	
	$users = $wpdb->get_results(
		"SELECT DISTINCT ID FROM $wpdb->users AS u
		INNER JOIN $wpdb->usermeta AS um ON u.ID = um.user_id 
		WHERE um.meta_key = 'ims_status' 
		AND um.meta_value IN ( '$status',
			( SELECT DISTINCT meta_value 
			 FROM $wpdb->usermeta 
			 WHERE meta_value LIKE '%customer%' ) 
		) $srch LIMIT $limit, $perpage"
	, 'ARRAY_N' );
	
	if( empty( $users[0] ) )
		return array( );
		
	foreach( $users as $id )	
		$users_ids[] = $id[0];
		
	return $users_ids;
}


/**
 * Display/Return customer count by status
 *
 * @since 0.5.0
 * return unit
 */
function ims_customers_count_links( ){
	global $wpdb,$pagenowurl; 
	
	$r = $wpdb->get_results(
		"SELECT meta_value AS status, count(meta_key) AS count 
		FROM $wpdb->usermeta
		WHERE meta_key = 'ims_status' 
		GROUP by meta_value"
	);
	
	if( empty($r) )
		return;
		
	$labels = array(
		'active' => __( 'Active', ImStore::domain ),
		'inactive' => __( 'Inactive', ImStore::domain ),
	);
	
	foreach( $r as $obj ){
		$count 	 = ( ( $obj->status == $_GET['status'] ) || ( $obj->status == 'active' && empty( $_GET['status'] ) ) ) ? $obj->count : 0 ;
		$current = ( ( $obj->status == $_GET['status'] ) || ( $obj->status == 'active' && empty( $_GET['status'] ) ) ) ? ' class="current"' : '';
		$links[] = '<li><a href="' . $pagenowurl . '&amp;status=' . $obj->status . '"' . $current . '>' . $labels[$obj->status] . ' <span class="count">(' . $obj->count . ')</span></a></li>';
	}
	echo implode( ' | ', $links );
	
	if( $s = $_GET['s'] ){
		$search	= $wpdb->escape( $s );	
		$count = $wpdb->get_var( 
			"SELECT count(ID)FROM $wpdb->users AS u
			INNER JOIN $wpdb->usermeta AS um ON u.ID = um.user_id 
			WHERE um.meta_key = 'ims_status' 
			AND um.meta_value IN ( 'active',
				( SELECT DISTINCT meta_value 
				 FROM $wpdb->usermeta 
				 WHERE meta_value LIKE '%customer%' ) 
			) AND ( user_login LIKE '%$search%' 
				OR user_email LIKE '%$search%' 
				OR um.meta_value LIKE '%$search%' 
			)"
		);
	}
	
	return $count;
	
}


/**
 * Insert a customer
 *
 * @since 0.5.0
 * return array errors
 */
function create_ims_customer( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error();
	
	if( empty( $_POST['first_name'] ) )
		$errors->add( 'empty_first_name', __( 'The first name is required.', ImStore::domain ) );
	
	if( empty( $_POST['last_name'] ) )
		$errors->add( 'empty_last_name', __( 'The last name is required.', ImStore::domain ) );
	
	if( empty( $_POST['user_email'] ) )
		$errors->add( 'empty_last_name', __( 'The email is required.', ImStore::domain ) );
		
	if( !is_email( $_POST['user_email'] ) )
		$errors->add( 'empty_last_name', __( 'Wrong email format. That doesn&#8217;t look like an email to me.', ImStore::domain ) );
		
	$user_name = sanitize_user( $_POST['first_name'] .' '. $_POST['last_name'] );
	if( username_exists( $user_name ) && !isset( $_POST['update_customer'] ) ) 
		$errors->add( 'customer_exists', __( 'That customer already exists.' ) );
		
	if( !empty( $errors->errors ) )
		return $errors;
		
	$new_user = array(
		'ID' 			=> $_POST['user_id'],
		'user_pass' 	=> wp_generate_password( 12, false ),
		'user_login' 	=> $user_name ,
		'user_nicename' => $user_name ,
		'user_email' 	=> $_POST['user_email'],
		'first_name' 	=> $_POST['first_name'],
		'last_name' 	=> $_POST['last_name'],
		'role' 			=> 'customer'
	);

	if( isset( $_POST['update_customer'] ) ) $user_id = wp_update_user( $new_user );
	else $user_id = wp_insert_user( $new_user );
		
	if( is_wp_error( $user_id ) && !isset( $_POST['update_customer'] ) )
		return $user_id;

	$meta_keys = array ( 'ims_zip', 'ims_city', 'ims_phone', 'ims_state', 'ims_status', 'ims_address', 'ims_enewsletter');
	foreach( $meta_keys as $key ){
		if( !empty( $_POST[$key] ) )
		update_user_meta( $user_id, $key, $_POST[$key] );
	}
	
	$status = ( $_POST['ims_status'] ) ? $_POST['ims_status'] : 'active';
	update_user_meta( $user_id, 'ims_status', $status );
	
	if( isset( $_POST['update_customer'] ) ) wp_redirect( $pagenowurl . '&ms=2' );	
	else wp_redirect( $pagenowurl . '&ms=1' );	
	
}


/**
 * Update user status
 *
 * @since 0.5.0
 * return void
 */
function update_ims_customer_status( ){
	global $wpdb, $pagenowurl;
		
	$updated = $wpdb->query( $wpdb->prepare( 
		"UPDATE $wpdb->usermeta 
		SET meta_value = '%s' 
		WHERE meta_key = 'ims_status' 
		AND user_id IN ( %s )"
		, $_GET['action'], implode( ',', $_GET['customer'] ) 
	));

	if( $updated )
		wp_redirect( $pagenowurl . "&ms=5&c=$updated" );	
}


/**
 * delete users
 *
 * @since 0.5.0
 * return void
 */
function delete_ims_customers( ){
	global $wpdb, $pagenowurl;

	$customer_ids = implode( ',', $_GET['customer'] );	
	$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->users WHERE ID IN ( %s ) ", $customer_ids ) );
	
	if( $deleted ){
		$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id IN ( %s ) ", $customer_ids ) );
		wp_redirect( $pagenowurl . "&ms=6&c=$deleted" );	
	}
	
}

?>
