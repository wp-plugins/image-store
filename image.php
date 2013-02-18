<?php

	/**
	 * Image Store - secure image
	 *
	 * @file image.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/image.php
	 * @since 0.5.0
	 */
	 
	// define constants
	define( 'SHORTINIT', true );
	define( 'DOING_AJAX', true );
	
	//load wp
	require_once '../../../wp-load.php';
	
	
	class ImStoreImage {
		
		private $id = false;
		private $path = false;
		private $image_dir = false;
		private $watermark = false;
		private $content_dir = false;
		
		private $data = array( );
		private $opts = array( );
		private $metadata = array( );
		
		public $image_sizes = array( 
			1 => 'preview', 
			2 => 'thumbnail', 
			3 => 'mini', 
			4 => 'original' 
		);
		
		/**
		 * Constructor
		 *
		 * @return void
		 * @since 0.5.0 
		 */
		function ImStoreImage( ) {
		
			if ( empty( $_GET['i'] ) )
				die( );
			
			$this->key =  substr( preg_replace("([^a-zA-Z0-9])", '', NONCE_KEY ), 0, 15 );
			
			$url_data = explode( ':', $this->url_decrypt( $_GET['i'], false ), 3 );
			if ( empty( $url_data[0] ) || empty( $url_data[1] ) || !is_numeric( $url_data[0] ) )
				die( );
				
			$this->id = $url_data[0];
			$this->content_dir = rtrim( WP_CONTENT_DIR, '/' );
			$this->data = wp_cache_get( 'ims_meta_image_' . $this->id, 'ims' );
			
			if ( false == $this->data ) {
				global $wpdb;
				
				$this->data = $wpdb->get_row(
					$wpdb->prepare(
					"SELECT meta_value  meta FROM $wpdb->postmeta 
					WHERE meta_key = '_wp_attachment_metadata' 
					AND $wpdb->postmeta.post_id = %d LIMIT 1", $this->id
				) );
				wp_cache_set( 'ims_meta_image_' . $this->id, $this->data,  'ims' );
			}
			
			if ( empty( $this->data->meta ) )
				die( );
			
			$this->metadata = maybe_unserialize( $this->data->meta );
			$this->path = $this->content_dir  . "/". dirname( $this->metadata['file'] );
			
			if ( !preg_match( '/_resized/i', $this->path ) )
				$this->path .= "/_resized";
			
			$size =  'thumbnail';
			if( isset( $this->image_sizes[ $url_data[1] ] ) )
				$size = $this->image_sizes[ $url_data[1] ];
			
			if( $size == 'original' )
				$this->image_dir = $this->content_dir  . "/". $this->metadata['file'];
			
			elseif( isset( $this->metadata['sizes'][ $size ]['file'] ) )
				$this->image_dir = $this->path . '/' . $this->metadata['sizes'][ $size ]['file'];
			
			if ( isset( $url_data[2] ) )
				$this->watermark = 1;
		
			$this->display_image( );
		}
		
		/**
		 * Display image
		 *
		 * @return void
		 * @since 0.5.0 
		 */
		function display_image( ) {
			
			$ext = trim( substr( strrchr( $this->image_dir, '.' ),1 ) );
			header( 'Content-Type: image/' . $ext );
			
			$color = isset( $_GET['c'] ) ? $_GET['c'] : false;
			$cache = get_option( 'ims_cache_time', time(  ) );
			$cache_time = ( @filemtime( $this->image_dir ) + $cache );
			
			$modified = gmdate( "D, d M Y H:i:s", $cache_time );
			$etag = '"' . md5( $this->image_dir . $color . $this->watermark . $modified ) . '"';
			$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;
			
			header( 'ETag: ' . $etag );
			header( "Last-Modified: $modified GMT");
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time(  ) + 100000000 ) . ' GMT');
			header( 'Cache-Control:max-age=' . ( time(  ) + 100000000 ) . ',must-revalidate' );
			
			if ( ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && isset( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& ( strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) == ( $cache_time ) ) ) || ( $client_etag == $etag )) {
				header( 'HTTP/1.1 304 Not Modified' );
				die( );
			}
			
			if ( empty( $color ) && !$this->watermark ) {
				@readfile( $this->image_dir );
				die( );
			}
	
			if ( !function_exists( 'get_site_option' ) ) {
				header( "HTTP/1.0 404 Not Found" );
				die( );
			}
			
			if ( get_site_option( 'ims_sync_settings' ) )
				$this->opts = get_blog_option( 1, 'ims_front_options' );
			else $this->opts = get_option( 'ims_front_options' );
			
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
						
			//add watermark		
			if ( $this->opts['watermark'] ) {
				
				$position = get_option( 'ims_wlocal' );
				
				//text watermark
				if ( $this->opts['watermark'] == 1 ) {
					
					$font_text = $this->opts['watermark_text'];
					$font_size = $this->opts['watermark_size'];
					$font = dirname( __FILE__ ) . '/_fonts/arial.ttf';
					$rgb = $this->HexToRGB( $this->opts['watermark_color'] );
					
					$black = imagecolorallocatealpha( $image, 0, 0, 0, 90 );
					$icolor = imagecolorallocatealpha( $image, $rgb['r'], $rgb['g'], $rgb['b'], $this->opts['watermark_trans'] );
					
					$info = getimagesize( $this->image_dir );
					$tb = imagettfbbox( $font_size, 0, $font, $font_text );
					
					switch ( $position ) {
						case 1:
							$x = 2;
							$y = abs($tb[5]) + 2;
							break;
						case 2:
							$x = ceil(( $info[0] - $tb[2] ) / 2);
							$y = abs($tb[5]) + 2;
							break;
						case 3:
							$x = ($info[0] - $tb[2] ) - 4;
							$y = abs($tb[5]) + 2;
							break;
						case 4:
							$x = 2;
							$y = $info[1] / 2;
							break;
						case 5:
							$x = ceil(( $info[0] - $tb[2] ) / 2);
							$y = $info[1] / 1.7;
							break;
						case 6:
							$x = ($info[0] - $tb[2] ) - 4;
							$y = $info[1] / 2;
							break;
						case 7:
							$x = 2;
							$y = $info[1] / 1.03;
							break;
						case 9:
							$x = ($info[0] - $tb[2] ) - 4;
							$y = $info[1] / 1.03;
							break;
						default:
							$x = ceil(( $info[0] - $tb[2] ) / 2);
							$y = $info[1] / 1.03;
					}
					
					imagettftext( $image, $font_size, 0, $x, $y, $black, $font, $font_text );
					imagettftext( $image, $font_size, 0, $x, $y, $icolor, $font, $font_text );					
				
				}else if ( $this->opts['watermark'] == 2  && $this->opts['watermarkurl'] ) {
					
					$wmtype = wp_check_filetype( basename( $this->opts['watermarkurl'] ) );
					if ( preg_match( '/(png|jpg|jpeg|gif)$/i', $wmtype['ext'] ) ){
						
						if( file_exists( $this->content_dir  . "/". $this->opts['watermarkurl'] ) )
							$wmpath = $this->content_dir  . "/". $this->opts['watermarkurl'];
						else $wmpath = $this->opts['watermarkurl'];
						
						if ( $content = @file_get_contents( $wmpath ) ) {
							
							switch ( $wmtype['ext'] ) {
								case "jpg":
								case "jpeg":
									$watermark = @imagecreatefromjpeg( $wmpath );
									break;
								case "gif":
									$watermark = @imagecreatefromgif( $wmpath );
									break;
								case "png":
									$watermark = @imagecreatefrompng( $wmpath );
									break;
							}
							
							if( !empty( $watermark ) ){
								if( $wminfo = getimagesize( $wmpath ) ){
									
									$info = getimagesize( $this->image_dir );
									$wmratio = $this->image_ratio( $wminfo[0], $wminfo[1], max( $info[0], $info[1] ) );
									
									switch ( $position ) {
										case 1:
											$x = $y = 2;
											break;
										case 2:
											$x = ( $info[0] - $wmratio['w'] ) / 2;
											$y = 2;
											break;
										case 3:
											$x = ( $info[0] - $wmratio['w'] ) - 4;
											$y = 2;
											break;
										case 4:
											$x = 2;
											$y = ( $info[1] - $wmratio['h'] ) / 2;
											break;
										case 6:
											$x = ( $info[0] - $wmratio['w'] ) - 4;
											$y = ( $info[1] - $wmratio['h'] ) / 2;
											break;
										case 7:
											$x = 2;
											$y = ( $info[1] - $wmratio['h'] ) - 4;
											break;
										case 8:
											$x = ( $info[0] - $wmratio['w'] ) / 2;
											$y = ( $info[1] - $wmratio['h'] ) - 4;
											break;
										case 9:
											$x = ( $info[0] - $wmratio['w'] ) - 4;
											$y = ( $info[1] - $wmratio['h'] ) - 4;
											break;
										default:
											$x = ( $info[0] - $wmratio['w'] ) / 2;
											$y = ( $info[1] - $wmratio['h'] ) / 1.7;
									}
									
									$wmnew = imagecreatetruecolor( $wmratio['w'], $wmratio['h'] );
							
									// keep transperancy
									if ( $wmtype['ext'] == "png" ) {
										$background = imagecolorallocate( $wmnew, 0, 0, 0 );
										ImageColorTransparent( $wmnew, $background );
										imagealphablending( $wmnew, true );
									}
									
									// resize watermark and merge images
									imagecopyresampled( $wmnew, $watermark, 0, 0, 0, 0, $wmratio['w'], $wmratio['h'], $wminfo[0], $wminfo[1] );
									imagecopymerge( $image, $wmnew, $x, $y, 0, 0, $wmratio['w'], $wmratio['h'], 30 );
									
									@imagedestroy( $wmnew );
									@imagedestroy( $watermark );
									
								}
							}
						}
					}
				}
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
			
			
			$quality = get_option( 'preview_size_q', 85 );
			
			// create new image
			switch ( $ext ) {
				case "jpg":
				case "jpeg":
					imagejpeg( $image, NULL, $quality );
					break;
				case "gif":
					imagegif( $image );
					break;
				case "png":
					$quality = ( ceil( $quality / 10 ) > 9 ) ? 9 : ceil( $quality / 10 );
					imagepng( $image, NULL, $quality );
					break;
				default:
					die( );
			}

			@imagedestroy( $image );
			
			die( );
		}
		
		/**
		 * Conver hex color to rgb
		 *
		 * @param string $hex
		 * @return unit/string
		 * @since 0.5.0 
		 */
		function HexToRGB( $hex ) {
			$hex = ereg_replace( "#", "", $hex );
			$color = array( );
	
			if (strlen($hex) == 3) {
				$color['r'] = hexdec(str_repeat(substr($hex, 0, 1), 2));
				$color['g'] = hexdec(str_repeat(substr($hex, 1, 1), 2));
				$color['b'] = hexdec(str_repeat(substr($hex, 2, 1), 2));
			} else if (strlen($hex) == 6) {
				$color['r'] = hexdec(substr($hex, 0, 2));
				$color['g'] = hexdec(substr($hex, 2, 2));
				$color['b'] = hexdec(substr($hex, 4, 2));
			}
			return $color;
		}
		
		/**
		 * Get image ratio
		 *
		 * @param unit $w
		 * @param unit $h
		 * @param unit $immax
		 * @return unit
		 * @since 0.5.0 
		 */
		function image_ratio( $w, $h, $immax ) {
			$i = array( );
			$max = max( $w, $h );
			$r = $max > $immax ? ( $immax / $max ) : 1;
			$i['w'] = ceil( $w * $r * .8 );
			$i['h'] = ceil( $h * $r * .8 );
			return $i;
		}
		
		/**
		 * Encrypt url
		 *
		 * @parm string $string
		 * @return string
		 * @since 2.1.1
		 */
		function url_decrypt( $string, $url = true ) {
			
			$decoded = '';
			$string = ( $url ) ? urldecode( $string ) : $string;
			$string = base64_decode( implode( '/', explode( '::', $string ) ) );
			
			for ( $i = 0; $i < strlen( $string ); $i++ ) {
				$char = substr( $string, $i, 1 );
				$keychar = substr( $this->key, ( $i % strlen( $this->key ) ) - 1, 1);
				$char = chr( ord( $char ) - ord( $keychar ) );
				$decoded.=$char;
			}
			
			return $decoded;
		}
	}
	
	
	// do that thing you do 
	new ImStoreImage( );