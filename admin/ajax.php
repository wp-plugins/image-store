<?php

	/**
	 * Image Store - Ajax Actions
	 *
	 * @file ajax.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/ajax.php
	 * @since 0.5.0
	 */
	 
	//dont cache file
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Last-Modified:' . gmdate( 'D,d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-control:no-cache,no-store,must-revalidate,max-age=0');
	
	//An action is required
	if ( ! isset( $_REQUEST['action'] ) )
		die( );	
		
	//define constants
	define( 'WP_ADMIN', true );
	define( 'DOING_AJAX', true );
	
	$_SERVER['PHP_SELF'] = "/wp-admin/imstore-ajax.php";

	//load wp
	require_once '../../../../wp-load.php';
	
	/**
	 * Change the image status
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function ajax_imstore_edit_image_status( ) {
		
		check_ajax_referer( "ims_ajax" );
		
		if ( !current_user_can("ims_manage_galleries") || empty( $_GET['imgid'] ) )
			die( );
			
		wp_update_post( array( 
			"ID" => intval( trim( $_GET['imgid'] ) ), 
			'post_status' => $_GET['status']
		) );
		
		die( );
	}
	
	/**
	 * Update post
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function ajax_imstore_update_post( ) {
		
		check_ajax_referer( "ims_ajax" );
		
		if ( !current_user_can( "ims_manage_galleries" ) || empty( $_GET['imgid'] ) )
			die( );
	
		$post = array(
			'ID' => (int) $_GET['imgid'],
			'menu_order' => $_GET['order'],
			'post_title' => $_GET['imgtitle'],
			'post_excerpt' => $_GET['caption'],
		);
		
		wp_update_post( $post );
		die( );
	}
	
	/**
	 * Display image ipct data
	 * 
	 * @return void
	 * @since 3.1.0
	 */
	function ajax_ims_display_iptc( ) {
		
		check_ajax_referer( "ims_ajax" );
		
		if ( !current_user_can( "ims_manage_galleries" )
			|| empty( $_REQUEST['id'] ) ) die( );
		
		$id = (int) $_REQUEST['id'];
		$meta = get_post_meta( $id, '_wp_attachment_metadata', true );
		
		if ( empty( $meta['image_meta'] ) || !is_array( $meta['image_meta'] ) )
			die( );
		
		echo '<form action="" method="post" class="meta-form">
		<div class="ims-img-metadata">';
		
		foreach ( $meta['image_meta'] as $key => $data ) {
			echo '<div class="ims-meta-field">
				<label for="' . esc_attr( $key ) . '">' . ucwords( str_replace( array( '_', '-' ), ' ', $key) ) . '</label>
				<input type="text" name="' . $key . '" value="' . esc_attr( $data ) . '" class="" />
			</div>';
		}
		
		echo '</div>
			<div class="ims-clear"><input name="save-metadata" type="submit" class="button-primary" value="' . __( 'Save', 'ims' ) . '" />
			<input name="imageid" type="hidden"  value="' . esc_attr( $id ) . '" /></div>
		</form>';
		
		die( );
	}
	
	/**
	 * Delete post
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function ajax_imstore_delete_post( ) {
		
		check_ajax_referer( "ims_ajax" );
		
		if ( empty( $_GET['postid'] ) )
			die( );
		
		if ( !current_user_can( "ims_manage_galleries" )
		&& !current_user_can( "ims_change_pricing" ) )
			die( ); 
		
		global $ImStore;
		$postid = (int) $_GET['postid'];
		
		if ( !empty( $_GET['parent'] ) && !empty( $_GET['deletefile'] ) && $ImStore->opts['deletefiles'] ) {
			$meta = get_post_meta( $postid, '_wp_attachment_metadata', true );
			
			if( $meta && is_array( $meta['sizes'] ) ) {
				
				$imgpath = $ImStore->content_dir . '/' . dirname( $meta['file'] );
				@unlink( $ImStore->content_dir . '/' . $meta['file'] );
				
				foreach ( $meta['sizes'] as $size ) {
					@unlink( $size['path'] );
					@unlink( $imgpath . "/" . $size['file'] );
					@unlink( $imgpath . "/_resized/" . $size['file'] );
				}
			}
		}

		wp_delete_post( $postid, true );
		
		die( );
	}
	
	/**
	 * modify image size mini when thumbnail 
	 * is modify by the image edit win
	 * 
	 * @return void
	 * @since 0.5.5
	 */
	function ajax_ims_get_image_mini( ) {
		
		if ( empty( $_GET['imgid'] ) )
			die( );
			
		$postid = (int) $_GET['imgid'];	
		check_ajax_referer("image_editor-{$postid}");
		
		if ( 'ims_image' != get_post_type( $postid ) )
			die( );
		
		$meta = wp_get_attachment_metadata( $postid );
		
		if ( empty( $meta['sizes']['mini']['url'] ) )
			die( );
		
		echo $meta['sizes']['mini']['url'];
		
		die( );
	}
	
	/**
	 * Serch galleries
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function ajax_ims_search_galleries() {
		
		check_ajax_referer( "ims_ajax" );
		
		if ( !current_user_can( "ims_manage_galleries" ) )
			die( );
		
		$q = empty($_GET['q']) ? false : $_GET['q'];
		$qfilter = ( $q ) ? " AND p.post_title LIKE '%%%s%%' " : false;
		$limit = ( isset( $_GET['c'] ) && is_numeric( $_GET['c'] ) ) ? $_GET['c'] . "," . $_GET['c'] + 10 : "0, 30 ";
		
		global $wpdb, $ImStore;
		
		$galleries = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.id, pm.meta_value v, p.post_title t FROM $wpdb->posts p 
			LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
			WHERE 1=1 AND ( pm.meta_key = '_ims_gallery_id' ) 
			AND p.post_type = 'ims_gallery' $qfilter 
			ORDER BY p.post_date DESC LIMIT $limit"
		, $q ));
		
		if ( empty( $galleries ) ) {
			echo '<li class="gal-0"><span class="gtitle"><em>' . __( ' Sorry, nothing found.', 'ims' ) . '</em></span></li>' ;
			die();
		}
		
		foreach ( $galleries as $gal )
			echo '<li class="gal-' . $gal->id . '"><span class="gtitle">' . $gal->t . '</span><span class="id">' . trim($gal->v) . '</span></li>';
		
		die( );
	}
	
	/**
	 * vote actions
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ajax_ims_vote_actions( ){
		
		check_ajax_referer( "ims_ajax_favorites" );
				
		if ( empty( $_GET['action'] ) || empty( $_GET['imgid'] ) ) 
			die( );
		
		global $ImStore, $user_ID;
		
		if ( empty( $user_ID ) ) 
			die( );
		
		$action = $_GET['action'];
		$imgid = $ImStore->url_decrypt( $_GET['imgid'] );
		
		if( $action == 'remove-vote' )
			delete_user_meta( $user_ID, '_ims_image_like', $imgid );
			
		else if( $action == 'vote' )
			add_user_meta( $user_ID, '_ims_image_like', $imgid, false );
		
		echo $ImStore->get_image_vote_count( $imgid );
	
		die( );
	}
	
	/**
	 * favorite image actions
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ajax_ims_favorites_images_actions( ) {
		
		check_ajax_referer( "ims_ajax_favorites" );
				
		if ( empty( $_GET['action'] ) )
			die( );
		
		if ( empty( $_GET['imgids'] ) ) {
			echo __( 'Please, select an image', 'ims' ) . '|ims-error';
			die( );
		}
				
		$join = array( ) ;
		$dec_ids = array( );
		$action = $_GET['action'];
		$new = explode( ',', $_GET['imgids'] );
				
		global $ImStore, $user_ID;
		
		foreach ( $new as $id )
			$dec_ids[] = $ImStore->url_decrypt( $id );
		
		if( $user_ID ) 
			$join = explode( ',', get_user_meta( $user_ID, '_ims_favorites', true ) );
			
		else if( isset( $_COOKIE['ims_favorites_' . COOKIEHASH] ) )
			$join = explode( ',', $_COOKIE['ims_favorites_' . COOKIEHASH] );
	
		
		if( $action == 'remove-favorites' ){
			
			if( empty( $_GET['count'] ) )
				$join = array( );
			
			$join = array_flip( $join );
			foreach ( $dec_ids as $remove)
				unset( $join[$remove] );
			
			$join = array_filter( array_unique( array_flip( $join ) ) );
			
			if( $user_ID ) update_user_meta( $user_ID, '_ims_favorites',  implode( ',', $join ) );
			else  setcookie( 'ims_favorites_' . COOKIEHASH, implode( ',', $join ), 0, COOKIEPATH, COOKIE_DOMAIN);

			if ( count( $new ) < 2 ) echo __( 'Image removed from favorites', 'ims' ) . '|ims-success|' . count( $join );
			else echo sprintf( __( '%d images removed from favorites', 'ims' ), count( $new ) ) . '|ims-success|' . count( $join );

		} else if ( $action == 'favorites' ) {
			
			 $join = array_filter( array_unique( array_merge( $join, $dec_ids ) ) ); 
			 
			 if( $user_ID ) update_user_meta( $user_ID, '_ims_favorites',  implode( ',', $join ) );
			else  setcookie( 'ims_favorites_' . COOKIEHASH, implode( ',', $join ), 0, COOKIEPATH, COOKIE_DOMAIN);

			if ( count( $new ) < 2 ) echo __( 'Image added to favorites', 'ims' ) . '|ims-success|' . count( $join );
			else echo sprintf( __( '%d images added to favorites', 'ims' ), count( $new ) ) . '|ims-success|' . count( $join );
			
		}
		
		die( );	
	}
	
	
	
	//do that thing you do
	switch ( $_GET['action'] ) {
		case 'imageiptc':
			ajax_ims_display_iptc( );
			break;
		case 'favorites':
		case 'remove-favorites':
			ajax_ims_favorites_images_actions( );
			break;
		case 'vote':
		case 'remove-vote':
			ajax_ims_vote_actions( );
			break;
		case 'deleteimage':
		case 'deletepackage':
			ajax_imstore_delete_post( );
			break;
		case 'upadateimage':
			ajax_imstore_update_post ();
			break;
		case 'edit-mini-image':
			ajax_ims_get_image_mini( );
			break;
		case 'editimstatus':
			ajax_imstore_edit_image_status( );
			break;
		case 'searchgals':
			ajax_ims_search_galleries( );
			break;
		default: die( );
	}
	
	die( );