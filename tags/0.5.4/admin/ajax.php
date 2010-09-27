<?php 

/**
 * Ajax events for admin area
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/

//dont cache file
header( 'Expires: 0');
header( 'Pragma: no-cache' );
header( 'Cache-control: private');
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-control: no-cache, no-store, must-revalidate, max-age=0');


//define constants
define( 'DOING_AJAX', true );
define( 'WP_ADMIN', true );

//load wp
require_once '../../../../wp-load.php';

//make sure that the request came from the same domain	
if ( stripos( $_SERVER['HTTP_REFERER'], get_bloginfo('siteurl')) === false ) 
	die( );


/**
 * Move price list to trash
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_imstore_pricelist_delete( ){
	
	if( !current_user_can( "ims_change_pricing" ) )
		return;
		
	check_ajax_referer( "ims_ajax" );
	
	wp_delete_post( intval( $_GET['listid'] ), true );
	
	die( );
}


/**
 * Move price list to trash
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_imstore_package_delete( ){
	
	if( !current_user_can( "ims_change_pricing" ) )
		return;
		
	check_ajax_referer( "ims_ajax" );
	
	wp_delete_post( intval( $_GET['packageid'] ), true );
	
	die( );
}


/**
 * display event path
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_ims_swfupload_path( ){
	global $wpdb;
	
	if( !current_user_can( "ims_import_images" ) )
		return;
	
	check_ajax_referer( "ims_ajax" );
	
	echo '../wp-content' . get_post_meta( $_GET['galleryid'], '_ims_folder_path' , true );
	
	die( );	
}



/**
 * add image to database
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_ims_flash_image_data( ){
	global $wpdb;
	
	if( !current_user_can( 'ims_import_images' ) )
		return false;
	
	$galleid = $_GET['galleryid'];
	$filename = $_GET['imagename'];
	$abspath = $_GET['filepath'];
	$filetype = wp_check_filetype( $filename );
	$des_path = dirname( $abspath ) . '/_resized' ;
	$relative = str_replace( str_replace( '\\' , '/', WP_CONTENT_DIR ), '', str_replace( '\\' , '/', $des_path . '/' . $filename ));
	$guid = WP_CONTENT_URL . $relative;
	if( !file_exists( $des_path ) ) @mkdir( $des_path, 0775 );

	$attachment = array(
		'guid' => $guid,
		'post_title' => $filename,
		'post_type' => 'ims_image',
		'post_mime_type'=> $filetype['type'],
		'post_status' => 'publish',
		'post_parent' => $galleid,
	);
	
	//if image exist dont't load it
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE 1=1 AND guid = %s", $guid ) ) ) return;
	
	$attach_id = wp_insert_post( $attachment );
	if( empty( $attach_id ) ) return;
	
	include_once( ABSPATH . 'wp-admin/includes/image.php');
	
	//resize images
	$img_sizes = get_option( 'ims_dis_images' );
	$downloadsizes = get_option( 'ims_download_sizes' );
	if( is_array( $downloadsizes ) ) $img_sizes += $downloadsizes;
	
	foreach( $img_sizes as $img_size ){
		$resized = image_resize( $abspath, $img_size['w'], $img_size['h'], $img_size['crop'], null, $des_path , $img_size['q'] );
		if ( !is_wp_error( $resized ) && $resized && $info = getimagesize($resized) ) {
			$imgname = basename( $resized );
			$data = array(
				'file' 	=> $imgname,
				'width' => $info[0],
				'height'=> $info[1],
			);
		}
		
		//copy file to be use when plugin is uninstall
		@copy( $abspath, $des_path . '/' . $filename );
			
		//create metadata
		$imagesize = getimagesize( $abspath );
		$metadata['width'] = $imagesize[0];
		$metadata['height'] = $imagesize[1];
		list($uwidth, $uheight) = wp_constrain_dimensions($metadata['width'], $metadata['height'], 128, 96);
		$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
		
		switch( $imagesize['channels'] ){ 
			case 1: $metadata['color'] = 'BW'; break;
			case 3: $metadata['color'] = 'RGB'; break;
			case 4: $metadata['color'] = 'CMYK'; break;
			default: $metadata['color'] = __( 'Unknown', ImStore::domain );
		}
		
		$metadata['file'] = $relative;
		$metadata['url'] = basename( $filepath );
		$metadata['sizes'][$img_size['name']] = $data;
		$metadata['image_meta'] = wp_read_image_metadata( $abspath );
	}
	
	//update image count
	$count = intval( get_post_meta( $galleid, '_ims_image_count', true ) ) + 1;
	update_post_meta( $galleid, '_ims_image_count', $count );
	
	wp_update_attachment_metadata( $attach_id, $metadata );
	
}

/**
 * Add images to favorites
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_ims_add_images_to_favorites( ){
	
	$id = intval( $_GET['galid'] );
	check_ajax_referer( "ims_ajax_favorites" );
	
	if( empty( $_GET['imgids'] ) || empty( $id ) ){
		echo __( 'Please, select an image', ImStore::domain ) . '|error';
	}else{
		$old = get_post_meta( $id, '_ims_favorites', true );
		if( is_array( $old ) ){
			$new = explode( ',', $_GET['imgids'] );
			$ids = array_merge( $old, $new );
			$ids = array_unique( $ids );
		}else{
			$new = explode( ',', $_GET['imgids'] );
			$ids = $new;
		}
		update_post_meta( $id, '_ims_favorites', $ids );
		if( count( $new ) < 2 ) echo __( 'Image added to favorites', ImStore::domain ) . '|success';
		else echo sprintf( __( '%d images added to favorites', ImStore::domain ), count( $new ) ) . '|success';
	}
	
}


/**
 * Remove images from favorites
 * 
 * @return void
 * @since 0.5.0
 */
function ajax_ims_remove_images_from_favorites( ){
	
	$id = intval( $_GET['galid'] );
	check_ajax_referer( "ims_ajax_favorites" );
	
	if( empty( $_GET['imgids'] ) || empty( $id ) ){
		echo __( 'Please, select an image', ImStore::domain ) . '|error';
	}else{
		$new = explode( ',', $_GET['imgids'] );
		$old = get_post_meta( $id, '_ims_favorites', true );
		$old = array_flip( $old );
		foreach( $new as $remove ) unset( $old[$remove] );
		$ids = array_flip( $old );
		
		update_post_meta( $id, '_ims_favorites', $ids );
		if( count( $new ) < 2 ) echo __( 'Image removed from favorites', ImStore::domain ) . '|success';
		else echo sprintf( __( '%d images removed from favorites', ImStore::domain ), count( $new ) ) . '|success';
	}
	
}


if( $_GET['action'] == 'deletelist' )
	ajax_imstore_pricelist_delete( );

if( $_GET['action'] == 'deletepackage' )
	ajax_imstore_package_delete( );
	
if( $_GET['action'] == 'swuploadfolder' )
	ajax_ims_swfupload_path( );
	
if( $_GET['action'] == 'flashimagedata' )
	 ajax_ims_flash_image_data( );

if( $_GET['action'] == 'favorites' )
	 ajax_ims_add_images_to_favorites( );

if( $_GET['action'] == 'remove-favorites' )
	 ajax_ims_remove_images_from_favorites( );

?>