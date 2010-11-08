<?php 
/**
 * ImStoreFront - single gallery shorcode 
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.3 
*/

class ImStoreShortCode{


	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function __construct( ){
		add_shortcode( 'ims-gallery', array( &$this, 'ims_gallery_shortcode' ) );
	}
	
	
	/**
	 * Core function display gallery
	 *
	 * @param array $atts
	 * @return void
	 * @since 0.5.0 
	 */
	function ims_gallery_shortcode( $atts ) {
		
		if( !is_single( ) ) return;
		
		extract( $atts = shortcode_atts(array(
			'id' => 0,
			'caption' => 1,
			'lightbox' => 1,
			'number' => false,
			'order' => false,
			'orderby' => false,
			'slideshow' => false,
		), $atts ) );
		
		$sort = array(
			'date' => 'post_date',
			'title' => 'post_title',
			'custom' => 'menu_order',
			'caption' => 'post_excerpt',
		);
		
		global $wpdb;	
		$this->opts = get_option( 'ims_front_options' );
		$this->gallery_id = $wpdb->get_var( $wpdb->prepare( 
			"SELECT post_id 
			 FROM $wpdb->postmeta 
			 WHERE meta_key = '_ims_gallery_id'
			 AND meta_value = '%s'", $id 
		)) ;
		
		$this->limit = $number;
		$this->order = ( $order ) ? $order : $this->opts['imgsortdirect'];
		$this->orderby = ( $sort[$orderby] ) ? $sort[$orderby] : $this->opts['imgsortorder'];
		$this->get_gallery_images( );
		
		if( $slideshow ) $this->display_slideshow( $atts ) ;
		else $this->display_galleries( $atts );
	}
	
	
	/**
	 * Get gallery images
	 *
	 * @return array
	 * @since 0.5.3 
	 */
	function get_gallery_images( ){
		global $wpdb;
		
		$wpdb->show_errors();
		if( $this->gallery_id  ) $parent = " = " . $this->gallery_id ;
		else $parent = " IN ( SELECT ID FROM $wpdb->posts WHERE post_type = 'ims_gallery' AND post_status = 'publish' AND post_password = '' )";
		
		if( $this->limit ) $limit = " LIMIT $this->limit ";
		
		$this->attachments = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, guid,
			meta_value, post_excerpt
			FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND post_status = 'publish'
			AND post_parent $parent
			ORDER BY $this->orderby $this->order $limit" 
		, $this->gallery_id ));
 
		if( empty( $this->attachments ) )
			return;
		
		foreach( $this->attachments as $post ){
			$post->meta_value = unserialize( $post->meta_value );
			$images[] = $post;
		}
		$this->attachments = $images;
	}
	
	
	/**
	 * Display galleries
	 *
	 * @param array $atts
	 * @return array
	 * @since 0.5.3 
	 */
	function display_galleries( $atts ){ 
		
		extract( $atts );
		
		$itemtag 	= 'ul';
		$icontag 	= 'li';
		$captiontag = 'div';
		$nonce 		= '_wpnonce=' . wp_create_nonce( 'ims_secure_img' );
		
		$output = "<{$itemtag} class='ims-gallery'>";
		foreach ( $this->attachments as $image ){

			$link 		= IMSTORE_URL . "image.php?$nonce&amp;img={$image->ID}";
			$title_att 	= ( $caption ) ? 'title="' . $image->post_excerpt . '"' : ' ' ;
			$tagatts 	= ( $lightbox ) ? ' class="ims-colorbox" rel="gallery" ' : ' class="ims-image" rel="image" ';
			$imagetag 	= '<img src="' . $image->meta_value['sizes']['thumbnail']['url'] . '" alt="' . $image->post_title . '" />'; 
			
			$output .= "<{$icontag}>";
			$output .= '<a href="' . $link . '"'. $tagatts . $title_att .' >' . $imagetag . '</a>';
			$output .= "</{$icontag}>";
		}
		echo $output .= "</{$itemtag}>";
	}
	
	
	
	/**
	 * Display slideshow
	 *
	 * @param array $atts
	 * @return array
	 * @since 0.5.3 
	 */
	function display_slideshow( $atts ){ 
		
		extract( $atts );
		
		//navigation
		$output .= '<div class="ims-imgs-nav">';
		$output .= '<div id="ims-thumbs">
						<ul class="thumbs">';
		if( !empty( $this->attachments ) ){
			$nonce = '_wpnonce=' . wp_create_nonce( 'ims_secure_img' );
			foreach( $this->attachments as $image ){
				$title = $image->post_title;
				$w = $image->meta_value['sizes']['mini']['width'];
				$h = $image->meta_value['sizes']['mini']['height'];
				$imagetag = '<img src="' . $image->meta_value['sizes']['mini']['url'] . '" width="' . $w . '" height="' . $h . '" alt="'. $title . '" />'; 
				$output .= '<li class="ims-thumb"><a class="thumb" href="' . IMSTORE_URL . "image.php?$nonce&amp;img={$image->ID}" . '" rel="nofollow">' . $imagetag . '</a>';
				if( $caption ) $output .= '<span class="caption">' . $image->post_excerpt . '</span>';
				$output .= '</li>';
			}
		}
		$output .= '	</ul>
					</div>';
		$output .= '</div>';	
		
		//slideshow
		$output .= '<div class="ims-slideshow-box">';
		$output .= '<div class="ims-preview"><div class="ims-slideshow-row">
						<div id="ims-slideshow" class="ims-slideshow" ></div>
					</div></div>';
		$output .= '<div class="ims-slideshow-tools-box">
					<form action="" method="post" class="ims-slideshow-tools">
					<div class="image-color">
						<label><input type="checkbox" name="ims-color" id="ims-color-bw" value="bandw" /> ' . __( 'Black &amp; White', ImStore::domain). ' </label>
						<label><input type="checkbox" name="ims-color" id="ims-color-sepia" value="sepia" /> ' . __( 'Sepia', ImStore::domain). ' </label>
					</div>
						<div id="ims-player" class="ims-player">
							<a href="#" class="bk" rel="nofollow" role="button">' . __( 'Back', ImStore::domain ) . ' </a> 
							<a href="#" class="py" rel="nofollow" role="button">' . __( 'Play', ImStore::domain ) . ' </a> 
							<a href="#" class="nx" rel="nofollow" role="button">' . __( 'Next', ImStore::domain ) . ' </a>
						</div>
					</form>
					<div id="ims-caption" class="ims-caption"></div>
					</div>';				
		$output .= '<div class="cl"></div></div>';	
		echo $output;
	}
	
}

new ImStoreShortCode( );

?>