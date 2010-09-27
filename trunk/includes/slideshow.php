<?php 

/**
 * Slideshow page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0 
*/
 
// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

?>

<div id="ims-mainbox" class="slideshow">
	
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
	
	<div class="imst-innerbox">
	
		<div class="ims-imgs-nav">
			<div id="ims-thumbs">
				<ul class="thumbs">
				<?php if( !empty( $this->attachments ) ){
					$nonce = '_wpnonce=' . wp_create_nonce( 'ims_secure_img' );
					foreach( $this->attachments as $image ){
						$title = $image->post_title;
						$w = $image->meta_value['sizes']['mini']['width'];
						$h = $image->meta_value['sizes']['mini']['height'];
						$imagetag = '<img src="' . $image->meta_value['sizes']['mini']['url'] . '" width="' . $w . '" height="' . $h . '" alt="'. $title . '" />'; 
						echo '<li class="ims-thumb"><a class="thumb" href="' . IMSTORE_URL . "image.php?$nonce&amp;img={$image->ID}" . '" rel="nofollow">' . $imagetag . '</a>
						<span class="caption">' . $image->post_excerpt . '</span></li>';
					}
				}?>
				</ul>
			</div>
		</div>
	
		<div class="ims-slideshow-box">
			<div class="ims-preview">
				<div class="ims-slideshow-row">
					<div id="ims-slideshow" class="ims-slideshow" ></div>
				</div>
			</div>
			
			<div class="ims-slideshow-tools-box">
			<div class="zoom">&nbsp;</div>
				<form action="" method="post" class="ims-slideshow-tools">
					<?php if( $this->pages[5] ){?>
					<div class="add-images-to-cart-single"><a href="#"><?php _e( 'Add to cart', ImStore::domain)?></a></div>
					<div class="add-to-favorite-single"><a href="#"><?php _e( 'Add to favorites', ImStore::domain)?></a></div>
					<div class="image-color">
						<label><input type="checkbox" name="ims-color" id="ims-color-bw" value="bandw" /> <?php _e( 'Black &amp; White', ImStore::domain)?></label>
						<label><input type="checkbox" name="ims-color" id="ims-color-sepia" value="sepia" /> <?php _e( 'Sepia', ImStore::domain)?> </label>
					</div>
					<?php }?>
					<div id="ims-player" class="ims-player">
						<a href="#" class="bk" rel="nofollow"><?php _e( 'Back', ImStore::domain )?></a> 
						<a href="#" class="py" rel="nofollow"><?php _e( 'Play', ImStore::domain )?></a> 
						<a href="#" class="nx" rel="nofollow"><?php _e( 'Next', ImStore::domain )?></a>
					</div>
				</form>
				<div id="ims-caption" class="ims-caption"></div>
			</div>
		</div>
		
	</div>
	
	<?php if( !$this->opts['disablestore'] ) $this->display_list_form( );?>
	
	<div class="cl"></div>
</div>