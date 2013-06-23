<?php

	/**
	 * Image Store - album template
	 *
	 * @file taxonomy-ims_album.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/theme/taxonomy-ims_album.php
	 * @since 1.0.0
	 */
	 
	 get_header(); ?>

		<div id="primary">
			<div id="content" role="main">
				
				<header class="page-header">
					<h1 class="page-title"><?php single_cat_title( ); ?></h1>
				</header>
				
				<div class="ims-tax ims-gallery <?php echo "ims-cols-" . $ImStore->opts['columns'] ?>">
				<?php while ( have_posts( ) ) : the_post( ); echo $ImStore->taxonomy_content( );  endwhile; ?>
				</div>
				
				<nav role="navigation">
					<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older Galleries', 'ims' ) ); ?></div>
					<div class="nav-next"><?php previous_posts_link( __( 'Newer Galleries <span class="meta-nav">&rarr;</span>', 'ims' ) ); ?></div>
				</nav><!-- #nav-above -->

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_footer(); ?>
				

