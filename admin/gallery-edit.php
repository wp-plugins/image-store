<?php 

/**
 * Edit gallery page
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


//bulk actions
if( !empty( $_GET['doaction'] ) ){
	check_admin_referer( 'ims_images' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_images( $this->opts['deletefiles'] );
			break;
		default:
		ims_image_status( );
	}
}

//update all data
if( !empty( $_GET['deleteall'] ) ){
	check_admin_referer( 'ims_images' );
	empty_image_trash( $this->opts['deletefiles'] );
}

//update all data
if( !empty( $_GET['savechanges'] ) ){
	check_admin_referer( 'ims_images' );
	update_image_data( );
}

//link actions
if( !empty( $_GET['action'] ) && !empty( $_GET['img'] ) ){
	check_admin_referer( 'ims_image_link' );
	switch( $_GET['action'] ){
		case 'delete':
			delete_ims_images( $this->opts['deletefiles'] );
			break;
		default:
		ims_image_status( );
	}
}

//change gallery sort
if( !empty( $_GET['dosort'] ) ){
	check_admin_referer( 'ims_images' );
	if( $_GET['sortby'] ) update_post_meta( $_GET['id'], '_ims_sortby', $_GET['sortby'] );
	else delete_post_meta( $_GET['id'], '_ims_sortby' );
	
	if( $_GET['order'] ) update_post_meta( $_GET['id'], '_ims_order', $_GET['order'] );
	else delete_post_meta( $_GET['id'], '_ims_order' );
}

// update gallery info
if( isset( $_POST['updategallery'] ) ){
	check_admin_referer( 'ims_update_gallery' );
	$errors = update_gallery_info( $this->opts['disablestore'] );
}

$message[1] 	= __( 'Trash emptied.', ImStore::domain );
$message[2] 	= __( 'Image deleted.', ImStore::domain );
$message[3] 	= __( 'Image published.', ImStore::domain );
$message[4] 	= __( 'Information updated.', ImStore::domain );
$message[5] 	= __( 'Image moved to trash.', ImStore::domain );
$message[9] 	= __( 'Gallery information updated.', ImStore::domain );
$message[6] 	= sprintf( __( '%s images deleted.', ImStore::domain ), $_GET['c'] );
$message[7] 	= sprintf( __( '%s images published.', ImStore::domain ), $_GET['c'] );
$message[8] 	= sprintf( __( '%d galleries moved to trash.', ImStore::domain ), $_GET['c'] );


global $wpdb;
$gal_id 		= intval( $_GET['id'] );
$date_format 	= get_option( 'date_format' );
$date_format 	= get_option( 'date_format' );
$pageid			= get_option( 'ims_page_secure' );
$status 		= ( $_GET['status'] ) ? $_GET['status'] : 'publish';
$is_trash 		= ( isset( $_GET['status'] ) ) && ( $_GET['status'] == 'trash' );
$columns 		= get_column_headers( 'toplevel_page_' . IMSTORE_FOLDER . '-edit' );
$hidden			= implode( '|', get_hidden_columns( 'toplevel_page_' . IMSTORE_FOLDER . '-edit' ) ) ;
$nonce 			= '&_wpnonce=' . wp_create_nonce( 'ims_image_link' );
$imgnonce 		= '&_wpnonce=' . wp_create_nonce( "ims_edit_image" ) . "&TB_iframe=true&height=570";

$order 			= ( $_sort = get_post_meta( $gal_id, '_ims_order', true ) ) ? $_sort : $this->opts['imgsortdirect'];
$sortby 		= ( $_sortby = get_post_meta( $gal_id, '_ims_sortby', true ) ) ? $_sortby : $this->opts['imgsortorder'];

global $user_ID;
$closed = get_user_meta( $user_ID , 'closedpostboxes_toplevel_page_imstore-edit' ) ;
$closed = implode(',', (array)$closed[0] );

$gallery 		= get_post( $gal_id );
$gallerymeta 	= get_post_custom( $gal_id );

foreach ( $gallerymeta as $key => $value ) 
	$gallery->$key = $value[0];

$images = get_posts( array( 
	'post_parent' => $gal_id, 
	'post_type' => 'ims_image', 
	'orderby' => trim($sortby),
	'order' => trim($order),
	'numberposts' => -1,
	'post_status' => $status
));

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
	
	<div id="poststuff" class="metabox-holder meta-box-sortables">
	<?php if( !empty($_GET['ms']) ){ ?>
	<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
	
	<!-- GALLERY INFO-->
	
	<div id="gallery-info" class="postbox<?php if( preg_match( "/(gallery-info)/i", $closed ) ) echo ' closed' ?>">
	<div class="handlediv" title="Click to toggle"><br></div>
	<h3 class="hndle"><span><?php _e( 'Gallery Information', ImStore::domain )?></span></h3>
	<div class="inside">
		<form method="POST" action="<?php echo $pagenowurl . "&edit=1&id=$gal_id"?>">
		<input type="hidden" name="galid" value="<?php echo $gal_id ?>" />
		<?php 
		wp_nonce_field( 'ims_update_gallery' ); 
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		?>
		<table class="ims-table" >
		 	<tr>
				<td width="18%"><?php _e( 'Folder path', ImStore::domain )?></td>
				<td width="30%"><?php echo $gallery->_ims_folder_path ?></td>
				<td width="18%"><?php _e( 'Gallery ID', ImStore::domain )?></td>
				<td><?php echo $gallery->_ims_gallery_id?></td>
			</tr>
			<tr>
				<td width="18%">
					<label for="post_title"><?php _e( 'Gallery Name', ImStore::domain )?>
					<small>(<?php _e( 'required', ImStore::domain )?>)</small></label>
				</td>
				<td width="30%">
					<input type="text" name="post_title" id="post_title" value="<?php echo esc_attr( $gallery->post_title )?>" class="inputxl" />
				</td>
				<td width="18%">
					<label for="date" class="date-icon"><?php _e( 'Event Date', ImStore::domain )?></label>
				</td>
				<td> 
					<input type="text" name="date" id="date" class="inputmd" value="<?php echo date_i18n( $date_format, strtotime( $gallery->post_date ) ) ?>" />
					<input type="hidden" name="post_date" id="post_date" value="<?php echo esc_attr( $gallery->post_date ) ?>"/>
				</td>
			</tr>
			<tr>
				<td valign="top"><label for="post_password"><?php _e( 'Password', ImStore::domain )?></label></td>
				<td><input type="text" name="post_password" id="post_password" value="<?php echo esc_attr( $gallery->post_password ) ?>" class="inputxl" /></td>
				<td><label for="expire" class="date-icon"><?php _e( 'Expiration Date', ImStore::domain )?>	</label></td>
				<td><input type="text" name="expire" id="expire" class="inputmd" value="<?php echo date_i18n( $date_format, strtotime( $gallery->ims_expire ) ) ?>" />
					<input type="hidden" name="ims_expire" id="ims_expire" value="<?php echo esc_attr( $gallery->ims_expire ) ?>"/>
				</td>
			</tr>
			<?php if( !$this->opts['disablestore'] ){ ?>
			<tr>
				<td><label for="ims_tracking"><?php _e( 'Tracking Number', ImStore::domain )?></label></td>
				<td><input type="text" name="ims_tracking" id="ims_tracking" value="<?php echo esc_attr( $gallery->ims_tracking )?>" class="inputxl" /></td>
				<td><label for="_ims_price_list"><?php _e( 'Price list', ImStore::domain)?>
					<small>(<?php _e( 'required', ImStore::domain )?>)</small></label>
				</td>
				<td><?php $lists = $this->get_ims_pricelists( );?>
					<select name="_ims_price_list" id="_ims_price_list" >
						<option value=""><?php _e( 'Select a list &#8212;', ImStore::domain )?></option>
						<?php foreach( $lists as $list ):?>
						<option value="<?php echo $list->ID?>" <?php selected( $list->ID, $gallery->_ims_price_list )?>><?php echo $list->post_title ?></option>
						<?php endforeach?>
					</select>
				</td>
			</tr>
			<!--<tr>
				<td valign="top"><label for="ims_downloads"><?php _e( 'Downloads', ImStore::domain )?></label></td>
				<td><input type="text" name="ims_downloads" id="ims_downloads" value="<?php echo $gallery->ims_downloads ?>" class="inputsm"/></td>
				<td valign="top"><label for="ims_download_max"><?php _e( 'Downloads allowed', ImStore::domain )?></label></td>
				<td><input type="text" name="ims_download_max" id="ims_download_max" value="<?php echo $gallery->ims_download_max ?>" class="inputsm"/></td>-->
			</tr>
			<?php } ?>
			<tr>
				<td><label for="ims_visits"><?php _e( 'Visits', ImStore::domain )?></label></td>
				<td><input type="text" name="ims_visits" id="ims_visits" value="<?php echo $gallery->ims_visits ?>" class="inputmd"/></td>
				<td><label for="_ims_customer"><?php _e( 'Customer', ImStore::domain )?></label></td>
				<td><?php $customers = $this->get_ims_active_customers( ) ?>
					<select name="_ims_customer" id="_ims_customer">
						<option value=""><?php _e( 'Select customer &#8212;', ImStore::domain )?></option>
						<?php foreach( $customers as $customer ):?>
						<option value="<?php echo $customer->ID?>" <?php selected( $customer->ID, $gallery->_ims_customer )?>><?php echo $customer->user_login?></option>
						<?php endforeach?> 
						</select>
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="3" class="submit">
					<input type="submit" name="updategallery" value="<?php _e( 'Update information', ImStore::domain)?>" class="button-primary" />
				</td>
			</tr>
		</table>
	 </form>
	</div>
	</div>
	
	<!-- IMAGES -->
	
	<ul class="subsubsub"><?php ims_image_count_links( )?></ul>
	<form method="get" action="<?php echo $pagenowurl . "&edit=1&id=$gal_id"?>">
		
		<input type="hidden" name="edit" value="1" />
		<input type="hidden" name="id" value="<?php echo $_GET['id']?>" />
		<input type="hidden" name="page" value="<?php echo $_GET['page']?>" />
		<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'ims_images' ) ?>" />
	
	
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action">
					<option value="0" selected="selected"><?php _e( 'Bulk Actions', ImStore::domain )?></option>
					<?php if ( $is_trash ):?>
					<option value="publish"><?php _e( 'Restore', ImStore::domain )?></option> 
					<option value="delete"><?php _e( 'Delete Permanently', ImStore::domain )?></option>
					<?php else:?>
					<option value="trash"><?php _e( 'Move to Trash', ImStore::domain )?></option>
					<?php endif?>
				</select>
				<input type="submit" value="<?php _e( 'Apply', ImStore::domain )?>" name="doaction" class="button action" />
		
				<?php if ( $is_trash ):?>
				
				<input type="submit" name="deleteall" value="<?php _e( 'Empty Trash', ImStore::domain )?>" class="button" />
				
				<?php else:?>
				
				<span class="sort-label"><?php _e( 'Sort', ImStore::domain )?></span>
				<select name="sortby">
					<option value="0"><?php _e( 'Default', ImStore::domain )?></option> 
					<option value="menu_order"<?php selected('menu_order', $_sortby )?>><?php _e( 'Custom order', ImStore::domain )?></option> 
					<option value="post_excerpt"<?php selected('post_excerpt', $_sortby )?>><?php _e( 'Caption', ImStore::domain )?></option>
					<option value="post_title"<?php selected('post_title', $_sortby )?>><?php _e( 'Image title', ImStore::domain )?></option>
					<option value="post_date"<?php selected('post_date', $_sortby )?>><?php _e( 'Image date', ImStore::domain )?></option>
				</select>
				<select name="order">
					<option value="0"><?php _e( 'Default', ImStore::domain )?></option> 
					<option value="ASC"<?php selected('ASC', $_sort )?>><?php _e( 'Ascending', ImStore::domain )?></option>
					<option value="DESC"<?php selected('DESC', $_sort )?>><?php _e( 'Descending', ImStore::domain )?></option> 
				</select>
				<input type="submit" name="dosort" value="<?php _e( 'Sort', ImStore::domain )?>" class="button action" /> |
				<a href="<?php echo get_permalink( $pageid ) . "&imsgalid=$gal_id{$nonce}"?>" class="button"><?php _e( "View", ImStore::domain )?></a> 
				<?php endif?>
			</div>
			
			<?php if ( !$is_trash ):?>
			<p class="search-box"><input type="submit" name="savechanges" value="<?php _e( 'Save Changes', ImStore::domain )?>" class="button-primary" /></p>
			<?php endif?>
			
		</div>
		
		
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr><?php print_column_headers( 'toplevel_page_' . IMSTORE_FOLDER . '-edit' )?></tr>
			</thead>
			<tbody>
			<?php $counter = 0; foreach( $images as $image ): $id = $image->ID; ?>
				<tr id="item-<?php echo $id?>" class="iedit<?php if( ($counter%2) ) echo ' alternate'; $counter++ ?>">
				<?php foreach( $columns as $key => $column ): ?> 
				<?php if( $hidden ) $class = ( preg_match( "/($hidden)/i", $key ) )? ' hidden' : '';?>
				<?php $imagemeta = get_post_meta( $id, '_wp_attachment_metadata' ); ?>
				<?php switch( $key ){
					case 'cb':?>
					<th scope="row" class="column-<?php echo $key . $class?> check-column">
						<input type="checkbox" name="images[]" value="<?php echo $id?>" />
						<input type="hidden" name="imageids[]" value="<?php echo $id?>" />
					</th>
					<?php break;
					
					case 'thumb':?>
					<td class="column-<?php echo $key . $class ?>" >
					<a href="<?php echo WP_CONTENT_URL .$imagemeta[0]['file'] ?>" class="thickbox" rel="gallery" >
					<img src="<?php echo dirname( $image->guid ) . '/' . $imagemeta[0]['sizes']['mini']['file'] ?>" /></a></td>
					<?php break;
					
					case 'metadata':?>
					<td class="column-<?php echo $key . $class ?>" >
						<?php echo $imagemeta[0]['width'] .' x '. $imagemeta[0]['height'] . __(' pixels', ImStore::domain )?><br />
						<?php echo __('Format: ', ImStore::domain ) . str_replace( 'image/', '', $image->post_mime_type )?><br />
						<?php echo __('Color: ', ImStore::domain ) . $imagemeta[0]['color'] ?><br />
						<div class="row-actions" id="media-head-<?php echo $id?>">
							<?php if ( $is_trash ):?>
							<a href="<?php echo $pagenowurl . "&edit=1&id=$gal_id&action=delete&img=$id{$nonce}"?>">Delete</a> | 
							<a href="<?php echo $pagenowurl . "&edit=1&id=$gal_id&action=publish&img=$id{$nonce}"?>">Restore</a>
							<?php else: ?>
              <?php $inonce = wp_create_nonce( "image_editor-$id" );?>
							<a href="<?php echo IMSTORE_ADMIN_URL . "image-edit.php?editimage=$id$imgnonce" ?>" class="thickbox">Edit</a> |
              <a href="<?php echo $pagenowurl . "&edit=1&id=$gal_id&action=trash&img=$id{$nonce}"?>">Trash</a>
              <img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) )?>" class="imgedit-wait-spin" alt="loading"/>
							<?php endif?>
						</div>
					</td>
					<?php break;
					
					case 'thetitle':?>
					<td class="column-<?php echo $key. $class?>" >
					<?php $disable = ( $is_trash )? 'disabled="disabled"' : ''?>
					<input type="text" name="post_title[<?php echo $id?>]" value="<?php echo $image->post_title ?>" <?php echo $disable?> class="inputxl"/>
					<textarea name="post_excerpt[<?php echo $id?>]" rows="3" <?php echo $disable?> class="inputxl"><?php echo $image->post_excerpt?></textarea>
					</td>
					<?php break;
					
					case 'imauthor':?>
					<td class="column-<?php echo $key . $class?>" >
						<?php echo $wpdb->get_var( "SELECT display_name FROM $wpdb->users WHERE ID = $image->post_author" ) ?>
					</td>
					<?php break;
					
					case 'order':?>
					<td class="column-<?php echo $key . $class?>" >
						<input type="text" name="menu_order[<?php echo $id?>]" <?php echo $disable?> value="<?php if( $image->menu_order )echo $image->menu_order ?>" class="inputxl" />
					</td>
					<?php break;
					
					case 'imageid':?>
					<td class="column-<?php echo $key . $class?>" ><?php echo sprintf( "%05d", $id )?></td>
					<?php break;
					
					
					default:?>
					<td class="column-<?php echo $key . $class?>" >&nbsp;</td>
				
				<?php }?>
				
				<?php endforeach?>
				</tr>
			<?php endforeach?>
			</tbody>
		</table>
		<p>&nbsp; </p>
	</form>
	
	</div>
</div>


<?php 

/**
 * Return galleries count by status
 *
 * @since 1.1.0
 * return array
 */
