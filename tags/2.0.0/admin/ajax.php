<?php 
/**
 *Ajax events for admin area
 *
 *@package Image Store
 *@author Hafid Trujillo
 *@copyright 20010-2011
 *@since 0.5.0
*/

//dont cache file
header('Expires:0');
header('Pragma:no-cache');
header('Cache-control:private');
header('Last-Modified:'.gmdate('D,d M Y H:i:s').' GMT');
header('Cache-control:no-cache,no-store,must-revalidate,max-age=0');

//define constants
define('DOING_AJAX',true);
define('WP_ADMIN',true);

//load wp
require_once '../../../../wp-load.php';

//make sure that the request came from the same domain	
if(stripos($_SERVER['HTTP_REFERER'],get_bloginfo('siteurl')) === false) 
	die();


/**
 *Move price list to trash
 *
 *@return void
 *@since 0.5.0
*/
function ajax_imstore_pricelist_delete(){
	if(!current_user_can("ims_change_pricing"))return;
	check_ajax_referer("ims_ajax");
	wp_delete_post(intval($_GET['listid']),true);
	die();
}

/**
 *Delete post
 *
 *@return void
 *@since 2.0.0
*/
function ajax_imstore_delete_post(){
	if(!current_user_can("ims_change_pricing"))return;
	check_ajax_referer("ims_ajax");
	
	$metadata = get_post_meta((int)$_GET['postid'],'_wp_attachment_metadata');
	if($metadata[0]['sizes'] && !empty($_GET['deletefile'])){
		foreach($metadata[0]['sizes'] as $size)
			@unlink(WP_CONTENT_DIR.$folder.'/'.$size['file']);
		@unlink(WP_CONTENT_DIR.$metadata[0]['file']);
		@unlink(WP_CONTENT_DIR.str_replace('_resized/','',$metadata[0]['file']));
	}
	wp_delete_post((int)$_GET['postid'],true);
	die();
}

/**
 *Update post
 *
 *@return void
 *@since 2.0.0
*/
function ajax_imstore_update_post(){
	if(!current_user_can("ims_manage_galleries")) return;
	check_ajax_referer("ims_ajax");
	$post = array(
		'ID' => $_GET['imgtid'],
		'menu_order' => $_GET['order'],
		'post_title' => $_GET['imgtitle'],
		'post_excerpt' => $_GET['caption'],
	);
	wp_update_post( $post );
	die();
}

