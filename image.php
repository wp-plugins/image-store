<?php 


/**
*Image store - secure image
*
*@package Image Store
*@author Hafid Trujillo
*@copyright 20010-2011
*@since 0.5.0 
*/


//define constants
if(isset($_REQUEST['w']))
define('SHORTINIT',true);
define('DOING_AJAX',true);

//load wp
if(!empty($_REQUEST['p'])) 
	require_once '../../../wp-load.php';
	
class ImStoreImage{
	
	/**
	*Constructor
	 *
	*@return void
	*@since 0.5.0 
	*/
	function __construct(){
		
		if($dh = @opendir(dirname(__FILE__)."/admin/key")){
			while(false !== ($obj = readdir($dh))){
				if($obj == '.' || $obj == '..'){ continue;
				}else{ $this->key = current(explode('.',$obj)); break;}
			}
			@closedir($dh);
		}
		
		if(empty($_GET['i'])) die();
		$path = $this->url_decrypt($_GET['i']);
		$this->root = implode('/',explode('/',str_replace('\\','/',dirname(__FILE__)),-3))."/";
		if(!preg_match("/(_resized)/",$path)) $path = dirname($path)."/_resized/".basename($path);;
		
		$this->image_dir = "{$this->root}wp-content/$path";
		
		if(!file_exists($this->image_dir)) die();
		$this->display_image();
	}
	
	/**
	*Display image
	 *
	*@return void
	*@since 0.5.0 
	*/
	function display_image(){
		
		$ext = end(explode('.',basename($this->image_dir)));
		header('Content-Type: image/'.$ext);
		
		//Optional support for X-Sendfile and X-Accel-Redirect
		if (defined('WPMU_ACCEL_REDIRECT') && WPMU_ACCEL_REDIRECT == true ) {
			header( 'X-Accel-Redirect: ' . str_replace( WP_CONTENT_DIR, '', $this->image_dir ) );
			die();
		} elseif (defined('WPMU_SENDFILE')  && WPMU_ACCEL_REDIRECT == true ) {
			header( 'X-Sendfile: ' . $this->image_dir );
			die();
		}
		
		$modified 	= gmdate("D, d M Y H:i:s",filemtime($this->image_dir));
		$etag 		= '"' . md5( $modified ) . '"';
		
		header( "Last-Modified: $modified GMT" );
		header( 'ETag: ' . $etag );
		header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );
		header('Cache-Control:max-age='.(time() + 100000000 ).',must-revalidate');

		// Support for Conditional GET
		$client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;
		
		if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
			$_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;
		
		$client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
		$client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;
		$modified_timestamp = strtotime($modified);
		
