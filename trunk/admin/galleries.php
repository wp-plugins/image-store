<?php 

/**
 * galleries page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 1.1.0
*/


// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_manage_galleries' ) ) 
	die( );

//clear cancel post data
if( isset( $_POST['cancel'] ) )
	wp_redirect( $pagenowurl );	

//update screen options
if( !empty( $_POST['screen_options'] ) ){
	update_user_meta( $user_ID, $_POST['screen_options']['option'], $_POST['screen_options']['value'] );
	wp_redirect( $pagenowurl );	
};

// empty trash
if( isset( $_GET['deleteall'] ) ){
	check_admin_referer( 'ims_galleries' );
	empty_events_trash( $this->opts['deletefiles'] );
}

//bulk actions
if( !empty( $_GET['doaction'] ) ){
	check_admin_referer( 'ims_galleries' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_galleries( $this->opts['deletefiles'] );
			break;
		default:
		ims_change_status( );
	}
}


//link actions
if( !empty( $_GET['action'] ) && !empty( $_GET['id'] ) ){
	check_admin_referer( 'ims_galleries_link' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_galleries( $this->opts['deletefiles'] );
			break;
		default:
		ims_change_status( );
	}
}

global $wpdb; 
$message[1] 	= __( 'Trash emptied', ImStore::domain );
$message[2] 	= __( 'Gallery deleted.', ImStore::domain );
$message[3] 	= __( 'Gallery status updated.', ImStore::domain );
$message[4] 	= __( 'Gallery moved to trash.', ImStore::domain );
$message[5] 	= __( 'A new gallery was created.', ImStore::domain );
$message[6] 	= sprintf( __( '%d galleries deleted.', ImStore::domain ), $_GET['c'] );
$message[7] 	= sprintf( __( '%d galleries moved to trash.', ImStore::domain ), $_GET['c'] );
$message[8] 	= sprintf( __( 'Status updated on %d galleries.', ImStore::domain ), $_GET['c'] );

$date_format	= get_option( 'date_format' );
$pageid			= get_option( 'ims_page_secure' );
$nonce 			= '&_wpnonce=' . wp_create_nonce( 'ims_galleries_link' );
$is_trash		= ( isset( $_GET['status'] ) ) && ( $_GET['status'] == 'trash' );
$hidden 		= implode( '|', get_hidden_columns( 'toplevel_page_' . IMSTORE_FOLDER ) ) ;
$columns 		= get_column_headers( 'toplevel_page_' . IMSTORE_FOLDER );
$galleries 	= get_ims_galleries( $this->per_page ) ;

