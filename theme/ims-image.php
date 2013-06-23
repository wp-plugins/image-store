<?php

	/**
	 * Image Store - single image template
	 *
	 * @file ims-image.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/theme/ims-image.php
	 * @since 1.0.0
	 */
	 
	the_post( );

	get_header( ); ?>

		<div id="primary" class="content-area">
			<div id="content" class="site-content" role="main">
            
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'image-attachment' ); ?>>
                	<header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
    
                        <div class="entry-meta">
                            <?php
                                $published_text  = __( '<span class="attachment-meta">Published on <time class="entry-date" datetime="%1$s">%2$s</time> in <a href="%3$s" title="Return to %4$s" rel="gallery">%5$s</a></span>', 'ims' );
                                $post_title = get_the_title( $post->post_parent );
                                if ( empty( $post_title ) || 0 == $post->post_parent )
                                    $published_text  = '<span class="attachment-meta"><time class="entry-date" datetime="%1$s">%2$s</time></span>';
    
                                printf( $published_text,
                                    esc_attr( get_the_date( 'c' ) ),
                                    esc_html( get_the_date() ),
                                    esc_url( get_permalink( $post->post_parent ) ),
                                    esc_attr( strip_tags( $post_title ) ),
                                    $post_title
                                );
    
                                $metadata = wp_get_attachment_metadata( );
                                printf( '<span class="attachment-meta full-size-link"><a href="%1$s" title="%2$s">%3$s (%4$s &times; %5$s)</a></span>',
                                    esc_url( wp_get_attachment_url( ) ),
                                    esc_attr__( 'Link to full-size image', 'ims' ),
                                    __( 'Full resolution', 'ims' ),
                                    $metadata['width'],
                                    $metadata['height']
                                );
								
								if( $ImStore->active_store )
								printf( '<span class="cart-link"><a href="%1$s" title="%2$s">%3$s</a></span>',
                                    esc_url( $ImStore->get_permalink( "shopping-cart", true, false, $post->post_parent ) ),
                                    esc_attr__( 'Link to shopping cart', 'ims' ),
                                    __( 'Cart', 'ims' )
								);
    
                                edit_post_link( __( 'Edit', 'ims' ), '<span class="edit-link">', '</span>', $post->post_parent ); 
								?>
                        </div><!-- .entry-meta -->
                    </header><!-- .entry-header -->
                    
                    <div class="entry-content">
                        <nav id="image-navigation" class="navigation image-navigation" role="navigation">
                            <span class="nav-previous"><?php previous_post_link( '%link', __( '<span class="meta-nav">&larr;</span> Previous') ); ?></span>
                            <span class="nav-next"><?php next_post_link( '%link', __( 'Next <span class="meta-nav">&rarr;</span>') ); ?></span>
                        </nav><!-- #nav-single -->
    
                        <div class="entry-attachment">
                            <div class="attachment">
                                <?php the_content(); ?>
                            </div><!-- .attachment -->
                        </div><!-- .entry-attachment -->
    
                    </div><!-- .entry-content -->
                </article><!-- #post -->
                
                <?php comments_template( ); ?>
			
			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>