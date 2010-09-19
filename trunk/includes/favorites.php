<?php 

/**
 * Favorites page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0 
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );
	
$this->get_favorite_images( );
?>
<div id="ims-mainbox" class="favorites">

	<div class="ims-nav-box"><?php $this->store_nav( )?></div>
	
	<div class="ims-labels">
		<span class="title"><?php echo $this->gallery->post_title?></span>
		<span class="divider"> | </span>
		<span class="expires">
		<?php echo __( "Expires: ", ImStore::domain ) . date_i18n( get_option( 'date_format' ), strtotime( $this->gallery->post_expire ))?>
		</span>
	</div>
	
	<div class="ims-message<?php echo $css?>">
		<?php if( $this->error ) echo $this->error?>
		<?php if( $this->message ) echo $this->message?>
	</div>
	
	<?php if( $this->pages[5] ){?>
	<div class="ims-toolbar">
		<ul class="ims-tools-nav">
			<li class="ims-select-all"><a href="#" rel="nofollow"><?php _e( "Select all", ImStore::domain )?></a></li>
			<li class="ims-unselect-all"><a href="#" rel="nofollow"><?php _e( "Unselect all", ImStore::domain )?></a></li>
			<li class="add-images-to-cart"><a href="#" rel="nofollow"><?php _e( "Add to cart", ImStore::domain )?></a></li>
			<li class="remove-from-favorite"><a href="#" rel="nofollow"><?php _e( "Remove", ImStore::domain )?></a></li>
		</ul>
	</div>
	<?php }?>
	
	<div class="ims-innerbox"><?php $this->display_galleries( )?></div>
	
	<?php if( !$this->opts['disablestore'] ) $this->display_list_form( );?>
	
	<div class="cl"></div>
</div>