/**
 *add image to database
 *
 *@return void
 *@since 0.5.0
*/
function ajax_ims_flash_image_data(){
	global $wpdb,$current_user;
	
	if(!current_user_can('ims_add_galleries'))
		return false;
	
	@ini_set('memory_limit','256M');
	@ini_set('max_execution_time',1000);
	
	$galleid 	= $_GET['galleryid'];
	$filename 	= sanitize_file_name($_GET['imagename']);
	$abspath 	= $_GET['filepath'];
	$filetype 	= wp_check_filetype($filename);
	$despath 	= dirname($abspath).'/_resized';
	$relative 	= str_replace(str_replace('\\','/',WP_CONTENT_DIR),'',str_replace('\\','/',$despath.'/'.$filename));
	$guid 		= WP_CONTENT_URL.$relative;
	if(!file_exists($despath)) @mkdir($despath,0775);

	//if image exist dont't load it
	if($wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE 1=1 AND guid = %s",$guid))) return;
	include_once(ABSPATH.'wp-admin/includes/image.php');
	
	//resize images
	$img_sizes = get_option('ims_dis_images');
	$img_sizes['thumbnail']['name'] = "thumbnail";
	$img_sizes['thumbnail']['crop'] = '1';
	$img_sizes['thumbnail']['q'] 	= '95';
	$img_sizes['thumbnail']['w'] 	= get_option("thumbnail_size_w");
	$img_sizes['thumbnail']['h'] 	= get_option("thumbnail_size_h");
	
	$downloadsizes = get_option('ims_download_sizes');
	if(is_array($downloadsizes)) $img_sizes += $downloadsizes;
	
	foreach($img_sizes as $img_size){
		$resized = image_resize($abspath,$img_size['w'],$img_size['h'],$img_size['crop'],null,$despath,$img_size['q']);
		if(!is_wp_error($resized) && $resized && $info = @getimagesize($resized)){
			$imgname = basename($resized);
		}else{
			$info 		= getimagesize($abspath);
			$imgname 	= basename($abspath);
		}
		
		@copy($abspath,"$despath/$filename");

		$metadata['file'] 	= $relative;
		$metadata['width'] 	= $info[0];
		$metadata['height'] = $info[1];
		$metadata['path'] 	= "$despath/$filename";
		$metadata['url'] 	= $guid;
		
		list($uwidth,$uheight) = wp_constrain_dimensions($metadata['width'],$metadata['height'],100,100);
		$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
		
		switch($info['channels']){ 
			case 1:$metadata['color'] = 'BW'; break;
			case 3:$metadata['color'] = 'RGB'; break;
			case 4:$metadata['color'] = 'CMYK'; break;
			default:$metadata['color'] = __('Unknown',ImStore::domain);
		}
		$data = array(
			'file'	=>$imgname,
			'width'	=>$info[0],
			'height'=>$info[1],
			'url'	=> dirname($guid)."/$imgname",
			'path'	=> dirname($abspath)."/$imgname",
		);
		$metadata['sizes'][$img_size['name']] = $data;
		$metadata['image_meta'] = wp_read_image_metadata($abspath);
	}
	
	$attachment = array(
		'guid' => $guid,
		'post_title' => $filename,
		'post_type' => 'ims_image',
		'post_mime_type'=> $filetype['type'],
		'post_status' => 'publish',
		'post_parent' => $galleid,
	);
	
	$attach_id = wp_insert_post($attachment);
	
	if(empty($attach_id)) return;
	wp_update_attachment_metadata($attach_id,$metadata);
	
	$hidden 	= implode('|',(array)get_user_option('manageims_gallerycolumnshidden'));
	$imgnonce 	= '&_wpnonce='.wp_create_nonce("ims_edit_image")."&TB_iframe=true&height=570";
	$columns 	= array(
					'cb' => '<input type="checkbox">',
					'imthumb' => __('Thumbnail',ImStore::domain),'immetadata' => __('Metadata',ImStore::domain),
					'imtitle' => __('Title/Caption',ImStore::domain),'imdate' => __('Date',ImStore::domain),
					'imauthor'=> __('Author',ImStore::domain),'imorder'	=> __('Order',ImStore::domain),
					'imageid' => __('ID',ImStore::domain),
				);
	$row = '<tr id="item-'.$attach_id.'" class="iedit">';
	foreach($columns as $key => $column){
		if($hidden) $class = (preg_match("/($hidden)/i",$key))?' hidden':'';
		switch($key){
			case 'cb':
				$row .= '<th class="column-'.$key.' check-column"><input type="checkbox" name="galleries[]" value='.$attach_id.'" /></th>';
				break;
			case 'imthumb':
				$row .= '<td class="column-'.$key.$class.'">';
				$row .= '<a href="'.$attachment['guid'].'" class="thickbox" rel="gallery">';
				$row .= '<img src="'.dirname($attachment['guid']).'/'.$metadata['sizes']['mini']['file'].'" /></a>';
				$row .= '</td>';
				break;
			case 'immetadata':
				$row .= '<td class="column-'.$key.$class.'">';
				$row .= __('Format:',ImStore::domain).str_replace('image/','',$filetype['type']).'<br />';
				$row .= $metadata['width'].' x '.$metadata['height'].__(' pixels',ImStore::domain).'<br />';
				$row .= __('Color:',ImStore::domain).$metadata['color'].'<br />';
				$row .= '<div class="row-actions" id="media-head-'.$attach_id.'">';
				$row .= '<a href="'.IMSTORE_ADMIN_URL.'image-edit.php?editimage='.$attach_id.$imgnonce.'" class="thickbox">'.__('Edit',ImStore::domain).'</a> |';
				$row .= '<a href="#img=$id">'.__('Trash',ImStore::domain).'</a>';
				$row .= '</div>';
				$row .= '</td>';
				break;
			case 'imtitle':
				$row .= '<td class="column-'.$key.$class.'">';
				$row .= '<input type="text" name="img_title['.$attach_id.']" value="'.$filename.'" class="inputxl"/>';
				$row .= '<textarea name="post_excerpt['.$attach_id.']" rows="3" class="inputxl"></textarea>';
				$row .= '</td>';
				break;
			case 'imauthor':
				$row .= '<td class="column-'.$key.$class.'">'.$current_user->display_name.'</td>';
				break;
			case 'imdate':
				//$row .= '<td class="column-'.$key.$class.'">'.date_i18n(get_option('date_format'),strtotime($image->post_date)).'</td>';
				break;
			case 'imorder':
				$row .= '<td class="column-'.$key.$class.'">';
				$row .= '<input type="text" name="menu_order['.$attach_id.']" class="inputxl" />';
				$row .= '</td>';
				break;
			case 'imageid':
				$row .= '<td class="column-'.$key.$class.'">'.sprintf("%05d",$attach_id).'</td>';
				break;
			default:
				$row .= '<td class="column-'.$key.$class.'">&nbsp;</td>';
		}
	}
	echo $row .= '</tr>';
}

/**
 *Change the image status
 *
 *@return void
 *@since 2.0.0
*/
function ajax_imstore_edit_image_status(){
	if(!current_user_can("ims_manage_galleries"))return;
	check_ajax_referer("ims_ajax");
	wp_update_post(array("ID" => trim($_GET['imgid']),'post_status' => $_GET['status']));
	die();
}

/**
*Add images to favorites
*
*@return void
*@since 0.5.0
*/
function ajax_ims_add_images_to_favorites(){
	check_ajax_referer("ims_ajax_favorites");
	global $user_ID; $id = intval($_GET['galid']);
	if(empty($_GET['imgids']) || empty($id)){
		echo __('Please,select an image',ImStore::domain).'|ims-error'; return;
	}elseif(is_user_logged_in()){
		$new 	= explode(',',$_GET['imgids']);
		$join 	= trim(get_user_meta($user_ID,'_ims_favorites',true).",". $_GET['imgids'],','); 
		$ids	= implode(',',array_unique(explode(',',$join)));
		update_user_meta($user_ID,'_ims_favorites',$ids);
	}else{ 
		$new 	= explode(',',$_GET['imgids']);
		$join 	= trim($_COOKIE['ims_favorites_'.COOKIEHASH].",". $_GET['imgids'],',');
		$ids	= implode(',',array_unique(explode(',',$join)));
		setcookie('ims_favorites_'.COOKIEHASH,$ids,0,COOKIEPATH);
	}
	if(count($new) < 2) echo __('Image added to favorites',ImStore::domain).'|ims-success';
	else echo sprintf(__('%d images added to favorites',ImStore::domain),count($new)).'|ims-success';
} 

/**
 *Remove images from favorites
 *
 *@return void
 *@since 0.5.0
 */
function ajax_ims_remove_images_from_favorites(){
	check_ajax_referer("ims_ajax_favorites");
	global $user_ID; $id = intval($_GET['galid']);
	if(empty($_GET['imgids']) || empty($id)){
		echo __('Please,select an image',ImStore::domain).'|ims-error'; return;
	}elseif(is_user_logged_in()){
		$new 	= explode(',',$_GET['imgids']);
		$join 	= array_flip(explode(',',trim(get_user_meta($user_ID,'_ims_favorites',true),',')));
		foreach($new as $remove) unset($join[$remove]);
		$ids	= implode(',',array_flip($join));
		update_user_meta($user_ID,'_ims_favorites',$ids);
	}else{
		$new 	= explode(',',$_GET['imgids']);
		$join 	= array_flip(explode(',',trim($_COOKIE['ims_favorites_'.COOKIEHASH],',')));
		foreach($new as $remove) unset($join[$remove]);
		$ids	= implode(',',array_flip($join));
		setcookie('ims_favorites_'.COOKIEHASH,$ids,0,COOKIEPATH);
	}
	if(count($new) < 2) echo __('Image removed from favorites',ImStore::domain).'|ims-success';
	else echo sprintf(__('%d images removed from favorites',ImStore::domain),count($new)).'|ims-success';
}


if($_GET['action'] == 'flashimagedata')
	 ajax_ims_flash_image_data();
	 
if($_GET['action'] == 'deletelist')
	ajax_imstore_pricelist_delete();
	
if($_GET['action'] == 'deleteimage')
	ajax_imstore_delete_post();
	 
if($_GET['action'] == 'upadateimage')
	ajax_imstore_update_post();
	
if($_GET['action'] == 'deletepackage')
	ajax_imstore_delete_post();
	
if($_GET['action'] == 'editimstatus')
	ajax_imstore_edit_image_status();

if($_GET['action'] == 'favorites')
	 ajax_ims_add_images_to_favorites();
	 
if($_GET['action'] == 'remove-favorites')
	 ajax_ims_remove_images_from_favorites();

?>