<?php 

/**
 * Import page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_add_galleries' ) ) 
	die( );
	

//disable flash upload
if ( isset( $_POST['disableflash'] ) ){
	check_admin_referer( 'ims_upload_images' );
	$opts = get_option( 'ims_back_options' );
	$opts['swfupload'] = '0';	
	update_option( 'ims_back_options', $opts );
}

//enable flash upload
if ( isset( $_POST['enableflash'] ) ){
	check_admin_referer( 'ims_upload_images' );
	$opts = get_option( 'ims_back_options' );
	$opts['swfupload'] = '1';
	update_option( 'ims_back_options', $opts );
}

//upload zip file
if( isset( $_POST['zipupload'] ) ){
	check_admin_referer( 'ims_upload_zip' );
	$errors = upload_ims_zipfile( $this->opts['galleriespath'] );
}

//upload folder (ftp)
if( isset( $_POST['importfolder'] ) ){
	check_admin_referer( 'ims_import_folder' );
	$errors = import_ims_folder( $this->opts['galleriespath'] );
}

//upload single image no flash
if( isset( $_POST['uploadimage'] ) ){
	check_admin_referer( 'ims_upload_images' );
	$errors = ims_sigle_image( );
}

// Create new gallery
if( isset( $_POST['addnewgallery'] ) ){
	check_admin_referer( 'ims_new_gallery' );
	$errors = add_ims_gallery( $this->opts['galleriespath'], $this->opts['galleryexpire'], $this->opts['disablestore'] );
}

$this->opts 	+= (array)get_option( 'ims_back_options' );
$message[1] 	= sprintf( __( '%1$d images added.', ImStore::domain ), $_GET['c'] );
$message[2] 	= __( 'Image added.', ImStore::domain ) ;
$message[3] 	= __( 'New gallery succesfully created.', ImStore::domain ) ;

$galleries 		= get_ims_galleries( );
$downloadmax 	= ( $_POST['ims_download_max'] ) ? esc_attr( $_POST['ims_download_max'] ) : '0';
$date_format	= get_option( 'date_format' );
$date 			= ( $_POST['date'] ) ? esc_attr( $_POST['date'] ) : date( $date_format, current_time( 'timestamp' ) );
$post_date 		= ( $_POST['post_date'] ) ? esc_attr( $_POST['post_date'] ) : date( 'Y-m-d H:i:s', current_time( 'timestamp' ) );

?>

<div class="wrap imstore">
	
	<?php screen_icon( 'new-gallery' )?>
	<h2><?php _e( 'Add New Gallery', ImStore::domain )?></h2>
	
	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error"><?php
		foreach ( $errors->get_error_messages( ) as $err )
				echo "<p><strong>$err</strong></p>\n"; ?>
	</div>
	<?php endif; ?>
 		 		
	<div id="poststuff" class="metabox-holder">
		
	<?php if( !empty($_GET['ms']) ){ ?>
	<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
 		 		
 		<ul class="ims-tabs add-menu-item-tabs">
			<li class="tabs"><a href="#upload-zip"><?php _e( 'Upload a Zip-File', ImStore::domain )?></a></li>
			<li class="tabs"><a href="#import-folder"><?php _e( 'Import folder', ImStore::domain )?></a></li>
			<li class="tabs"><a href="#upload-images"><?php _e( 'Upload Images', ImStore::domain )?></a></li>
  	<li class="tabs"><a href="#new-gallery"><?php _e( 'New Gallery', ImStore::domain )?></a></li>
		</ul>
		
		<!-- Upload zip file -->
		
		<div id="upload-zip" class="ims-box">
		<form method="POST" action="<?php echo $pagenowurl . '#upload-zip' ?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'ims_upload_zip' )?>
			<table class="ims-table"> 
				<tbody>
					<tr> 
						<td scope="row" width="30%"><?php _e( 'Save into', ImStore::domain )?></td> 
						<td rowspan="2"><label for="zipfile"><?php _e( 'Select zip file', ImStore::domain )?></label></td>
					</tr>
					<tr valign="top">
						<td scope="row" rowspan="5" class="inline-editor" valign="top">
					 	<div class="inline-edit-col">
							<ul class="cat-checklist category-checklist">
								<li><label><input type="radio" name="gallery" checked="checked" value="new"> <?php _e( 'New Gallery', ImStore::domain )?></label></li>
								<?php if( !empty($galleries) ){ foreach ( $galleries as $gal ): ?>
								<li><label><input type="radio" name="gallery" value="<?php echo $gal->ID ?>"> <?php echo $gal->post_title ?></label></li>
								<?php endforeach; } ?>
							</ul>
						</div>
						</td>
					</tr>
					<tr> 
						<td scope="row" ><input type="file" name="zipfile" id="zipfile" /></td> 
					</tr>
					<tr>
						<td scope="row">
							<label for="zipurl"><?php _e( 'Or enter zip file URL', ImStore::domain )?></label><br />
							<input type="text" name="zipurl" id="zipurl" class="inputxl"/><br />
							<small><?php _e( 'Import a zip file with images from a url', ImStore::domain )?></small>
						</td>
					</tr>
					<tr>
						<td scope="row">
						<input class="button-primary fl" type="submit" id="zipupload" name="zipupload" value="<?php _e('Start upload',ImStore::domain)?>"/>
						<div class="loading">&nbsp;<?php _e( 'Uploading', ImStore::domain )?> </div>
						</td>
					</tr>
					<tr>
						<td scope="row" valign="top" class="btop">
							<small><?php echo sprintf( __( "Imported galleries will be created using the zip name and default gallery settings. Your server's maximum file size upload is <strong>%sB</strong>. ", ImStore::domain ), ini_get( 'upload_max_filesize'))?></small>
						</td>
					</tr>
				</tbody>
			</table>
			<input name="MAX_FILE_SIZE" type="hidden" value="<?php echo return_bytes( ini_get( 'upload_max_filesize') ); ?>" />
		</form>
		</div>
		
		<!-- Import Folder -->
		
		<div id="import-folder" class="ims-box">
		<form method="POST" action="<?php echo $pagenowurl . '#import-folder'?>">
		<?php wp_nonce_field( 'ims_import_folder' )?>
			<table class="ims-table">
				<tr><td scope="row" colspan="2">&nbsp;</td></tr>
				<tr> 
					<td scope="row" width="30%" valign="top">
						<label for="galleryfolder"><?php _e( 'Import From Server Path', ImStore::domain )?></label><br />
					</td> 
					<td>
						<input type="text" name="galleryfolder" id="galleryfolder" class="inputxl" /><br />
						<small><?php _e( "New galleries will be created using the folder's name and default gallery settings. Path relative to the gallery folder path set on the settings page", ImStore::domain )?></small>
					</td> 
				</tr>
				<tr>
					<td scope="row">&nbsp;</td>
					<td>
						<input type="submit" name="importfolder" id="importfolder" value="<?php _e( 'Import folder', ImStore::domain )?>" class="button-primary" />
						<div class="loading">&nbsp; <?php _e( 'Scanning', ImStore::domain )?></div>
					</td>
				</tr>
				<tr><td scope="row" colspan="2">&nbsp;</td></tr>
			</table>
		</form>
		</div>
		
		<!-- Upload Images -->
		
		<div id="upload-images" class="ims-box">
		<form method="POST" action="<?php echo $pagenowurl . '#upload-images'?>" enctype="multipart/form-data">
		<?php wp_nonce_field( 'ims_upload_images' )?>
			<table class="ims-table">
				<tr>
					<td scope="row" width="30%"><label for="intogalleryimage"><?php _e( 'Save into', ImStore::domain )?></label></td> 
					<td><label for="imagefiles"><?php _e( 'Upload image', ImStore::domain )?></label>:</td>
				</tr>
				<tr valign="top">
					<td scope="row" rowspan="5" class="inline-editor" valign="top">
					 	<div class="inline-edit-col">
							<ul class="cat-checklist category-checklist">
								<?php if( !empty($galleries) ){ foreach ( $galleries as $gal ): ?>
								<li><label><input type="radio" name="gallery" value="<?php echo $gal->ID ?>"> <?php echo $gal->post_title ?></label></li>
								<?php endforeach; } ?>
							</ul>
						</div>
					</td>
					<td valign="top" class="flash-upload">
						<?php if ( $this->opts['swfupload'] ){?>
						<input type="button" value="<?php _e( 'Select files', ImStore::domain )?>" class="button selectfiles" />
						<?php } ?>
						<input id="imagefiles" name="imagefiles" type="file" />
					</td>
				</tr>
				<tr>
					<td scope="row">
					<input type="submit" name="uploadimage" value="<?php _e('Upload images', ImStore::domain)?>" class="button-primary upload-images"/>
					</td>
				</tr>
				<tr><td scope="row">&nbsp;</td></tr>
				<tr>
					<td valign="top" class="btop pt">
					
					<?php if ( $this->opts['swfupload'] ){?>
						<input type="submit" name="disableflash" value="<?php _e( 'Disable flash upload', ImStore::domain )?>" class="button" /><br />
						<small><?php _e( 'Disable flash upload it if you have problems uploading images', ImStore::domain )?></small>
						<?php } else {?>
						<input type="submit" name="enableflash" value="<?php _e( 'Enable flash upload', ImStore::domain ) ?>" class="button" /><br />
						<small><?php _e( 'It will allow you to load multiple files at once', ImStore::domain )?></small>
					<?php }?>
					</td>
				</tr>
			</table>
		</form>
		</div>
		
		
		<!-- NEW GALLERY -->
		
		<div id="new-gallery" class="ims-box" >
   <form method="POST" action="<?php echo $pagenowurl . '#new-gallery' ?>" >
			<?php wp_nonce_field( 'ims_new_gallery' )?>
   <table class="ims-table" >
				<tr>
					<td width="18%">
						<label for="post_title"><?php _e( 'Gallery Name', ImStore::domain )?>
						<small>(<?php _e( 'required', ImStore::domain )?>)</small></label>
					</td>
					<td width="30%">
						<input type="text" name="post_title" id="post_title" value="<?php echo esc_attr( $_POST['post_title'] )?>" class="inputxl" />
					</td>
					<td width="18%">
						<label for="date" class="date-icon"><?php _e( 'Event Date', ImStore::domain )?></label>
					</td>
					<td>
						<input type="text" name="date" id="date" class="inputmd" value="<?php echo $date ?>" />
						<input type="hidden" name="post_date" id="post_date" value="<?php echo $post_date ?>"/>
					</td>
				</tr>
				<tr>
					<td valign="top"><label for="post_password"><?php _e( 'Password', ImStore::domain )?></label></td>
					<td><input type="text" name="post_password" id="post_password" value="<?php echo esc_attr( $_POST['post_password'] ) ?>" class="inputxl" /></td>
					<td><label for="expire" class="date-icon"><?php _e( 'Expiration Date', ImStore::domain )?></label></td>
					<td>
						<input type="text" name="expire" id="expire" class="inputmd" value="<?php echo esc_attr( $_POST['expire'] )?>" />
						<input type="hidden" name="ims_expire" id="ims_expire" value="<?php echo esc_attr( $_POST['ims_expire'] ) ?>"/>
					</td>
					<!--<td valign="top"><label for="ims_download_max"><?php _e( 'Downloads allowed', ImStore::domain )?></label></td>
					<td><input name="ims_download_max" type="text" id="ims_download_max" value="<?php echo $downloadmax ?>" class="inputsm"/></td>-->
				</tr>
				<?php if( !$this->opts['disablestore'] ){ ?>
				<tr>
					<td><label for="ims_tracking"><?php _e( 'Tracking Number', ImStore::domain )?></label></td>
					<td><input type="text" name="ims_tracking" id="ims_tracking" value="<?php echo esc_attr( $_POST['ims_tracking'] )?>" class="inputxl" /></td>
					<td>
						<label for="_ims_price_list"><?php _e( 'Price list', ImStore::domain)?>
						<small>(<?php _e( 'required', ImStore::domain )?>)</small></label>
					</td>
					<td><?php $lists = $this->get_ims_pricelists( );?>
						<select name="_ims_price_list" id="_ims_price_list" >
							<option value=""><?php _e( 'Select a list &#8212;', ImStore::domain )?></option>
							<?php foreach( $lists as $list ):?>
							<option value="<?php echo $list->ID?>" <?php selected( $list->ID, $_POST['_ims_price_list'] )?>><?php echo $list->post_title ?></option>
							<?php endforeach?> 
						</select>
					</td>
				</tr>
				<?php } ?>
				<tr class="imstore-table">
					<td colspan="4">&nbsp;</td>
					</tr>
				<tr class="imstore-table">
					<td><strong><?php _e( 'Customer', ImStore::domain )?></strong></td>
					<td><?php $customers = $this->get_ims_active_customers( ) ?>
						<select name="_ims_customer" id="_ims_customer">
							<option value=""><?php _e( 'Select customer &#8212;', ImStore::domain )?></option>
							<?php foreach( $customers as $customer ):?>
							<option value="<?php echo $customer->ID?>" <?php selected( $customer->ID, $_POST['_ims_customer'] )?>><?php echo $customer->user_login?></option>
							<?php endforeach?> 
						</select>
					</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr class="imstore-table">
					<td colspan="2"><?php _e( 'or enter information', ImStore::domain )?></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
				<tr class="imstore-table">
					<td><label for="first_name"><?php _e( 'Name', ImStore::domain )?></label></td>
					<td><input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $_POST['first_name'] )?>" class="inputxl" /></td>
					<td><label for="last_name"><?php _e( 'Last Name', ImStore::domain )?></label></td>
					<td><input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $_POST['last_name'] )?>" class="inputxl"/></td>
				</tr>
				<tr class="imstore-table">
					<td><label for="user_email"><?php _e( 'Email', ImStore::domain )?></label></td>
					<td><input type="text" name="user_email" id="user_email" value="<?php echo esc_attr( $_POST['user_email'] )?>" class="inputxl" /></td>
					<td><?php if ( class_exists('MailPress') ):?>
						<label for="ims_enewsletter"><?php _e( 'Add to eNewsletter', ImStore::domain )?></label>
					<?php endif?></td>
					<td><?php if ( class_exists('MailPress') ):?>
						<input type="checkbox" name="ims_enewsletter" id="ims_enewsletter" value="1"<?php checked( 1, $_POST['ims_enewsletter'] )?> />
					<?php endif?></td>
				</tr>
				<tr class="imstore-table">
					<td><label for="ims_address"><?php _e( 'Address', ImStore::domain )?></label></td>
					<td><input type="text" name="ims_address" id="ims_address" value="<?php echo esc_attr( $_POST['ims_address'] )?>" class="inputxl" /></td>
					<td><label for="ims_city"><?php _e( 'City', ImStore::domain )?></label></td>
					<td><input type="text" name="ims_city" id="ims_city" value="<?php echo esc_attr( $_POST['ims_city'] )?>" class="inputmd" /></td>
				</tr>
				<tr>
					<td><label for="ims_state"><?php _e( 'State', ImStore::domain )?></label></td>
					<td><input type="text" name="ims_state" id="ims_state" value="<?php echo esc_attr( $_POST['ims_state'] )?>" class="input" />
						<label for="ims_zip"><?php _e( 'Zip', ImStore::domain )?></label>
						<input type="text" name="ims_zip" id="ims_zip" value="<?php echo esc_attr( $_POST['ims_zip'] )?>" class="inputsm" /></td>
					<td><label for="ims_phone"><?php _e( 'Phone', ImStore::domain )?></label></td>
					<td><input type="text" name="ims_phone" id="ims_phone" value="<?php echo esc_attr( $_POST['ims_phone'] )?>" class="inputxl" /></td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td colspan="3" class="submit">
					<input type="submit" name="addnewgallery" value="<?php _e( 'Add Gallery', ImStore::domain)?>" class="button-primary" />
					</td>
				</tr>
			</table>
   </form>
  </div>

	</div>
	
</div>


<?php 
/**
 * Upload zip file
 *
 * @param string $galspath
 * @return void
 * @since 0.5.0
 */
