<?php

/**
 * Image Store - Image RSS
 *
 * @file image-rss.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_store/image-rss.php
 * @since 0.5.3
 */
 
class ImStoreFeeds {
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function ImStoreFeeds( ) {
		add_action( 'init', array( &$this, 'init' ), 10 );
		add_action( 'wp_print_styles', array( &$this, 'print_rss_link' ), 0 );
	}
	
	/**
	 * Display feeds
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function init( ) {
		add_feed( 'imstore', array( &$this, 'dis_feed' ) );
	}
	
	/**
	 * Display rss link 
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function print_rss_link( ) {
		
		if ( !is_singular( "ims_gallery" ) && !is_tax("ims_album") && !is_tax( "ims_tags" )  )
			return; 
		
		$permalinks = get_option( 'permalink_structure' );
		
		global $post;
		if( is_singular( "ims_gallery" ) && $post->post_password )
			return;

		if ( is_singular( "ims_gallery" ) ) {
			$query = ( empty( $permalinks ) ) ? '&amp;feed=imstore' : '/feed/imstore/';
			echo '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo( 'name' ) . " &raquo; " .
			__( 'Gallery Feed', 'ims' ) . '" href="' . trim( get_permalink( ), '/' ) . $query . '" />' . "\n";
		}
		
		$tag = get_query_var( 'ims_tags' );
		$album = get_query_var( 'ims_album' );
		
		if ( is_tax( "ims_album" )  && $album ) {
			$query = ( empty(  $permalinks ) ) ? '&amp;feed=rss' : '/feed/';
			echo '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo( 'name' ) . " &raquo; " .
			__( 'Galleries album feed', 'ims' ) . '" href="' . trim( get_term_link( $album, "ims_album"), '/') . $query . '" />' . "\n";
		}
		
		if ( is_tax( "ims_tags" ) && $tag ) {
			$query = ( empty(  $permalinks ) ) ? '&amp;feed=rss' : '/feed/';
			echo '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo( 'name' ) . " &raquo; " .
			__( 'Galleries tag feed', 'ims' ) . '" href="' . trim( get_term_link( $tag, "ims_tags"), '/') . $query . '" />' . "\n";
		}
		
	}
	
	/**
	 * Display feed
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function dis_feed( ) {
		
		if( !is_feed( ) )
			return;
		
		global $ImStore, $post ;
		if( $post->post_password )
			return;

		$ImStore->order = 'DESC';
		$ImStore->sortby = 'date';
		$ImStore->galid = $post->ID;
		$ImStore->posts_per_page = get_option( 'posts_per_rss' );
		
		$ImStore->get_gallery_images( );
		$this->charset = get_bloginfo( 'charset' );
		
		if ( $ImStore->attachments )
			$this->display_rss( );
		
		header( 'content-type:text/plain;charset=' . $this->charset );
		wp_die( __("No images have been added yet.", 'ims' ) );
	}
	
	/**
	 * Display rss feed
	 *
	 * @return void
	 * @since 0.5.3 
	 */
	function display_rss( ) {
		global $ImStore;
		
		header('Content-Type:' . feed_content_type( 'rss-http' ) . '; charset=' . $this->charset, true);
		echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '" standalone="yes"?>
			<rss version="2.0"
			 xmlns:atom="http://www.w3.org/2005/Atom"
			 xmlns:dc="http://purl.org/dc/elements/1.1/"
			 xmlns:media="http://search.yahoo.com/mrss/"
			 xmlns:wfw="http://wellformedweb.org/CommentAPI/"
			 xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
			 xmlns:content="http://purl.org/rss/1.0/modules/content/"
			 xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" >
			<channel>';
		?>
        
        <title><?php bloginfo_rss( 'name' ); _e( 'image RSS', 'ims' ); ?></title>
        <atom:link href="<?php self_link( ); ?>" rel="self" type="application/rss+xml" />
        <link><?php bloginfo_rss( 'url' ) ?></link>
        <description><?php bloginfo_rss( "description" ) ?>	</description>
        
        <lastBuildDate><?php echo mysql2date( 'D,d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
        <language><?php echo get_option( 'rss_language' ); ?></language>
        
        <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'weekly' ); ?></sy:updatePeriod>
        <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>

        <?php foreach ( $ImStore->attachments as $image ) { 
			$author = $image->meta['image_meta']['author'];
		?>
        	<item>
							<title><?php echo $image->post_title ?></title>
							<link><?php echo $ImStore->get_image_url( $image->ID ) ?></link>
							<pubDate><?php echo date( 'c', strtotime( $image->post_date ) )?></pubDate>
							<media:thumbnail url="<?php echo $ImStore->get_image_url( $image->ID, 2 ) ?>"/>
								<media:content type="<?php echo $image->post_mime_type ?>" url="<?php echo $ImStore->get_image_url( $image->ID ) ?>"/>
							<media:text type="plain" lang="<?php bloginfo( 'language' ) ?>" ><![CDATA[<?php echo wp_strip_all_tags( $image->post_excerpt ) ?>]]></media:text>
							<media:credit role="photographer" scheme="urn:yvs"><?php echo $author ?></media:credit>
            </item>
		<?php } 
 
		echo '</channel></rss>';
		die( );
		
	}
}