?>

 
<div class="wrap imstore">
	
	<?php screen_icon( 'galleries' )?>
	<h2><?php _e( 'Galleries', ImStore::domain )?>
	<?php if ( !empty( $_GET['s'] ) )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', ImStore::domain) . '</span>', esc_html( $_GET['s'] ) )?>
	</h2>
	
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error"><?php
		foreach ( $errors->get_error_messages() as $err )
				echo "<p><strong>$err</strong></p>\n"; ?>
	</div>
	<?php endif; ?>
	
	<div id="poststuff" class="metabox-holder">
	<?php if( !empty($_GET['ms']) ){ ?>
	<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
	

	<!-- MANAGE GALLERIES -->
	
	<ul class="subsubsub"><?php $count = ims_gallery_count_links( )?></ul>
	
	<form method="get" action="<?php echo $pagenowurl?>">
	
	<input type="hidden" name="page" value="<?php echo $_GET['page']?>" />
	
	<?php wp_nonce_field( 'ims_galleries' )?>
	
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option value="" selected="selected"><?php _e( 'Bulk Actions', ImStore::domain )?></option>
					<?php if ( $is_trash ):?>
					<option value="pending"><?php _e( 'Restore', ImStore::domain )?></option> 
					<option value="delete"><?php _e( 'Delete Permanently', ImStore::domain )?></option>
					<?php else:?>
					<option value="publish"><?php _e( 'Publish', ImStore::domain )?></option>
					<option value="pending"><?php _e( 'Pending', ImStore::domain )?></option>
					<option value="trash"><?php _e( 'Move to Trash', ImStore::domain )?></option>
					<?php endif?>
				</select>
				<input type="submit" name="doaction" value="<?php _e( 'Apply', ImStore::domain )?>" class="button action" />
				
				<select name='m'>
					<option value='0'><?php _e( 'Select date created', ImStore::domain )?></option>
					<?php foreach( ims_galleries_archive( ) as $archive ): $date = strtotime( $archive->y .'-'. $archive->m ) ?>
					<option value="<?php echo date( 'Ym', $date )?>" <?php selected( date( 'Ym', $date ), $_GET['m'] )?> >
					<?php echo date_i18n( $date_format , $date )?></option>
					<?php endforeach?>
				</select>
				
				<input type="submit" value="<?php _e( 'Filter', ImStore::domain )?>" class="button" />
				<?php if ( $is_trash ):?>
				<input type="submit" name="deleteall" value="<?php _e( 'Empty Trash', ImStore::domain )?>" class="button" />
				<?php endif?>
			</div>
			<p class="search-box">
			<input type="text" id="media-search-input" name="s" value="<?php echo esc_attr( $_GET['s'] )?>" />
			<input type="submit" value="<?php _e( 'Search Galleries', ImStore::domain )?>" class="button" />
			</p>
		</div>
	
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr><?php print_column_headers( 'toplevel_page_' . IMSTORE_FOLDER )?></tr>
			</thead>
			<tbody>
				<?php $counter = 0; foreach( $galleries as $gallery ): $id = $gallery->ID; ?>
				<tr id="item-<?php echo $id?>" class="iedit<?php if( ($counter%2) ) echo ' alternate'; $counter++ ?>">
					<?php foreach( $columns as $key => $column ): ?> 
					<?php if( $hidden ) $class = ( preg_match( "/($hidden)/i", $key ) )? ' hidden' : '';?>
						<?php switch( $key ){
						
						case 'cb':?>
						<th scope="row" class="column-<?php echo $key . $class?> check-column">
							<input type="checkbox" name="galleries[]" value="<?php echo $id?>" />
						</th>
						<?php break;
						
						case 'gallery':?>
						<td class="column-<?php echo $key . $class?>" > 
							<strong><?php 
							if( !$is_trash ) echo '<a href="' . $pagenowurl . "&amp;edit=1&amp;id=$id".'">'. $gallery->post_title .'</a>';
							else echo $gallery->post_title ;
							?></strong>
							<div class="row-actions">
								<?php if ( $is_trash ):?>
								<a href="<?php echo $pagenowurl . "&amp;action=delete&amp;id=$id{$nonce}"?>"><?php _e( "Delete", ImStore::domain )?></a> | 
								<a href="<?php echo $pagenowurl . "&amp;action=pending&amp;id=$id{$nonce}"?>"><?php _e( "Restore", ImStore::domain )?></a>
								<?php else:?>
								<a href="<?php echo $pagenowurl . "&amp;edit=1&amp;id=$id{$nonce}"?>"><?php _e( "Edit", ImStore::domain )?></a> |
								<a href="<?php echo get_permalink( $pageid ) . "&imsgalid=$id{$nonce}"?>"><?php _e( "View", ImStore::domain )?></a> | 
								<a href="<?php echo $pagenowurl . "&amp;action=trash&amp;id=$id{$nonce}"?>"><?php _e( "Trash", ImStore::domain )?></a>
								<?php endif?>
							</div>
						</td>
						<?php break;
						
						case 'galleryid':?>
							<td class="column-<?php echo $key . $class?>" > <?php echo get_post_meta( $id, '_ims_gallery_id', true ) ?></td>
						<?php break;
						
						case 'pswrd':?>
							<td class="column-<?php echo $key . $class?>" > <?php echo $gallery->post_password ?></td>
						<?php break;
						
						case 'tracking':?>
							<td class="column-<?php echo $key . $class?>" > <?php echo $gallery->ims_tracking ?></td>
						<?php break;
						
						case 'images':?>
							<td class="column-<?php echo $key . $class?>" > <?php echo $gallery->_ims_image_count ?></td>
						<?php break;
						
						case 'visits':?>
							<td class="column-<?php echo $key . $class?>" > <?php echo $gallery->ims_visits ?></td>
						<?php break;
						
						case 'expire':?>
							<td class="column-<?php echo $key . $class?>" ><?php echo date_i18n( $date_format, strtotime( $gallery->post_expire ) )?></td>
						<?php break;
						
						case 'datecrtd': ?>
							<td class="column-<?php echo $key . $class?>" ><?php echo date_i18n( $date_format, strtotime( $gallery->post_date ) )?></td>
						<?php break;
						
						default:?>
						<td class="column-<?php echo $key . $class?>" >&nbsp;</td>
						
						<?php }?>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<div class="tablenav"><?php $this->imstore_paging( $this->per_page, $count )?></div>
		
	</form>
	</div>

</div>


<?php 

/**
 * Get all galleries
 *
 * @param unit $perpage 
 * @since 1.1.0
 * return array
 */
function get_ims_galleries( $perpage ){
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
	
	$r = $wpdb->get_results(
		"SELECT ID, post_password, post_title, 
		post_status, post_date, post_expire
		FROM $wpdb->posts AS p $join
		WHERE post_type = 'ims_gallery' 
		AND post_status $status
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
		$galleries[] = $post;
	}
	
	return $galleries;
	
}


/**
 * Display/Return galleries count by status
 *
 * @since 1.1.0
 * return unit
 */
function ims_gallery_count_links( ){
	global $wpdb,$pagenowurl; 
	
	$r = $wpdb->get_results(
		"SELECT post_status AS status, count(post_status) AS count 
		FROM $wpdb->posts
		WHERE post_type = 'ims_gallery' 
		GROUP by post_status"
	);
	
	if( empty($r) )
		return $r;
	
	$labels = array(
		'trash' 	=> __( 'Trash', ImStore::domain ),
		'publish' 	=> __( 'Published', ImStore::domain ),
		'pending' 	=> __( 'Pending', ImStore::domain ),
		'expire' 	=> __( 'Expired', ImStore::domain ),
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
			FROM $wpdb->posts AS p 
			JOIN $wpdb->postmeta AS pm ON ( p.ID = pm.post_id )
			WHERE post_type = 'ims_gallery' 
			AND post_status $status
			AND ( post_title LIKE '%$search%' 
				 OR post_excerpt LIKE '%$search%' 
				 OR pm.meta_value LIKE '%$search%' 
			)
			GROUP BY ID "
		);
	}
	
	return $count;
}