function upload_ims_zipfile( $galspath ){
	global $wpdb, $pagenowurl, $galleryid;

	$errors = new WP_Error( );
	
	if( $_FILES['zipfile']['error'] == 4 && empty( $_POST['zipurl'] ) ){
		$errors->add( 'no_file', __( 'Please select a file to upload.', ImStore::domain ) );
		return $errors;
	}

	//remote upload
	if ( !empty( $_POST['zipurl'] ) ){
		
		if ( !preg_match('/^http(s)?:\/\//i', $_POST['zipurl']) ) 
			 $errors->add( 'wrong_url', __( 'Not a valid URL path', ImStore::domain ) );
			
		if ( !preg_match('/(zip)$/i', $_POST['zipurl']) ) 
			 $errors->add( 'wrong_file', __( 'This is not a zip file.', ImStore::domain ) );
		
		if( !empty( $errors->errors ) )
			return $errors;
		
		$filename 	 = basename( $_POST['zipurl'] );
		$download_file = download_url( $_POST['zipurl'] );
		
		if ( is_wp_error( $download_file ) ){
			 $errors->add( 'fail_uploadload_1', __( 'Upload failed.', ImStore::domain ) );
			 return $errors;
		}
			
	//file upload
	}elseif( $filename = $_FILES['zipfile']['name'] ){
		
		if ( !preg_match('/(zip)$/i', $_FILES['zipfile']['name']) ){ 
			 $errors->add( 'wrong_file', __( 'This is not a zip file.', ImStore::domain ) );
			return $errors;
		}

		if ( $_FILES['zipfile']['error'] != '0' || $_FILES['zipfile']['size'] == 0 ) {
			$errors->add( 'fail_uploadload_2' , __( 'Upload failed. Make sure to check your file uplaod size limit!!', ImStore::domain ) );
		 	return $errors;
		}

		$download_file	= $_FILES['zipfile']['tmp_name'];
	}


	//check if is a new gallery
	if( $_POST['gallery'] == 'new' ){
		
		$filename	= sanitize_title_with_dashes( strtok( $filename, '.' ) );
		$gallerypath = WP_CONTENT_DIR . $galspath . '/' . $filename;
					
		if( file_exists( $gallerypath ) ){
			$errors->add( 'gallery_exists', __( 'Gallery already exist, change .zip name or chose a gallery where to upload it.', ImStore::domain ) );
			return $errors;
		}
		$galleryid = add_ims_gallery_default( $filename, $galspath . '/' . $filename );	
		
	}else{
		$galleryid	= intval( $_POST['gallery'] );
		$folderpath = get_post_meta( $galleryid, '_ims_folder_path', true );
		$gallerypath = WP_CONTENT_DIR . $folderpath ;
	}

	if( empty( $gallerypath ) ){
		$errors->add( 'wrong_file', __( 'Could not get a valid folder name.' , ImStore::domain ) );
		return $errors;
	}
	
	if( empty( $galleryid ) ){
		$errors->add( 'gal_error', __( 'There was an error extracting the images.' , ImStore::domain ) );
		return $errors;
	}
	
	//process zip file
	include_once	( ABSPATH . 'wp-admin/includes/class-pclzip.php');
	$PclZip 		= new PclZip( $download_file );
	$ziped			= $PclZip->extract( PCLZIP_OPT_PATH, $gallerypath,
							 PCLZIP_OPT_REMOVE_ALL_PATH,
    			 			 PCLZIP_CB_PRE_EXTRACT, '_ims_unzip_images',
							 PCLZIP_CB_POST_EXTRACT, '_ims_create_img_metadata',
							 PCLZIP_OPT_SET_CHMOD, 0775
	); 
	
	@unlink( $download_file );
	
	if ( empty( $ziped ) ){
		$errors->add( 'gal_error', __( 'There was a problem unziping file.', ImStore::domain ) );
		return $errors;
	}
	
	foreach( $ziped as $file ){
		if( $file['status'] == 'ok' )
			$imagesunziped[] = 1;
	}
	
	//update image count
	$count = intval( get_post_meta( $galleryid, '_ims_image_count', true ) ) + count( $imagesunziped );
	update_post_meta( $galleryid, '_ims_image_count', $count );
	
	wp_redirect( $pagenowurl . "&ms=1&c=" . count( $imagesunziped ) . '#upload-zip' );
	
}