function ims_image_count_links( ){
	global $wpdb,$pagenowurl; 
	
	$r = $wpdb->get_results(
		"SELECT post_status AS status, count(post_status) AS count 
		FROM $wpdb->posts
		WHERE post_type = 'ims_image' 
		AND post_parent = '" . intval( $_GET['id'] ) . "'
		GROUP by post_status"
	);
	
	if( empty($r) )
		return $r;
	
	$labels = array(
		'trash' => __( 'Trash', ImStore::domain ),
		'publish' => __( 'Published', ImStore::domain ),
	);
	
	foreach( $r as $obj ){
		$current = ( ( $obj->status == $_GET['status']) || ( $obj->status == 'publish' && empty( $_GET['status'] ) ) ) ? ' class="current"' : '';
		$links[] = '<li>
		<a href="' . $pagenowurl . '&amp;edit=1&amp;id=' . $_GET['id'] . '&amp;status=' . $obj->status . '"' . $current . '>' . $labels[$obj->status] . ' <span class="count">(' . $obj->count . ')</span></a></li>';
		if( $obj->status != 'trash') $all += $obj->count ;
	}
	
	echo implode( ' | ', $links );
	
}

/**
 * change status
 *
 * @return void
 * @since version 0.5.0
 */
function ims_image_status( ){
	global $wpdb, $pagenowurl;
	
	if( !empty( $_GET['img'] ) ) $_GET['images'] = array( intval($_GET['img']) );
	if( empty( $_GET['images'] ) ) return;
	
	$updated = $wpdb->query(
		"UPDATE $wpdb->posts 
		SET post_status = '" . $wpdb->escape( $_GET['action'] ) . "' 
		WHERE ID IN (" . $wpdb->escape( implode( ',' , $_GET['images'] ) ) . ")"
	);
	
	$count = count( $_GET['images'] );
	$s = ( $_GET['action'] == 'trash' ) ? 1 : 2 ;
	
	if( $count < 2 && $s == 2 ) $a = 3;
	elseif( $count < 2 && $s == 1 ) $a = 5;
	elseif( $s == 1 ) $a = 8;
	else $a = 7;
	
	wp_redirect( $pagenowurl . "&edit=1&id=" . $_GET['id'] . "&ms=$a&c=$count" );
	
}


