<?php

	/**
	 * Image Store - Slideshow Page
	 *
	 * @file slideshow.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/_store/slideshow.php
	 * @since 0.5.0
	 */

	// Stop direct access of the file
	if ( !defined( 'ABSPATH' ) )
		die( );
		
	if( !$this->opts['bottommenu'] )
		$output .= $this->slide_show_nav( );
	
	$output .= '<div class="ims-slideshow-box">';
	$output .= '
		<div class="ims-preview">
			<div class="ims-slideshow-row">
				<div id="ims-slideshow" class="ims-slideshow" ></div>
			</div>
		</div><!--.ims-preview-->';
	$output .= '<div class="ims-slideshow-tools-box">' . "\n";
	$output .= '<div id="ims-caption" class="ims-caption"></div>' . "\n";
	
	
	if( $this->opts['bottommenu'] )
		$output .= $this->slide_show_nav( );
	
	$output .= '<form method="post" class="ims-slideshow-tools">' . "\n";
	
	if ( $this->active_store ) 
		$output .= '<div class="add-images-to-cart-single"><a href="#" role="button" rel="nofollow">' . esc_html__( 'Add to cart', 'ims' ) . '</a></div>' . "\n";
	
	if( $this->opts['favorites'] )
		$output .= '<div class="add-to-favorite-single"><a href="#" role="button" rel="nofollow">' . esc_html__( 'Add to favorites', 'ims' ) . '</a></div>' . "\n";
	
	$output .= apply_filters( 'ims_slideshow_actions', '' );
	
	$output .= '<div id="ims-player" class="ims-player">' . "\n";
	$output .= '<a href="#" class="bk" rel="nofollow">' . __( 'Back', 'ims' ) . '</a>';
	$output .= '<a href="#" class="py" rel="nofollow">' . __( 'Play', 'ims' ) . '</a>';
	$output .= '<a href="#" class="nx" rel="nofollow">' . __( 'Next', 'ims' ) . '</a>';
	$output .= '</div><!--#ims-player-->' . "\n";
	
	//color options
	$output .= '<div class="image-color">' . "\n";
	if ( !empty( $this->listmeta['colors'] ) ){
		$output .= '<span class="ims-color-label">' . __( 'Color Preview:', 'ims' ) . '</span>' . "\n";
		foreach ( $this->listmeta['colors'] as $key => $color ){
			if( isset( $color['code'] ) && $color['code'] )
				$output .= '<label><input type="checkbox" name="ims-color[]" value="'. esc_attr( $color['code'] ) .
				'" class="ims-color ims-color-'. esc_attr( $color['code'] ) .'" /> ' . esc_html( $color['name'] ) . '</label>	' . "\n";
		}
	}
	
	$output .= apply_filters( 'ims_color_options', '' );
	$output .= '</div><!--.image-color-->' . "\n";
	
	$output .= '</form><!--.ims-slideshow-tools-->' . "\n";
	
	$output .= apply_filters( 'ims_after_slideshow', '' );
	
	$output .= '</div><!--.ims-slideshow-tools-box-->' . "\n";
	$output .= '<div class="ims-cl"></div>' . "\n";
	$output .= '</div><!--.ims-slideshow-box-->' . "\n";