/**
 * get all published galleries
 *
 * @return boolean/array
 * @since 0.5.0
 */
function get_ims_galleries( ){
	global $wpdb; 

	return $wpdb->get_results( "
		SELECT ID, post_title 
		FROM $wpdb->posts
		WHERE post_type = 'ims_gallery'
		AND post_status != 'trash'"
	);
}


/**
 * Pre call back function for extract PCLZIP 
 * 
 * @param unknown $p_event
 * @param array &$p_header to extract
 * @return unit
 * @since 0.5.0
 */
function _ims_unzip_images( $p_event, &$p_header ) {
		
		$filename = basename( $p_header['filename'] );

		//remove mac hidden files
		if( $filename{0} == '.' ) return 0;
		
		//check extension remove others	
		if ( preg_match( '/(png|jpg|jpeg|gif)$/i', $filename ) ){
			$filename = wp_unique_filename( dirname( $p_header['filename'] ), sanitize_file_name( $filename ) );
			$p_header['filename'] = dirname( $p_header['filename'] ).'/'. $filename ;
			return 1;
		}
		
	 	return 0;
}


/**
 * Post call back function for extract PCLZIP 
 * 
 * @param unknown $p_event
 * @param array &$p_header to extract
 * @return void
 * @since 0.5.0
 */
