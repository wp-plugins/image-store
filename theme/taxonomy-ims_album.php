<?php get_header(); ?>

		<section id="primary">
			<div id="content" role="main">

				<header class="page-header">
					<h1 class="page-title"><?php single_term_title( ); ?></h1>
				</header>
				
				
				<div class="ims-gallery">
				<?php while ( have_posts( ) ) : the_post(); ?>
					<figure class="hmedia ims-img"><?php
						$images = get_children( array(
							'numberposts' => 1,
							'post_type'=>'ims_image', 
							'post_parent' => $post->ID,
							'orderby' => 'menu_order',
							'order' => 'ASC'
						)); 
						foreach( $images as  $image )
							$data = wp_get_attachment_metadata( $image->ID ); 
											
						$size = ' width="'. $data['sizes']['thumbnail']['width'] .'" height="'.$data['sizes']['thumbnail']['height'].'"';
						echo '<a href="'. get_permalink() . '" title="View &quot;'. get_the_title( $post->ID ).'&quot; gallery" rel="enclosure">
						<img src="'. $ImStore->get_image_url($image->ID, 2 ) .'" class="colorbox-2" alt="'.get_the_title( $post->ID ).'"'.$size.' /></a>'; 
						echo '<figcaption class="gallery-caption"><span class="fn ims-img-name">'.get_the_title( $post->ID ).'</span></figcaption>';
					?></figure>

				<?php endwhile; ?>
				</div>
				
				<?php //theme_content_nav( 'nav-below' , 'galleries' ); ?>

			</div><!-- #content -->
		</section><!-- #primary -->

<?php get_sidebar( 'galleries' ); ?>
<?php get_footer(); ?>