/**
 * Get gallery archive
 *
 * @return array
 * @since version 0.5.0
 */
function ims_galleries_archive( ){
	global $wpdb;
	
	$status = ( !empty( $_GET['status'] ) ) ? " = '" . $wpdb->escape( $_GET['status'] ) . "' " : " != 'trash' ";
	$r = $wpdb->get_results( "
		SELECT distinct 
		YEAR( post_date ) AS y,
		MONTH( post_date ) AS m
		FROM $wpdb->posts 
		WHERE post_status $status 
		AND post_type = 'ims_gallery'
		AND post_date != 0"
	);
	return $r;
	
}


/**
 * change status
 *
 * @return void
 * @since version 0.5.0
 */
function ims_change_status( ){
	global $wpdb, $pagenowurl;
	
	if( !empty( $_GET['id'] ) ) $_GET['galleries'] = array( intval($_GET['id']) );
	if( empty( $_GET['galleries'] ) ) return;
	
	$updated = $wpdb->query(
		"UPDATE $wpdb->posts 
		SET post_status = '" . $wpdb->escape( $_GET['action'] ) . "' 
		WHERE ID IN (" . $wpdb->escape( implode( ',' , $_GET['galleries'] ) ) . ")"
	);
	
	$count = count( $_GET['galleries'] );
	$s = ( $_GET['action'] == 'trash' ) ? 1 : 2 ;
	
	if( $count < 2 && $s == 2 ) $a = 3;
	elseif( $count < 2 && $s == 1 ) $a = 4;
	elseif( $s == 1 ) $a = 7;
	else $a = 8;
	
	wp_redirect( $pagenowurl . "&ms=$a&c=$count" );
	
}