function _ims_create_img_metadata( $p_event, &$p_header ){
	global $galleryid, $wpdb;
	
	if ( $p_header['status'] == 'ok' ) {

		$filename = sanitize_file_name( basename( $p_header['filename'] ) );
		$filetype = wp_check_filetype( $filename );
		$des_path = dirname( $p_header['filename'] ) . '/_resized' ;
		$relative = str_replace( str_replace( '\\' , '/', WP_CONTENT_DIR ), '', str_replace( '\\' , '/', $des_path . '/' . $filename ));
		$guid = WP_CONTENT_URL . $relative;
		if( !file_exists( $des_path ) ) @mkdir( $des_path, 0775 );
		
		$attachment = array(
			'guid' => $guid,
			'post_title' => $filename,
			'post_type' => 'ims_image',
			'post_mime_type'=> $filetype['type'],
			'post_status' => 'publish',
			'post_parent' => $galleryid,
		);
		
		//if image exist dont't load it
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE 1=1 AND guid = %s", $guid ) ) ) return 0;
		
		$attach_id = wp_insert_post( $attachment );
		if( empty( $attach_id ) ) return 1;
		
		//resize images
		$img_sizes = get_option( 'ims_dis_images' );
		$img_sizes['thumbnail']['name'] = "thumbnail";
		$img_sizes['thumbnail']['crop'] = '1';
		$img_sizes['thumbnail']['q'] 	= '95';
		$img_sizes['thumbnail']['w'] 	= get_option("thumbnail_size_w");
		$img_sizes['thumbnail']['h'] 	= get_option("thumbnail_size_h");
			
		$downloadsizes = get_option( 'ims_download_sizes' );
		if( is_array( $downloadsizes ) ) $img_sizes += $downloadsizes;
		
		foreach( $img_sizes as $img_size ){
			$resized = image_resize( $p_header['filename'], $img_size['w'], $img_size['h'], $img_size['crop'], null, $des_path, $img_size['q'] );
			if ( !is_wp_error( $resized ) && $resized && $info = getimagesize($resized) ) {
				$imgname = basename( $resized );
			}else{
				$info = getimagesize( $p_header['filename'] );
				$imgname = basename( $p_header['filename'] );
			}
			
			$data = array(
				'file' 	=> $imgname,
				'width' => $info[0],
				'height'=> $info[1],
			);
			
			//copy file to be use when plugin is uninstall
			@copy( $p_header['filename'], $des_path . '/' . $filename ); 
			
			//create metadata
			$imagesize = getimagesize( $p_header['filename'] );
			$metadata['width'] = $imagesize[0];
			$metadata['height'] = $imagesize[1];
			list($uwidth, $uheight) = wp_constrain_dimensions( $metadata['width'], $metadata['height'], 100, 100 );
			$metadata['hwstring_small'] = "height='$uheight' width='$uwidth'";
			
			switch( $imagesize['channels'] ){ 
				case 1: $metadata['color'] = 'BW'; break;
				case 3: $metadata['color'] = 'RGB'; break;
				case 4: $metadata['color'] = 'CMYK'; break;
				default: $metadata['color'] = __( 'Unknown', ImStore::domain );
			}

			$metadata['file'] = $relative;
			$metadata['sizes'][$img_size['name']] = $data;
			$metadata['image_meta'] = wp_read_image_metadata( $p_header['filename'] );
		}
		wp_update_attachment_metadata( $attach_id, $metadata );
	}
	
	return 1;
}


