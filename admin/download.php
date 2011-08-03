<?php 


/**
 * Image store - download image
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/


//define constants
define('WP_ADMIN',true);
define('DOING_AJAX',true);

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
	function __construct(){
		
		if(empty($_REQUEST['img'])) die();
		
		global $ImStore; 
		$img_size = explode('x',strtolower($_GET['sz']));
		
		$this->store = (is_admin()) ? $ImStore->admin : $ImStore->store;
		$this->attachment = get_post_meta($this->store->decrypt_id($_REQUEST['img']),'_wp_attachment_metadata',true);
		
		if($this->attachment['sizes'][$_GET['sz']]['url']){
			$this->image_dir = str_ireplace(WP_CONTENT_URL,WP_CONTENT_DIR,$this->attachment['sizes'][$_GET['sz']]['url']);
		}elseif(count($img_size)==2 && !$this->store->opts['downloadorig']){ 
			$this->image_dir = image_resize($this->attachment['path'],$img_size[0],$img_size[1],0,0,0,100);
			
			if(is_wp_error($this->image_dir) && !$this->store->opts['downloadorig'])
				$this->image_dir = $this->attachment['sizes']['preview']['path'];
			elseif(is_wp_error($this->image_dir) && $this->store->opts['downloadorig'])
				$this->image_dir = $this->attachment['path'];
			
		}elseif($this->store->opts['downloadorig']){
			$this->image_dir = $this->attachment['path'];
		}else{
		 	$this->image_dir = $this->attachment['sizes']['preview']['path'];
		}
		$this->display_image();
	}
	
	
	/**
	 * display image
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function display_image(){
		global $wpdb; 
		
		$realname	= basename($this->image_dir);
		$filetype 	= wp_check_filetype($realname);
		$filename 	= $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = " . $_REQUEST['img']) ; 
		$filename	= ($filename)?$filename:$realname;
		
		header('Expires: 0');
		header('Pragma: no-cache');
		header('Cache-control: private');
		header('Last-Modified: ' . gmdate('D,d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-cache,must-revalidate,max-age=0');
		header('Content-Description: File Transfer');
		header("Content-Transfer-Encoding: binary");
		header('Content-Type: ' . $filetype['type']);
		header('Content-Disposition: attachment; filename=' . $filename);
		
		// Optional support for X-Sendfile and X-Accel-Redirect
		if (defined('WPMU_ACCEL_REDIRECT') && WPMU_ACCEL_REDIRECT == true ) {
			header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '', $this->image_dir ) );
			die();
		} elseif (defined('WPMU_SENDFILE')  && WPMU_ACCEL_REDIRECT == true ) {
			header( 'X-Sendfile: ' . $this->image_dir );
			die();
		}
		
		if(!$_REQUEST['c'] || $_REQUEST['c'] =='color' ){
			readfile($this->image_dir); die();}
		
		switch($filetype['ext']){
			case "jpg":
			case "jpeg":
				$image = imagecreatefromjpeg($this->image_dir);
				break;
			case "gif":
				$image = imagecreatefromgif($this->image_dir);
				break;
			case "png":
				$image = imagecreatefrompng($this->image_dir);
				break;
		}
		
		//gray scale
		if($_REQUEST['c'] == 'ims_bw'){
			imagefilter($image,IMG_FILTER_GRAYSCALE);
			imagefilter($image,IMG_FILTER_BRIGHTNESS,+10);
		}
		
		//sepia
		if($_REQUEST['c'] == 'ims_sepia'){
			imagefilter($image,IMG_FILTER_GRAYSCALE); 
			imagefilter($image,IMG_FILTER_BRIGHTNESS,-10);
			imagefilter($image,IMG_FILTER_COLORIZE,35,25,10);
		}
		
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
		
		imagedestroy($image);
		die();
	}


}

//do that thing you do 
$ImStoreImage = new ImStoreDownloadImage();
?>