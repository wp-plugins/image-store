<?php 

/**
 * Settings page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since version 0.5.0
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_change_settings' ) ) 
	die( );


//save gallery settings 
if ( !empty( $_POST['updategalleries'] ) ) { 
	check_admin_referer( 'ims_gallery_settings' );
	
	foreach( array( '_wpnonce', '_wp_http_referer', 'updateoption' ) as $key )
		unset( $_POST[$key] );

	foreach( array( 'deletefiles', 'securegalleries', 'mediarss', 'disablestore', 'stylesheet' ) as $box )
		if( empty( $_POST[$box] ) ) $_POST[$box] = '';
	
	//make sure gallerypath has a trailing slash
	if( !preg_match( '/^\//', $_POST['gallerypath'] ) ) 
		$_POST['gallerypath'] = '/' . $_POST['gallerypath'] ;
		
	update_option( 'ims_front_options', wp_parse_args( $_POST, $this->opts ) );
	wp_redirect( $pagenowurl . '&ms=4' );	
}


//save image settings 
if ( !empty( $_POST['updateimages'] ) ) { 
	check_admin_referer( 'ims_image_settings' );
	
	$sizes = get_option( 'ims_sizes' );
	$resize = get_option( 'ims_dis_images' );
	$downlaods = get_option( 'ims_download_sizes' );
	
	foreach( (array)$downlaods as $values ){
		delete_option( $values['name'] . "_crop" );
		delete_option( $values['name'] . "_size_h" );
		delete_option( $values['name'] . "_size_w" );
	}
		
	foreach( array( '_wpnonce', '_wp_http_referer', 'updateimages' ) as $key )
		unset( $_POST[$key] );
	
	$x=0;
	do{
		if( isset( $_POST['imgid_' . $x] ) )
			unset( $_POST['imagesize_' . $x]);
		
		if( $_POST['imagesize_' . $x]['name'] ){
			$_POST['imagesize_' . $x]['crop'] = 0;
			$downlaodsizes[] = $_POST['imagesize_' . $x];
		}

		$newsizes[] = array( 'name' => $_POST['imagesize_' . $x]['w'] . 'x' . $_POST['imagesize_' . $x]['h'] );
		unset( $_POST['imagesize_' . $x] );
		unset( $_POST['imgid_' . $x] );
		
		$x++;
	}while( !empty( $_POST['imagesize_' . $x] ) );
	
	foreach( (array)$downlaodsizes as $values ){
		update_option( $values['name'] . "_crop", 0 );
		update_option( $values['name'] . "_size_h", $values['w'] );
		update_option( $values['name'] . "_size_w", $values['w'] );
	}
	
	if( is_array( $newsizes) && !empty( $newsizes ) )
		$sizes = array_merge( $sizes, $newsizes );
	
	$preview['preview'] = $_POST['preview'];
	$resize = array_merge( $resize, $preview );
	update_option( 'ims_dis_images', $resize );
	
	unset( $_POST['preview'] );
	
	update_option( 'ims_sizes', $sizes );
	update_option( 'ims_download_sizes', $downlaodsizes );
	update_option( 'ims_front_options', wp_parse_args( $_POST, $this->opts ) );
	
	wp_redirect( $pagenowurl . '&ms=4#image-settings' );	

}

//save payment settings 
if ( !empty( $_POST['updatecheckout'] ) ) { 
	check_admin_referer( 'ims_checkout_settings' );
	
	foreach( array( '_wpnonce', '_wp_http_referer', 'updatecheckout' ) as $key )
		unset( $_POST[$key] );
		
	foreach( array( 'registercheckout', 'sameasbilling' ) as $box )
		if( empty( $_POST[$box] ) ) $_POST[$box] = '';

	update_option( 'ims_front_options', wp_parse_args( $_POST, $this->opts ) );
	wp_redirect( $pagenowurl . '&ms=4#checkout-settings' );	
}


//save checkout settings 
if ( !empty( $_POST['updatepayment'] ) ) { 
	check_admin_referer( 'ims_payment_settings' );
	
	foreach( array( '_wpnonce', '_wp_http_referer', 'updatecheckout' ) as $key )
		unset( $_POST[$key] );
		
	foreach( array( 'autocharge' ) as $box )
		if( empty( $_POST[$box] ) ) $_POST[$box] = '';

	update_option( 'ims_front_options', wp_parse_args( $_POST, $this->opts ) );
	wp_redirect( $pagenowurl . '&ms=4#payment-settings' );	
}


//reset options
if( !empty( $_POST['resetsettings'] ) ){
	check_admin_referer( 'ims_reset_settings' );
	
	include_once ( IMSTORE_ABSPATH . '/admin/install.php' );
	ImStoreInstaller::imstore_default_options( );
	
	wp_redirect( $pagenowurl . '&ms=3#reset_settings' );	
}

//uninstall Image Store
if( !empty( $_POST['uninstall_ims'] ) ){
	check_admin_referer( 'ims_reset_settings' );
	
	include_once ( IMSTORE_ABSPATH . '/admin/install.php' );
	ImStoreInstaller::imstore_uninstall( );
}

//update/add user capabilities
if( !empty( $_POST['updateuser'] ) ) {
	check_admin_referer( 'ims_caps_settings' );
	foreach( $this->useropts['caplist'] as $cap ){
		if( !empty( $_POST[$cap] ) ) $newcaps[$cap] = 1;
	}
	update_usermeta( $_POST['ims_user'], 'ims_user_caps', $newcaps );
	wp_redirect( $pagenowurl . '&ms=2&userid=' . $_GET['userid'].'#caps_settings' );	
}

$this->opts += get_option( 'ims_dis_images' );

$message[1] = 		__( "Cache cleared.", ImStore::domain );
$message[2] = 		__( 'The user was updated.', ImStore::domain );
$message[3] = 		__( 'All settings were reseted.', ImStore::domain );
$message[4] = 		__( 'The settings were updated.', ImStore::domain );


?>
<div class="wrap imstore">
	
	<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="paypal-donate">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="D64HFDXBBMXEG">
		<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1" alt="donate">
	</form>

	
	<?php screen_icon( 'options-general' )?>
	<h2><?php _e( 'Settings', ImStore::domain )?></h2>
 		 		
	<div id="poststuff" class="metabox-holder">
	<?php if( !empty($_GET['ms']) ){ ?>
	<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
 		 		
		<ul class="ims-tabs add-menu-item-tabs">
			<li class="tabs"><a href="#gallery-settings"><?php _e( 'Gallery Settings', ImStore::domain )?></a></li>
			<li class="tabs"><a href="#image-settings"><?php _e( 'Image Settings', ImStore::domain )?></a></li>
			<?php if( !$this->opts['disablestore'] ){ ?>
			<li class="tabs"><a href="#payment-settings"><?php _e( 'Payment options', ImStore::domain )?></a></li>
			<li class="tabs"><a href="#checkout-settings"><?php _e( 'Checkout', ImStore::domain )?></a></li>
			<?php } ?>
			<?php if( current_user_can( 'ims_change_permissions' ) ){ ?>
			<li class="tabs"><a href="#caps_settings"><?php _e( 'User permissions', ImStore::domain )?></a></li>
			<?php } ?>
			<li class="tabs"><a href="#reset_settings"><?php _e( 'Reset', ImStore::domain )?></a></li>
		</ul>
 		 		
 		<!-- Gallery Settings -->
		
 		<div id="gallery-settings" class="ims-box">
		<form method="post" action="<?php echo $pagenowurl ?>" >
		<?php wp_nonce_field( 'ims_gallery_settings' )?>
		<table class="ims-table"> 
			<tbody>
			<tr> 
				<td scope="row" width="22%"><label for="galleriespath"><?php _e( 'Gallery folder path', ImStore::domain )?></label></td>
				<td>
				<input type="text" name="galleriespath" id="galleriespath" class="inputlg" value="<?php $this->_v( 'galleriespath' )?>" ><br />
				<small><?php _e( 'Default folder path for all the galleries images', ImStore::domain )?></small>
				</td>
			</tr>
			<tr class="alternate">
				<td scope="row"><label for="deletefiles"> <?php _e( 'Delete image files', ImStore::domain )?> </label></td>
				<td><input type="checkbox" name="deletefiles" id="deletefiles" value="1" <?php checked( '1', $this->_vr( 'deletefiles' ) )?> />
				<small> <?php _e( 'Delete files from server, when deleting a gallery/images', ImStore::domain )?> </small></td>
			</tr>
			<tr>
				<td scope="row"><label for="securegalleries"><?php _e( 'Secure Galleries', ImStore::domain )?></label></td>
				<td><input type="checkbox" name="securegalleries" id="securegalleries" value="1" <?php checked( '1', $this->_vr( 'securegalleries' ) )?>/>
					<small><?php _e( 'Secure all new galleries with a password by default.', ImStore::domain )?></small></td>
			</tr>
			<!--<tr class="alternate">
				<td scope="row" width="22%"><label for="mediarss"><?php _e( 'Media RSS feed', ImStore::domain )?></label></td>
				<td><input type="checkbox" name="mediarss" id="mediarss" value="1" <?php checked( '1', $this->_vr( 'mediarss' ) )?>/>
				<small><?php _e( 'Add RSS feed the blog header for unsercure galleries. Useful for CoolIris/PicLens', ImStore::domain )?></small>
				</td>
			</tr>-->
			<tr class="alternate">
				<td scope="row" width="22%"><label for="stylesheet"><?php _e( 'Use CSS', ImStore::domain )?></label></td>
				<td><input type="checkbox" name="stylesheet" id="stylesheet" value="1" <?php checked( '1', $this->_vr( 'stylesheet' ) )?>/>
				<small><?php _e( 'Use the Image Store stylesheet?', ImStore::domain )?></small>
				</td>
			</tr>
			<tr>
				<td scope="row"><label for="disablestore"><?php _e( 'Disable store features', ImStore::domain )?></label></td>
				<td><input type="checkbox" name="disablestore" id="disablestore" value="1" <?php checked( '1', $this->_vr( 'disablestore' ) )?> />
					<small><?php _e( 'Use as a gallery manager only, not a store.', ImStore::domain )?></small></td>
			</tr>
			<tr class="alternate">
				<td scope="row" width="22%"><label for="stylesheet"><?php _e( 'Gallery Columns', ImStore::domain )?></label></td>
				<td><input type="text" name="displaycolmns" id="displaycolmns" class="inputsm" value="<?php $this->_v( 'displaycolmns' )?>" />
				<small><?php _e( 'Display gallery in how many columns', ImStore::domain )?></small>
				</td>
			</tr>
			<!--<tr class="alternate">
				<td scope="row"><label for="downloadmax"><?php _e( 'Downloads allowed', ImStore::domain )?></label></td>
				<td><input type="text" name="downloadmax" id="downloadmax" class="inputsm" value="<?php $this->_v( 'downloadmax' )?>" />
					<small><?php _e( 'Default number of downloads.', ImStore::domain )?></small></td>
			</tr>-->
			<tr>
				<td scope="row"><label for="galleryexpire"><?php _e( 'Galleries expire after ', ImStore::domain )?></label></td>
				<td><input type="text" name="galleryexpire" id="galleryexpire" class="inputxm" value="<?php $this->_v( 'galleryexpire' )?>"/>
					( <?php _e( 'days' )?> )</td>
			</tr>
			<tr class="alternate">
				<td valign="top"><?php _e( 'Sort images', ImStore::domain )?></td>
				<td><label><input name="imgsortorder" type="radio" value="menu_order" <?php checked('menu_order', $this->_vr( 'imgsortorder'))?> />
					<?php _e( 'Custom order', ImStore::domain )?></label><br />
					<label><input name="imgsortorder" type="radio" value="post_excerpt" <?php checked('post_excerpt', $this->_vr( 'imgsortorder'))?> />
					<?php _e( 'Caption', ImStore::domain )?></label><br />
					<label><input name="imgsortorder" type="radio" value="post_title" <?php checked('post_title', $this->_vr( 'imgsortorder'))?> />
					<?php _e( 'Image title', ImStore::domain )?></label><br />
					<label><input name="imgsortorder" type="radio" value="post_date" <?php checked('post_date',$this->_vr( 'imgsortorder'))?>/>
					<?php _e( 'Image date', ImStore::domain )?></label></td>
			</tr>
			<tr>
				<td><?php _e( 'Sort direction', ImStore::domain )?>:</td>
				<td><label><input name="imgsortdirect" type="radio" value="ASC" <?php checked('ASC', $this->_vr( 'imgsortdirect') )?>/>
					<?php _e( 'Ascending', ImStore::domain )?></label>
					<label><input name="imgsortdirect" type="radio" value="DESC" <?php checked('DESC', $this->_vr( 'imgsortdirect') )?>/>
					<?php _e( 'Descending', ImStore::domain )?></label></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td class="submit">
					<input type="submit" name="updategalleries" class="button-primary" value="<?php _e( 'Save Changes', ImStore::domain )?>"/>
				</td>
			</tr>
			</tbody> 
		</table>
	</form>
	</div>

	<!-- Image Settings -->

	<div id="image-settings" class="ims-box">
	<form method="post" action="<?php echo $pagenowurl . '#image-settings'?>" >
	<?php wp_nonce_field( 'ims_image_settings' )?>
	<table class="ims-table"> 
		<tbody>
			<tr class="t alternate">
				<td colspan="2" scope="row"><?php _e( 'Image preview size (pixels)')?></td>
				<td colspan="4"><label><?php _e( 'Max Width', ImStore::domain ) ?>
					<input type="text" name="preview[w]" class="inputsm" value="<?php $this->_v( 'preview', 'w' )?>" /></label>
					<label><?php _e( 'Max Height', ImStore::domain )?>
					<input type="text" name="preview[h]" class="inputsm" value="<?php $this->_v( 'preview', 'h' )?>" /></label>
					<label><?php _e( 'Quality', ImStore::domain )?>
					<input type="text" name="preview[q]" class="inputsm" value="<?php $this->_v( 'preview', 'q' )?>" />(%)</label></td>
			</tr>
			<tr><td scope="row" colspan="6">&nbsp;</td></tr>
			<tr>
				<td colspan="2" scope="row">&nbsp;</td>
				<td colspan="4">
				<label><input type="radio" name="watermark" value="0" <?php checked( '0', $this->_vr( 'watermark' )) ?> />
				<?php _e( 'No watermark', ImStore::domain )?></label> &nbsp;
				<label><input type="radio" name="watermark" value="1" <?php checked( '1', $this->_vr( 'watermark'))?> /> 
				<?php _e( 'Use text as watermark', ImStore::domain )?></label> &nbsp;
				<label><input type="radio" name="watermark" value="2" <?php checked( '2', $this->_vr( 'watermark'))?> />
				<?php _e( 'Use image as watermark', ImStore::domain )?></label></td>
			</tr>
			<tr class="t alternate">
				<td colspan="2" scope="row"><label for="watermarktext"><?php _e( 'Watermark text', ImStore::domain )?></label></td>
				<td colspan="4">
				<input type="text" name="watermarktext" id="watermarktext" class="input" value="<?php $this->_v( 'watermarktext')?>"/>
				<label><?php _e( 'Color', ImStore::domain )?>
				<input type="text" name="textcolor" class="inputxm" value="<?php $this->_v( 'textcolor' )?>" /> <small>Hex </small></label>
				<label><?php _e( 'Font size', ImStore::domain )?>
				<input type="text" name="fontsize" class="inputxm" value="<?php $this->_v( 'fontsize' )?>" /></label>
				<label><?php _e( 'Transperency', ImStore::domain )?>
				<input type="text" name="transperency" class="inputxm" value="<?php $this->_v( 'transperency' )?>" /> (0-127)</label></td>
			</tr>
			<tr>
				<td colspan="2" scope="row">
				<label for="watermarkurl"><a id="addwatermarkurl"><?php _e( 'Watermark URL', ImStore::domain )?></a></label></td>
				<td colspan="4">
				<input type="text" name="watermarkurl" id="watermarkurl" class="inputlg" value="<?php $this->_v( 'watermarkurl')?>"/></td>
			</tr>
			<tr><td scope="row" colspan="6">&nbsp;</td></tr>
			<tr class="alternate"><td scope="row" colspan="6"><?php _e( 'Downlaodable Image Sizes', ImStore::domain )?></td></tr>
			<tr class="t">
				<td scope="row"><?php _e( 'Delete', ImStore::domain )?></td>
				<td scope="row"><?php _e( 'Image Size', ImStore::domain )?></td>
				<td scope="row"><?php _e( 'Width', ImStore::domain )?></td>
				<td scope="row"><?php _e( 'Height', ImStore::domain )?></td>
				<td scope="row"><?php _e( 'Quality', ImStore::domain )?></td>
				<td scope="row">
				<input type="button" id="addimagesize" value="<?php _e( 'Add image size', ImStore::domain )?>" class="button"></td>
			</tr>
			<?php if( $sizes = get_option('ims_download_sizes') ): for( $x=0; $x<count($sizes); $x++ ): ?>
			<tr class="t image-size">
			<td scope="row"><input type="checkbox" name="imgid_<?php echo $x?>" class="inputmd" value="1" /></td>
				<td><input type="text" name="imagesize_<?php echo $x?>[name]" class="inputmd" value="<?php echo $sizes[$x]['name'] ?>" /></td>
				<td><label><input type="text" name="imagesize_<?php echo $x?>[w]" class="inputsm" value="<?php echo $sizes[$x]['w'] ?>" /></label></td>
				<td><label><input type="text" name="imagesize_<?php echo $x?>[h]" class="inputsm" value="<?php echo $sizes[$x]['h'] ?>" /></label></td>
				<td><label><input type="text" name="imagesize_<?php echo $x?>[q]" class="inputsm" value="<?php echo $sizes[$x]['q'] ?>" />(%)</label></td>
				<td>&nbsp;</td>
			</tr>
			<?php endfor; endif;?>
			<tr class="t ims-image-sizes">
				<td colspan="2" scope="row">&nbsp;</td>
				<td colspan="4" class="submit">
					<input type="submit" name="updateimages" class="button-primary" value="<?php _e( 'Save Changes', ImStore::domain )?>"/>
				</td>
			</tr>
			</tbody> 
		</table>
		</form>
	</div>

	<?php if( !$this->opts['disablestore'] ){ ?>
	
	<!-- Payment Settings -->

	<div id="payment-settings" class="ims-box">
	<form method="post" action="<?php echo $pagenowurl . '#payment-settings'?>" >
	<?php wp_nonce_field( 'ims_payment_settings' )?>
	<table class="ims-table"> 
		<tbody>
		<tr>
			<td scope="row" width="20%"> <label for="symbol"><?php _e( 'Currency Symbol', ImStore::domain )?></label></td>
			<td colspan="3"><input type="text" name="symbol" id="symbol" class="inputxm" value="<?php $this->_v( 'symbol' )?>" /></td>
		</tr>
		<tr class="t alternate">
			<td scope="row"> <label for="symbol"><?php _e( 'Currency Symbol Location', ImStore::domain )?></label></td>
			<td colspan="3">
				<label><input type="radio" value="1" name="clocal"<?php checked( '1', $this->_vr( 'clocal' ) )?> />
				<?php _e( '&#036;100', ImStore::domain )?></label>
				<label><input type="radio" value="2" name="clocal"<?php checked( '2', $this->_vr( 'clocal' ) )?> />
				<?php _e( '&#036; 100', ImStore::domain )?></label>
				<label><input type="radio" value="3" name="clocal"<?php checked( '3', $this->_vr( 'clocal' ) )?> 		/>
				<?php _e( '100&#036;', ImStore::domain )?></label>
				<label><input type="radio" value="4" name="clocal"<?php checked( '4', $this->_vr( 'clocal' ) )?> 		/>
				<?php _e( '100 &#036;', ImStore::domain )?></label>
			</td>
		</tr>
		<tr>
			<td><label for="currency"><?php _e( ' Default Currency:', ImStore::domain )?></label></td>
			<td colspan="4"><select name="currency" id="currency" 		>
				<option value="">Please Choose Default Currency</option>
				<option value="AUD"<?php selected('AUD', $this->_vr( 'currency') )?>><?php _e( 'Australian Dollar', ImStore::domain )?></option>
				<option value="CAD"<?php selected('CAD', $this->_vr( 'currency') )?>><?php _e( 'Canadian Dollar', ImStore::domain )?></option>
				<option value="CZK"<?php selected('CZK', $this->_vr( 'currency') )?>><?php _e( 'Czech Koruna', ImStore::domain )?></option>
				<option value="DKK"<?php selected('DKK', $this->_vr( 'currency') )?>><?php _e( 'Danish Krone', ImStore::domain )?></option>
				<option value="EUR"<?php selected('EUR', $this->_vr( 'currency') )?>><?php _e( 'Euro', ImStore::domain )?></option>
				<option value="HKD"<?php selected('HKD', $this->_vr( 'currency') )?>><?php _e( 'Hong Kong Dollar', ImStore::domain )?></option>
				<option value="HUF"<?php selected('HUF', $this->_vr( 'currency') )?>><?php _e( 'Hungarian Forint', ImStore::domain )?></option>
				<option value="ILS"<?php selected('ILS', $this->_vr( 'currency') )?>><?php _e( 'Israeli New Sheqel', ImStore::domain )?></option>
				<option value="JPY"<?php selected('JPY', $this->_vr( 'currency') )?>><?php _e( 'Japanese Yen', ImStore::domain )?></option>
				<option value="MXN"<?php selected('MXN', $this->_vr( 'currency') )?>><?php _e( 'Mexican Peso', ImStore::domain )?></option>
				<option value="NOK"<?php selected('NOK', $this->_vr( 'currency') )?>><?php _e( 'Norwegian Krone', ImStore::domain )?></option>
				<option value="NZD"<?php selected('NZD', $this->_vr( 'currency') )?>><?php _e( 'New Zealand Dollar', ImStore::domain )?></option>
				<option value="PLN"<?php selected('PLN', $this->_vr( 'currency') )?>><?php _e( 'Polish Zloty', ImStore::domain )?></option>
				<option value="GBP"<?php selected('GBP', $this->_vr( 'currency') )?>><?php _e( 'Pound Sterling', ImStore::domain )?></option>
				<option value="SGD"<?php selected('SGD', $this->_vr( 'currency') )?>><?php _e( 'Singapore Dollar', ImStore::domain )?></option>
				<option value="SEK"<?php selected('SEK', $this->_vr( 'currency') )?>><?php _e( 'Swedish Krona', ImStore::domain )?></option>
				<option value="CHF"<?php selected('CHF', $this->_vr( 'currency') )?>><?php _e( 'Swiss Franc', ImStore::domain )?></option>
				<option value="USD"<?php selected('USD', $this->_vr( 'currency') )?>><?php _e( 'U.S. Dollar', ImStore::domain )?></option>
			</select></td>
		</tr>
		<tr class="alternate">
			<td scope="row"><label for="gateway">Gateway</label></td>
			<td scope="row" colspan="3">
			<select name="gateway" id="gateway">
				<!--<option value="manual"<?php selected('manual', $this->_vr( 'gateway') )?>><?php _e( 'Manual', ImStore::domain )?></option>
				<option value="merchant"<?php selected('merchant', $this->_vr( 'gateway') )?>><?php _e( 'Merchant Account', ImStore::domain )?></option>-->
				<option value="paypalsand"<?php selected('paypalsand', $this->_vr( 'gateway') )?>><?php _e( 'Paypal Cart Sanbox', ImStore::domain)?> </option>
				<option value="paypalprod"<?php selected('paypalprod', $this->_vr( 'gateway') )?>><?php _e( 'Paypal Cart Production', ImStore::domain )?></option>
				<!--<option value="googlesand"<?php selected('googlesand', $this->_vr( 'gateway') )?>><?php _e( 'Google Checkout Sandbox', ImStore::domain )?></option>
				<option value="googleprod"<?php selected('googleprod', $this->_vr( 'gateway') )?>><?php _e( 'Google Checkout Production', ImStore::domain )?></option>-->
			</select></td>
		</tr>
		<!--<tr>
			<td scope="row"> <label for="paymentname"><?php _e( 'Display Name', ImStore::domain )?></label></td>
			<td colspan="3"><input type="text" name="paymentname" id="paymentname" class="inputlg" value="<?php $this->_v( 'paymentname' )?>" /></td>
		</tr>
-->		<tr>
			<td scope="row"> <label for="paypalname"><?php _e( 'PayPal API username', ImStore::domain )?></label></td>
			<td><input type="text" name="paypalname" value="<?php $this->_v( 'paypalname' )?>" id="paypalname" class="inputxl" /></td>
			<td width="20%"><label for="paypalpass"><?php _e( 'PayPal API password', ImStore::domain )?></label> </td>
			<td><input type="text" name="paypalpass" class="inputxl" 		value="<?php $this->_v( 'paypalpass' )?>" id="livepass" /></td>
		</tr>
		<tr class="alternate">
			<td><label for="paypalsig"><?php _e( 'PayPal API signature', ImStore::domain )?></label> &nbsp;</td>
			<td colspan="4"><input type="text" name="paypalsig" id="livesig" class="inputxl" value="<?php $this->_v( 'paypalsig' )?>"/></td>
		</tr>
		<tr><td scope="row" colspan="4">&nbsp;</td></tr>
		<tr>
			<td scope="row"><label for="listener"><?php _e( 'IPN Listener page', ImStore::domain )?></label></td>
			<td colspan="4"><?php echo get_permalink( $this->_vr('mangerpage') )?>
			<input type="text" name="listener" id="listener" class="inputlg" value="<?php $this->_v( 'listener' )?>" /></td>
		</tr>
		<tr class="alternate">
			<td scope="row"><label for="cancelpage"><?php _e( 'Cancel page', ImStore::domain )?></label></td>
			<td colspan="4"><?php echo get_permalink( $this->_vr('mangerpage') )?>
			<input type="text" name="cancelpage" id="cancelpage" class="inputlg" value="<?php $this->_v( 'cancelpage' )?>" /></td>
		</tr>
		<tr>
			<td scope="row"><label for="returnpage"><?php _e( 'Return page', ImStore::domain )?></label></td>
			<td colspan="4"><?php echo get_permalink( $this->_vr('mangerpage') )?>
				<input type="text" name="returnpage" id="returnpage" class="inputlg" value="<?php $this->_v( 'returnpage' )?>" /></td>
		</tr>
		<tr><td scope="row" colspan="4">&nbsp;</td></tr>
		<!--<tr class="alternate">
			<td scope="row"><?php _e( 'Merchant ID', ImStore::domain )?></td>
			<td><input type="text" name="googleid" class="inputxl" value="<?php $this->_v( 'googleid' )?>" /></td>
			<td><?php _e( "Merchant Key", ImStore::domain )?></td>
			<td><input type="text" name="googlekey" class="inputxl" value="<?php $this->_v( 'googlekey' )?>" /></td>
		</tr>
		<tr>
			<td scope="row"><?php _e( 'Turn on auto charging', ImStore::domain )?> </td>
			<td colspan="3"><input type="checkbox" name="autocharge" value="1" <?php checked( '1', $this->_vr( 'autocharge' ) )?> />
				<small><?php _e( 'Check to enable', ImStore::domain )?></small>
			</td>
		</tr>-->
		<tr class="alternate">
			<td scope="row">&nbsp;</td>
			<td class="submit">
				<input type="submit" name="updatepayment" class="button-primary" value="<?php _e( 'Save Changes', ImStore::domain )?>"/>
			</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>
			
		</tr>		
		</tbody>
	</table>
	</form>
	</div>

	<!-- Checkout Settings -->

	<div id="checkout-settings" class="ims-box">
	<form method="post" action="<?php echo $pagenowurl . '#checkout-settings'?>" >
	<?php wp_nonce_field( 'ims_checkout_settings' )?>
	<table class="ims-table">
		<tbody>
		<tr>
			<td scope="row" width="24%"><label for="taxamount"><?php _e( 'Tax', ImStore::domain )?></label></td> 
			<td colspan="3"><input type="text" name="taxamount" id="taxamount" class="inputsm" value="<?php $this->_v( 'taxamount' )?>" />
				<select name="taxtype" id="taxtype">
				<option value="percent"><?php _e( 'Percent', ImStore::domain )?></option>
				<option value="amount"><?php _e( 'Amount', ImStore::domain )?></option>
				</select> <small> Set tax to 0 to remove tax calculation.</small></td> 
		</tr>
		<!--<tr class="alternate">
			<td scope="row">&nbsp;</td> 
			<td colspan="3"><label><input type="checkbox" name="registercheckout" value="1" <?php checked( '1', $this->_vr( 'registercheckout' ) )?>/> 
			<?php _e( 'Users must register before checking out', ImStore::domain )?></label></td> 
		</tr> 
		<tr>
			<td scope="row"></td> 
			<td colspan="3"><label><input type="checkbox" name="sameasbilling" value="1" <?php checked( '1', $this->_vr( 'sameasbilling' ) )?>/> 
			<?php _e( 'Enable Shipping Same as Billing Option', ImStore::domain )?></label></td> 
		</tr>-->
		<tr class="alternate">
			<td scope="row"><label for="notifyemail"><?php _e( 'Order Notification email(s)', ImStore::domain )?></label></td>
			<td colspan="3"><input type="text" name="notifyemail" id="notifyemail" class="inputlg" value="<?php $this->_v( 'notifyemail' )?>" /></td>
		</tr>
		<tr>
			<td scope="row"><label for="notifysubj"><?php _e( 'Order Notification subject', ImStore::domain )?></label></td>
			<td colspan="3"><input type="text" name="notifysubj" id="notifysubj" class="inputlg" value="<?php $this->_v( 'notifysubj' )?>" /></td>
		</tr>
		<tr class="alternate">
			<td valign="top"><label for="notifymssg"><?php _e( 'Order Notification message', ImStore::domain )?></label></td>
			<td colspan="3"><textarea name="notifymssg" id="notifymssg" rows="5" class="inputlg" ><?php $this->_v( 'notifymssg' )?></textarea><br />
			<small><?php _e( 'Tags: ', ImStore::domain ); echo str_replace( '/', '', implode( ', ', $this->opts['tags'] ) )?> </small>
		</td>
		</tr>
		<tr><td scope="row" colspan="2">&nbsp;</td></tr>
		<tr>
			<td valign="top"><label for="thankyoureceipt"><?php _e( 'Purchase Receipt', ImStore::domain )?></label></td>
			<td colspan="3">
				<textarea name="thankyoureceipt" id="thankyoureceipt" rows="4" class="inputlg" ><?php $this->_v( 'thankyoureceipt' )?></textarea><br />
				<small><?php _e( 'Thank you message and receipt information', ImStore::domain )?></small>
			</td>
		</tr>
		<tr class="alternate">
			<td valign="top"><label for="termsconds"><?php _e( 'Terms and Conditions', ImStore::domain )?></label></td>
			<td colspan="3"><textarea name="termsconds" id="termsconds" rows="6" class="inputlg" ><?php $this->_v( 'termsconds' )?></textarea></td>
		</tr>
		<tr>
			<td scope="row">&nbsp;</td>
			<td class="submit">
				<input type="submit" name="updatecheckout" class="button-primary" value="<?php _e( 'Save Changes', ImStore::domain )?>"/>
			</td>
		</tr>
		</tbody>
	</table>
	</form>
	</div>
	
	<?php } ?>
	<!-- Set User Permissions -->
	
	<div id="caps_settings" class="ims-box">
	<form method="post" action="<?php echo $pagenowurl . '&userid='. $_GET[ 'userid' ] . '#caps_settings'?>" >
	<?php wp_nonce_field( 'ims_caps_settings' )?>
	<h4><label><?php ims_dowpdown_users( $_GET[ 'userid' ] )?></label></h4>
	<div class="permissions">
		<?php if( $_GET[ 'userid' ] )	:?>
		<?php $ims_user_caps = get_usermeta( $_GET[ 'userid' ], 'ims_user_caps' )?>
		<?php foreach( $this->useropts['caplist'] as $imscap ):?>
			<label><input name="<?php echo $imscap?>" type="checkbox" value="1" <?php checked( '1', $ims_user_caps[$imscap] )?> />
			<?php echo ucwords( preg_replace('/(^ims_)|(_)/', ' ', $imscap ))?></label>
		<?php endforeach?>
		<?php endif?><div class="clear"></div>
	</div>
		<input type="submit" name="updateuser" class="button-primary" value="<?php _e( 'Save User', ImStore::domain )?>"/>
	</form>
	</div>


	<!-- Remove Plugin Data -->

	
	<div id="reset_settings" class="ims-box">
	<form method="post" action="<?php echo $pagenowurl . '#reset_settings'?>" >
	<?php wp_nonce_field( 'ims_reset_settings' )?>
	<table class="ims-table">
		<tbody>
		<tr><td scope="row">&nbsp;</td></tr>
		<tr>
			<td scope="row">
			<!--<input type="submit" name="clearcache" value="<?php _e( 'Clear image cache', ImStore::domain )?>" class="button-primary"/>-->
			<input type="submit" name="resetsettings" value="<?php _e( 'Reset All Settings to defaults', ImStore::domain)?>" class="button"/>
				</td>
		</tr>
		<tr><td scope="row">&nbsp;</td></tr>
		<tr>
			<td scope="row" class="error">
			<p><strong><?php _e( 'UNINSTALL IMAGE STORE WARNING', ImStore::domain )?>:</strong> </p>
			<?php _e( 'Once uninstalled, this cannot be undone. <strong> You should backup your database </strong> and image files before doing this, Just in case!!.', ImStore::domain )?>
			<?php _e("If you are not sure what are your doing, please don't do anything", ImStore::domain )?> !!!!
			<p><input name="uninstall_ims" type="submit" value="<?php _e( 'Uninstall Image Store', ImStore::domain)?>" class="button" id="uninstallImStore" /></p>
			</td>
		</tr>
		</tbody>
	</table>
	</form>
	</div>

	</div>
</div>



<?php



/**
 * Create a dropdown menu of the ImStore users
 *
 * @return void
 * @since 0.5.0 
 */
function ims_dowpdown_users( $selected = '' ) {
	global $wpdb;

	$q = "SELECT ID, user_login, meta_key, meta_value 
			FROM $wpdb->users 
			JOIN $wpdb->usermeta 
			ON $wpdb->users.ID = $wpdb->usermeta.user_id 
			WHERE meta_key = '{$wpdb->prefix}capabilities'
			AND meta_value NOT LIKE '%customer%' ";
			
	$output .= '<select name="ims_user" id="ims_user" >';
	$output .= '<option value="">'.__( 'Select User', ImStore::domain ).' &#8212; </option>';
	foreach( $wpdb->get_results( $q, 'ARRAY_A' ) as $user ):
		$roles = @unserialize( $user['meta_value'] ) ;
		if( !$roles['administrator'] ): $userCount ++; 
			$output .= '<option value="'. $user['ID'] .'" ' . selected( $user['ID'], $selected, false ).' >' . $user['user_login'] . '</option>';
		endif;
	endforeach ;
	$output .= "</select>";
	
	if( $userCount > 0 ) echo $output; 
	else echo __( 'No users to manage', ImStore::domain );
}

?>
