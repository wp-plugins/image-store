<?php

/**
 * Image Store - image tools widget 
 *
 * @file widget-tools.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/widget-tools.php
 * @since 3.1.0
 */
 

class ImStoreWidgetTools extends WP_Widget {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.1.0
	 */
	function ImStoreWidgetTools() {
		$widget_ops = array(
			'classname' => 'ims-widget-tools',
			'description' => __( "Display Image Store tools and navigation", 'ims' )
		);
		
		$this->WP_Widget( 'ims-widget-tools', __( 'Image Store Tools', 'ims' ), $widget_ops );
	}
	
	/**
	 * Configuration form.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	function form( $instance ) {
		extract( wp_parse_args( $instance, array( 'title' => '' ) ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>"> <?php _e ('Title', 'ims' ) ?> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $title ) ?>" /></label>
		</p>
		<?php
	}
	
	/**
	 * Display widget.
	 *
	 * @since 3.1.0
	 * @return void
	 */
	function widget( $args, $instance ) {
		
		extract( $args ); extract( $instance );
		
		// do not display image tools on widgets
		global $ImStore;
		$ImStore->is_widget = true;
		
		$parent_id = 0;
		echo $before_widget . "\n";
		if ( $title ) echo $before_title . $title . $after_title . "\n";
		echo '<div class="ims-innner-widget">';
		
		if( is_singular( 'ims_gallery' ) )
			echo $ImStore->store_nav( );
		echo $ImStore->store_subnav( );
		
		if( $ImStore->cart['images'] ){
			echo '<div class="ims-gallery ims-tools-gal"><div class="ims-gal-innner">';
			
			foreach ( $ImStore->cart['images'] as $id => $image ) {
				
				$parent_id = $ImStore->get_post_parent_id( $id );
				$link		 	= get_permalink( $parent_id );
				$title 		= esc_attr( get_the_title( $id ) );
				 
				if( $meta = (array) get_post_meta( $id, '_wp_attachment_metadata', true ) ){
					$meta+= array( 'link' => $link, 'alt' => $title, 'title' => $title, 'location' => 'tools-widget' );
					echo $ImStore->image_tag( $id, $meta, 3, false );
				}
			}
			
			echo '</div><!--.ms-tools-gal-inner--></div><!--.ims-tools-gal-->';
		}
		
		// restore setting to allow content to display after sidebar
		$ImStore->is_widget = false;
		
		$link = '<a href="' . $ImStore->get_permalink( 'shopping-cart', true, false, $parent_id ) . 
		'" role="link" class="ims-checkout" title="' . __( 'Checkout', 'ims' ) . '">%s</a>';

		echo '<div class="ims-tools">';
		if ( $ImStore->cart['items'] )
			echo '<div class="ims-items"><span class="ims-label">' . __( 'Total Items:', 'ims' ) . ' </span>' . sprintf( $link, $ImStore->cart['items'] ) . "</div>";
		
		if ( $ImStore->cart['total'] )
			echo '<div class="ims-total"><span class="ims-label">' . __( 'Total:', 'ims' ) . ' </span>' . sprintf( $link, $ImStore->format_price( $ImStore->cart['total'] ) ) . "</div>";
		echo '</div><!--.ims-tools-->';

		echo '</div><!--.ims-innner-widget-->';
		echo $after_widget . "\n";
	}
	
	/**
	 * Deprecated
	 *
	 * @param string $page
	 * @since 3.1.0
	 * return string
	 */
	function get_permalink( $id, $page = false ) {
		global $ImStore;
		$ImStore->get_permalink( $page, true, false, $id );
	}
	
}