/**
 * Add an gallery with default values
 *
 * @parm string $galleryname
 * @parm string $gallerypath
 * @return unit|bol 
 * @since 0.5.0
 */
function add_ims_gallery_default( $galleryname, $gallerypath ){
	global $wpdb, $ImStore;
	
	$opts 		= $ImStore->admin->opts;
	$expire 	= ( $opts['galleryexpire'] ) ? 
				  date( 'Y-m-d', ( current_time( 'timestamp' ) ) + ( $opts['galleryexpire'] * 86400 ) ) : '';
	$password 	= ( $opts['securegalleries'] ) ? wp_generate_password( 8 ) : '';
	$gallery 	= array( 
			'post_expire'	=> $expire,
			'post_status'	=> 'pending',
			'post_type' 	=> 'ims_gallery', 
			'post_title' 	=> $galleryname,
			'post_password'	=> $password,
	);
	
	// dont create duplicate galleries
	$gallery_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ims_folder_path' AND meta_value = '$gallerypath'" ); 
	
	if ( !$gallery_id ){ 
		$new_entry = '1'; 
		$gallery_id = wp_insert_post( $gallery ); 
	}
	
	if( empty( $gallery_id ) )
		return false;
	 
	if( $new_entry ){
		update_post_meta( $gallery_id, '_ims_folder_path', $gallerypath );
		update_post_meta( $gallery_id, 'ims_download_max', $opts['downloadmax'] );
		update_post_meta( $gallery_id, '_ims_price_list', get_option( 'ims_pricelist' ) );	
		update_post_meta( $gallery_id, '_ims_gallery_id', ImStoreAdmin::unique_linkid( ) );	
	}
	
	return $gallery_id;

}


