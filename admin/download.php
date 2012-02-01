<?php 
/**
 * Image store - download image
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0
*/

//dont cache file
header( 'Last-Modified:'.gmdate( 'D,d M Y H:i:s').' GMT' );
header( 'Cache-control:no-cache,no-store,must-revalidate,max-age=0' );

//define constants
define( 'WP_ADMIN',true);
define( 'DOING_AJAX',true);
$_SERVER['PHP_SELF'] = "/wp-admin/download.php";

//load wp
require_once '../../../../wp-load.php';

//make sure that the request came from the same domain	
if(!wp_verify_nonce($_REQUEST["_wpnonce"],"ims_download_img"))
	die();

class ImStoreDownloadImage{
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function ImStoreDownloadImage( ){
	
		if( empty($_REQUEST['img']) || 
		!wp_verify_nonce( $_REQUEST["_wpnonce"], "ims_download_img") ) {
			die( );
		}
		
		global $ImStore;
		
		$this->clean = false;
		$this->image_dir = '';
		$imgsize = $_REQUEST['sz'];
		$this->id =  (int) $ImStore->decrypt_id( $_REQUEST['img'] );
		
		$dimentions = array();
		$sizes = get_option( 'ims_sizes', true );
		
		foreach( $sizes as $size ){
			if( $size['name'] == $imgsize){
				$dimentions = $size;
				break;
			}
		}
		
		if( empty($dimentions['w']) || empty( $dimentions['h'] )  ){
			$size = explode( 'x', strtolower( $imgsize ) );
			$dimentions['w'] = $size[0] ;
			$dimentions['h'] = $size[1] ;
		}
		
		$this->attachment = get_post_meta( $this->id, '_wp_attachment_metadata', true );
		
		if( isset( $this->attachment['sizes'][$imgsize]['path'] ) ){
			$this->image_dir = $this->attachment['sizes'][$imgsize]['url'];
			
		}elseif( $dimentions['w'] && $dimentions['h'] && empty( $this->store->opts['downloadorig'] ) ){ 
			
			$this->clean = true;
			$this->image_dir = image_resize( WP_CONTENT_URL . "/". $this->attachment['file'], $dimentions['w'], $dimentions['h'], 0, 0, 0, 100 );
			
			if( is_wp_error($this->image_dir)  &&  isset( $this->attachment['sizes']['preview']['url'] ) ){
				$this->clean = false;
				$this->image_dir = $this->attachment['sizes']['preview']['url'] ;
				
			}elseif( is_wp_error($this->image_dir) ){
				$this->clean = false;
				$this->image_dir = WP_CONTENT_URL . "/". $this->attachment['file'];
				
			}
		}elseif( $this->store->opts['downloadorig'] ){
			$this->image_dir = WP_CONTENT_URL . "/". $this->attachment['file'];

		}else{
			$this->image_dir = $this->attachment['sizes']['preview']['path'];
			
		}
		$this->display_image( );
	}
	
	
	
	/**
	*Display image
	 *
	*@return void
	*@since 0.5.0 
	*/
	function display_image( ){
		
		global $wpdb;
		$ext = end( explode( '.', basename( $this->image_dir ) ) );
		$filename 	= $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = " . $this->id) ;
	
		header( 'Content-Type: image/'.$ext );

		//Optional support for X-Sendfile and X-Accel-Redirect
		if ( defined( 'WPMU_ACCEL_REDIRECT' ) && WPMU_ACCEL_REDIRECT == true ){
			header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '', $this->image_dir ) );
			die( );
		} elseif ( defined( 'WPMU_SENDFILE' ) && WPMU_ACCEL_REDIRECT == true ){
			header( 'X-Sendfile: ' . $this->image_dir );
			die( );
		}
		
		$color = isset( $_REQUEST['c'] ) ? $_REQUEST['c'] : false;
		$modified 	= gmdate( "D, d M Y H:i:s", @filemtime( $this->image_dir ) ); $etag = '"' . md5( $modified . $color ) . '"';
		$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;
				
		header( 'ETag: ' . $etag );
		header( 'Cache-control: private');
		header( "Last-Modified: $modified GMT" );
		header( 'Content-Description: File Transfer');
		header( "Content-Transfer-Encoding: binary");
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time( ) + 100000000 ) . ' GMT' );
		header( 'Cache-Control:max-age=' . ( time( ) + 100000000 ).', must-revalidate' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		
		if( ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && isset( $_SERVER['HTTP_IF_NONE_MATCH'] )
		&&( strtotime( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) == filemtime( $this->image_dir ) ) ) || ( $client_etag == $etag ) ){
			header( 'HTTP/1.1 304 Not Modified' ); 
			die( );
		}
		
		if( empty( $_REQUEST['c'] ) || $_REQUEST['c'] == 'ims_color' ){
			readfile( $this->image_dir ); 
			die();
		}
		
		
		switch( $ext ){
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
		
		$color = $_REQUEST['c'];
		
		//gray scale
		if( $color == 'ims_bw' ){
			imagefilter( $image, IMG_FILTER_GRAYSCALE );
			imagefilter( $image, IMG_FILTER_BRIGHTNESS, +10 );
		}
		
		//sepia
		if( $color == 'ims_sepia' ){
			imagefilter( $image, IMG_FILTER_GRAYSCALE ); 
			imagefilter( $image, IMG_FILTER_BRIGHTNESS, -10 );
			imagefilter( $image, IMG_FILTER_COLORIZE, 35, 25, 10 );
		}
		
		do_action( 'ims_apply_color_filter', &$image );
		
		//create new image
		switch($filetype['ext']) {
			case "jpg":
			case "jpeg":
				imagejpeg($image,NULL,100);
				break;
			case "gif":
				imagegif($image);
				break;
			case "png":
				imagepng($image,NULL,9);
				break;
		}
		
		@imagedestroy( $image );
		if( $this->clean ) 
			@unlink( $this->image_dir );
			
		die( );
	}
	
	
}
//do that thing you do 
$ImStoreImage = new ImStoreDownloadImage();