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
	
	global $ImStore;
		
	$output .= '<div class="ims-simple-slideshow">';
	$output .= '<div id="ims-simple-slideshow">' ;
	
	foreach( $this->attachments as $attachment ){		
		$output .= 
		'<figure class="ims-img" >
			<img src="' . esc_attr( $ImStore->imgurl ). '" 
			data-ims-src="' .  esc_attr( $ImStore->get_image_url( $attachment->ID, 1 ) ) . '" role="img" />';
			if( ! empty( $caption ) ){
				$post = get_post( $attachment->ID );
				$output .= '<figcaption>' . (  ( $ImStore->opts['titleascaption'] ) ?  $post->post_title :  $post->post_excerpt ) . '</figcaption>';
			}
		$output .= '</figure>';
	}
				
	$output .= '</div><!--#ims-slideshow-->';
	$output .= '<div class="ims-slideshow-tools-box">' . "\n";
	
	$output .= apply_filters( 'ims_after_slideshow', '' );
	
	$output .= '<div class="ims-cl"></div>' . "\n";
	$output .= '</div><!--.ims-simple-slideshow-->' . "\n";