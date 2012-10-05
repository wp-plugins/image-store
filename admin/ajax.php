<?php

/**
 * Ajax events for admin area
 *
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0
 */
//dont cache file
header('Last-Modified:' . gmdate('D,d M Y H:i:s') . ' GMT');
header('Cache-control:no-cache,no-store,must-revalidate,max-age=0');

//define constants
define('WP_ADMIN', true);
define('DOING_AJAX', true);

$_SERVER['PHP_SELF'] = "/wp-admin/imstore-ajax.php";

//load wp
require_once '../../../../wp-load.php';

/**
 * Change the image status
 *
 * @return void
 * @since 2.0.0
 */
function ajax_imstore_edit_image_status() {
	check_ajax_referer("ims_ajax");
	if (!current_user_can("ims_manage_galleries") || empty($_GET['imgid']))
		die();
	wp_update_post(array(
		"ID" => trim($_GET['imgid']),
		'post_status' => $_GET['status']
	));
	die();
}

/**
 * Move price list to trash
 *
 * @return void
 * @since 0.5.0
 */
function ajax_imstore_pricelist_delete() {
	check_ajax_referer("ims_ajax");
	if (!current_user_can("ims_change_pricing") || empty($_GET['postid']))
		die();
	wp_delete_post(intval($_GET['postid']), true);
	die();
}

/**
 * Update post
 *
 * @return void
 * @since 2.0.0
 */
function ajax_imstore_update_post() {
	check_ajax_referer("ims_ajax");
	if (!current_user_can("ims_manage_galleries") || empty($_GET['imgid']))
		die();

	$post = array(
		'ID' => $_GET['imgid'],
		'menu_order' => $_GET['order'],
		'post_title' => $_GET['imgtitle'],
		'post_excerpt' => $_GET['caption'],
	);
	wp_update_post($post);
	die();
}

/**
 * Delete post
 *
 * @return void
 * @since 2.0.0
 */
function ajax_imstore_delete_post() {

	check_ajax_referer("ims_ajax");
	if (empty($_GET['postid']))
		die();
		
	if (current_user_can("ims_manage_galleries")
	|| current_user_can("ims_change_pricing")) {
		
		global $ImStore;
		$postid = (int) $_GET['postid'];
		
		//delete file from server
		if (!empty($_GET['parent']) && isset($_GET['deletefile']) && $_GET['deletefile'] == true) {
			$data = get_post_meta($postid, '_wp_attachment_metadata', true);
			if ($data && is_array($data['sizes'])) {
				$imgpath = $ImStore->content_dir . '/' . dirname($data['file']);
				foreach ($data['sizes'] as $size) {
					if (file_exists($imgpath . "/_resized/" . $size['file']))
						unlink($imgpath . "/_resized/" . $size['file']);
					else @unlink($imgpath . "/" . $size['file']);
				}
				@unlink($ImStore->content_dir . '/' . $data['file']);
			}
		}
		wp_delete_post($postid, true);
	}
	die();
}

/**
 * Serch galleries
 *
 * @return void
 * @since 3.0.0
 */