/**
 * change status
 *
 * @return void
 * @since version 0.5.0
 */
function delete_ims_images( $delete_files ){
	global $pagenowurl;
	
	if( !empty( $_GET['img'] ) ) $_GET['images'] = array( intval( $_GET['img'] ) );
	if( empty( $_GET['images'] ) ) return;

	$count = 0;
	foreach( $_GET['images'] as $image ){
		if( $delete_files ){
			$metadata = get_post_meta( $image, '_wp_attachment_metadata' );
			$folder = dirname( $metadata[0]['file'] );
			
			if( $metadata[0]['sizes'] ){
				foreach( $metadata[0]['sizes'] as $size )
					@unlink( WP_CONTENT_DIR . $folder . '/' . $size['file'] );
				@unlink( WP_CONTENT_DIR . $metadata[0]['file'] );
				@unlink( WP_CONTENT_DIR . str_replace( '_resized/', '', $metadata[0]['file'] ) );
			}
		}
		wp_delete_post( $image , true );
		$count++;
	}
	
	//update image count
	$galleid = (int)$_GET['id'];
	$imagecount = intval( get_post_meta( $galleid, '_ims_image_count', true ) ) - $count;
	update_post_meta( $galleid, '_ims_image_count', $imagecount );
	
	$count = count( $_GET['images'] );
	$a = ( $count < 2 ) ? 2 : 6 ;
	wp_redirect( $pagenowurl . "&edit=1&id=" . $_GET['id'] . "&ms=$a&c=$count" );

	
}