/**
 * Import images from folder
 * 
 * @return boolean/array
 * @since 0.5.0
 */
function import_ims_folder( $galspath ){
	global $pagenowurl, $galleryid;

	$errors = new WP_Error( );
	
	if( empty( $_POST['galleryfolder'] ) ){
		$errors->add( 'no-path', __( 'Please enter a folder path.', ImStore::domain ) );
		return $errors;
	}
	
	$gallerypath = WP_CONTENT_DIR . $galspath . '/' . $_POST['galleryfolder'];
	
	if( !file_exists( $gallerypath ) ){
		$errors->add( 'no-folder', __( "The folder doesn&#8217;t exist, please check your path.", ImStore::domain ) );
		return $errors;
	}
	
	$newname = sanitize_file_name( $_POST['galleryfolder'] );
	$newpath = WP_CONTENT_DIR . $galspath . '/' . $newname ;
	
	if( @rename( $gallerypath, $newpath ) ) {
		
		$galleryid 	= add_ims_gallery_default( $newname, $galspath . '/' . $newname );	
		
		if ( $dh = @opendir ( $newpath ) ){
			while ( false !== ( $obj = readdir ( $dh ) ) ){
				if ( $obj{0} != '.' ){ 
					if( preg_match( '/(png|jpg|jpeg|gif)$/i', $obj ) ){
						$count++;
						$p_header['status']		= 'ok';
						$p_header['filename'] 	= $newpath . '/' . $obj;
						$imagesunziped[] = _ims_create_img_metadata( null , $p_header );
					}
				}
			}
			@closedir ( $dh );
		}
	}
	
	//update image count
	update_post_meta( $galleryid, '_ims_image_count', count( $imagesunziped ) );
	wp_redirect( $pagenowurl . "&ms=1&c=" . count( $imagesunziped ) );	
	
}