		if ( ( $client_last_modified && $client_etag )
			? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $etag ) )
			: ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $etag ) )
			) {
			status_header( 304 );
			die();
		}
		
		if(empty($_REQUEST['p'])){
			readfile( $this->image_dir );
			die();
		}
		
		//use to process big images
		ini_set('memory_limit','256M');
		ini_set('allow_url_fopen',true);
		ini_set('set_time_limit','1000');
		
		$opts 		= get_option('ims_front_options');
		$filetype 	= wp_check_filetype(basename($this->image_dir));
		
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

		//add water mark		
		if($opts['watermark']){
			
			//text watermark
			if($opts['watermark'] == 1){
				$font_size = $opts['fontsize'];
				$font = dirname(__FILE__).'/_fonts/arial.ttf';
				$rgb = $this->HexToRGB($opts['textcolor']);
				
				$black = imagecolorallocatealpha($image,0,0,0,90);
				$color = imagecolorallocatealpha($image,$rgb['r'],$rgb['g'],$rgb['b'],$opts['transperency']);
				
				$info = getimagesize($this->image_dir);
				$tb = imagettfbbox($font_size,0,$font,$opts["watermarktext"]);
				
				$y = $info[1]/1.15;
				$x = ceil(($info[0] - $tb[2]) / 2);
				
				imagettftext($image,$font_size,0,$x,$y,$black,$font,$opts["watermarktext"]);
				imagettftext($image,$font_size,0,$x,$y,$color,$font,$opts["watermarktext"]);
			
			//image watermark
			}elseif($opts['watermark'] == 2){
				
				$wmpath		= $opts["watermarkurl"];
				$wmtype 	= wp_check_filetype(basename($opts["watermarkurl"]));

				if(!preg_match('/(png|jpg|jpeg|gif)$/i',$wmtype['ext'])){
					readfile( $this->image_dir ); 
					die();
				}
				
				//if(file_exists($wmpath)){
					switch($wmtype['ext']){
						case "jpg":
						case "jpeg":
							$watermark = @imagecreatefromjpeg($wmpath);
							break;
						case "gif":
							$watermark = @imagecreatefromgif($wmpath);
							break;
						case "png":
							$watermark = @imagecreatefrompng($wmpath);
						 break;
					}
					
					$wminfo 	= getimagesize($wmpath);
					$info		= getimagesize($this->image_dir);
					$wmratio 	= $this->image_ratio($wminfo[0],$wminfo[1],max($info[0],$info[1]));
					
					$x = ($info[0] - $wmratio['w'])/2; 
					$y = ($info[1] - $wmratio['h'])/1.7;
					
					$wmnew = imagecreatetruecolor($wmratio['w'],$wmratio['h']);
					
					//keep transperancy
					if($wmtype['ext'] == "png"){
						$background = imagecolorallocate($wmnew,0,0,0);
						ImageColorTransparent($wmnew,$background);
						imagealphablending($wmnew,true);
					}
					
					//resize watermarl and merge images
					imagecopyresampled($wmnew,$watermark,0,0,0,0,$wmratio['w'],$wmratio['h'],$wminfo[0],$wminfo[1]);
					imagecopymerge($image,$wmnew,$x,$y,0,0,$wmratio['w'],$wmratio['h'],30);
					
					@imagedestroy($wmnew);
					@imagedestroy($watermark);
				//}
			}
			
		}
		
		
		//gray scale
		if($_REQUEST['c'] == 'g'){
			imagefilter($image,IMG_FILTER_GRAYSCALE);
			imagefilter($image,IMG_FILTER_BRIGHTNESS,+10);
		}
		
		//sepia
		if($_REQUEST['c'] == 's'){
			imagefilter($image,IMG_FILTER_GRAYSCALE); 
			imagefilter($image,IMG_FILTER_BRIGHTNESS,-10);
			imagefilter($image,IMG_FILTER_COLORIZE,35,25,10);
		}
		
		$quality = ($q=get_option('preview_size_q')) ? $q : 85;
		
		//create new image
		switch($filetype['ext']){
			case "jpg":
			case "jpeg":
				imagejpeg($image,NULL,$quality);
				break;
			case "gif":
				imagegif($image);
				break;
			case "png":
				$quality = (ceil($quality/10)>9) ? 9 : ceil($quality/10);
				imagepng($image,NULL,$quality);
				break;
		}
		@imagedestroy($image);
		die();
	}
	
	/**
	*Conver hex color to rgb
	*
	*@param string $hex
	*@return unit/string
	*@since 0.5.0 
	*/
	function HexToRGB($hex){
		$hex = ereg_replace("#","",$hex);
		$color = array();
 
		if(strlen($hex) == 3){
			$color['r'] = hexdec(substr($hex,0,1).$r);
			$color['g'] = hexdec(substr($hex,1,1).$g);
			$color['b'] = hexdec(substr($hex,2,1).$b);
		}
		else if(strlen($hex) == 6){
			$color['r'] = hexdec(substr($hex,0,2));
			$color['g'] = hexdec(substr($hex,2,2));
			$color['b'] = hexdec(substr($hex,4,2));
		}
		return $color;
	}
	
	/**
	*Get image ratio
	*
	*@param unit $w
	*@param unit $h
	*@param unit $immax
	*@return unit
	*@since 0.5.0 
	*/
	function image_ratio($w,$h,$immax){
		$max	= max($w,$h);
		$r		= $max > $immax?($immax / $max):1;
		$i['w']	= ceil($w*$r*.7);
		$i['h']	= ceil($h*$r*.7);
		return $i;
	}
	
	/**
	 *Decrypt url
	 *
	 *@parm string $string 
	 *@return string
	 *@since 2.1.1
	 */	
	function url_decrypt($string) {
		$result= '';
		$string = ($string)? $string : '7ie6wkze';
		$string = base64_decode($string);
		for($i=0; $i<strlen($string); $i++) {
			$char = substr($string, $i, 1);
			$keychar = substr($this->key, ($i % strlen($this->key))-1, 1);
			$char = chr(ord($char)-ord($keychar));
			$result.=$char;
		}  
	return $result;
	}

}

//do that thing you do 
$ImStoreImage = new ImStoreImage();
?>