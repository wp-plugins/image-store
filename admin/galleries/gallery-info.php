<?php

	/**
	 * Image Store - Galley Info Metabox
	 *
	 * @file gallery-info.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/galleries/gallery-info.php
	 * @since 3.2.1
	 */
	
	if ( !current_user_can( 'ims_manage_galleries') )
		die( );

	$default = array( '_ims_visits' => 0, '_ims_sortby' => '', '_ims_tracking' => '', '_ims_order' => '', 'expire' => '', 'ims_expire' => '',
	'_dis_store' => false, '_ims_price_list' => 0, '_to_attach' => $this->opts['attchlink'], '_ims_gallery_id' => $this->unique_id( ) );
	
	$instance = array( );
	foreach ( $this->meta as $key => $val ) {
		if ( isset( $val[0] ) )
			$instance[$key] = $val[0];
	}
		
	extract( wp_parse_args( $instance, $default ) );
	
	if ( $this->pagenow == 'post-new.php' && $this->opts['galleryexpire'] ) 
		$time = ( current_time( 'timestamp' ) ) + ( $this->opts['galleryexpire'] * 86400 );
	 else $time = strtotime( $this->gallery->post_expire );
	
	if ( $this->pagenow != 'post-new.php' )
		$this->disabled = ' disabled="disabled"';
	
	if( $time > 0 ){
		$expire = date_i18n( $this->dformat, $time );
		$ims_expire = date_i18n( 'Y-m-d H:i', $time );
	}
	
	$folderfield = '<input type="text" name="_ims_folder_path" id="_ims_folder_path" value="' . esc_attr( $this->galpath ) . '"' . $this->disabled . ' />';
	?>
	
	<table class="ims-table" >
		<tr>
			<td class="short"><label for="_ims_folder_path"><?php _e( 'Folder path', 'ims' ) ?></label></td>
			<td class="long"><?php echo $folderfield ?></td>
			<td><label for="gallery_id"><?php _e( 'Gallery ID', 'ims' ) ?></label></td>
			<td><input type="text" name="_ims_gallery_id" id="gallery_id" value="<?php echo esc_attr( $_ims_gallery_id ) ?>"/></td>
		</tr>
		<?php if ( $this->opts['store'] ) { ?>
			<tr>
				<td><label for="_ims_tracking"><?php _e( 'Tracking Number', 'ims' ) ?></label></td>
				<td class="long"><input type="text" name="_ims_tracking" id="_ims_tracking" value="<?php echo esc_attr( $_ims_tracking ) ?>" /></td>
				<td><label for="_ims_price_list"><?php _e( 'Price List', 'ims' ) ?></label></td>
				<td>
					<select name="_ims_price_list" id="_ims_price_list" >
						<?php foreach ( $this->get_pricelists( ) as $list ) : ?>
							<option value="<?php echo esc_attr( $list->ID ) ?>" <?php selected( $list->ID, $_ims_price_list ) ?> ><?php echo esc_html( $list->post_title ) ?></option>
						<?php endforeach ?>
					</select>
				</td>
			</tr>
		<?php } ?>
		<tr>
			<td><label for="sortby"><?php _e( 'Sort Order', 'ims' ) ?></label></td>
			<td colspan="3">
				<select name="_ims_sortby" id="sortby">
					<option value="0"><?php _e( 'Default', 'ims' ) ?></option>
					<?php foreach ( $this->sortby as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ) ?>" <?php selected( $val, $_ims_sortby ) ?>><?php echo esc_html( $label ) ?></option> 
					<?php endforeach ?>
				</select>
				<select name="_ims_order">
					<option value="0"><?php _e( 'Default', 'ims' ) ?></option> 
					<?php foreach ( $this->order as $val => $label ) : ?>
						<option value="<?php echo esc_attr( $val ) ?>" <?php selected( $val, $_ims_order ) ?>><?php echo $label ?></option> 
					<?php endforeach ?>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="imsexpire" class="date-icon"><?php _e( 'Expiration Date', 'ims' ) ?></label></td>
			<td class="long">
				<input type="text" name="imsexpire" id="imsexpire" value="<?php echo $expire ?>" />
				<input type="hidden" name="_ims_expire" id="_ims_expire" value="<?php echo $ims_expire ?>"/>
			</td>
			<td><label for="_ims_visits"><?php _e( 'Visits', 'ims' ) ?></label></td>
			<td><input type="text" name="_ims_visits" id="_ims_visits" value="<?php echo esc_attr( $_ims_visits ) ?>" /></td>
		</tr>
		<tr>
			<td><label for="_dis_store" ><?php _e( 'Disable Store', 'ims' ) ?></label></td>
			<td><input type="checkbox" name="_dis_store" id="_dis_store" <?php checked( true, $_dis_store ) ?> value="1" /></td>
			<td><label for="_to_attach"><?php _e( 'Link to attachment', 'ims' ) ?></label></td>
			<td><input type="checkbox" name="_to_attach" id="_to_attach" <?php checked( true, $_to_attach ) ?> value="1" /></td>
		</tr>
	<?php do_action( 'ims_info_metabox', $this ) ?>
	</table>
		