/**
 * upload single image
 * 
 * @return void
 * @since 0.5.0
 */
function ims_sigle_image( ){
	global $pagenowurl, $galleryid;
	
	$errors = new WP_Error( );
	
	if( empty( $_POST['gallery'] ) ){
		$errors->add( 'select_file' , __( 'Please, select a gallery!', ImStore::domain ) );
		return $errors;
	}
	
	
	if( !$filename = $_FILES['imagefiles']['name'] ){
		$errors->add( 'select_file' , __( 'Please, select file to upload.', ImStore::domain ) );
		return $errors;
	}
	
	if ( $_FILES['imagefiles']['error'] != '0' ) {
		$errors->add( 'fail_uploadload_2' , __( 'Upload failed.', ImStore::domain ) );
		return $errors;
	}
	
	if ( !preg_match( '/(png|jpg|jpeg|gif)$/i', $filename ) ){
		$errors->add( 'not_an_image' , __( 'The file is not an image file.', ImStore::domain ) );
		return $errors;
	}
	
	$galleryid		= $_POST['gallery'];
	$filename 		= sanitize_file_name( $_FILES['imagefiles']['name'] );
	$gallerypath 	= get_post_meta( $_POST['gallery'], '_ims_folder_path' , true );
	$target_file 	= WP_CONTENT_DIR . $gallerypath . '/' . $filename ;
	
	if( file_exists( $target_file ) ){
		$errors->add( 'image exists' , __( 'This image already exists.', ImStore::domain ) );
		return $errors;
	}
	
	if( !@move_uploaded_file( $_FILES['imagefiles']['tmp_name'], $target_file ) ){
		$errors->add( 'fail_uploadload_3' , __( 'Upload failed.', ImStore::domain ) );
		return $errors;
	}
	
	$p_header['status']		= 'ok';
	$p_header['filename'] 	= $target_file ;
	_ims_create_img_metadata( null , $p_header );
	
	@unlink( $_FILES['imagefiles']['tmp_name'] );
	
	//update image count
	$count = intval( get_post_meta( $galleryid, '_ims_image_count', true ) ) + 1;
	update_post_meta( $galleryid, '_ims_image_count', $count );
	
	wp_redirect( $pagenowurl . "&ms=2#upload-images" );	
	
}


/**
 * Insert a customer
 *
 * @since 0.5.0
 * return array errors
 */