/**
 * update all image data
 *
 * @return void
 * @since version 0.5.0
 */
function update_image_data( ){
	global $wpdb, $pagenowurl;

	if( empty( $_GET['imageids'] ) ) return;
	foreach( $_GET['imageids'] as $image ){
		$post = array( );
		$post['ID'] = $image;
	  $post['post_title'] = $_GET['post_title'][$image];
		$post['menu_order'] = $_GET['menu_order'][$image] ;
	  $post['post_excerpt'] = $_GET['post_excerpt'][$image];
		wp_update_post( $post );
	}
	wp_redirect( $pagenowurl . "&edit=1&id=" . $_GET['id'] . "&ms=4" );
}



/**
 * Empty trash
 *
 * @param bool $delete_files
 * @return void
 * @since version 0.5.0
 */
function empty_image_trash( $delete_files ){
	global $wpdb, $pagenowurl;
	
	$trash = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'ims_image' AND post_status = 'trash'" );
	
	if( empty( $trash ) ) return;
	
	foreach( $trash as $image ){
		if( $delete_files ){
			$metadata = get_post_meta( $image->ID, '_wp_attachment_metadata' );
			$folder = dirname( $metadata[0]['file'] );
			
			if( $metadata[0]['sizes'] ){
				foreach( $metadata[0]['sizes'] as $size )
					@unlink( WP_CONTENT_DIR . $folder . '/' . $size['file'] );
				@unlink( WP_CONTENT_DIR . $metadata[0]['file'] );
				@unlink( WP_CONTENT_DIR . str_replace( '_resized/', '', $metadata[0]['file'] ) );
			}
		}
		wp_delete_post( $image->ID , true );
		$count++;
	}
	
	//update image count
	$galleid = (int)$_GET['id'];
	$imagecount = intval( get_post_meta( $galleid, '_ims_image_count', true ) ) - $count;
	update_post_meta( $galleid, '_ims_image_count', $imagecount );
	
	wp_redirect( $pagenowurl . "&edit=1&id=" . $_GET['id'] . "&ms=1" );
		
}


