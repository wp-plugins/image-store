<?php

	/**
	 * Image Store - Admin Settings
	 *
	 * @file settings.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/settings/settings.php
	 * @since 0.5.0
	 */
	 
	 if( !current_user_can( 'ims_change_settings' ) ) 
		die( );
	
	//tab navigation
	$settings_tabs = apply_filters( 'ims_settings_tabs', array(
		'general' 	=> __( 'General', 'ims'),
		'taxonomies' 	=> __( 'Groups', 'ims'),
		'gallery' 	=> __( 'Gallery', 'ims'),
		'image' 	=> __( 'Image', 'ims'),
		'slideshow' => __( 'Slideshow', 'ims'),
		'payment' 	=> __( 'Payment', 'ims'),
		'checkout' 	=> __( 'Checkout', 'ims'),
		'permissions' => __( 'Permissions', 'ims'),
		'reset' 	=> __( 'Reset', 'ims'),
	));
	
	//unset permission tab if user doesn't have access
	if( !current_user_can( 'ims_change_permissions' ) )
		unset( $settings_tabs['permissions'] );
	
	$css = '';
	include( IMSTORE_ABSPATH . "/admin/settings/settings-fields.php");
	?>
    
    <ul class="ims-tabs add-menu-item-tabs">
		<?php foreach( $settings_tabs as $name => $tab ):?>
        <li class="tabs"><a href="#<?php echo $name ?>-box"><?php echo $tab ?></a></li>
        <?php endforeach?>
    </ul>
    
    
    <?php foreach($settings_tabs as $boxid => $box ): ?>
    
    <div id="<?php echo $boxid ?>-box" class="ims-box">
		<form method="post" class="<?php echo "$boxid-table" ?>" action="<?php echo $this->pageurl , '#', $boxid ?>-box" >
        	<?php if( isset( $settings[$boxid] ) && is_array( $settings[$boxid] ) ) : //start setting ?>
           	
            <table class="ims-table">
				<tbody>
                
                	<?php foreach( $settings[$boxid] as $name => $row ){ 
					
					$active = false;
					$name = esc_attr( $name );
					
					if ( isset( $row['type'] ) && $row['type'] == 'checkbox' && $this->vr( $name ) ) 
						$active = " checkbox-on";
					else 	if ( isset( $row['type'] ) && $row['type'] == 'checkbox' && !isset( $row['multi'] ) ) 
						$active = " checkbox-off";
						
					
					echo '<tr class="row row-' . $name . $css . $active . '">';
						
						if( isset( $row['col'] ) ){
							
							foreach( (array)$row['opts'] as $id => $opt ){
								echo '<td class="col"><label for="', $id , '">', $opt['label'] , '</label></td>';
								echo '<td class="col-fields">';
								
								if( !isset( $opt['type'] ) );
								elseif( $this->is_checkbox( $opt['type'] ) )
									echo '<input type="',  esc_attr( $opt['type'] ) , '" name="', $id , '" id="', $name , '" value="'. 
									esc_attr((isset($opt['val']) ? $opt['val'] : 0 )) , '"', checked( $opt['val'], $this->vr( $id ), 0 ), ' /> '; 
								else 
									echo '<input type="',  esc_attr( $opt['type'] ) , '" name="', $id , '" id="', $name , '" value="', 
									esc_attr( ($val = $this->vr( $id ) ) ? $val : $opt['val'] ) , '" />';
							
								echo ( isset( $opt['desc'] ) ) ? ' <small> '. esc_html( $opt['desc'] ). '</small>' : '';
								echo '</td>';
							}
						
						}elseif( isset( $row['multi'] ) ){
							
							echo '<td class="multi">' , $row['label'] , '</td>';
							echo '<td class="multi-fields">'; 
							
							foreach( (array)$row['opts'] as $id => $opt ){
								$user = ( isset($opt['user']) ) ? $opt['user'] : 0 ;
								echo '<label>';
								
								if( $this->is_checkbox($opt['type']) )
									echo ' <input type="', $opt['type'] , '" name="', $name , '[', $id , ']" value="'. 
									esc_attr((isset($opt['val']) ? $opt['val'] : 0 )) , '"', checked( $opt['val'], $this->vr( $name, $id, $user ), 0 ) , ' /> ' , $opt['label'];
								else echo $opt['label'] , ' <input type="', $opt['type'] , '" name="', $name , '[' , $id , ']" id="', 
									$name,$id, '" value="', esc_attr( ( $val = $this->vr( $name, $id ) ) ? $val : $opt['val'] ) , '" />';
								echo ( isset( $opt['desc'] ) ) ? ' <small> '. esc_html( $opt['desc'] ) . '</small>' : '';
								
								echo '</label>';
							}
							
							echo ( isset( $row['desc'] ) ) ? ' <small> '. esc_html( $row['desc'] ) . '</small>' : '';
							echo '</td>'; 
						
						}else if( isset( $row['type'] ) ){
							$unstall = ( $row['type'] == 'uninstall' ) ? ' form-invalid error' : '' ;
							
							echo '<td class="first">', (( $row['type'] == 'empty' ) ? '&nbsp;' : '<label for="'. $name .'">' . $row['label'] . '</label>' ), '</td>';
							echo '<td class="row-fields', $unstall , '">'; 
								
								switch( $row['type'] ){
									case 'select':
										echo '<select name="' . $name . '" id="' . $name  .'">';
										foreach( ( array ) $row['opts'] as $val => $opt )
											echo '<option value="' . esc_attr( $val ) . '"' . selected( $val, $this->vr( $name ) ) , '>', esc_html( $opt ) . '</option>';
										echo '</select>';
										break;
									case 'textarea':
										echo '<textarea name="' . $name . '" id="'. $name . '" >' . _wp_specialchars( $this->vr( $name ) ) . '</textarea>';
										break;
									case 'radio':
										foreach( (array)$row['opts'] as $val => $opt )
											echo '<label><input type="' . $row['type'] . '" name="' . $name . '" value="', 
											esc_attr( $val ) . '"' . checked( $val, $this->vr( $name ), 0 ) , ' /> ' . $opt . '</label><br /> ';
										break;
									case 'checkbox':
										echo '<input type="', $row['type'] , '" name="'. $name , '" id="'. $name , '" value="'. 
										esc_attr( $row['val'] ) . '"' . checked( $row['val'], $this->vr( $name ) , 0 )  . ' /> ';
										break;
									case 'empty':
										echo '&nbsp;';
										break;
									case 'uninstall':
										echo ( isset( $row['desc'] ) ) ? $row['desc'] : ''; unset( $row['desc'] );
										echo '<p><input type="submit" name="' . $name . '" id="' . $name . '" value="' . esc_attr( $row['val'] ) . '" class="button" /></p>';
										break;
									case 'reset':
									case 'submit':
									case 'button':
										echo '<input type="' . $row['type'] . '" name="' . $name . '" id="' . $name . '" class="button" value="' . esc_attr( $row['val'] ) . '" /> ';
										break;
									default:
										echo '<input type="' . $row['type'] . '" name="' . $name . '" id="' . $name  . '" value="' . 
										esc_attr( ( $val = $this->vr( $name ) ) ? $val : $row['val'] ) . '" /> ';
								}
									
								echo ( isset( $row['desc'] ) ) ? ' <small> ' . esc_html( $row['desc'] ) . '</small>' : '';
							echo '</td>'; 
						}
						
					echo '</tr>';
					
					$css = ( $css == ' alternate' ) ? '' : ' alternate'; 
					}?>
          
        		<?php do_action( 'ims_settings', $boxid) ?>
             	<tr>
                    <td>&nbsp;</td>
                    <td class="submit">
                      <input type="hidden" name="ims-action" value="<?php echo esc_attr( $boxid ) ?>" />
                      <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save', 'ims')?>" />
                    </td>
             	</tr>
            </tbody>
          </table>
          
          <?php endif?>
		  <?php wp_nonce_field( 'ims_settings')?>
        </form>
      </div><!-- #<?php echo $boxid ?> -->
   
	<?php endforeach?>
