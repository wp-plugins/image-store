<?php 


/**
 * Image store - image rss
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.3 
*/

//define constants
define( 'DOING_AJAX', true );

//load wp
require_once '../../../wp-load.php';

class ImStoreImage{
	
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function __construct( ){
		
		if( empty( $_REQUEST['img'] ) ) die( );
		$this->attachment = get_post_meta( $_REQUEST['img'], '_wp_attachment_metadata', true );
		
		if( $_REQUEST['thumb'] ) 
			$this->image_dir = str_ireplace( WP_CONTENT_URL, WP_CONTENT_DIR, $this->attachment['sizes']['thumbnail']['url'] );
		elseif( $this->attachment['sizes']['preview']['url'] ) 
			$this->image_dir = str_ireplace( WP_CONTENT_URL, WP_CONTENT_DIR, $this->attachment['sizes']['preview']['url'] );
		else $this->image_dir = WP_CONTENT_DIR . $this->attachment['file'];
		
		if( !file_exists ( $this->image_dir ) ) die( ); 
		
		$this->display_image( );
		
	}
	
	
	/**
	 * Display image
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function display_image( ){
		
		//use to process big images
		ini_set('memory_limit', '256M');
		ini_set('set_time_limit', '1000');
		
		$filetype 	= wp_check_filetype( basename( $this->image_dir ) );
		$gmdate_mod = gmdate( "D, d M Y H:i:s", filemtime( $this->image_dir ) );
		
		//header( 'Pragma: no-cache' );
		//header( 'Cache-control: private');
		header( 'Expires: ' . $gmdate_mod );
		header( 'Last-Modified: ' . $gmdate_mod );
		header( 'Content-Type: ' . $filetype['type'] );
		//header( 'Cache-control: no-cache, no-store, must-revalidate, max-age=0');

		switch( $filetype['ext'] ){
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
		}
		
		
		//add water mark		
		$opts = get_option( 'ims_front_options' );
		if( $opts['watermark'] ){
			
			//text watermark
			if( $opts['watermark'] == 1 ){
				$font_size = $opts['fontsize'];
				$font = IMSTORE_ABSPATH . '_fonts/arial.ttf';
				$rgb = $this->HexToRGB( $opts['textcolor'] );
				
				$black = imagecolorallocatealpha( $image, 0,0,0,90);
				$color = imagecolorallocatealpha( $image, $rgb['r'], $rgb['g'], $rgb['b'], $opts['transperency'] );
				
				$info = getimagesize( $this->image_dir );
				$tb = imagettfbbox( $font_size, 0, $font, $opts["watermarktext"] );
				
				$y = $info[1]/1.15;
				$x = ceil(( $info[0] - $tb[2]) / 2);
				
				imagettftext( $image, $font_size, 0, $x, $y, $black, $font, $opts["watermarktext"] );
				imagettftext( $image, $font_size, 0, $x, $y, $color, $font, $opts["watermarktext"] );
			
			//image watermark
			}elseif( $opts['watermark'] == 2 ){
				
				$wmpath		= ABSPATH . str_ireplace( get_bloginfo('url'), '', $opts["watermarkurl"] );
				$wmtype 	= wp_check_filetype( basename( $opts["watermarkurl"] ) );
				
				if( file_exists( $wmpath ) ){
					switch( $wmtype['ext'] ) {
						case "jpg":
						case "jpeg":
							$watermark = imagecreatefromjpeg( $wmpath );
							break;
						case "gif":
							$watermark = imagecreatefromgif( $wmpath );
							break;
						case "png":
							$watermark = imagecreatefrompng( $wmpath );
						 break;
					}
					$wminfo 	= getimagesize( $wmpath );
					$info		= getimagesize( $this->image_dir );
					$wmratio 	= $this->image_ratio( $wminfo[0], $wminfo[1], max($info[0], $info[1] ) );
					
					$x = ( $info[0] - $wmratio['w'] )/2; 
					$y = ( $info[1] - $wmratio['h'] )/1.48;
					
					$wmnew = imagecreatetruecolor( $wmratio['w'], $wmratio['h'] );
					
					//keep transperancy
					if( $wmtype['ext'] == "png" ){
						$background = imagecolorallocate( $wmnew, 0, 0, 0);
						ImageColorTransparent( $wmnew, $background );
						imagealphablending( $wmnew, true );
					}
					
					//resize watermarl and merge images
					imagecopyresampled( $wmnew, $watermark, 0, 0, 0, 0, $wmratio['w'], $wmratio['h'], $wminfo[0], $wminfo[1] );
					imagecopymerge( $image, $wmnew, $x, $y, 0, 0, $wmratio['w'] , $wmratio['h'], 30 );
					
					@imagedestroy( $wmnew );
					@imagedestroy( $watermark );
				}
			}
			
		}
		
		//create new image
		switch( $filetype['ext'] ) {
			case "jpg":
			case "jpeg":
				imagejpeg( $image );
				break;
			case "gif":
				imagegif( $image );
				break;
			case "png":
				imagepng( $image );
				break;
		}
		
		imagedestroy ( $image );
		die( );
	}
	
	
	/**
	 * Conver hex color to rgb
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function HexToRGB( $hex ) {
		$hex = ereg_replace("#", "", $hex);
		$color = array();
 
		if(strlen($hex) == 3) {
			$color['r'] = hexdec(substr($hex, 0, 1) . $r);
			$color['g'] = hexdec(substr($hex, 1, 1) . $g);
			$color['b'] = hexdec(substr($hex, 2, 1) . $b);
		}
		else if(strlen($hex) == 6) {
			$color['r'] = hexdec(substr($hex, 0, 2));
			$color['g'] = hexdec(substr($hex, 2, 2));
			$color['b'] = hexdec(substr($hex, 4, 2));
		}
 
		return $color;
	}
	
	
	/**
	 * Get image ratio
	 *
	 * @return unit
	 * @since 0.5.0 
	 */
	function image_ratio( $w, $h, $immax ) {
		$max	= max( $w, $h );
		$r		= $max > $immax ? ( $immax / $max) : 1;
		$i['w']	= ceil( $w * $r * .4 );
		$i['h']	= ceil( $h * $r * .4 );
		return $i;
	}

}

//do that thing you do 
$ImStoreImage = new ImStoreImage( );
?>