function create_ims_customer( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['first_name'] ) )
		$errors->add( 'empty_first_name', __( 'The first name is required.', ImStore::domain ) );
	
	if( empty( $_POST['last_name'] ) )
		$errors->add( 'empty_last_name', __( 'The last name is required.', ImStore::domain ) );
	
	if( empty( $_POST['user_email'] ) )
		$errors->add( 'empty_last_name', __( 'The email is required.', ImStore::domain ) );
		
	if( !is_email( $_POST['user_email'] ) )
		$errors->add( 'empty_last_name', __( 'Wrong email format. That doesn&#8217;t look like an email to me.', ImStore::domain ) );
		
	$user_name = sanitize_user( $_POST['first_name'] .' '. $_POST['last_name'] );
	if( username_exists( $user_name ) && !isset( $_POST['update_customer'] ) ) 
		$errors->add('customer_exists', __( 'That customer already exists.' ) );
		
	if( !empty( $errors->errors ) )
		return $errors;
		
	$new_user = array(
		'ID' 			=> $_POST['user_id'],
		'user_pass' 	=> wp_generate_password( 12, false ),
		'user_login' 	=> $user_name,
		'user_nicename' => $user_name,
		'user_email' 	=> $_POST['user_email'],
		'first_name' 	=> $_POST['first_name'],
		'last_name' 	=> $_POST['last_name'],
		'role' 			=> 'customer'
	);

	$user_id = wp_insert_user( $new_user );
	if( is_wp_error( $user_id ) )
		return $user_id;

	$meta_keys = array ( 'ims_zip', 'ims_city', 'ims_phone', 'ims_state', 'ims_status', 'ims_address', 'ims_enewsletter');
	foreach( $meta_keys as $key ){
		if( !empty( $_POST[$key] ) )
			update_user_meta( $user_id, $key, $_POST[$key] );
	}
	update_user_meta( $user_id, 'ims_status', 'active' );
	
	return $user_id;
}



/**
 * Add an gallery
 *
 * @parm string $galspath 
 * @parm string $to_expire 
 * @return string on error
 * @since 0.5.0
 */
function add_ims_gallery( $galspath, $to_expire, $disablestore ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['post_title'] ) )
		$errors->add( 'empty_gal_name', __( 'Please enter a gallery name.', ImStore::domain ) );

	if( empty( $_POST['_ims_price_list'] ) && !$disablestore )
		$errors->add( 'empty_list', __( 'Please select a price list.', ImStore::domain ) );
	
	$gallerypath = $galspath . '/' . sanitize_title( $_POST['post_title'] );
	if( $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ims_folder_path' AND meta_value = '$gallerypath'" ) )
		$errors->add( 'exists', __( 'This gallery already exists please use a different name.', ImStore::domain ) );
		
	if( !empty( $errors->errors ) )
		return $errors;
	
	
	// assign/create cutomer
	if( !empty( $_POST['_ims_customer'] ) )
		$cutomerid = intval( $_POST['_ims_customer'] );
	elseif( !empty( $_POST['first_name'] ) || !empty( $_POST['last_name'] ) )
		$cutomerid = create_ims_customer( );
	
	if( is_wp_error( $cutomerid ) )
		return $cutomerid;
	
	$expire = ( !empty($_POST['expire']) ) ? $_POST['ims_expire'] : '';
	$gallery = array( 
			'post_expire'	=> $expire,
			'post_status'	=> 'pending',
			'post_type' 	=> 'ims_gallery', 
			'post_date'		=> $_POST['post_date'],
			'post_title' 	=> $_POST['post_title'],
			'post_password'	=> $_POST['post_password'],
	);
	
	
	$gallery_id = wp_insert_post( $gallery );
	
	if( empty( $gallery_id ) ){
		$errors->add( 'error_insert', __( 'There was a problem creating the gallery.', ImStore::domain ) );
		return $errors;
	}
	 
	update_post_meta( $gallery_id, '_ims_customer',$cutomerid );	
	update_post_meta( $gallery_id, '_ims_folder_path', $gallerypath);
	update_post_meta( $gallery_id, 'ims_tracking', $_POST['ims_tracking'] );
	update_post_meta( $gallery_id, '_ims_price_list', $_POST['_ims_price_list'] );	
	update_post_meta( $gallery_id, 'ims_download_max', $_POST['ims_download_max'] );
	update_post_meta( $gallery_id, '_ims_gallery_id', ImStoreAdmin::unique_linkid( ) );	
	
	wp_redirect( $pagenowurl . "&ms=3#new-gallery" );	
}


/**
 * Return value in bytes
 *
 * @parm string $size_str 
 * @return string 
 * @since 0.5.2
 */
function return_bytes( $size_str ){
  switch (substr ($size_str, -1)){
    case 'M': case 'm': return (int)$size_str * 1048576;
    case 'K': case 'k': return (int)$size_str * 1024;
    case 'G': case 'g': return (int)$size_str * 1073741824;
    default: return $size_str;
  }
}

?>