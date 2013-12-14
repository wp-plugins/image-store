<?php

/**
 * Image Store - image widget 
 *
 * @file widget.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/widget.php
 * @since 0.5.3
 */
 
class ImStoreWidget extends WP_Widget {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.3
	 */
	function ImStoreWidget( ) {
		$widget_ops = array(
			'classname' => 'ims-widget',
			'description' => __( "Display images from unsecure galleries", 'ims' )
		);
		$this->WP_Widget( 'ims-widget', __( 'Image Store', 'ims' ), $widget_ops);
	}
	
	/**
	 * Display widget.
	 *
	 * @return void
	 * @since 0.5.3
	 */
	function widget( $args, $instance ) {
		
		extract( $args );
		extract( $instance );

		echo $before_widget . "\n";
		if ( $title ) echo $before_title . $title . $after_title . "\n";
		
		$this->get_widget_images( $instance );
		$this->display_images( ! empty( $filmstrip ), $instance );

		echo $after_widget . "\n";
	}
	
	/**
	 * Configuration form.
	 *
	 * @return void
	 * @since 0.5.3
	 */
	function form( $instance ) {
		
		$instance = wp_parse_args( $instance, array(
			'title' => NULL, 'limit' => false, 'filmstrip' => 1,
			'galid' => false, 'show' => false, 'orderby' => false,
		) );
		
		extract( $instance );
		
		$order_options = array(
			'post_date' => __( 'Date', 'ims' ),
			'post_title' => __( 'Title', 'ims' ),
			'menu_order' => __( 'Custom', 'ims' ),
			'post_excerpt' => __( 'Caption', 'ims' ),
		);
		
		$show_options = apply_filters( 'ims_widget_display_options', array(
			'gal' => __( 'Gallery', 'ims' ),
			'DESC' => __( 'Oldest images', 'ims' ),
			'ASC' => __( 'Latest images', 'ims' ),
			'rand' => __( 'Random images', 'ims' ),
		) );
		
		?>
        
    	<p>
			<label for="<?php echo $this->get_field_id( 'title' ) ?>"><?php _e( 'Title', 'ims' ) ?>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ) ?>" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $title )?>" /></label>
		</p>
		
		<?php  do_action( 'ims_widget_admin_options', $instance, $this ); ?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'filmstrip' )?>"> <?php _e( 'Filmstrip mode', 'ims')?> 
				<input id="<?php echo $this->get_field_id( 'filmstrip' )?>" name="<?php echo $this->get_field_name( 'filmstrip' ) ?>" type="checkbox" <?php checked( 'on', $filmstrip ) ?> /> 
				<br /><small><?php _e( 'Display images in film strip mode', 'ims')?></small>
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'orderby' ) ?>"><?php _e( 'Order by', 'ims' ) ?></label>
			<select id="<?php echo $this->get_field_id( 'orderby' ) ?>" name="<?php echo $this->get_field_name( 'orderby' ) ?>">
				<?php foreach ( $order_options as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo selected( $value, $orderby ) ?> ><?php echo esc_html( $label ) ?></option> 
				<?php } ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'show' ) ?>"><?php _e( 'Show', 'ims' ) ?></label>
			<select id="<?php echo $this->get_field_id( 'show' ) ?>" name="<?php echo $this->get_field_name( 'show' ) ?>">
				<?php foreach ( $show_options as $value => $label ) { ?>
					<option value="<?php echo esc_attr( $value ) ?>" <?php echo selected( $value, $show ) ?> ><?php echo esc_html( $label ) ?></option> 
				<?php } ?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'limit' ); ?>"><?php _e( 'How many images', 'ims' ); ?> 
			<input id="<?php echo $this->get_field_id( 'limit' ); ?>" name="<?php echo $this->get_field_name( 'limit' ); ?>" size="4" type="text" value="<?php echo esc_attr( $limit ) ?>"/></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'galid' ); ?>"><?php _e( 'Gallery ID', 'ims' ); ?>
			<input id="<?php echo $this->get_field_id( 'galid' ); ?>" name="<?php echo $this->get_field_name( 'galid' ); ?>" class="widefat" type="text" value="<?php echo esc_attr( $galid ) ?>"/></label>
			<small><?php _e( 'To be use with "show" gallery option', 'ims' ); ?></small>
		</p>
        
		<?php
		
	}
	
	/**
	 * Get recent images
	 * From unsecure galleries
	 *
	 * @param array $instance
	 * @return array
	 * @since 0.5.3 
	 */
	function get_widget_images( $instance ) {
		
		if ( empty( $instance ) )
			return;

		global $wpdb;
		extract( $instance );
				
		if ( $show == 'gal' )
			$parent = $wpdb->prepare(
			" = ( SELECT post_id FROM $wpdb->postmeta 
			 WHERE meta_key = '_ims_gallery_id'
			 AND meta_value = %s LIMIT 1 ) ", $galid
		);
		
		else $parent = " IN ( SELECT ID FROM $wpdb->posts 
		WHERE post_type = 'ims_gallery'  AND post_status = 'publish' AND post_password = '' ) ";
		
		if ( $show == 'gal' ) {
			$order = " DESC";
		} elseif ( $show == 'rand' ) {
			$orderby = '';
			$order = " RAND( )";
		} else {
			$order = esc_sql( $show );
		}
		
		if ( $limit ) $limit = "LIMIT $limit";
		$images = wp_cache_get( 'ims_widget_' . $this->number, 'ims' );
		
		if ( false == $images) {
			
			$images = $wpdb->get_results( "SELECT p.*, pm.meta_value meta
				FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm
				ON p.ID = pm.post_id WHERE post_type = 'ims_image' 
				AND post_status = 'publish' AND post_parent $parent
				AND pm.meta_key = '_wp_attachment_metadata'
				ORDER BY $orderby $order $limit "
			);
			
			wp_cache_set( 'ims_widget_' . $this->number, $images, 'ims' );
		}
		
		if ( empty( $images ) )
			return;
		
		foreach ( $images as $image ) {
			$image->meta = maybe_unserialize( $image->meta );
			$this->attachments[] = $image;
		}
	}
	
	/**
	 * Display galleries
	 *
	 * @param bool $filmstrip
	 * @return array
	 * @since 0.5.3 
	 */
	function display_images( $filmstrip, $instance ) {
		
		if ( empty( $this->attachments ) )
			return;
		
		// do not display image tools on widgets
		global $ImStore;
		$ImStore->is_widget = true;
		
		$output = apply_filters( 'ims_widget_images', false, $this->attachments, $instance );
		
		if ( false != $output ) {
			echo $output;
			$ImStore->is_widget = false;
			return true;
		}
	
		extract( $ImStore->gallery_tags );
		$css = 'ims-gallery wgt-ims-gallery';
		
		if( $filmstrip )
			$css .=" ims-filmstrip";
		
		$output = "<{$gallerytag} class='$css'>
		<div class='ims-gal-innner'>";
		
		foreach ( $this->attachments as $image ) {
			if ( ! isset( $image->meta['sizes']['mini'] ) )
				continue;
			
			$title = $image->post_title;
			$link = get_permalink( $image->post_parent );
			
			$image->meta += array( 'link' => $link, 'alt' => $title, 'title' => $title, 'class' => array() );
			$output .= $ImStore->image_tag( $image->ID, $image->meta, 3, false );
		}
		
		// restore setting to allow content to display after sidebar
		$ImStore->is_widget = false;
		
		$output .= "</div></{$gallerytag}><!--.ims-gallery-->";
		echo $output .= '<div class="ims-cl"></div>';
	}
}