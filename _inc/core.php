<?php

/**
 * Image Store - core
 *
 * @file core.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/core.php
 * @since 3.0.0
 */

class ImStore {
	
	/**
	 * Public variables
	 *
	 * Make sure that new language( .mo ) files have 'ims-' as base name
	 */
	public $dformat = '';
	public $content_url = '';
	public $content_dir = '';
	
	public $sync = false;
	public $perma = false;
	public $blog_id = false;
	
	public $color = array( );
	public $opts = array( );
	public $pages = array( );
	public $page_slugs = array( );
	public $promo_types = array( );
	public $rules_property = array( );
	
	public $version = '3.2.2';
	public $customer_role = 'customer';
	public $optionkey = 'ims_front_options';
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function ImStore( ) {
		
		global $wp_version, $blog_id;
		$this->wp_version = $wp_version;
							
		add_action( 'plugins_loaded', array( &$this, 'define_constant' ), 5 );
		add_action( 'plugins_loaded', array( &$this, 'set_core_variables' ), 6 );
		
		add_action( 'init', array( &$this, 'int_actions' ), 0 );
		add_action( 'wp_loaded', array( &$this, 'flush_rules' ) );
		add_action( 'wp_logout', array( &$this, 'logout_ims_user' ), 10 );
		add_action( 'imstore_expire', array( &$this, 'expire_galleries' ) );
		add_action( 'set_current_user', array( &$this, 'set_user_caps' ), 10 );
		add_action( 'plugins_loaded', array( &$this, 'image_store_init' ), 100 );
		add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ), 10 );
		
		add_filter( 'posts_orderby', array( &$this, 'posts_orderby' ), 10, 3 );
		add_filter( 'post_type_link', array( &$this, 'gallery_permalink' ), 10, 3 );
	}
	
	/**
	 * Inital plugin actions
	 *
	 * @return void
	 * @since 0.3.1
	 */
	function image_store_init( ) {
		
		$this->locale = get_locale( );
		if ( $this->locale == 'en_US' || is_textdomain_loaded( 'ims' ) )
			return;
			
		$mofile = $this->content_dir . '/languages/_ims/' . 'ims' . '-' . $this->locale . '.mo';
		if ( !file_exists( $mofile ) && is_admin( ) && current_user_can( 'activate_plugins' ) ) {
			$time = get_option( '_ims_no_lan_file' );
			if ( $time + ( 86400 * 2 ) <= time(  ) )
				$this->download_language_file( $mofile );
		}
		
		if ( function_exists( 'load_plugin_textdomain' ) )
			load_plugin_textdomain( 'ims', false, apply_filters( 'ims_load_textdomain', '../languages/_ims/', 'ims', $this->locale ) );
		elseif ( function_exists( 'load_textdomain' ) )
			load_textdomain( 'ims', apply_filters( 'ims_load_textdomain', $mofile, 'ims', $this->locale ) );
	}
	
	/**
	 * Download language file
	 *
	 * @return void
	 * @since 3.0.1
	 */
	function download_language_file( $mofile ) {
		
		add_option( '_ims_no_lan_file', time(  ) );
		
		$data = @file_get_contents( "https://xparkmedia.com/xm/wp-content/languages/ims-" . $this->locale . ".zip" );
		
		if ( empty( $data ) ) 
			return;

		if ( !file_exists( $path = dirname( $filedir ) ) )
			@mkdir($path, 0755, true);
		
		if( !is_writable( $path ) )
			return;
		
		$temp = $path . '/temp.zip';
		@file_put_contents( $temp, $data );
		
		include_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
		$PclZip = new PclZip( $temp );
		
		if ( false == ( $archive = $PclZip->extract( PCLZIP_OPT_EXTRACT_AS_STRING ) ) )
			return;
		
		foreach ( $archive as $file )
			@file_put_contents( $path . "/" . $file['filename'], $file['content'] );

		@unlink( $temp );
	}
	
	/**
	 * Define contant variables
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function define_constant( ) {
		
		do_action( 'ims_define_constants', $this );
		
		define( 'IMSTORE_URL', WP_PLUGIN_URL . "/" . IMSTORE_FOLDER );
		define( 'IMSTORE_ADMIN_URL', IMSTORE_URL . '/admin' );
		
		if ( !defined( 'WP_SITE_URL' ) )
			define( 'WP_SITE_URL', get_bloginfo( 'url' ) );
			
		if ( !defined( 'WP_CONTENT_URL' ) )
			define( 'WP_CONTENT_URL', get_bloginfo('wpurl') . '/wp-content' );
		
		$this->content_dir = rtrim( WP_CONTENT_DIR, '/' );
		$this->content_url = rtrim( WP_CONTENT_URL, '/' );
	}
	
	/**
	 * Set object variables
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function set_core_variables( ){
		
		global $blog_id;
						
		if ( is_multisite( ) && $blog_id ) {
			$this->blog_id = ( int ) $blog_id;
			$this->sync = get_site_option( 'ims_sync_settings' );
		}
		
		if ( $this->sync )  
			switch_to_blog( 1 ); 

		$this->opts = get_option( $this->optionkey );
		$this->opts['ims_searchable'] = get_option( 'ims_searchable' );
		
		if ( is_multisite( ) )
			restore_current_blog( );
		
		$this->key = apply_filters( 'ims_image_key', 
			substr( preg_replace( "([^a-zA-Z0-9])", '', NONCE_KEY ), 0, 15 ) 
		);
		
		do_action( 'ims_set_variables', $this );
	}
	
	/**
	 * Initial actions
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function int_actions( ) {
		
		$this->register_post_types( );
		
		//load gallery widget
		if ( $this->opts['imswidget'] ){
			include_once( apply_filters( 'ims_widget_path', IMSTORE_ABSPATH . '/_inc/widget.php' ) );
			register_widget( 'ImStoreWidget' );
		}
		
		//load tools widget
		if ( $this->opts['widgettools'] ){
			include_once( apply_filters( 'ims_widget_path', IMSTORE_ABSPATH . '/_inc/widget-tools.php' ) );
			register_widget( 'ImStoreWidgetTools' );
		}
		
		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
		
		$this->load_pages( );
		$this->load_color_opts( );
		
		$this->loc = $this->opts['clocal'];
		$this->sym = $this->opts['symbol'];
		
		$this->dformat = get_option( 'date_format' );
		$this->perma = get_option( 'permalink_structure' );
		$this->cformat = array( '', "$this->sym%s", "$this->sym %s", "%s$this->sym", "%s $this->sym");
		
		$this->units = apply_filters( 'ims_units', array(
			'in' => __('in', 'ims'), 'cm' => __('cm', 'ims'), 'px' => __('px', 'ims')
		));

		$this->promo_types = apply_filters( 'ims_promo_types', array(
			'1' => __( 'Percent', 'ims' ),
			'2' => __( 'Amount', 'ims' ),
			'3' => __( 'Free Shipping', 'ims' ),
		));

		$this->rules_property = apply_filters('ims_rules_property', array(
			'items' => __( 'Item quantity', 'ims' ),
			'total' => __( 'Total amount', 'ims' ),
			'subtotal' => __( 'Subtotal amount', 'ims' ),
		));

		$this->rules_logic = apply_filters('ims_rules_logic', array(
			'equal' => __( 'Is equal to', 'ims' ),
			'more' => __( 'Is greater than', 'ims' ),
			'less' => __( 'Is less than', 'ims' ),
		));
		
	}
	
	/**
	 * Register custom post types
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function register_post_types( ) {
		
		$searchable = get_option( 'ims_searchable' ) ? false : true;
		
		//image type to be able to display images
		$image = apply_filters( 'ims_image_post_type', array(
			'public' => true,
			'show_ui' => false,
			'revisions' => false,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'show_in_nav_menus' => false,
			'rewrite' => array( 'slug' => $this->opts['image_slug'], 'with_front' => false ),
		) );
		
		//gallery post type assign
		$posttype = apply_filters( 'ims_gallery_post_type', array(
			'labels' => array(
				'name' => _x( 'Galleries', 'post type general name', 'ims' ),
				'singular_name' => _x( 'Gallery', 'post type singular name', 'ims' ),
				'add_new' => _x( 'Add New', 'Gallery', 'ims' ),
				'add_new_item' => __( 'Add New Gallery', 'ims' ),
				'edit_item' => __( 'Edit Gallery', 'ims' ),
				'new_item' => __( 'New Gallery', 'ims' ),
				'view_item' => __( 'View Gallery', 'ims' ),
				'search_items' => __( 'Search galleries', 'ims' ),
				'not_found' => __( 'No galleries found', 'ims' ),
				'not_found_in_trash' => __( 'No galleries found in Trash', 'ims' ),
			),
			'public' => true,
			'show_ui' => true,
			'menu_position' => 33,
			'publicly_queryable' => true,
			'hierarchical' => false,
			'revisions' => false,
			'capability_type' => 'page',
			'query_var' => 'ims_gallery',
			'show_in_nav_menus' => false,
			'exclude_from_search' => $searchable,
			'menu_icon' => IMSTORE_URL . '/_img/imstore.png',
			'supports' => array( 'title', 'comments', 'author', 'excerpt', 'page-attributes' ),
			'rewrite' => array( 'slug' => $this->opts['gallery_slug'], 'with_front' => false ),
			'taxonomies' => array( 'ims_album' )
		) );
		
		//taxomomy albums
		$albums = apply_filters( 'ims_album_taxonomy', array(
			'labels' => array(
				'name' => _x( 'Albums', 'taxonomy general name', 'ims' ),
				'singular_name' => _x( 'Album', 'taxonomy singular name', 'ims' ),
				'search_items' => __( 'Search Albums', 'ims' ),
				'all_items' => __( 'All Albums', 'ims' ),
				'parent_item' => __( 'Parent Album', 'ims' ),
				'parent_item_colon' => __( 'Parent Album:', 'ims' ),
				'edit_item' => __( 'Edit Album', 'ims' ),
				'update_item' => __( 'Update Album', 'ims' ),
				'add_new_item' => __( 'Add New Album', 'ims' ),
				'new_item_name' => __( 'New Album Name', 'ims' ),
				'menu_name' => __( 'Album', 'ims' ),
			),
			'show_ui' => true,
			'query_var' => true,
			'hierarchical' => true,
			'show_in_nav_menus' => true,
			'rewrite' => array( 'slug' => $this->opts['album_slug'] ),
		));
		
		//register taxomomy tags
		$tags = apply_filters( 'ims_tag_taxonomy',  array(
			'labels' => array(
				'name' => _x( 'Tags', 'taxonomy general name', 'ims' ),
				'singular_name' => _x( 'Tag', 'taxonomy singular name', 'ims' ),
				'search_items' => __( 'Search Tags', 'ims' ),
				'all_items' => __( 'All Tags', 'ims' ),
				'edit_item' => __( 'Edit Tag', 'ims' ),
				'update_item' => __( 'Update Tag', 'ims' ),
				'add_new_item' => __( 'Add New Tag', 'ims' ),
				'new_item_name' => __( 'New Tag Name', 'ims' ),
				'menu_name' => __( 'Tags', 'ims' ),
			),
			'show_ui' => true,
			'query_var' => true,
			'hierarchical' => false,
			'rewrite' => array( 'slug' => $this->opts['tag_slug'] ),
		));

		register_post_type( 'ims_image', $image );
		register_post_type( 'ims_gallery', $posttype );
		
		register_taxonomy( 'ims_tags', array( 'ims_gallery' ), $tags );
		register_taxonomy( 'ims_album', array( 'ims_gallery' ), $albums );
	}
	
	/**
	 * load pages to use for permalinks
	 * and to display the correct section
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function load_pages( ) {
		
		$this->pages['photos'] = __( 'Photos', 'ims' );
		$this->pages['slideshow'] = __( 'Slideshow', 'ims' );
		$this->pages['favorites'] = __( 'Favorites', 'ims' );

		if ( empty( $this->opts['disablestore'] ) ) {
			$this->pages['price-list'] = __( 'Price List', 'ims' );
			$this->pages['shopping-cart'] = __( 'Shopping Cart', 'ims' );
			$this->pages['receipt'] = __( 'Receipt', 'ims' );
			$this->pages['checkout'] = __( 'Checkout', 'ims ');
		}
		
		$this->pages = apply_filters( 'ims_load_pages', $this->pages );
		
		//create page slugs
		foreach( $this->pages as $key => $page ){
			if( preg_match('/[^\\p{Common}\\p{Latin}]/u', $this->pages[$key]) )
				$this->page_slugs[$key] = $page;
			else $this->page_slugs[$key] = sanitize_title( $this->pages[$key] );
		}
	}
	
	/**
	 * load color options
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function load_color_opts( ) {
		$this->color = array( 'ims_color' => __( 'Full Color', 'ims' ) );

		if ( empty( $this->opts['disablebw'] ) )
			$this->color['ims_sepia'] = __( 'B &amp; W', 'ims' );
	
		if ( empty( $this->opts['disablesepia'] ) )
			$this->color['ims_bw'] = __( 'Sepia ', 'ims' );

		$this->color = apply_filters( 'ims_color_opts', $this->color );
	}
	
	/**
	 * Flush rules
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function flush_rules( ) {
		$rules = get_option( 'rewrite_rules' );
		if ( !isset( $rules[$this->opts['gallery_slug'] . "/([^/]+)/feed/(imstore)/?$"]) ) {
			global $wp_rewrite;
			$wp_rewrite->flush_rules( );
		}
	}
	
	/**
	 * Allow post to be sorted by excerpt
	 *
	 * @param string $orderby
	 * @param obj $query
	 * @return string
	 * @since 3.0.0
	 */
	function posts_orderby($orderby, $query) {
		if (empty($query->query_vars['orderby'])
		|| empty($query->query['orderby'])
		|| $query->query['orderby'] != 'excerpt')
			return $orderby;

		global $wpdb;
		return $wpdb->posts . ".post_excerpt";
	}
	
	/**
	 * Add support for gallery permalink
	 *
	 * @param string $permalink
	 * @param obj $post
	 * @param string $leavename
	 * @return string
	 * @since 3.0.0
	 */
	function gallery_permalink( $permalink, $post ) {
		if ( $post->post_type != 'ims_gallery' )
			return $permalink;
		return trim( str_replace('%imspage%', '', $permalink), '/' );
	}
	
	/**
	 * logout user
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function logout_ims_user( ) {
		setcookie( 'ims_galid_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'wp-postpass_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );
	}
	
	/**
	 * Add user capabilities to current user
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function set_user_caps( ) {
		global $current_user;
		if ( !isset( $current_user->ID ) || isset( $current_user->caps['administrator'] ) )
			return; 

		if ( !empty( $current_user->ims_user_caps ) )
			$current_user->allcaps += $current_user->ims_user_caps;
	}
	
	/**
	 * Get image votes
	 *
	 * @param int $image_id
	 * @return int 	
	 * @since 3.2.1
	 */
	function get_image_vote_count( $image_id ){
		
		if( empty( $image_id ) )
			return 0;
		
		$image_id = (int) $image_id;
		if( $count = wp_cache_get( "ims_vote_count_{$image_id}", 'ims' ) )
			return $count;
			
		global $wpdb;
		$count = $wpdb->get_var (  $wpdb->prepare( 
			"SELECT count( meta_value ) FROM $wpdb->usermeta 
			WHERE meta_key = '_ims_image_like' 
			AND meta_value = %d " , $image_id )
		);
		
		wp_cache_set( "ims_vote_count_{$image_id}", $count, 'ims' );
		
		return $count;
	}
	
	/**
	 * Set galleries to expired
	 * and delete unprocess orders
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function expire_galleries( ) {
		global $wpdb;

		do_action( 'ims_before_cron' );
		$time = date( 'Y-m-d', current_time( 'timestamp' ) );
		
		//change status for expired galleries
		$wpdb->query(
			"UPDATE $wpdb->posts SET post_status = 'expire'  WHERE post_expire <= '$time'
			AND post_expire != '0000-00-00 00:00:00' AND post_type = 'ims_gallery'"
		);
		
		//delete orders not proccessed
		$wpdb->query( "DELETE p,pm FROM $wpdb->posts p  LEFT JOIN $wpdb->postmeta pm ON(p.ID = pm.post_id)
			WHERE post_expire <= '$time' AND post_type = 'ims_order' AND post_status = 'draft'"
		);

		do_action( 'ims_after_cron' );
	}
	
	/**
	 * Rewrites for custom page managers
	 *
	 * @param array $wp_rewrite
	 * @return array
	 * @since 0.5.0
	 */
	function add_rewrite_rules( $wp_rewrite ) { 
	
		if( empty( $this->opts['gallery_slug'] ) )
			$this->opts['gallery_slug']  = 'galleries';
			
		$wp_rewrite->add_rewrite_tag( "%gallery%", '([^/]+)', "ims_gallery=");
		$wp_rewrite->add_rewrite_tag( '%imslogout%', '([^/]+)', 'imslogout=');
		$wp_rewrite->add_rewrite_tag( '%imsmessage%', '([0-9]+)', 'imsmessage=');
		$wp_rewrite->add_permastruct( 'ims_gallery', $this->opts['gallery_slug'] . '/%ims_gallery%/%imspage%/', false );
		
		$new_rules[$this->opts['gallery_slug'] . "/([^/]+)/feed/(imstore)/?$"] =
		"index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . "&feed=" . $wp_rewrite->preg_index( 2 );
		$new_rules[ $this->opts['gallery_slug'] . "/([^/]+)/logout/?$"] = "index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . '&imslogout=1';
		
		foreach ( $this->pages as $id => $page ) {			
			if ( $id == 'photos' ) {
				$new_rules[$this->opts['gallery_slug'] . "/([^/]+)/page/([0-9]+)/?$"] =
				"index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . "&imspage=$id" .
				'&paged=' . $wp_rewrite->preg_index( 2 );

				$new_rules[$this->opts['gallery_slug'] . "/([^/]+)/" . $this->page_slugs[$id] . "/page/([0-9]+)/?$"] =
				"index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . "&imspage=$id" .
				'&paged=' . $wp_rewrite->preg_index( 2 );
			}
			
			$new_rules[$this->opts['gallery_slug'] . "/([^/]+)/" . $this->page_slugs[$id] . "/ms/([0-9]+)/?$"] =
			"index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . "&imspage=$id" .
			'&imsmessage=' . $wp_rewrite->preg_index( 2 );

			$new_rules[$this->opts['gallery_slug'] . "/([^/]+)/" . $this->page_slugs[$id] . "/?$"] =
			"index.php?ims_gallery=" . $wp_rewrite->preg_index( 1 ) . "&imspage=$id";
						
			if( $id == 'receipt' ){
				$new_rules["(.?.+?)/" . $this->page_slugs[$id] . "/?$"] =
				"index.php?pagename=" . $wp_rewrite->preg_index( 1 ) .  "&imspage=$id";
			}
		}
				
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		$wp_rewrite->rules["/page/?([0-9]+)/?$"] = "index.php?paged=" . $wp_rewrite->preg_index( 1 );
		
		return $wp_rewrite;
	}
	
	/**
	 * Error messages
	 *
	 * @param obj $errors
	 * @param bol $retrun
	 * @return string|null
	 * @since 3.0.0
	 */
	function error_message( $errors, $return = false ) {
		$error = '<div class="ims-message ims-error error">' . "\n";
		foreach ( $errors->get_error_messages( ) as $err )
			$error .= "<p><strong>$err</strong></p>\n";
		$error .= '</div>' . "\n";

		if ( $return ) return $error;
		else echo $error;
	}
	
	/**
	 * Fast in_array function
	 *
	 * @parm string $elem
	 * @parm array $array
	 * @return bool
	 * @since 3.0.0
	 */
	function in_array( $elem, $array ) {
		foreach ( $array as $val )
			if ( $val == $elem )
				return true;
		return false;
	}
	
	/**
	 * Format price
	 *
	 * @parm unit $price
	 * @parm string $before
	 * @parm string $after
	 * @return string
	 * @since 3.0.0
	 */
	function format_price( $price, $sym = true, $before = '', $after = '' ) {
		if ( stripos( $price, $this->sym ) !== false )
			return $price;

		if ( $this->opts['disable_decimal'] )
			$price = number_format_i18n( (double) $price );
		else $price = number_format( (double) $price, 2 );

		$char = ( $sym ) ? $this->cformat[$this->loc] : "%s";
		return sprintf( $before . $char, $price . $after );
	}
	
	/**
	 * Get memory limit
	 *
	 * @return string
	 * @since 3.1.0
	 */
	function get_memory_limit( ){
		if( !defined( 'WP_MAX_MEMORY_LIMIT' ) )
			return '256M';
		elseif( WP_MAX_MEMORY_LIMIT == false || WP_MAX_MEMORY_LIMIT == '' )
			return '256M';
		else return WP_MAX_MEMORY_LIMIT;
	}
	
	/**
	 * get post parent id
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function get_post_parent_id( $post_ID ) {
		$post = get_post( $post_ID );
		if ( !$post || is_wp_error( $post ) )
			return false;
		return (int) $post->post_parent;
	}
	
	/**
	 * Encrypt url
	 *
	 * @parm string $string
	 * @return string
	 * @since 2.1.1
	 */
	function url_decrypt( $string, $url = true ) {
		
		$decoded = '';
		$string = ( $url ) ? urldecode( $string ) : $string;
		$string = base64_decode( implode( '/', explode( '::', $string ) ) );
		
		for ( $i = 0; $i < strlen( $string ); $i++ ) {
			$char = substr( $string, $i, 1 );
			$keychar = substr( $this->key, ( $i % strlen( $this->key ) ) - 1, 1);
			$char = chr( ord( $char ) - ord( $keychar ) );
			$decoded.=$char;
		}
		
		return $decoded;
	}
}