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
	
	wp_enqueue_style( 'colors' );
	wp_enqueue_style( 'imgareaselect' );
	wp_enqueue_style( 'adminstyles',IMSTORE_URL.'/_css/admin.css',false, '0.5.0', 'all' );
	
	$id = isset( $_GET['editimage']  ) ? intval( $_GET['editimage'] ) : 0;
	$nonce = wp_create_nonce( "image_editor-". $id );
	
	@header( 'Content-Type:'.get_option( 'html_type').'; charset='.get_option( 'blog_charset'));
	_wp_admin_html_begin();
	?>
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
	<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?> class="wp-core-ui no-js">
	<script type="text/javascript">document.body.className = document.body.className.replace('no-js', 'js');</script>
	
	<div class="ims-edit-image imgedit-wra ims_image">
		 <table class="slidetoggle describe form-table">
             <thead class="media-item-info" id="media-head-<?php echo $id?>">
                 <tr valign="top">
                 	<td class="A1B1" id="thumbnail-head-<?php echo $id?>"></td>
                 </tr>
             </thead>
             <tbody>
             	<tr><td class="image-editor" id="image-editor-<?php echo $id?>"></td></tr>
             </tbody>
         </table>
	</div>
	
	<?php do_action('admin_print_footer_scripts'); ?>
	
	<script type="text/javascript">
		imageEdit.open( <?php echo "$id, '$nonce' "?> ); 
		jQuery( document ).ready( function( $ ){
			
			get_ims_tubmnail = function( ){
				var postid = <?php echo $id?>; 
				var data,history = imageEdit.filterHistory( postid, 0 );
								
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
				}, 1000 );
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
			
		});
	</script>
	<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
	</body>
</html>