/**
 * Add an gallery
 *
 * @parm unit $downloadmax maximun number of downloads
 * @return string on error
 * @since 1.1.0
 */
function update_gallery_info( $disablestore ){
	global $wpdb, $pagenowurl;
	
	if( empty( $_POST['galid'] ) || !is_numeric( $_POST['galid'] ) )
		return;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['post_title'] ) )
		$errors->add( 'empty_gal_name', __( 'Please enter a gallery name.', ImStore::domain ) );

	if( empty( $_POST['_ims_price_list'] ) && !$disablestore )
		$errors->add( 'empty_list', __( 'Please select a price list.', ImStore::domain ) );
		
	if( !empty( $errors->errors ) )
		return $errors;
	
	$galleid = $_POST['galid'];
	$gallery = array(
			'ID'			=> $galleid,
			'post_date'		=> $_POST['post_date'],
			'post_expire'	=> $_POST['ims_expire'],
			'post_title' 	=> $_POST['post_title'],
			'post_password'	=> $_POST['post_password'],
	);
	
	wp_update_post( $gallery );
	
	update_post_meta( $galleid, 'ims_visits', $_POST['ims_visits'] );	
	update_post_meta( $galleid, 'ims_tracking', $_POST['ims_tracking'] );
	update_post_meta( $galleid, 'ims_downloads', $_POST['ims_downloads'] );	
	update_post_meta( $galleid, '_ims_customer', $_POST['_ims_customer'] );	
	update_post_meta( $galleid, '_ims_price_list', $_POST['_ims_price_list'] );	
	update_post_meta( $galleid, 'ims_download_max', $_POST['ims_download_max'] );
	
	wp_redirect( $pagenowurl . "&edit=1&id=$galleid&ms=9" );
}


?>