/**
 * Delete folder
 *
 * @param string $dir 
 * @since version 0.5.0
 * return boolean
 */
function delete_ims_folder( $dir ){
	if ( $dh = @opendir ( $dir ) ){
		while ( false !== ( $obj = readdir ( $dh ) ) ){
			if ( $obj == '.' || $obj == '..') continue;
			if ( is_dir( $dir . '/' . $obj ) ) delete_ims_folder( $dir . '/' . $obj );
			else @unlink ( $dir . '/' . $obj ); 
		}
		closedir ( $dh );
		return rmdir ( $dir );
	}
}


/**
 * Empty trash
 *
 * @param bool $delete_files
 * @return void
 * @since version 0.5.0
 */
function empty_events_trash( $delete_files ){
	global $wpdb, $pagenowurl;
	
	$trash = $wpdb->get_results( 
		"SELECT ID, meta_value folder FROM $wpdb->posts, $wpdb->postmeta
		WHERE post_type = 'ims_gallery' AND post_status = 'trash'
		AND meta_key = '_ims_folder_path' 
		OR post_parent IN ( 
			SELECT ID FROM $wpdb->posts 
			WHERE post_type = 'ims_gallery' 
			AND post_status = 'trash'
		) GROUP BY ID "
	);
	
	if( empty( $trash ) )
		return;
	
	foreach( $trash as $g ){
		if( $g->folder && $delete_files && !is_serialized( $g->folder ) )
			delete_ims_folder( WP_CONTENT_DIR . $g->folder );
		$gallery_ids[] = $g->ID;
	}
	
	$ids = $wpdb->escape( implode( ',', $gallery_ids ) );
		
	$wpdb->query( 
		"DELETE p, pm FROM $wpdb->posts p 
		LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) 
		WHERE post_parent IN ( $ids )
		OR ID IN ( $ids ) "
	);
		
	wp_redirect( $pagenowurl . "&ms=1" );

}



/**
 * Delete galleries
 *
 * @param bool $delete_files
 * @return void
 * @since version 0.5.0
 */
function delete_ims_galleries( $delete_files ){
	global $wpdb, $pagenowurl;
	
	if( !empty( $_GET['id'] ) ) $_GET['galleries'] = array( intval($_GET['id']) );
	if( empty( $_GET['galleries'] ) ) return;
	
	$ids = $wpdb->escape( implode( ',', $_GET['galleries'] ) );

	if( $delete_files ){
		
		$folders = $wpdb->get_results( 
			"SELECT meta_value folder FROM $wpdb->postmeta
			WHERE meta_key = '_ims_folder_path'
			AND post_id IN ( $ids ) "
		);
		foreach( $folders as $f )
			delete_ims_folder( WP_CONTENT_DIR . $f->folder );
			
	}

	$wpdb->query( 
		"DELETE p, pm FROM $wpdb->posts p 
		LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) 
		WHERE post_parent IN ( $ids )
		OR ID IN ( $ids ) "
	);

	$count = count( $_GET['galleries'] );
	$a = ( $count < 2 ) ? 2 : 6 ;
	wp_redirect( $pagenowurl . "&ms=$a&c=$count" );

}

?>