function ajax_ims_search_galleries() {
	check_ajax_referer("ims_ajax");
	if (!current_user_can("ims_manage_galleries"))
		die();

	$q = empty($_GET['q']) ? false : $_GET['q'];
	$qfilter = ($q) ? " AND p.post_title LIKE '%%%s%%' " : false;
	$limit = (isset($_GET['c']) && is_numeric($_GET['c'])) ? $_GET['c'] . "," . $_GET['c'] + 10 : "0, 30 ";

	global $wpdb, $ImStore;
	$galleries = $wpdb->get_results($wpdb->prepare("SELECT p.id, pm.meta_value v, p.post_title t FROM $wpdb->posts p 
	LEFT JOIN $wpdb->postmeta pm ON p.ID = pm.post_id 
	WHERE 1=1 AND ( pm.meta_key = '_ims_gallery_id' ) 
	AND p.post_type = 'ims_gallery' $qfilter ORDER BY p.post_date DESC LIMIT $limit", $q));

	if (empty($galleries)) {
		echo '<li class="gal-0"><span class="gtitle"><em>' . __(' Sorry, nothing found.', 'ims') . '</em></span></li>' . "\n";
		die();
	}

	foreach ($galleries as $gal) {
		echo '<li class="gal-' . $gal->id . '"><span class="gtitle">' . $gal->t . '</span><span class="id">' . trim($gal->v) . '</span></li>' . "\n";
	}
	die();
}

/**
 * Add images to favorites
 *
 * @return void
 * @since 0.5.0
 */
function ajax_ims_add_images_to_favorites() {
	
	check_ajax_referer("ims_ajax_favorites");

	global $user_ID, $ImStore;

	$id = (int) $_GET['galid'];
	if (empty($_GET['imgids']) || empty($id)) {
		echo __('Please, select an image', 'ims') . '|ims-error';
		die();
	}

	$new = explode(',', $_GET['imgids']);

	foreach ($new as $id)
		$dec_ids[] = $ImStore->url_decrypt($id);

	if (is_user_logged_in()) {
		$join = trim(get_user_meta($user_ID, '_ims_favorites', true) . "," . implode(',', $dec_ids), ',');
		$ids = array_unique(explode(',', $join));
		update_user_meta($user_ID, '_ims_favorites', implode(',', $ids));
	} else {
		$join = isset($_COOKIE['ims_favorites_' . COOKIEHASH]) ?
				trim($_COOKIE['ims_favorites_' . COOKIEHASH] . "," . implode(',', $dec_ids), ',') : implode(',', $dec_ids);
		$ids = array_unique(explode(',', $join));
		setcookie('ims_favorites_' . COOKIEHASH, implode(',', $ids), 0, COOKIEPATH, COOKIE_DOMAIN);
	}

	if (count($new) < 2)
		echo __('Image added to favorites', 'ims') . '|ims-success|' . count($ids);
	else
		echo sprintf(__('%d images added to favorites', 'ims'), count($new)) . '|ims-success|' . count($ids);

	die();
}

/**
 * Remove images from favorites
 *
 * @return void
 * @since 0.5.0
 */
function ajax_ims_remove_images_from_favorites() {
	
	check_ajax_referer("ims_ajax_favorites");

	global $user_ID, $ImStore;
	$id = intval($_GET['galid']);

	if (empty($_GET['imgids']) || empty($id)) {
		echo __('Please, select an image', 'ims') . '|ims-error';
		return;
	}

	$dec_ids = array();
	$new = explode(',', $_GET['imgids']);
	
	foreach ($new as $id)
		$dec_ids[] = trim($ImStore->url_decrypt($id));

	if (is_user_logged_in()) {
		if (empty($_GET['count']))
			update_user_meta($user_ID, '_ims_favorites', '');
		else {
			$join = array_flip(explode(',', trim(get_user_meta($user_ID, '_ims_favorites', true), ',')
					));

			foreach ($dec_ids as $remove)
				unset($join[$remove]);

			$ids = implode(',', array_flip($join));
			update_user_meta($user_ID, '_ims_favorites', $ids);
		}
	} else {
		if (empty($_GET['count']))
			setcookie('ims_favorites_' . COOKIEHASH, 0, 0, COOKIEPATH, COOKIE_DOMAIN);
		else {
			$join = array_flip(explode(',', trim($_COOKIE['ims_favorites_' . COOKIEHASH], ',')));

			foreach ($dec_ids as $remove)
				unset($join[$remove]);

			$ids = implode(',', array_flip($join));
			setcookie('ims_favorites_' . COOKIEHASH, $ids, 0, COOKIEPATH, COOKIE_DOMAIN);
		}
	}

	if (count($new) < 2)
		echo __('Image removed from favorites', 'ims') . '|ims-success';
	else
		echo sprintf(__('%d images removed from favorites', 'ims'), count($new)) . '|ims-success';

	die();
}

/**
 * modify image size mini when thumbnail 
 * is modify by the image edit win
 * 
 * @return void
 * @since 0.5.5
 */
function ajax_ims_edit_image_mini() {

	$post_id = intval($_GET['imgid']);
	check_ajax_referer("image_editor-{$post_id}");

	if ('ims_image' != get_post_type($post_id))
		die();

	$meta = wp_get_attachment_metadata($post_id);
	
	if (empty($meta['file']))
		die();

	global $ImStore;
	if (stristr($meta['file'], 'wp-content') !== false)
		$path = dirname(str_ireplace($ImStore->content_dir,'', $meta['file']));
	else
		$path = dirname($meta['file']);

	if (!preg_match(" /(_resized)/i", $path))
		$path = "$path/_resized";

	$img = $ImStore->content_dir . "/$path/" . $meta['sizes']['thumbnail']['file'];

	include_once( ABSPATH . 'wp-admin/includes/image-edit.php' );
	$resized_file = image_resize($img, get_option("mini_size_w"), get_option("mini_size_h"), true);

	if (!is_wp_error($resized_file) && $resized_file && $info = getimagesize($resized_file))
		$meta['sizes']['mini'] = array(
			'file' => basename($resized_file),
			'width' => $info[0],
			'height' => $info[1],
		);

	wp_update_attachment_metadata($post_id, $meta);
	die();
}

/**
 * Display image ipct data
 * 
 * @return void
 * @since 3.1.0
 */
function ajax_ims_display_iptc() {

	check_ajax_referer("ims_ajax");

	if (!current_user_can("ims_manage_galleries")
			|| empty($_REQUEST['id']))
		die();

	$id = (int) $_REQUEST['id'];
	$meta = get_post_meta($id, '_wp_attachment_metadata', true);

	if (empty($meta['image_meta']) || !is_array($meta['image_meta']))
		die();

	echo '<style>
		.ims-clear{ clear:both; padding:20px; text-align:right}
		.ims-img-metadata{ padding-top:30px;}
		.ims-img-metadata .ims-meta-field{ width:50%; float:left}
		.ims-img-metadata .ims-meta-field label{ width:45%; margin-righ:1%; display:inline-block  }
		</style>';

	echo '<form action="" method="post" class="meta-form">';
	echo '<div class="ims-img-metadata">';
	foreach ($meta['image_meta'] as $key => $data) {
		echo '<div class="ims-meta-field">';
		echo '<label for="' . $key . '">' . ucwords(str_replace(array('_', '-'), ' ', $key)) . '</label>';
		echo '<input type="text" name="' . $key . '" value="' . esc_attr($data) . '" class="" />';
		echo '</div>';
	}
	echo '</div>';
	echo '<div class="ims-clear"><input name="save-metadata" type="submit" class="button-primary" value="' . __('Save') . '" />
			<input name="imageid" type="hidden"  value="' . $id . '" /></div>';
	echo '</form>';
	die();
}

switch ($_GET['action']) {
	case 'deletelist':
		ajax_imstore_pricelist_delete();
		break;
	case 'editimstatus':
		ajax_imstore_edit_image_status();
		break;
	case 'deleteimage':
		ajax_imstore_delete_post();
		break;
	case 'deletepackage':
		ajax_imstore_delete_post();
		break;
	case 'upadateimage':
		ajax_imstore_update_post();
		break;
	case 'edit-mini-image':
		ajax_ims_edit_image_mini();
		break;
	case 'favorites':
		ajax_ims_add_images_to_favorites();
		break;
	case 'remove-favorites':
		ajax_ims_remove_images_from_favorites();
		break;
	case 'imageiptc':
		ajax_ims_display_iptc();
		break;
	case 'searchgals':
		ajax_ims_search_galleries();
		break;
	default: die();
}