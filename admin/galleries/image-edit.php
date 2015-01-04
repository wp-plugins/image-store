<?php

	/**
	 * Image Store - Edit Images
	 *
	 * @file image-edit.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/galleries/image-edit.php
	 * @since 0.5.0
	 */
	 
	//dont cache file
	header( 'Expires:0' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Last-Modified:' . gmdate( 'D,d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-control:no-cache,no-store,must-revalidate,max-age=0');
	
	//define constants
	define( 'WP_ADMIN', true );
	define( 'DOING_AJAX', true );
	
	$_SERVER['PHP_SELF'] = "/wp-admin/image-edit.php";
	
	//load wp
	require_once '../../../../../wp-admin/admin.php';
	
	if( ! current_user_can( 'upload_files' ) )
	wp_die( __( 'Cheatin&#8217; uh?', 'ims' ) );
	
	check_admin_referer("ims_edit_image");
		
	wp_enqueue_script( 'image-edit' );
	
	wp_enqueue_style( 'wp-admin' );
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'media-views' );
	wp_enqueue_style( 'imgareaselect' );
	wp_enqueue_style( 'adminstyles', IMSTORE_URL.'/_css/admin.css',false, '0.5.0', 'all' );
	
	
	$id = isset( $_GET['editimage']  ) ? intval( $_GET['editimage'] ) : 0;
	$imgnonce = isset( $_GET['_wpnonce']  ) ? $_GET['_wpnonce']  : false;
	$nonce = wp_create_nonce( "image_editor-". $id );
	
	$image = get_post( $id );
	$meta = get_post_meta( $id, '_wp_attachment_metadata', true );
	
	if( empty( $meta['image_meta'] ))
		$meta['image_meta'] = array();
	
	@header( 'Content-Type:'.get_option( 'html_type').'; charset='.get_option( 'blog_charset'));
	$admin_html_class = ( is_admin_bar_showing() ) ? 'wp-toolbar' : '';
	?>
	
	<!DOCTYPE html>
	<!--[if IE 8]>
	<html xmlns="http://www.w3.org/1999/xhtml" class="ie8 <?php echo $admin_html_class; ?>" <?php do_action( 'admin_xml_ns' ); ?> <?php language_attributes(); ?>>
	<![endif]-->
	<!--[if !(IE 8) ]><!-->
	<?php /** This action is documented in wp-admin/includes/template.php */ ?>
	<html xmlns="http://www.w3.org/1999/xhtml" class="ims-image-edit" <?php do_action( 'admin_xml_ns' ); ?> <?php language_attributes(); ?>>
	<!--<![endif]-->
	<head>
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
	
	
	<title><?php bloginfo('name') ?> &rsaquo; <?php _e('Edit Image'); ?> &#8212; <?php _e('WordPress'); ?></title>
	<script type="text/javascript"> //<![CDATA[
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}}; var ajaxurl = '<?php echo admin_url( 'admin-ajax.php', 'relative' ); ?>', pagenow = 'media-upload-popup', adminpage = 'media-upload-popup', isRtl = <?php echo (int) is_rtl(); ?>; //]]></script>
	<?php
	do_action('admin_enqueue_scripts', 'media-upload-popup');
	do_action('admin_print_styles-media-upload-popup');
	do_action('admin_print_styles');
	do_action('admin_print_scripts-media-upload-popup');
	do_action('admin_print_scripts');
	do_action('admin_head-media-upload-popup');
	do_action('admin_head');
	?>
	</head>
	<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?> class="wp-core-ui no-js edit-attachment-frame">
	<script type="text/javascript">document.body.className = document.body.className.replace('no-js', 'js');</script>
	
	<div class="media-frame-router">
		<div class="media-router">
			<a href="#image-editor" class="media-menu-item active"><?php _e('Edit Image',  'ims' )?></a>
			<a href="#attachment-meta" class="media-menu-item"><?php _e('Edit IPTC', 'ims' )?></a>
		</div>
	</div>
	
	<div class="ims-edit-image imgedit-wra ims_image">
             <div class="media-item-info" id="media-head-<?php echo $id?>">
                 	<div class="A1B1" id="thumbnail-head-<?php echo $id?>"></div>
             </div>
			 <div class="media-modal wp-core-ui">
				<div class="attachment-meta">
					<div class="attachment-info">
						<div class="thumbnail thumbnail-image">
							 <?php echo '<img src="' . $ImStore->content_url . "/" . $meta['file'] . '" />';?>
							 <div class="details">
								<div><strong><?php _e('File name:', 'ims')?></strong> <?php echo basename($meta['file']);?></div>
								<div><strong><?php _e('File type:', 'ims')?></strong> <?php echo $image->post_mime_type?></div>
								<div><strong><?php _e('Uploaded on:', 'ims')?></strong> <?php echo date_i18n( get_option( 'date_format' ), strtotime($image->post_date ))?></div>
								<div><strong><?php _e('File size:', 'ims')?></strong> <?php echo size_format(filesize( $ImStore->content_dir . "/" . $meta['file'] ))  ?></div>
								<div><strong><?php _e('Dimensions:', 'ims')?></strong> <?php echo $meta['width'] . ' x ' . $meta['height'] . __( ' pixels', 'ims' ) ?></div>
							</div>
						</div>
					</div>
					<form action="" method="post" class="attachment-fields" >
						<?php 	foreach ( $meta['image_meta'] as $key => $data ) {
						echo '<div class="ims-meta-field">
							<label class="setting" for="' . esc_attr( $key ) . '">' . ucwords( str_replace( array( '_', '-' ), ' ', $key) ) . '</label>
							<input type="text" name="' . esc_attr( $key ) . '" id="' .  esc_attr( $key ) . '" value="' . esc_attr( $data ) . '" />
						</div>';
					}?>
						<input name="imageid" type="hidden"  value="<?php esc_attr_e( $id ) ?>" />
						<input name="imgnonce" type="hidden"  value="<?php echo esc_attr( $imgnonce ) ?>" />
						<input name="save-metadata" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save', 'ims' ) ?>" />
					</form>
				</div>
				<div class="image-editor" id="image-editor-<?php echo $id?>"></div>
			</div>
	</div>
	
	<?php do_action('admin_print_footer_scripts'); ?>
	
	<script type="text/javascript">
		imageEdit.open( <?php echo "$id, '$nonce' "?> ); 
		jQuery( document ).ready( function( $ ){
			
			get_ims_tubmnail = function( ){
				var postid = <?php echo $id?>; 
				var data, history = imageEdit.filterHistory( postid, 0 );
								
				data = {
					imgid			:postid,
					history		:history,
					action		:'edit-mini-image',
					_wpnonce	:'<?php echo $nonce?>'
				};
				setTimeout( function( ){ 
					$.get( '<?php echo IMSTORE_ADMIN_URL . '/ajax.php'?>', data, function( url ){
						if( url ) parent.ims_image_edit_update( <?php echo $id?>, url );
					})
				}, 400 );
			}
			
			var target = 'all'; //what to edit
			
			//set target
			$( "#image-editor-<?php echo $id?>" ).delegate( ".imgedit-group input[type='radio']", 'click', function( ){
				target = $( this ).val( ); 
			});
			
			//restore
			$( "#image-editor-" + <?php echo $id?> ).delegate( ".imgedit-settings input.button-primary", 'click', function( ){ 
				get_ims_tubmnail( );
				setTimeout( "parent.tb_remove( )", 1000 ); 
			});
			
			//cancel / close window
			$( "#image-editor-<?php echo $id?>" ).delegate( ".imgedit-submit input.button", 'click', function( ){ 
				setTimeout( "parent.tb_remove( )", 500 ); 
			});
			
			//save
			$( "#image-editor-<?php echo $id?>" ).delegate( ".imgedit-submit input.imgedit-submit-btn", 'click', function( ){ 
				get_ims_tubmnail( );
				setTimeout( "parent.tb_remove( )", 2000 );
			});
			
			//tabs
			$tabs = $('.media-frame-router a').click(function(){
				$tabs.removeClass('active');
				$(this).addClass('active');
				
				$('.media-modal > div').hide();
				$( '.' + $(this).attr('href').replace('#','') ).show();
			});
			
			//tabs select by hash
			if( hash = window.location.hash ){
				
				selector = hash.replace('#','');
				$tabs.removeClass('active');
				
				setTimeout( function(){
					$('.media-modal > div').hide();
					$( '.' + selector ).fadeIn();
					$( 'a[href=#'+selector+']').addClass('active');
				}, 800 );
			};
			
			
		});
	</script>
	<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
	</body>
</html>