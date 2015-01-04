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
		
		private 	$id = false;
		private 	$image = false;
		private 	$image_dir = false;
		private 	$watermark = false;
		private 	$content_dir = false;
		private 	$gallery_path = false;
		
		private 	$data = array( );
		private 	$opts = array( );
		private 	$resize = array( );
		private 	$url_data = array();
		private 	$metadata = array( );
		
		public $ext = 'jpg'; 
		public $space = 10; 
		public $color = false; 
		public $image_sizes = array( 
			1 => 'preview', 
			2 => 'thumbnail', 
			3 => 'mini', 
			4 => 'original',
			5 => 'medium',
			6 => 'post_thumbnail',
		);
		
			
		/**
		 * Constructor
		 *
		 * @return void
		 * @since 0.5.0 
		 */
		function ImStoreImage( ) {
			
			// set encryption key
			$this->key = apply_filters( 'ims_image_key', 
				substr( preg_replace( "([^a-zA-Z0-9])", '', NONCE_KEY ), 0, 15 ) 
			);
			
			// get url data
			$url_data = explode( ':', 
				$this->url_decrypt( $_GET['i'], false ), 3 
			);
			
			//validate url data
			if ( empty( $url_data[0] ) || ! is_numeric( $url_data[0] ) )
				$this->status( 204 );
			
			$this->set_path_data( $url_data );
			$this->set_image_http_headers( );
			
			if( is_readable( $this->image_dir ))
				$this->display_image( );	
		}
		
		/**
		 * Set image path and resize data
		 *
		 * @parm array $url_data 
		 * @return string | array
		 * @since 3.3.3
		 */
		function set_path_data( $url_data ){
			
			//default size
			$this->id = $url_data[0];
			$this->size = 'thumbnail';
			$this->url_data = $url_data;
			
			if ( isset( $this->url_data[2] ) )
				$this->watermark = 1;
				
			if( isset( $this->image_sizes[ $url_data[1] ] ) )
				$this->size = $this->image_sizes[ $url_data[1] ];
										
			$this->color = isset( $_GET['c'] ) ? $_GET['c'] : false;
		}
		
		/**
		 * Set image http headers
		 *
		 * @return null | bool
		 * @since 3.5.2
		 */
		function set_image_http_headers ( ){
			
			$cache = get_option( 'ims_cache_time', time(  ) );
			$etag = md5( $this->color . $this->watermark . $this->id . $this->size . $cache ) ;
			$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;
								
			if ( $client_etag == $etag  )
				status_header( 304 );
			
			$this->get_image_metadata( );
		
			header( 'ETag: ' . $etag );
			header( 'Content-Type: image/' . $this->ext );
			header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time(  ) + 2419200 ) . ' GMT');
			header( 'Cache-Control:max-age=' . ( time(  ) + 2419200 ) . ',must-revalidate,public' );
			header( 'Last-Modified: ' . gmdate( "D, d M Y H:i:s", @filemtime( $this->image_dir ) ) .' GMT');
		}
		
		/**
		 * Get image metadata
		 *
		 * @return void
		 * @since 3.5.2
		 */
		function get_image_metadata ( ){
			
			// use cache data
			if ( false == ($this->data = wp_cache_get( 'ims_meta_image_' . $this->id, 'ims' )) ) {
				
				global $wpdb;
				$this->data = $wpdb->get_row(
					$wpdb->prepare(
					"SELECT meta_value  meta FROM $wpdb->postmeta 
					WHERE meta_key = '_wp_attachment_metadata' 
					AND $wpdb->postmeta.post_id = %d LIMIT 1", $this->id
				) );
				wp_cache_set( 'ims_meta_image_' . $this->id, $this->data,  'ims' );
			}
			
			//no data found
			if ( empty( $this->data->meta ) )
				$this->status( 204 );
	
			$this->content_dir 	= rtrim( WP_CONTENT_DIR, '/' ) ;
			$this->quality 			= get_option( 'preview_size_q', 85 );
			$this->metadata 		= maybe_unserialize( $this->data->meta );
			$this->original_file 	= $this->content_dir .  '/' . $this->metadata['file'];
			$this->gallery_path 	= str_ireplace( '/_resized', '', dirname( $this->original_file ));
			
			if( isset( $this->metadata['sizes'][ $this->size ]['file'] ) ) {
				$this->ext = trim( substr( strrchr( $this->metadata['sizes'][ $this->size ]['file'] , '.' ),1 ));
				return $this->image_dir = $this->gallery_path . "/_resized/". $this->metadata['sizes'][ $this->size ]['file'];
			}
			
			$this->resize = array( 
				get_option( "{$this->size}_size_w" ), 
				get_option( "{$this->size}_size_h" ),
			);
		}
		
		/**
		 * Display image
		 *
		 * @return void
		 * @since 0.5.0 
		 */
		function display_image( ) {
						
			if ( ! $this->color && ! $this->watermark && ! $this->resize ) {
				if( $this->size == 'original' )
					@readfile( $this->image_dir );
				else if( $this->create_image( ) );
					 $this->output_image( );
			}
			
			// multisize is not supported exit
			if ( function_exists( 'get_site_option' ) ) {
				if ( get_site_option( 'ims_sync_settings' ) )
					switch_to_blog( 1 );
			}
			
			//create image
			if( ! $this->create_image( ) )
				status_header( 204 );
				
			$this->opts = get_option( 'ims_front_options' );
				
			//add watermark		
			if ( $this->opts['watermark'] && $this->watermark )
				$this->add_water_mark( );

			//apply color filter
			if ( $this->color ) 
				$this->apply_color_filter(  );
			
			 $this->output_image( );
		}
		
		/**
		 * Apply watermark to image
		 *
		 * @return void
		 * @since 3.3.3
		 */
		function add_water_mark(){
			
			$position = get_option( 'ims_wlocal' );
			
			//text watermark
			if ( $this->opts['watermark'] == 1 ) {
				
				$font_text = $this->opts['watermark_text'];
				$font_size = $this->opts['watermark_size'];
				$font = dirname( __FILE__ ) . '/_fonts/arial.ttf';
				$rgb = $this->HexToRGB( $this->opts['watermark_color'] );
				$trans = round(abs( ( ( min($this->opts['watermark_trans'],100) * 127) / 100 ) - 100 ));
				
				$image_size 	= getimagesize( $this->image_dir );
				$tb = imagettfbbox( $font_size, 0, $font, $font_text );
				$icolor = imagecolorallocatealpha( $this->image, $rgb['r'], $rgb['g'], $rgb['b'], $trans );
				
				//title text
				if( ! empty( $this->opts['watermarktile']) ){
					
				  	foreach( $this->get_tile_points( $image_size[0], $image_size[1], abs( $tb[2] ), abs( $tb[5] ) ) as $m )
						imagettftext( $this->image, $font_size, 0, $m['x'], $m['y'], $icolor, $font, $font_text );
				
				// position text
				} else {
					
					switch ( $position ) {
						  case 1:
							  $x = 2;
							  $y = abs($tb[5]) + 2;
							  break;
						  case 2:
							  $x = ceil(( $image_size[0] - $tb[2] ) / 2);
							  $y = abs($tb[5]) + 2;
							  break;
						  case 3:
							  $x = ($image_size[0] - $tb[2] ) - 4;
							  $y = abs($tb[5]) + 2;
							  break;
						  case 4:
							  $x = 2;
							  $y = $image_size[1] / 2;
							  break;
						  case 5:
							  $x = ceil(( $image_size[0] - $tb[2] ) / 2);
							  $y = $image_size[1] / 1.7;
							  break;
						  case 6:
							  $x = ($image_size[0] - $tb[2] ) - 4;
							  $y = $image_size[1] / 2;
							  break;
						  case 7:
							  $x = 2;
							  $y = $image_size[1] / 1.03;
							  break;
						  case 9:
							  $x = ($image_size[0] - $tb[2] ) - 4;
							  $y = $image_size[1] / 1.03;
							  break;
						  default:
							  $x = ceil(( $image_size[0] - $tb[2] ) / 2);
							  $y = $image_size[1] / 1.03;
					  }
					  imagettftext( $this->image, $font_size, 0, $x, $y, $icolor, $font, $font_text );		
				}
				
			//image watermark
			} else if ( $this->opts['watermark'] == 2  && $this->opts['watermarkurl'] ) {
				
				//check file extension
				$wmtype = wp_check_filetype( basename( $this->opts['watermarkurl'] ) );
				if ( ! preg_match( '/(png|jpg|jpeg|gif)$/i', $wmtype['ext'] ) )
					return;
				
				
				if( ! file_exists( $this->content_dir . "/". $this->opts['watermarkurl'] ) ){
					$wmpath = $this->content_dir . "/watermark/". preg_replace( '/[^a-zA-Z0-9\.-_]/','',basename($this->opts['watermarkurl'])) ;
					if( ! file_exists( $wmpath ) && $content = @file_get_contents( $this->opts['watermarkurl'] ) ){
						if( ! file_exists( $this->content_dir . "/watermark/" ) )
							mkdir( $this->content_dir . "/watermark/", 0755 );
						@file_put_contents( $wmpath, $content );
					}
				} 	else $wmpath = $this->content_dir . "/". $this->opts['watermarkurl'];

				if ( empty( $wmpath ) ) 
					return;
				
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
				
				if( empty( $watermark ) || ! $wm_size = getimagesize( $wmpath )  )
					return;
								
				list( $orig_w, $orig_h ) = $wm_size;
				list( $dest_w, $dest_h ) = getimagesize( $this->image_dir );
				
				// resize watermark 
				if( $dims = $this->resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h ) ){
					list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;		
					
					$dst_w = ( $dst_w * .8 ); $dst_h = ( $dst_h * .8 );
					
					$wmnew = imagecreatetruecolor( $dst_w, $dst_h );
					imagecopyresampled( $wmnew, $watermark, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h );
					
					// keep transperancy
					if ( $wmtype['ext'] == "png" ) {
						$background = imagecolorallocate( $wmnew, 0, 0, 0 );
						ImageColorTransparent( $wmnew, $background );
						imagealphablending( $wmnew, true );
					}
					
					switch ( $position ) {
						case 1:
							$x = $y = 2;
							break;
						case 2:
							$x = ( $dest_w - $dst_w ) / 2;
							$y = 2;
							break;
						case 3:
							$x = ( $dest_w - $dst_w ) - 4;
							$y = 2;
							break;
						case 4:
							$x = 2;
							$y = ( $dest_h - $dst_h ) / 2;
							break;
						case 6:
							$x = ( $dest_w - $dst_w ) - 4;
							$y = ( $dest_h - $dst_h ) / 2;
							break;
						case 7:
							$x = 2;
							$y = ( $dest_h - $dst_h ) - 4;
							break;
						case 8:
							$x = ( $dest_w - $dst_w ) / 2;
							$y = ( $dest_h - $dst_h ) - 4;
							break;
						case 9:
							$x = ( $dest_w - $dst_w ) - 4;
							$y = ( $dest_h - $dst_h ) - 4;
							break;
						default:
							$x = ( $dest_w - $dst_w ) / 2;
							$y = ( $dest_h - $dst_h ) / 1.8;
					}
					
					if( ! empty( $this->opts['watermarktile'] ) ){
						foreach( $this->get_tile_points( $dest_w, $dest_h, $dst_w, $dst_h ) as $m )
							imagecopymerge( $this->image, $wmnew, $m['x'], $m['y'], 0, 0, $dst_w, $dst_h, 30 );
					}  else imagecopymerge( $this->image, $wmnew, $x, $y, 0, 0, $dst_w, $dst_h, 30 );
					
					imagedestroy( $wmnew );
					imagedestroy( $watermark );
				}
			}
		
		}
		
		/**
		 * Apply color filster to image
		 *
		 * @return void
		 * @since 3.3.3
		 */
		function apply_color_filter( $color = false ){
			
			if( $color !== false ) 
				_deprecated_argument( __FUNCTION__, '3.5.2' );

			$filters = get_option( 'ims_color_filters' );
			if ( ! isset( $filters[$this->color] ) ) 
				return;
				
			if ( $filters[$this->color]['grayscale'] )
				imagefilter( $this->image, IMG_FILTER_GRAYSCALE);
			
			if ( $filters[$this->color]['contrast'] )
				imagefilter( $this->image, IMG_FILTER_CONTRAST, $filters[$this->color]['contrast'] );
			
			if ( $filters[$this->color]['brightness'] )
				imagefilter( $this->image, IMG_FILTER_BRIGHTNESS, $filters[$this->color]['brightness'] );
			
			if ( $filters[$this->color]['brightness'] )
				imagefilter( $this->image, IMG_FILTER_BRIGHTNESS, $filters[$this->color]['brightness'] );
			
			if ( $filters[$this->color]['colorize'] ) {
				$args = array( $this->image, IMG_FILTER_COLORIZE);
				$args = array_merge( $args, explode( ',', $filters[$this->color]['colorize'] ) );
				call_user_func_array( 'imagefilter', $args );
			}
		}
		
		/**
		 * Create image resource
		 *
		 * @parm $ext deprecated
		 * @return bool
		 * @since 3.3.3
		 */
		function create_image( $ext = false ){
			
			if( $ext !== false ) 
				_deprecated_argument( __FUNCTION__, '3.5.2' );
			
			switch ( $this->ext ) {
				case "jpg":
				case "jpeg":
					$this->image = imagecreatefromjpeg( $this->image_dir );
					break;
				case "gif":
					$this->image = imagecreatefromgif( $this->image_dir );
					break;
				case "png":
					$this->image = imagecreatefrompng( $this->image_dir );
					break;
				default: status_header( 204 );
			}
			
			if( ! is_resource( $this->image ) )
				return false;
			
			if( ! $this->resize  )
				return true;
				
			list( $dest_w, $dest_h ) = $this->resize;
			list( $orig_w, $orig_h ) = getimagesize( $this->original_file );
			
			if( $dims = $this->resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, true ) ) {
				list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;			
			
				$image_new = imagecreatetruecolor( $dst_w, $dst_h );
				imagecopyresampled( $image_new, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );
				
				imagedestroy( $this->image );	
				$this->image = $image_new;
				return true;
			}
		}
		
		/**
		 * Ouptu image to browser
		 *
		 * @return void
		 * @since 3.3.3
		 */
		function output_image( ){
			
			//sharpen image
			$matrix = array(  array(-1, -1, -1),  array(-1, 20, -1),  array(-1, -1, -1) );
			$divisor = array_sum( array_map( 'array_sum', $matrix) );
			
			imageconvolution( $this->image, $matrix, $divisor, 0 );
			
			switch ( $this->ext ) {
				case "gif":
					imagegif( $this->image );
					break;
				case "png":
					imagepng ( $this->image, NULL, 2 );
					break;
				case "jpg":
				case "jpeg":
					imagejpeg( $this->image, NULL, $this->quality );
					break;
				default: 
				imagedestroy( $this->image );
				status_header( 204 );
			}
			
			imagedestroy( $this->image );
			die( );
		}
		
		/**
		 * Return calculated resized dimensions for image resizing
		 *
		 * @param int $orig_w Original width.
		 * @param int $orig_h Original height.
		 * @param int $dest_w New width.
		 * @param int $dest_h New height.
		 * @param bool $crop Optional
		 * @return bool | array
		 * @since 3.3.3
		 */
		function resize_dimensions( $orig_w, $orig_h, $dest_w, $dest_h, $crop = false ){
			
			// sizes must be larger than 0
			if ( $orig_w <= 0 || $orig_h <= 0 || $dest_w <= 0 || $dest_h <= 0 )
				return false;

			//resize image
			if ( ! $crop ) {
				$orig_max = max( $orig_w, $orig_h );
				$dest_max = max( $dest_w, $dest_h );
					
				$size_ratio = $orig_max > $dest_max ? ( $dest_max / $orig_max  ) : 1;
				return array( 0, 0, 0, 0, ceil( $orig_w * $size_ratio ), ceil( $orig_h * $size_ratio  ), (int) $orig_w, (int) $orig_h );
			}
			
			//crop image
			$aspect_ratio = $orig_w / $orig_h;
			$new_w = min( $dest_w, $orig_w );
			$new_h = min( $dest_h, $orig_h );
			
			if ( ! $new_h = min( $dest_h, $orig_h ) ) 
				 $new_w = intval( $new_h * $aspect_ratio);
				 
			if ( ! $new_w = min( $dest_w, $orig_w ) ) 
				$new_h = intval( $new_w / $aspect_ratio);

			$size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
			
			$crop_w = round($new_w / $size_ratio);
			$crop_h = round($new_h / $size_ratio);
			
			$s_x = floor( ($orig_w - $crop_w) / 2 );
			$s_y = floor( ($orig_h - $crop_h) / 2 );
			
			return array( 0, 0, $s_x, $s_y, $new_w, $new_h, $crop_w, $crop_h );
		}
		
		/**
		 * Conver hex color to rgb
		 *
		 * @param string $hex
		 * @return unit/string
		 * @since 0.5.0 
		 */
		function HexToRGB( $hex ) {
			$hex = str_replace( "#", "", $hex );
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
		 * Get image location for tilling
		 *
		 * @param unit $w image width
		 * @param unit $h image height
		 * @param unit $ww watermark width
		 * @param unit $wh watermark heigth
		 * @return array
		 * @since 3.2.8
		 */
		function get_tile_points( $w, $h, $ww, $wh ){
			
			$points = array();  $s = $this->space; 
			$p = ( $this->opts['watermark'] == 1 ) ? $wh : 1;
			
			for( $x = 0; $x < ( $w + $s ); $x += ( $s + $ww ) ) { 
				for( $y = $p; $y < ( $h + $s ); $y += ( $s + $wh ) ) 
					$points[] = array( 'x' => $x, 'y' => $y);
			} 
			return $points;
		}
		
		/**
		 * Decrypt url
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
		
		/**
		 * Set correct url response
		 *
		 * @parm unit $code
		 * @return void
		 * @since 3.3.3
		 */
		function status( $code ){
			status_header( $code );
			die();
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
			_deprecated_function( __FUNCTION__, '3.3.3', 'resize_dimensions' );
			return array( 'w' => false, 'h' => false );
		}
	}
	
	// do that thing you do 
	new ImStoreImage( );