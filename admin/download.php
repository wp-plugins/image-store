<?php

	/**
	 * Image Store - Download image
	 *
	 * @file download.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/download.php
	 * @since 0.5.0
	 */
		
		//define constants
		define( 'WP_ADMIN', true );
		define( 'DOING_AJAX', true );
		
		$_SERVER['PHP_SELF'] = "/wp-admin/download.php";
		
		//load wp
		require_once '../../../../wp-load.php';
		
		class ImStoreDownloadImage {
			
			private $id = false;
			private $clean = false;
			private $image_dir = '';
			
			/**
			 * Constructor
			 *
			 * @return void
			 * @since 0.5.0 
			 */
			function ImStoreDownloadImage() {
		
				//normalize nonce field
				wp_set_current_user( 0 );
		
				if ( empty( $_REQUEST['img'] ) || empty( $_REQUEST["_wpnonce"] ) ||
				!wp_verify_nonce( $_REQUEST["_wpnonce"], "ims_download_img" ) ) 
					die( );
				
				
				global $ImStore;

				$this->id = (int) $ImStore->url_decrypt( $_REQUEST['img'], false );
				$this->attachment = (array) get_post_meta( $this->id, '_wp_attachment_metadata', true );
				
				if ( empty( $this->attachment ) )
					wp_die( __( 'Sorry, we could find the image' ) );
					
				$sizes = get_option( 'ims_sizes', true );
				$imgsize = empty( $_REQUEST['sz'] ) ? 'preview' : $_REQUEST['sz'];
				
				$dimentions = array( );
				foreach ( $sizes as $size) {
					if ( $size['name'] == $imgsize ) {
						$dimentions = $size;
						break;
					}
				}
				
				
				if ( empty( $dimentions['w'] ) || empty( $dimentions['h'] ) ) {
					$size = explode( 'x', strtolower( $imgsize ) );
					
					if ( count( $size ) == 2 && is_numeric( $size[0] ) ) {
						$dimentions['w'] = $size[0];
						$dimentions['h'] = $size[1];
						
					} else {
						$dimentions['w'] = $dimentions['h'] = false;
						
					}
				}
					

				// search for the rigth image size
				
				if ( isset( $this->attachment['sizes'][$imgsize]['path'] ) ) {
					$this->image_dir = $this->attachment['sizes'][$imgsize]['url'];
					
				
				} elseif ( $dimentions['w'] && $dimentions['h'] && empty( $ImStore->opts['downloadorig'] ) ) {
					$full_image_path = $ImStore->content_dir . "/" . $this->attachment['file'];
					$this->clean = true;
					
					if( function_exists( 'wp_get_image_editor' ) ){
						$image_editor = wp_get_image_editor( $full_image_path );
						
						if ( ! is_wp_error( $image_editor ) ) {
							$image_editor->resize( $dimentions['w'], $dimentions['h'], true );
							$image_data = $image_editor->save( );
							$this->image_dir = $image_data['path'];
						}
							
							
					} else $this->image_dir = image_resize( $full_image_path, $dimentions['w'], $dimentions['h'], 0, 0, 0, 100 );
					
					
					if ( is_wp_error( $this->image_dir ) && isset( $this->attachment['sizes']['preview']['path'] ) ) {
						$this->image_dir = apply_filters( 'ims_download_image_preview', $this->attachment['sizes']['preview']['path'], $this->id );
						$this->clean = false;
					
					
					} elseif ( is_wp_error( $this->image_dir ) || empty( $this->image_dir ) ) {
						$this->image_dir = $ImStore->content_dir . "/" . $this->attachment['file'];
						$this->clean = false;
						
						
					}
					
					
				} elseif ( !empty( $ImStore->opts['downloadorig'] ) ) {
					$this->image_dir = apply_filters( 'ims_download_image_original', $ImStore->content_dir . "/" . $this->attachment['file'], $this->id);
					
				
				} elseif ( isset( $this->attachment['sizes']['preview']['path'] ) )
					$this->image_dir = apply_filters( 'ims_download_image_preview', $this->attachment['sizes']['preview']['path'], $this->id );
				
				
				if( file_exists( $this->image_dir ) )
					$this->display_image( );
				
			}
			
			/**
			 * Display image
			 *
			 * @return void
			 * @since 0.5.0 
			 */
			function display_image( ) {
				
				global $wpdb;
				
				$type = wp_check_filetype( basename( $this->image_dir ) ); 
				$filename = $wpdb->get_var( "SELECT post_title FROM $wpdb->posts WHERE ID = " . $this->id );
				
				$ext = $type['ext'];
				$download_fname = apply_filters( 'ims_download_filename', $filename .".{$ext}", $this->image_dir, $type, $this->id );
				
				header( "Content-Type: image/{$ext}" );
				
				if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) )
					header( 'Content-Length: ' . filesize( $this->image_dir ) );
				
				$color = isset($_REQUEST['c']) ? $_REQUEST['c'] : false;
				$modified = gmdate( "D, d M Y H:i:s", @filemtime( $this->image_dir )  );
				$etag = '"' . md5( $this->image_dir . $color . $modified ) . '"';
				
				header("Robots: none");
				header( 'X-Content-Type-Options: nosniff' );
	
				header( 'ETag: ' . $etag );
				header( 'Last-Modified: $modified GMT' );
				header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time(  ) + 100000000 ) . ' GMT');
				header( 'Cache-Control:max-age=' . ( time(  ) + 100000000 ) . ',must-revalidate' );
				
				header( 'Content-Description: File Transfer' );
				header( 'Content-Transfer-Encoding: binary' );
				header( 'Content-Disposition: attachment; filename="' . $download_fname .'"' );


				if ( !$color ) {
					@readfile( $this->image_dir );
					die( );
				}
				
				switch ( $ext ) {
					case "jpg":
					case "jpeg":
						$image = imagecreatefromjpeg( $this->image_dir );
						break;
					case "gif":
						$image = imagecreatefromgif( $this->image_dir );
						break;
					case "png":
						$image = imagecreatefrompng( $this->image_dir );
						break;
					default:
						die( );
				}
				
				//apply filter
				$filters = get_option( 'ims_color_filters' );
				
				if ( $color && isset( $filters[$color] ) ) {
					if ( $filters[$color]['grayscale'] )
						imagefilter( $image, IMG_FILTER_GRAYSCALE);
		
					if ( $filters[$color]['contrast'] )
						imagefilter( $image, IMG_FILTER_CONTRAST, $filters[$color]['contrast'] );
		
					if ( $filters[$color]['brightness'] )
						imagefilter( $image, IMG_FILTER_BRIGHTNESS, $filters[$color]['brightness'] );
		
					if ( $filters[$color]['brightness'] )
						imagefilter( $image, IMG_FILTER_BRIGHTNESS, $filters[$color]['brightness'] );
		
					if ( $filters[$color]['colorize'] ) {
						$args = array( $image, IMG_FILTER_COLORIZE);
						$args = array_merge( $args, explode( ',', $filters[$color]['colorize'] ) );
						call_user_func_array( 'imagefilter', $args );
					}
				}
		
				do_action( 'ims_apply_color_filter', $image );
				
				//create new image
				switch ( $ext ) {
					case "jpg":
					case "jpeg":
						imagejpeg( $image, NULL, 100 );
						break;
					case "gif":
						imagegif( $image );
						break;
					case "png":
						imagepng( $image, NULL, 9 );
						break;
				}
		
				
				@imagedestroy( $image );

				if ( $this->clean )
					@unlink( $this->image_dir );
		
				die( );
			}
		}
		
		//do that thing you do 
		new ImStoreDownloadImage( );