<?php

	/**
	 * Image Store - Customer Edit Form
	 *
	 * @file customer-edit.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/customers/customer-edit.php
	 * @since 3.2.1
	 */
	 
	 if ( !current_user_can( 'ims_manage_customers') )
		die( );
			
	?>
    
    <form id="ims-customer-edit" action="" method="post">
    	<div class="postbox">
        	<div class="handlediv"><br /></div>
            <h3 class='hndle'><span><?php echo $user_box_title[$action] ?></span></h3>
            
            <div class="inside">
            	<table class="ims-table">
                
                	<tr>
						<td width="15%"><label for="first_name"><?php _e( 'First Name', 'ims' ) ?></label></td>
						<td><input type="text" name="first_name" id="first_name" class="widefat" value="<?php echo esc_attr( $first_name ) ?>" /></td>
						<td width="15%"><label for="last_name"><?php _e( 'Last Name', 'ims' ) ?></label></td>
						<td><input type="text" name="last_name" id="last_name" class="widefat" value="<?php echo esc_attr( $last_name ) ?>" /></td>
					</tr>
                    
                    <tr class="alternate">
						<td><label for="ims_address"><?php _e( 'Address', 'ims' ) ?></label></td>
						<td><input type="text" name="ims_address" id="ims_address" class="widefat" value="<?php echo esc_attr( $ims_address ) ?>" /></td>
						<td><label for="ims_city"><?php _e( 'City', 'ims' ) ?></label></td>
						<td><input type="text" name="ims_city" id="ims_city" class="widefat" value="<?php echo esc_attr( $ims_city ) ?>" /></td>
					</tr>
                    
                    <tr>
						<td><label for="ims_state"><?php _e( 'State', 'ims' ) ?></label></td>
						<td><input type="text" name="ims_state" id="ims_state" class="widefat" value="<?php echo esc_attr( $ims_state ) ?>" /></td>
						<td><label for="ims_phone"><?php _e( 'Phone', 'ims' ) ?></label></td>
						<td><input type="text" name="ims_phone" id="ims_phone" class="widefat" value="<?php echo esc_attr( $ims_phone ) ?>"/></td>
					</tr>
                    
                    <tr class="alternate">
						<td><label for="ims_zip"><?php _e( 'Zip', 'ims' ) ?></label></td>
						<td><input type="text" name="ims_zip" id="ims_zip" class="widefat" value="<?php echo esc_attr( $ims_zip ) ?>" /></td>
						<td scope="row"><label for="user_email"><?php _e( 'Email', 'ims' ) ?></label></td>
						<td><input type="text" name="user_email" id="user_email" class="widefat" value="<?php echo esc_attr( $user_email ) ?>" /></td>
					</tr>
                    
                    <?php do_action( 'ims_cutomer_data_row', $userid ) ?>
                    
                    <tr class="ims-actions">
						<td colspan="2">
							<?php if ( class_exists( 'MailPress' ) ) { ?>
                            <input type="checkbox" name="_MailPress_sync_wordpress_user" id="_MailPress_sync_wordpress_user" value="<?php 
                            echo $_MailPress_sync_wordpress_user ?>" />
                            <label for="_MailPress_sync_wordpress_user"><?php _e( 'Include user in the eNewsletters', 'ims' ) ?></label>
                            <?php } ?>
                        </td>
                        <td colspan="2" class="textright">
							<input type="submit" name="cancel" value="<?php esc_attr_e( 'Cancel', 'ims' ) ?>" class="button-secondary" />
							<input type="submit" name="update_customer" value="<?php esc_attr_e( 'Save', 'ims' ) ?>" class="button-primary" />
							<input type="hidden" name="userid" value="<?php echo esc_attr( $userid ) ?>" />
							<input type="hidden" name="useraction" value="<?php echo esc_attr( $action ) ?>" />
							<?php wp_nonce_field( 'ims_update_customer' ) ?>
						</td>
                    </tr>
                
                </table><!--.ims-table-->
            </div><!--.inside-->
            
        </div><!--.postbox-->
	</form><!--#ims-customer-edit-->