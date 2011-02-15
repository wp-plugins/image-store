<?php 

//dont cache file
header( 'Expires: 0');
header( 'Pragma: no-cache' );
header( 'Cache-control: private');
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );

//define constants
define( 'DOING_AJAX', true );
define( 'WP_ADMIN', true );

//load wp
require_once '../../../../wp-admin/admin.php';

//make sure that the request came from the same domain	
if ( stripos( $_SERVER['HTTP_REFERER'], get_bloginfo('siteurl')) === false ) 
	wp_die( __('Cheatin&#8217; uh?', ImStore::domain ) );
	
if ( !current_user_can( 'edit_plugins' ) )
	wp_die( __('Cheatin&#8217; uh?', ImStore::domain ) );


check_admin_referer( "ims_edit_image" );

require_once '../../../../wp-admin/includes/media.php';

wp_enqueue_script('image-edit');
wp_enqueue_script('set-post-thumbnail' );
wp_enqueue_style('imgareaselect');

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title>
<?php bloginfo('name') ?>
&rsaquo;
<?php _e('Uploads'); ?>
&#8212;
<?php _e('WordPress'); ?>
</title>
<?php
wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'colors' );
wp_enqueue_style( 'adminstyles', IMSTORE_URL .'_css/admin.css', false, '0.5.0', 'all' );
	
// Check callback name for 'media'
if ( ( is_array( $content_func ) && ! empty( $content_func[1] ) && 0 === strpos( (string) $content_func[1], 'media' ) ) || 0 === strpos( $content_func, 'media' ) ) wp_enqueue_style( 'media' );
wp_enqueue_style( 'ie' );
?>
<script type="text/javascript">
//<![CDATA[
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>', pagenow = 'media-upload-popup', adminpage = 'media-upload-popup';
//]]>
</script>
<?php
do_action('admin_enqueue_scripts', 'media-upload-popup');
do_action('admin_print_styles-media-upload-popup');
do_action('admin_print_styles');
do_action('admin_print_scripts-media-upload-popup');
do_action('admin_print_scripts');
do_action('admin_head-media-upload-popup');
do_action('admin_head');

$id = intval( $_GET['editimage'] );
if ( is_string($content_func) )
	do_action( "admin_head_{$content_func}" );
?>
<style type="text/css">html{ height:98%!important}</style>
</head>
<body id="media-upload">

<div class="ims-edit-image">
 <table class="slidetoggle describe form-table">
  <thead class="media-item-info" id="media-head-<?php echo $id?>">
   <tr valign="top">
    <td class="A1B1" id="thumbnail-head-<?php echo $id?>">
     <p><img src="http://localhost/wp3/wp-admin/images/wpspin_light.gif" class="imgedit-wait-spin" alt="loading"></p>
    </td>
   </tr>
  </thead>
  <tbody><tr><td class="image-editor" id="image-editor-<?php echo $id?>"></td></tr></tbody>
 </table>
</div>

<?php 
do_action('admin_print_footer_scripts'); 
$nonce = wp_create_nonce( "image_editor-" . $id );
?>
<script type="text/javascript">
imageEdit.open( <?php echo $id . ',"' . $nonce . '"'?>); if(typeof wpOnload=='function')wpOnload();
jQuery("input[value='Cancel']").live('click', function(){ setTimeout( "parent.tb_remove()", 500 ); });
jQuery("input[value='Restore image']").live('click', function( ){ 
	setTimeout( "parent.tb_remove()", 2000 ); 
	setTimeout( "parent.location.reload()", 2000 ); 
}); jQuery("input[value='Save']").live('click', function( ){ 
	if( jQuery(this).attr('disabled') != 'disabled' ){
		setTimeout( "parent.tb_remove()", 2150 ); 
		setTimeout( "parent.location.reload()", 2150 ); 
	}
});
</script>
</body>
</html>
<?php
