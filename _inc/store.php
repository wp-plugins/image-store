<?php

/**
 * ImStoreFront - Fontend display
 *
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0
 */
 
class ImStoreFront extends ImStore {
	
	/**
	 * Public variables
	 */
	public $gal = false;
	public $error = false;
	public $term = false;
	public $imgurl = false;
	public $is_grid = false;
	public $user_id = false;
	public $imspage = false;
	public $success = false;
	public $message = false;
	public $is_widget = false;
	public $pricelist_id = false;
	public $cart_status = false;
	public $active_store = false;
	public $favorites_ids = false;
	public $is_taxonomy = false;
	public $post_logged_in = false;
	public $show_comments = false;
	
	public $post = array( );
	public $meta = array( );
	public $sizes = array( );
	public $subnav = array( );
	public $user_votes = array( );
	public $attachments = array( );
	public $gallery_tags = array( );
	public $shipping_opts = array( );
	
	public $galid = 0;
	public $direct = '';
	public $post_count = 0;
	public $found_posts = 0;
	public $gallery_expire = 0;
	public $favorites_count = 0;
	public $posts_per_page = 10;
	
	public $listmeta = array( 
		'colors' => array( ),
		'finishes' => array( )
	);
	
	public $image_sizes = array( 
		1 => 'preview', 
		2 => 'thumbnail', 
		3 => 'mini', 
		4 => 'mini' 
	);

	public $cart = array(
		'total' => 0,
		'items' => 0,
		'images' => array( ),
	);
	
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function ImStoreFront( ) {
		
		$this->ImStore( );
		
		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
		
		add_action( 'wp', array( &$this, 'add_hooks' ) );
		add_action( 'init', array( &$this, 'init_actions' ), 9 );
		
		add_filter( 'pre_get_posts', array( &$this, 'custom_types' ), 30, 1 );
		add_filter( 'parse_query', array( &$this, 'album_pagination' ), 20, 2 );
		add_filter( 'query_vars', array( &$this, 'add_var_for_rewrites' ), 10, 1 );
		
		//secure content 
		add_filter( 'template_redirect', array( &$this, 'secure_images' ), 1 );
		add_action( 'the_post', array( &$this, 'bypass_protected_galleries' ), -1 );
		
		add_filter( 'body_class', array( &$this, 'theme_class' ) );
		add_filter( 'ims_load_pages', array( &$this, 'deactivate_pages' ), 10, 1 );
		
		//shortcode 
		add_shortcode( 'image-store', array( &$this, 'imstore_shortcode' ) );
		
		//admin bar menu
		add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ), 99 );
		add_action( 'network_admin_menu', array( &$this, 'admin_bar_menu' ), 99 );
		
		ob_start( );
	}
	
	/**
	 * Set basic variables
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function init_actions( ){
		
		global $wp_rewrite, $user_ID;
		
		$this->user_id = $user_ID;
		$this->order = $this->opts['imgsortdirect'];
		$this->sortby = $this->opts['imgsortorder'];
			
		$this->imgurl = IMSTORE_URL . '/_img/1x1.trans.gif';
		$this->permalinks = $wp_rewrite->using_permalinks( );
				
		if ( isset( $_COOKIE[ 'ims_message_' . COOKIEHASH ] ) ) {
			$messages = array(
				'1' => __( 'Successfully added to cart', 'ims' ),
				'2' => __( 'Cart successfully updated', 'ims' ),
			);
			$this->message = $messages[ $_COOKIE[ 'ims_message_' . COOKIEHASH ] ];
		}
		
		if ( $this->opts['ims_searchable'] )
			add_filter( 'posts_where', array( &$this, 'search_images' ), 50, 2 );
			
		if( $this->user_id )
			$this->user_votes = get_user_meta( $this->user_id, '_ims_image_like' );
		
		$this->gallery_tags = apply_filters( 'ims_gallery_tags', array(
			'gallerytag' => 'div', 'imagetag' => 'figure', 'captiontag' => 'figcaption'
		), $this );
		
		if ( !empty( $this->opts['mediarss'] ) && !class_exists( 'ImStoreFeeds' ) ){
			require_once( IMSTORE_ABSPATH . '/_inc/image-rss.php' );
			$this->feeds = new ImStoreFeeds( );
		}
	}
	
	/**
	 * Initiate hooks
	 *
	 * @return void
	 * @since 3.1.6
	 */
	function add_hooks( ){
		
		$this->posts_per_page = get_query_var( 'posts_per_page' );
		
		if ( is_feed( ) && ( is_tax( 'ims_album' ) || is_tax( 'ims_tags' ) ) ) 
			add_filter( 'the_content', array( &$this, 'taxonomy_description' ) );
		
		//return if is a feed page
		if ( is_feed( ) ) 
			return;
		
		if( $this->opts['favorites'] )
			$this->set_favorites( );
		
		//add_filter( 'protected_title_format', array( &$this, 'remove_protected' ) );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts_styles' ) );
		add_shortcode( 'ims-gallery-content', array( &$this, 'gallery_shortcode' ) );
		
		if ( is_tax( 'ims_album' ) || is_tax( 'ims_tags' ) ){
			
			global $wp_query;
			$this->is_taxonomy = true;
			$this->term = $wp_query->get_queried_object( );
			
			add_filter( 'template_include', array( &$this, 'taxonomy_template' ) );
			add_shortcode( 'ims-taxonomy', array( &$this, 'imstore_tax_shortcode' ) );
		} 
		
		if( is_search( ) ) 
			add_filter( 'the_content', array( &$this, 'taxonomy_content' ) );
			
		if( is_page() )
			$this->imspage = get_query_var( 'imspage' );

		if( is_singular( ) || is_front_page( ) ){
							
			add_action( 'template_redirect', array( &$this, 'ims_init' ), 1 );
			add_action( 'template_redirect', array( &$this, 'post_actions' ), 2 );
			add_action( 'template_redirect', array( &$this, 'redirect_actions' ), 0 );
			
			add_filter( 'ims_gateways', array( &$this, 'add_gateways' ), 10 );
			add_filter( 'ims_localize_js', array( &$this, 'add_gallerific_js_vars' ), 0 );
		}
		
		if ( is_singular( 'ims_image' ) ) {
			add_filter( 'the_content', array( &$this, 'ims_image_content' ), 10 );
			add_filter( 'single_template', array( &$this, 'get_image_template' ), 10, 1 );
			add_filter( 'get_next_post_sort', array( &$this, 'adjacent_post_sort' ), 20 );
			add_filter( 'get_next_post_where', array( &$this, 'adjacent_post_where' ), 20 );
			add_filter( 'get_previous_post_sort', array( &$this, 'adjacent_post_sort' ), 20 );
			add_filter( 'get_previous_post_where', array( &$this, 'adjacent_post_where' ), 20 );
		}
		
		$allow = apply_filters('ims_activate_gallery_hooks', false );
			
		if ( is_singular( 'ims_gallery' ) || $allow ) { 
			
			add_filter( 'comments_array', array( &$this, 'hide_comments' ), 1, 1 ); 
			add_filter( 'comments_open', array( &$this, 'close_comments' ), 1, 1 );
			add_filter( 'redirect_canonical', array( &$this, 'redirect_canonical' ), 20, 2 );
			add_filter( 'single_template', array( &$this, 'change_gallery_template' ), 1 );
			add_filter( 'ims_after_pricelist_page', array( &$this, 'after_pricelist' ), 10, 2 );
	
			if ( !$this->imspage && $page = get_query_var( 'imspage' ) )
				$this->imspage = $page;
				
			else if ( !$this->imspage && reset( $this->pages ) )
				$this->imspage = key( $this->pages );
		}
		
		require_once( IMSTORE_ABSPATH . '/_store/shortcode.php' );
		$shortcode = new ImStoreShortCode( );
	}
	
	/**
	 * redirect actions
	 *
	 * @return void
	 * @since 3.1.7
	 */
	function redirect_actions( ){
		
		if ( isset( $_POST['ims-cancel-checkout'] ) ) {
			if( is_singular( 'ims_gallery') )
				wp_redirect( $this->get_permalink( 'shopping-cart', false ) );
			else wp_redirect( get_permalink( ) );
			die( );
		}
		
		if ( get_query_var( 'imslogout' ) ) {
			$this->logout_ims_user( );
			wp_redirect( get_permalink( get_option( 'ims_page_secure' ) ) );
			die( );
		}
	}
	
	/**
	 * Allow customer role to access images 
	 * without loging into each post
	 *
	 * @return void
	 * @since 3.0.5
	 */
	function bypass_protected_galleries( &$post ){
			
		if( !is_singular( 'ims_gallery' ) || empty( $post->ID )  )
			return;
					 
		global $wp_query;
		if( $wp_query->queried_object->ID != $post->ID || $post->post_type != 'ims_gallery' )
			return;
			
		if( current_user_can( 'administrator' ) ){
			$post->post_password = false;
			wp_cache_set( $post->ID, $post, 'posts' );
			return;	
		}
		
		if( !current_user_can( $this->customer_role ) )
			return;			
		
		if( !isset( $this->meta['_ims_customer'][0] ) )
			return;
		
		global $user_ID;	
		$meta = (array) maybe_unserialize( $this->meta['_ims_customer'][0] );

		if( $user_ID && in_array( $user_ID, 	$meta ) )
			$post->post_password = false;
	}
	
	/**
	 * Return 404 for secure images
	 * if the user is not loged in
	 *
	 * @return void
	 * @since 3.0.5
	 */
	function secure_images( ) {

		if ( !is_singular( 'ims_image' ) )
			return;
			
		global $post, $wp_version, $wp_hasher, $wp_query, $user_ID;
		
		$this->galid 	= $post->post_parent;
		$this->gal 		= get_post( $post->post_parent );
		$this->meta 	= get_post_custom( $this->galid );
		
		if ( $this->active_store = ( $this->opts['store'] && !get_post_meta( $post->post_parent, '_dis_store', true ) ) ) 
			$this->load_cart( ); 
		
		if( empty( $this->gal->post_password ) || current_user_can( 'administrator' ) )
			return;
		
		//check for login customer role
		if( current_user_can( $this->customer_role ) && isset( $this->meta['_ims_customer'][0] ) ){
			$meta = ( array ) maybe_unserialize( $this->meta['_ims_customer'][0] );
			if( $user_ID && in_array( $user_ID, 	$meta  ) )
				return;
		}
		
		// check for post cookie
		if ( empty( $_COOKIE['wp-postpass_' . COOKIEHASH] ) )
			$wp_query->set_404( );
			
		else if ( version_compare( $wp_version, '3.4', '>=' ) ){
			
			if ( empty( $wp_hasher ) ) {
				require_once( ABSPATH . 'wp-includes/class-phpass.php');
				$wp_hasher = new PasswordHash( 8, true );
			} $denied = !$wp_hasher->CheckPassword( $this->gal->post_password, $_COOKIE['wp-postpass_' . COOKIEHASH] );
		
		} else $denied = $this->gal->post_password !== $_COOKIE['wp-postpass_' . COOKIEHASH];
				
		if ( $denied ) status_header( 404 );
	}
	
	/**
	 * Populate object variables
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function ims_init( ) {
		
		global $post;
		
		$this->gal = $post; 
		
		if( is_singular( 'ims_image' ) )
			$this->gal = get_post( $post->post_parent );
		
		$this->galid = $this->gal->ID;
		$this->meta = get_post_custom( $this->galid );
		$this->gallery_expire = strtotime( $post->post_expire );	
		
		$this->is_grid = $this->in_array( $this->imspage, array( 'favorites', 'photos' ) );
		$this->post_logged_in =  isset( $_COOKIE['wp-postpass_' . COOKIEHASH] );
		$this->show_comments = $this->in_array( $this->imspage, array( 'photos', 'slideshow' ) );
		$this->active_store = ( $this->opts['store'] && empty( $this->meta['_dis_store'][0] ) );
		
		//clear order data
		if( $this->imspage != 'receipt'  && 
			isset( $_COOKIE['ims_orderid_' . COOKIEHASH] ) &&
			get_post_status(  $_COOKIE['ims_orderid_' . COOKIEHASH] ) != 'draft' ) {
			setcookie( 'ims_orderid_' . COOKIEHASH, false, ( time(  ) - 315360000 ), COOKIEPATH, COOKIE_DOMAIN );
		}  
		
		//set cart data if store is active
		if ( $this->active_store ) 
			$this->load_cart( );
		
		//remove pages cart data if store is not active
		 if( !$this->active_store ) {
			unset( $this->pages['price-list'] );
			unset( $this->pages['shopping-cart'] );
		}
		
		do_action( 'ims_gallery_init', $this );
	}
	
	/**
	 * Request post actions
	 *
	 * @return void
	 * @since 3.1.7
	 */
	function post_actions( ) {
		
		do_action( 'ims_before_post_actions', $this );
		
		if( empty( $_POST ) ) 
			return;
		
		//add images to cart
		if ( isset( $_POST['ims-add-to-cart'] ) )
			$this->add_to_cart( );
		
		//upate cart
		if ( isset( $_POST['ims-apply-changes'] ) )
			$this->update_cart( );
		
		//upate cart
		if ( isset( $_POST['ims-enotification'] ) )
			$this->imspage = 'checkout';
		
		//submit notification order
		if ( isset( $_POST['ims-enotice-checkout' ] ) )
			$this->validate_user_input( );
		
		$this->is_grid = $this->in_array( $this->imspage, array( 'favorites', 'photos' ) );

		do_action( 'ims_after_post_actions', $this );
	}
	
	/**
	 * Add gateway information
	 *
	 * @return void
	 * @since 3.2.5
	 */
	function add_gateways( $gateways ){
		
	 $path = IMSTORE_ABSPATH . "/_inc/gateways";
	 
	  return array_merge( array( 
			'paypalprod' => array(
				'include' => "{$path}/paypal.php",
				'class' => 'ImStoreCartPayPal',
				'name' => __( 'PayPal', 'ims' ),
				'url' => 'https://www.paypal.com/cgi-bin/webscr',
			),
			'paypalsand' => array(
				'include' => "{$path}/paypal.php",
				'class' => 'ImStoreCartPayPal',
				'name' => __( 'PayPal Sandbox', 'ims' ),
				'url' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			),
			'googleprod' => array(
				'include' => "{$path}/google.php",
				'class' => 'ImStoreCartGoogle',
				'name' => __( 'Google Checkout', 'ims' ),
				'url' => 'https://checkout.google.com/api/checkout/v2/checkoutForm/Merchant/'. $this->opts['googleid'],
			),
			'googlesand' => array(
				'include' => "{$path}/google.php",
				'class' => 'ImStoreCartGoogle',
				'name' => __( 'Google Checkout Sandbox', 'ims'),
				'url' => 'https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/'. $this->opts['googleid'],
			),
			'pagsegurosand' => array(
				'include' => "{$path}/pagseguro.php",
				'class' => 'ImStoreCartPagSeguro',
				'name' => __( 'PagSeguro Sandbox', 'ims'),
				'url' => false,
			),
			'pagseguroprod' => array(
				'include' => "{$path}/pagseguro.php",
				'class' => 'ImStoreCartPagSeguro',
				'name' => __( 'PagSeguro', 'ims'),
				'url' => 'https://pagseguro.uol.com.br/v2/checkout/payment.html',
			),
			'wepayprod' => array(
				'url' => false,
				'class' => 'ImStoreCartWePay',
				'include' => "{$path}/wepay.php",
				'name' => __( 'WePay', 'ims' ),
			),
			'wepaystage' => array(
				'url' => false,
				'class' => 'ImStoreCartWePay',
				'include' => "{$path}/wepay.php",
				'name' => __( 'WePay Stage', 'ims' )
			)
		 ), $gateways );
	}
	
	/**
	 * Load cart classes
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function load_cart( ){
		
		global $ImStoreCart;
		
		$this->get_price_list( );
		$this->listmeta = get_post_meta( $this->pricelist_id, '_ims_list_opts', true );
				
		//get list of gateways
		$this->gateways = apply_filters( 'ims_gateways', array(
			'enotification' => array(
				'include' => false,
				'url' => get_permalink( ),
				'name' => __( 'Checkout', 'ims' ),
			),
		));
		
		include_once( IMSTORE_ABSPATH . '/_inc/cart.php' );
		
		$ImStoreCart = new ImStoreCart( );
		$this->cart = $ImStoreCart->setup_cart( );
		
		$ImStoreCart->sizes = $this->sizes;
		$ImStoreCart->gallery_id = $this->galid;
		$ImStoreCart->listmeta = $this->listmeta;
				
		/*load gateways: to add new gateway add a new field using "ims_setting_fields" 
		and gateway informaiton using "ims_gateways" field key must match gateway key */
		foreach( (array) $this->gateways as $key => $gateway ){
			if( !empty( $this->opts['gateway'][$key] ) && !empty($gateway['include']) ){
				if( file_exists( $gateway['include'] ) && !class_exists( $gateway['class'] ) ) {
					include_once( $gateway['include'] ); new $gateway['class']( );
				}
			}	
		}
		
		//if( $this->imspage == 'shopping-cart' )
		add_filter( 'ims_store_cart_actions', array( $ImStoreCart, 'cart_actions' ), 50, 1 );
	}
	
	/**
	 * Add items to cart
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function add_to_cart( ) {
		
		global $ImStoreCart;
		
		if( !$ImStoreCart->verify_request( $_POST ) )
			return $this->error = $ImStoreCart->error;
		
		$this->cart = $ImStoreCart->add_to_cart( $_POST );
		
		if( $ImStoreCart->error )
			return $this->error = $ImStoreCart->error;
		
		global $paged;
		
		setcookie( 'ims_message_' . COOKIEHASH, 1, ( time(  ) + 4 ), COOKIEPATH, COOKIE_DOMAIN );	
		wp_redirect( $this->get_permalink( $this->imspage, false, $paged ) );
		
		die( );
	}
	
	/**
	 * update cart information
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function update_cart( ) {
		
		global $ImStoreCart;
		$ImStoreCart->update_cart( $_REQUEST );
				
		if( $ImStoreCart->error )
			return $this->error = $ImStoreCart->error;
		
		setcookie( 'ims_message_' . COOKIEHASH, 2, ( time(  ) + 4 ), COOKIEPATH, COOKIE_DOMAIN );		
		wp_redirect( $this->get_permalink( $this->imspage, false ) );
		
		die( );	
	}
	
	/**
	 * Validate user input from
	 * shipping information
	 *
	 * @since 1.0.2
	 * return array|errors
	 */
	function validate_user_input( ) {
		
		if( empty( $_POST ) )
			return;
		
		if ( !wp_verify_nonce( $_POST["_wpnonce"], "ims_submit_order" ) ) {
			
			wp_redirect( $this->get_permalink( 'checkout', false ) );
			die( );
		}
		
		$this->imspage = 'checkout';
		
		foreach ( $this->opts['checkoutfields'] as $key => $label ) {
			if ( $this->opts['required_' . $key] && empty( $_POST[$key] ) )
				$this->error .= sprintf( __( 'The %s is required.', 'ims' ), $label ) . "<br />";
		}
		
		global $ImStoreCart;
		
		if ( !empty( $_POST['user_email'] ) && !is_email( $_POST['user_email'] ) )
			$this->error .= __( 'Wrong email format.', 'ims' ) . "<br />";

		if ( empty( $this->cart['items'] ) || empty( $ImStoreCart->orderid ) || $ImStoreCart->status != 'draft' )
			$this->error .= __( 'Your shopping cart is empty.', 'ims' );

		if ( !empty( $this->error ) )
			return;
		
		foreach( array(  'user_email' => 'payer_email', 'first_name' => 'first_name', 'ims_address' => 'address_street',
		 'last_name' => 'last_name', 'ims_phone' => 'ims_phone',  'ims_zip' => 'address_zip', 'ims_state' => 'address_state',  
		 'ims_city' => 'address_city', 'address_country', 'instructions' => 'instructions' ) as $field => $cart_key ){
			if( !empty( $_POST[ $field ] ) )	 $ImStoreCart->data[ $cart_key ] = $_POST[ $field ];
		}
				
		$ImStoreCart->data['mc_gross'] = $this->cart['total'];
		$ImStoreCart->data['custom'] = $ImStoreCart->orderid;
		
		$ImStoreCart->data['num_cart_items'] = $this->cart['items'];
		$ImStoreCart->data['mc_currency'] = $this->opts['currency'];
		$ImStoreCart->data['payment_status'] = __( 'Pending', 'ims' );
		$ImStoreCart->data['method'] = __( 'Email Notification', 'ims' );
		
		$ImStoreCart->data['txn_id'] = sprintf( "%08d", $ImStoreCart->orderid );
		$ImStoreCart->data['payment_gross'] = number_format( $this->cart['total'], 2 );
		
		$data = wp_parse_args( $ImStoreCart->data ); 
		$ImStoreCart->data = array_intersect_key( $ImStoreCart->data, $data );
		
		$ImStoreCart->checkout( );
	}
	
	/**
	 * Populate favorites variables
	 *
	 * @return void
	 * @since 3.1.7
	 */
	function set_favorites( ){
				
		if ( is_user_logged_in( ) )
			$this->favorites_ids = trim( get_user_meta( $this->user_id, '_ims_favorites', true ), ', ' );
			
		elseif ( isset( $_COOKIE['ims_favorites_' . COOKIEHASH] ) )
			$this->favorites_ids = trim( $_COOKIE['ims_favorites_' . COOKIEHASH], ', ' );
		
		if ( $this->favorites_ids ){
			$ids = array_unique( explode( ',', $this->favorites_ids ) );
			$this->favorites_count = count( $ids );
			$this->favorites_ids = implode( ',', $ids );
		}
	}
	
	/**
	 * Add theme name body class
	 *
	 * @param array $classes
	 * @return array
	 * @since 3.2.1
	 */
	function theme_class( $classes ){
		if( $them_name = get_option( 'template' ) )
			$classes[] = $them_name;
		
		if( is_singular('ims_image') )
			$classes[] = 'attachment';
			
		return array_unique( $classes );
	}
	
	/**
	 * Add rewrite vars
	 *
	 * @param array $vars
	 * @return array
	 * @since 0.5.0
	 */
	function add_var_for_rewrites( $vars ) {
		array_push( $vars, 'imspage', 'imslogout' );
			return $vars;
	}
	
	/**
	 * Display albums(taxonomy)
	 *
	 * @param obj $query
	 * @return void
	 * @since 3.0.0
	 */
	function custom_types( &$query ) {
		global $wp_query;
		
		//only affect the main query
		if( $wp_query !== $query )
			return;
			
		if( !get_query_var( 'ims_tags' ) && !get_query_var( 'ims_album' ) )
			return;
		
		if( !get_query_var( 'post_type' ) )
			$query->set ( 'post_type', get_post_types( array( 'publicly_queryable' => true ) ) );		
		
		add_filter( 'posts_where', array( &$this, 'exclude_secured' ) ); 
	}
	
	/**
	 * Add paging option to albums
	 *
	 * @param $query object
	 * @return object
	 * @since 3.0.0
	 */
	function album_pagination( $query ) {
		global $wp_query;	
		
		//only affect the main query
		if( $wp_query !== $query )
			return $query;
			
		if ( ( !is_tax( 'ims_album' )  && !is_tax( 'ims_tags' ) ) )
			return $query;
		
		if ( is_tax( 'ims_album' ) )
			$query->set ( 'posts_per_page', $this->opts['album_per_page'] );
		
		if ( is_tax( 'ims_tags' ) )
			$query->set ( 'posts_per_page', $this->opts['tag_per_page'] );
			
		return $query;
	}
	
	/**
	 * Exclude secure galleries from 
	 * feed and taxonomy pages
	 *
	 * @param $where string
	 * @return string
	 * @since 3.2.1
	 */
	function exclude_secured( $where ){ 
		if( $this->opts['album_level'] ){
			global $wp_query, $wpdb;
			$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID IN ( SELECT tr.object_id FROM {$wpdb->term_relationships} tr 
			JOIN {$wpdb->term_taxonomy} tt ON ( tr.term_taxonomy_id = tt.term_taxonomy_id ) WHERE tt.term_id IN ( %d ) )", $wp_query->queried_object_id );
		}
		return $where .= " AND post_password = '' ";
	}
	
	/**
	 * Stop canonical redirect for
	 * Custom permalink structure
	 *
	 * @param string $redirect_url
	 * @param string $requested_url
	 * @return void
	 * @since 0.5.0
	 */
	function redirect_canonical( $redirect_url, $requested_url ) {
		if ( strpos( $requested_url, "/page/" ) )
			return false;
		return $redirect_url;
	}
	
	/**
	 * Load frontend js/css
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function load_scripts_styles( ) {
		
		if ( $this->opts['stylesheet'] )
			wp_enqueue_style( 'imstore', IMSTORE_URL . '/_css/imstore.css', false, $this->version, 'all' );
		
		wp_enqueue_script( 'sonar', IMSTORE_URL . '/_js/sonar.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'ims-touch', IMSTORE_URL . '/_js/touch.jquery.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'ims-xmslider', IMSTORE_URL . '/_js/xmslider.jquery.js', array( 'jquery' ), $this->version, true );
		wp_enqueue_script( 'imstore', IMSTORE_URL . '/_js/imstore.js', array( 'jquery', 'sonar' ), $this->version, true );
		
		if( is_singular( ) || is_front_page( ) ){
			
			if ( $this->opts['stylesheet'] )
				wp_enqueue_style( 'ims-single', IMSTORE_URL . '/_css/single.css', false, $this->version, 'all' );
			wp_enqueue_script( 'galleriffic', IMSTORE_URL . '/_js/galleriffic.js', array( 'jquery' ), $this->version, true );
		}
		
		if( $this->is_taxonomy && $this->opts['stylesheet'] )
			wp_enqueue_style( 'ims-single', IMSTORE_URL . '/_css/single.css', false, $this->version, 'all' );
		
		if( $this->opts['widgettools'] || ( $this->opts['store'] && is_singular( )) ){
			
			if ( $this->opts['stylesheet'] )
				wp_enqueue_style( 'imstore-store', IMSTORE_URL . '/_css/store.css', false, $this->version, 'all' );
			wp_enqueue_script( 'imstore-store', IMSTORE_URL . '/_js/store.js', array( 'jquery' ), $this->version, true );
		}	
		
		$localize = array( 
			'is_logged_in' => is_user_logged_in( ),
			'imstoreurl' => IMSTORE_ADMIN_URL,
			'wplightbox' => $this->opts['wplightbox'],
			'singin' =>__( 'Please, sign into your account to vote.' , 'ims' ),
			'ajaxnonce' => wp_create_nonce( "ims_ajax_favorites" ),
		);
		
		wp_localize_script( 'imstore', 'imstore', apply_filters( 'ims_localize_js', $localize ) );
	}
	
	/**
	 * Remove "protected"
	 * from gallery title
	 *
	 * @param string $title
	 * @return string
	 * @since 2.0.4
	 */
	function remove_protected( $title ) {
		global $post;
		if ( $post->post_type == 'ims_gallery' )
			return $post->post_title;
		return $title;
	}
	
	/**
	 * Remove page base on options
	 *
	 * @param array $pages 
	 * @return array
	 * @since 3.2.1
	 */
	function deactivate_pages( $pages ){
		
		if( !$this->opts['photos'] )
			unset( $pages['photos'] );
			
		if( !$this->opts['favorites'] )
			unset( $pages['favorites'] );
			
		if( !$this->opts['slideshow'] )
			unset( $pages['slideshow'] );
			
		return $pages;
	}
	
	/**
	 * Load gallerific variables
	 * only if they are required
	 *
	 * @param array $vars
	 * @return void
	 * @since 3.0.0
	 */
	function add_gallerific_js_vars( $vars ) {
		
		return array_merge( $vars, array(
			'galleriffic' => true,
			'numThumbs' => $this->opts['numThumbs'],
			'autoStart' => $this->opts['autoStart'],
			'playLinkText' => $this->opts['playLinkText'],
			'pauseLinkTex' => $this->opts['pauseLinkTex'],
			'prevLinkText' => $this->opts['prevLinkText'],
			'nextLinkText' => $this->opts['nextLinkText'],
			'closeLinkText' => $this->opts['closeLinkText'],
			'maxPagesToShow' => $this->opts['maxPagesToShow'],
			'slideshowSpeed' => $this->opts['slideshowSpeed'],
			'transitionTime' => $this->opts['transitionTime'],
			'nextPageLinkText' => $this->opts['nextPageLinkText'],
			'prevPageLinkText' => $this->opts['prevPageLinkText'],
		) );
		
	}
		
	/* Locate template file
	 *
	 * @param array $template
	 * @return string
	 * @since 3.2.1
	 */
	function locate_template( $templates ){
		foreach( $templates as $file ){
			 foreach( array( STYLESHEETPATH, TEMPLATEPATH, IMSTORE_ABSPATH . '/theme' ) as $path ){
				if( $file && file_exists( "{$path}/{$file}" ) )
					return "{$path}/{$file}";
			}
		}
		return false;
	}
	
	/**
	 * Change single gallery template
	 *
	 * @parm string $templates
	 * @return string
	 * @since 2.0.4
	 */
	function change_gallery_template( $template ) {
		global $wp_query;
		$type = $wp_query->get_queried_object( )->post_type;
		
		$templates = array(
			$this->opts['gallery_template'],
			"single-{$type}-{$this->imspage}.php",
			"single-{$type}.php",
			"single.php",
			"index.php"
		);
		
		if( $found = $this->locate_template( $templates ) )
			return $found;
			
		return $template;
	}
	
	/* Redirect single image templage
	 *
	 * @return string
	 * @since 3.0.0
	 */
	function get_image_template( $template ) {
		
		$templates = array(
			'single-ims-image.php', 
			'ims-image.php', 
			'ims_image.php', 
			'image.php', 
		);
		
		if( $found = $this->locate_template( $templates ) )
			return $found;
			
		return $template;
	}
	
	/* Redirect taxonomy template
	 * to display album galleries
	 *
	 * @param string $template
	 * @return string
	 * @since 2.0.0
	 */
	function taxonomy_template( $template ) {
		
		$user_defined = $this->opts['album_template'];
		
		if( is_tax( 'ims_tags' ) )
			$user_defined = $this->opts['tag_template'];
		
		$templates = array( 
			$user_defined ,
			"taxonomy-" . $this->term->taxonomy . ".php", 
			"taxonomy-" . str_replace( '_', '-', $this->term->taxonomy ) . ".php", 
			"taxonomy.php","page.php", "single.php", "index.php" 
		);
		
		if( $found = $this->locate_template( $templates ) ){
			if( !preg_match( '/(taxonomy|archive|tag)(.+)?\.php$/i', $found ) )
				add_filter( 'loop_start', array( &$this, 'empty_query' ), 10 );
			else add_filter( 'the_content', array( &$this, 'taxonomy_content' ) );
			return $found;
		}
		return $template;
	}
	
	/**
	 * Hide comments from store pages
	 * except photos and slideshow
	 *
	 * @param array $comments
	 * @return array
	 * @since 3.0.0
	 */
	function hide_comments( $comments ) {
		if ( $this->show_comments )
			return $comments;
		return array( );
	}
	
	/**
	 * Remove comments from albums
	 *
	 * @param bool $bool
	 * @return array
	 * @since 3.0.0
	 */
	function close_comments( $bool ) {
		if ( $this->show_comments )
			return $bool;
		return false;
	}
	
	/**
	 * Display list notes
	 *
	 * @param string $output
	 * @param int $list_id
	 * @return string
	 * @since 3.0.9
	 */
	function after_pricelist( $output, $list_id ) {
		$post = get_post( $list_id );
		if ( $post->post_excerpt )
			$output .= '<div class="ims-list-notes">' . $post->post_excerpt . '</div>';
		return $output;
	}
	
	/**
	 * Add visted count
	 *
	 * @since 3.1.0
	 * return void
	 */
	function visited_gallery( ) {
		if ( isset( $_COOKIE['ims_gal_' . $this->galid . '_' . COOKIEHASH] ) )
			return;
		setcookie( 'ims_gal_' . $this->galid . '_' . COOKIEHASH, true, 0, COOKIEPATH, COOKIE_DOMAIN );
		update_post_meta( $this->galid, '_ims_visits', get_post_meta( $this->galid, '_ims_visits', true ) + 1);
	}
	
	/**
	 * Display the secure section
	 * of the image store
	 *
	 * @param obj $errors
	 * @return string
	 * @since 3.0.0
	 */
	function display_secure( ) {

		$message = '';
		$errors = $this->validate_user( );
		
		if ( is_wp_error( $errors ) )
			$message = $this->error_message( $errors, true );
		
		if ( empty( $_COOKIE['wp-postpass_' . COOKIEHASH] )
		|| empty($_COOKIE['ims_galid_' . COOKIEHASH] ) )
			return $message .= $this->get_login_form( );
		
		if ( isset( $_COOKIE['wp-postpass_' . COOKIEHASH] ) ) {
			
			wp_redirect( get_permalink( $_COOKIE['ims_galid_' . COOKIEHASH] ) );
			die( );
		}
	}
	
	/**
	 * Get encrypted image url
	 *
	 * @param int $id
	 * @param int $size
	 * @since 3.0.0
	 * return string
	 */
	function get_image_url( $id, $size = 1 ) {
		
		//backwards compatibilty
		if ( is_array( $id ) && isset( $id['sizes']['thumbnail']['path'] ) )
			$id = $this->get_id_from_path( $id['sizes']['thumbnail']['path'] );
		elseif ( isset( $id->ID ) ) $id = $id->ID;
			
		$url = "$id:$size";
		
		//add watermark
		if ( $this->opts['watermark'] && $size != 2 && $size != 3 )
			$url .= ":1";
			
		$imgurl = $this->baseurl . $this->url_encrypt( $url );
		return apply_filters( 'ims_image_url', $imgurl, $id, $size );
	}
	
	/**
	 * User login function
	 *
	 * @return object
	 * @since 0.5.0
	 */
	function validate_user( ) {
	
		//try to login first
		if ( empty( $_POST ) || ( isset( $_REQUEST["login-imstore"] ) 
		&& !wp_verify_nonce( $_REQUEST["_wpnonce"], 'ims_access_form' ) ) )
			return false;
		
		$errors = new WP_Error( );
		if ( empty( $_REQUEST["ims-galbox-" . $this->galid] ) )
			$errors->add( 'emptyid', __( 'Please enter a gallery id. ', 'ims' ) );
		
		if ( empty( $_REQUEST["ims-pwdbox-" . $this->galid] ) )
			$errors->add( 'emptypswd', __( 'Please enter a password.', 'ims' ) );
		
		if ( !empty( $errors->errors ) )
			return $errors;
		
		$pass = $_REQUEST["ims-pwdbox-" . $this->galid];
		$galid = $_REQUEST["ims-galbox-" . $this->galid];
		
		$post = get_posts( array(
			'meta_value' => $galid,
			'post_type' => 'ims_gallery',
			'meta_key' => '_ims_gallery_id',
		)); $gal = isset( $post[0] ) ? $post[0] : $post;
		
		if ( empty( $gal->post_password ) || $gal->post_password !== $pass ) {
			
			$errors->add( 'nomatch', __( 'Gallery ID or password is incorrect. Please try again. ', 'ims' ) );
			return $errors;
		
		} elseif ( $gal->post_password === stripslashes( $pass ) ) {
			
			global $wp_version;
			$cookie_val = $gal->post_password;
			
			if ( version_compare( $wp_version, '3.4', '>=' ) ) {
				global $wp_hasher;
				if ( empty( $wp_hasher ) ) {
					require_once( ABSPATH . 'wp-includes/class-phpass.php');
					$wp_hasher = new PasswordHash( 8, true );
				}
				$cookie_val = $wp_hasher->HashPassword( stripslashes( $gal->post_password ) );
			}
			
			setcookie( 'ims_galid_' . COOKIEHASH, $gal->ID, 0, COOKIEPATH, COOKIE_DOMAIN );
			setcookie( 'wp-postpass_' . COOKIEHASH, $cookie_val, 0, COOKIEPATH, COOKIE_DOMAIN );
			
			update_post_meta( $gal->post_id, '_ims_visits', get_post_meta( $gal->ID, '_ims_visits', true ) + 1 );
			wp_redirect( get_permalink( $gal->ID ) );
			die( );
		}
	}
	
	/**
	 * Get imstore permalink
	 *
	 * @param string $page
	 * @param bool $encode
	 * @param unit $paged
	 * @param unit $postid
	 * @since 0.5.0
	 * return void
	 */
	function get_permalink( $page = '', $encode = true, $paged = 0, $postid = false ) {
	
		$link = '';
		if ( $this->permalinks && !is_preview( ) ) {
			
			if( isset( $this->pages[$page] ) )
				$link = "/" . $this->page_slugs[$page];
			
			if ( $page == 'logout' )
				$link .= "/". $page;
			
			if ( $paged )
				$link .= '/page/' . $paged;
		
		} else {
			
			if ( is_front_page( ) )
				$link .= '?page_id=' . $this->page_front;

			if ( $page == 'logout' )
				$link .= '&imslogout=1';
			elseif ( $page )
				$link .= '&imspage=' . $page;

			if ( is_preview( ) )
				$link .= '&preview=true';
		}
		
		if ( $encode ) 
			return apply_filters( 'ims_permalink', trim( get_permalink( $postid ), '/' ) . htmlspecialchars( $link ) , $page, $encode );
		else return apply_filters( 'ims_permalink', trim( get_permalink( $postid ), '/' ) . $link , $page, $encode );
	}
	
	/**
	 * Display taxonomy content
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function imstore_tax_shortcode( ){

		if( empty( $this->posts ) )
			return;
		
		global $post;
		
		$css = " ims-gallery" ;
		$css .= " {$this->term->taxonomy}";
		$css .= " ims-{$this->term->slug}" ;
		$css .= " ims-cols-" . $this->opts['columns'];
		
		$content = '<div id="ims-mainbox" class="ims-tax' . $css . '" >';
		foreach( $this->posts as $post )
			$content .= $this->taxonomy_content( );
		
		$nav = '<div class="ims-navigation">';
		$nav .= '<div class="nav-previous">' . get_previous_posts_link( __( '<span class="meta-nav">&larr;</span> Previous galleries', 'ims')) . '</div>';
		$nav .= '<div class="nav-next">' . get_next_posts_link( __( 'More galleries <span class="meta-nav">&rarr;</span>', 'ims')) . '</div>';
		$nav .= '</div><div class="ims-cl">';
		
		$content .= apply_filters( 'ims_taxonomy_navigation', $nav, $this->term );
		return  $content .= '</div><!--#ims-mainbox-->';
	}
	
	/**
	 * Core fuction display store
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function imstore_shortcode( $atts ) {
		
		if ( !is_singular( ) )
			return false;
		
		$atts = wp_parse_args( $atts, array(
			'all' => false, 	'list' => false, 'cart' => false, 'count' => false,
			'album' => false, 'secure' => false, 'favorites' => false,
		) ) ;
		
		extract( $atts );
		
		if ( $secure ):
		
			return $this->display_secure( $atts );
		
		elseif ( is_numeric( $list ) ) :
			
			$this->pricelist_id = $list;
			$this->imspage = 'price-list';
			$this->shipping_opts = $this->get_option( 'ims_shipping_options' );
			$this->sizes = get_post_meta( $this->pricelist_id, '_ims_sizes', true );
			$this->listmeta = get_post_meta( $this->pricelist_id, '_ims_list_opts', true );
			
			return $this->gallery_shortcode( );
		
		elseif ( $favorites ) :
		
			$this->get_favorite_images( );
			return  $this->display_galleries( );
		
		elseif ( $cart ) :
			
			if( !$this->opts['store'] )
				return;
				
			if ( empty( $this->imspage ) )
				$this->imspage = 'shopping-cart';
				
			$this->opts['widgettools'] = true;
			return $this->gallery_shortcode( );
		
		else:
			
			$this->is_taxonomy = true;
			$this->get_galleries( $atts );
			return $this->display_galleries( );
			
		endif;
	}
	
	/**
	 * Display gallery
	 *
	 * @return void
	 * @since 3.1.0
	 */
	function gallery_shortcode( ) {
	
		$css = ( $this->opts['widgettools'] ) ? 'ims-widget-s' : '';
		
		if( isset( $this->pages[$this->imspage] ) )
			$css .= "ims-" . sanitize_title( $this->pages[$this->imspage] );
		
		$output = '<div id="ims-mainbox" class="' . $css . '" >';
		
		if ( !$this->opts['widgettools'] ) 
			$output .= $this->store_nav( );
		
		$output .= '<div class="ims-labels">';
		if( $this->gallery_expire > 0 ) 
			$output .= '<span class="ims-expires">' . __( "Expires: ", 'ims' ) . date_i18n( $this->dformat, $this->gallery_expire ) . '</span>';
		$output .= '</div><!--.ims-labels-->';
		
		if ( !$this->opts['widgettools'] ) 
			$output .= $this->store_subnav( );
		
		$mcss = '';
		if ( $this->error )
			$mcss = ' ims-error';
		if ( $this->message )
			$mcss = ' ims-success';
		
		$output .= '<div class="ims-innerbox">';
		$output .= '<div class="ims-message' . $mcss . '">' . $this->message . $this->error . '</div>';

		$output .= apply_filters( 'ims_before_page', '', $this->imspage );
		
		switch ( $this->imspage ) {
			
			case 'slideshow':
			
				$this->get_gallery_images( );
				include( apply_filters( 'ims_slideshow_path', IMSTORE_ABSPATH . '/_store/slideshow.php' ) );
				
				break;
			
			case 'checkout':
				include( apply_filters( 'ims_checkout_path', IMSTORE_ABSPATH . '/_store/checkout.php' ) );
				break;
				
			case 'shopping-cart':
			
				global $ImStoreCart;
				include( apply_filters( 'ims_cart_path', IMSTORE_ABSPATH . '/_store/cart.php' ) );
				break;
			
			case "receipt":
				
				include( apply_filters( 'ims_receipt_path', IMSTORE_ABSPATH . '/_store/receipt.php' ) );
				break;
			
			case 'price-list':

				include( apply_filters( 'ims_pricelist_path', IMSTORE_ABSPATH . '/_store/price-list.php' ) );
				break;
			
			case "favorites":
			
				$this->get_favorite_images( );
				$output .= $this->display_galleries( );
				break;
			
			default:
			
				$this->get_gallery_images( );
				$output .= $this->display_galleries( );
		}
		
		$output .= apply_filters( 'ims_after_page', '', $this->imspage );
		$output .= '</div><!--.ims-innerbox-->';
		
		$output .= $this->display_order_form( );
		return $output .= '</div><!--#ims-mainbox-->';
	}
	
	/* Get image tag
	 *
	 * @param int $id
	 * @param array $data
	 * @param int $sz size code
	 * @return string
	 * @since 3.1.7
	 */
	function image_tag( $imageid, $data, $sz = 2 ){
		
		if( !is_array( $data) || empty( $imageid ) || !isset( $this->image_sizes[$sz] ) )
			return;
			
		$dimentions = '' ; 	
		$size = $this->image_sizes[$sz];
		$enc = $this->url_encrypt( $imageid );
		
		$data = apply_filters( 'ims_image_data',  $data );
		
		$classes = array( 'ims-img', 'imgid-' . $enc );
		if( !$this->is_taxonomy ) $classes[] = 'hreview';
		if( isset( $data['class'] ) && is_array( $data['class'] ) )
			$classes = array_merge( $classes, $data['class'] );
				
		$css = esc_attr( implode( ' ', $classes ) );
		$url = esc_attr( $this->get_image_url( $imageid, $sz ) );
		
		$link = esc_attr( apply_filters( 'ims_image_link', $data['link'], $data ) );
		
		extract( $this->gallery_tags );
		
		if( isset( $data['sizes'][$size]['width'] ) )
			$dimentions .= ' width="' . esc_attr( $data['sizes'][$size]['width'] ). '"';
			
		if( isset( $data['sizes'][$size]['height'] ) )
			$dimentions .= ' height="' . esc_attr( $data['sizes'][$size]['height'] ) . '"';
		
		$output  = '<' . $imagetag . ' class="' . esc_attr( $css ) .  '" >';
		$output .= '<span class="hmedia item">';
		
		$output .= '<a data-id="' . $enc . '" href="'. $link . '" class="url fn" title="' . esc_attr( $data['title'] ) . '" rel="enclosure">';
		$output .= '<img src="' . $this->imgurl. '" alt="'. esc_attr( $data['alt'] ) . '" ' . $dimentions . ' data-ims-src="' . $url  . '" role="img" /></a>';
		
		if( !$this->is_taxonomy && !$this->is_widget ) {
			$output .= '<span class="img-metadata">';
			
			if( $this->active_store || $this->opts['favorites'] ){
				$output .= ' <label><input name="imgs[]" type="checkbox" value="' . $enc . '" />
				<span class="ims-label"> ' . __( 'Select', 'ims' ) . '</span></label>';
			}
			
			if( $this->active_store ){
				$output .=  '<a id="'. esc_attr( $enc ) .'" href="#" rel="nofollow" title="' .
				 __( 'Add to cart' ) . '" class="box-add-to-cart button">'. _( 'Add to cart' )  . '</a>';
			}
			
			if( !$this->is_taxonomy && $this->opts['voting_like'] ){
				$voted = $this->in_array( $imageid, $this->user_votes) ? ' ims-voted' : '';
				$output .= '<span data-id="' . $enc . '" class="rating' . $voted . '"><em class="value">' . $this->get_image_vote_count( $imageid ) . '</em>+</span>';
			}
			
			$output  .= apply_filters( 'ims_image_tag_meta', '', $data, $imageid, $size, $enc );
			$output  .= '</span><!--.img-metadata-->';
		}
		
		$output  .= '</span><!--.item-->';
		
		if( isset( $data['caption'] ) )
			$output .= '<'. $captiontag . ' class="description gallery-caption">
				<span class="img-name">'. esc_attr( $data['caption'] ) . '</span>
			</'. $captiontag . '>'; 
		
		return $output .= apply_filters( 'ims_image_tag', '', $data, $imageid, $size, $enc ) . '</'.$imagetag.'><!--.ims-img-->';
	}
	
	/**
	 * Adding Admin bar
	 *
	 * @since  3.1
	 * @return void
	 */
	function admin_bar_menu( ) {

		if ( !current_user_can( 'ims_manage_galleries' ) )
			return;

		global $wp_admin_bar;

		$wp_admin_bar->add_menu( array(
			'id' => 'ims-menu', 'title' => __( 'Galleries', 'ims' ),
			'href' => admin_url( 'edit.php?post_type=ims_gallery' )
		) );
		
		$wp_admin_bar->add_menu( array(
			'parent' => 'ims-menu', 'id' => 'ims-menu-albums', 'title' => __( 'Albums', 'ims' ),
			'href' => admin_url( 'edit-tags.php?taxonomy=ims_album&post_type=ims_gallery' )
		) );
		
		$wp_admin_bar->add_menu( array(
			'parent' => 'ims-menu', 'id' => 'ims-menu-customers', 'title' => __( 'Customers', 'ims' ),
			'href' => admin_url( 'edit.php?post_type=ims_gallery&page=ims-customers' )
		) );
	}
	
	/**
	 * Display store navigation
	 *
	 * @param bool $deprecated
	 * @return string
	 * @since 0.5.0
	 */
	function store_nav( $deprecated = false ) {
		
		$nav = '<div class="imstore-nav"><ul  class="imstore-nav-inner" role="navigation" >';
		
		foreach ( $this->pages as $key => $page ) {
			if ( $key == 'receipt' || $key == 'checkout' )
				continue;
			
			$count = '';
			if ( $key == 'shopping-cart' && $this->cart['items'] && $this->imspage != "receipt" )
				$count = "<span>(" . $this->cart['items'] . ")</span>";
			elseif ( $key == 'favorites' && $this->favorites_count )
				$count = "<span>(" . $this->favorites_count . ")</span>";
				
			$css = ( $key == $this->imspage ) ? ' current' : '';
			$nav .= '<li class="ims-menu-' . $key . $css . '"><a href="' . esc_url( $this->get_permalink( $key )  ) . '">' . esc_html( $page ) . "</a> $count </li> ";
		}
		
		if ( !empty( $this->gal->post_password ) && $this->post_logged_in )
			$nav .= '<li class="ims-menu-logout"><a href="' .  esc_url( $this->get_permalink( "logout" ) ) . '">' . __( "Exit Gallery", 'ims' ) . '</a></li>';
			
		return $nav . "</ul></div>\n";
	}
	
	/**
	 * Display store sub-navigation
	 *
	 * @param bool $deprecated
	 * @return void
	 * @since 2.0.0
	 */
	function store_subnav( $deprecated = true ) {
		
		if ( !$this->is_grid || ( !$this->active_store && !$this->opts['favorites'] ) )
			return;
		
		$this->subnav = array(
		  'ims-scroll-up' => __( "Scroll to Top", 'ims' ),
			'ims-select-all' => __( "Select all", 'ims' ),
			'ims-unselect-all' => __( "Unselect all", 'ims' ),
		);
		
		if( $this->opts['favorites'] && $this->imspage != 'favorites'  )
			$this->subnav['add-to-favorite'] = __( "Add to favorites", 'ims' );
		
		if( $this->opts['favorites'] && $this->imspage == 'favorites'  )
			$this->subnav['remove-from-favorite'] =  __( "Remove", 'ims' );
		
		if( $this->active_store )
			$this->subnav['add-images-to-cart'] = __( "Add to cart", 'ims' );
		
		$this->subnav = apply_filters( 'ims_subnav', $this->subnav );
		
		$nav = '<div class="ims-toolbar"><ul class="ims-tools-nav">';
		foreach ( $this->subnav as $key => $label ) 
			$nav .= '<li class="' . esc_attr( $key ) . '"><a href="#" rel="nofollow" title="' . esc_attr( $label ) . '">' . esc_html( $label ) . '</a></li>';
		
		return $nav .= '</ul></div>'; 
	}
	
	/**
	 * Display slideshow navigation
	 *
	 * @param  array $attachments
	 * @return string
	 * @since 3.1.0
	 */
	function slide_show_nav( $attachments = array( ) ){
		
		if( empty( $attachments ) )
			$attachments = $this->attachments;
		
		$css = 'ims-nav-top';
		if( $this->opts['bottommenu'] )
			$css = 'ims-nav-bottom';
		
		$output = '<div class="ims-imgs-nav ' . $css . '">';
		$output .= '<div id="ims-thumbs">';
		$output .= '<ul role="list" class="thumbs">';
		
		foreach ( $attachments as $image ) {
			$size = '' ;
			$enc = $this->url_encrypt( $image->ID );
			
			if( isset( $image->meta['sizes'][$size]['width'] ) )
				$size .= ' width="' . esc_attr( $image->meta['sizes'][$size]['width'] ). '"';
				
			if( isset( $image->meta['sizes'][$size]['height'] ) )
				$size .= ' height="' . esc_attr( $image->meta['sizes'][$size]['height'] ) . '"';

			$img = '<img role="img" src="' . $this->get_image_url( $image->ID, 3 ) . '" class="photo" title="' . 
			esc_attr( $image->post_excerpt ) . '" alt="' . esc_attr( $image->post_title ) . '"' . $size . ' />';
						
			$output .=
			'<li data-id="' . $enc . '" role="hmedia listitem" class="ims-thumb">
				<a class="url fn thumb" href="' . $this->get_image_url( $image->ID, 1 ) . '" title="' . esc_attr( $image->post_title ) . '" rel="enclosure" >' . $img . '</a> 
				<span class="img-metadata caption"><span class="img-title fn">' . apply_filters( 'ims_image_title',  $image->post_title, $image ) . "</span>";
							
				if( $this->opts['voting_like'] ) {
					$voted = $this->in_array(  $image->ID, $this->user_votes) ? ' ims-voted' : '';
					$output .= '<span data-id="' . esc_attr( $enc ) . '" class="rating' . $voted . '"><em class="value">' . $this->get_image_vote_count( $image->ID ) . '</em>+</span>';
				}
				
			$output .= '<span class="ims-caption-text">' . apply_filters( 'ims_image_caption', $image->post_excerpt, $image, 'slideshow' ) . '</span>';
			$output .= '</span><!--.img-metadata--></li>';
		}
		
		$output .= '</ul><!--.thumbs-->';
		$output .= '</div><!--#ims-thumbs-->';
		$output .= '</div><!--.ims-imgs-nav-->';	
		
		return $output;
	}
			
	/**
	 * Display galleries
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function display_galleries( ) {
		
		$output = apply_filters( 'ims_before_galleries', '', $this->gallery_tags, $this );
		
		if ( '' != $output || empty( $this->attachments ) )
		 	return $output;
			
		global $post, $wp_query;
		extract( $this->gallery_tags );
		
		$lightbox = '';
		if( $attach = ( $this->opts['attchlink'] || !empty( $this->meta['_to_attach'][0] ) ) )
			$lightbox = ' nolightbox';
		
		if ( $post->post_excerpt && $this->in_array( $this->imspage, array( 'photos', 'slideshow' ) ) )
			$output = '<div class="ims-excerpt">' . $post->post_excerpt . '</div>';
		
		$output .= "<{$gallerytag} id='ims-gallery-" . $this->galid . "' class='ims-gallery ims-cols-" . $this->opts['columns'] . $lightbox . "' >";
		
		foreach ( $this->attachments as $image ) {
			
			$classes = array( );
			$caption = $image->post_excerpt;
			$title = $alt = get_the_title( $image->ID );
			$link = $this->get_image_url( $image->ID );

			if ( $this->is_taxonomy ) {
				
				$link = get_permalink( $image->post_parent );
				$title = $alt = $caption = get_the_title( $image->post_parent );
				
				if( post_password_required( $image->post_parent ) )
					$classes[] = 'ims-protected';
				
			}elseif ( $attach )  $link = get_permalink( $image->ID );
			
			if( $this->opts['titleascaption'] )
				$caption = $title;
							
			$image->meta += array( 'link' => $link, 'alt' => $alt, 'caption' => wptexturize( $caption ), 'class' => $classes, 'title' => $title );
			$output .= $this->image_tag( $image->ID, $image->meta );
		}
		
		$output .= "</{$gallerytag}>";
		
		
		$wp_query->is_single = false;
		
		$nav = '<div class="ims-navigation">';
		$nav .= '<div class="nav-previous">' . get_previous_posts_link( __( '<span class="meta-nav">&larr;</span> Previous images', 'ims' ) ) . '</div>';
		$nav .= '<div class="nav-next">' . get_next_posts_link( __( 'More images <span class="meta-nav">&rarr;</span>', 'ims' ) ) . '</div>';
		$nav .= '</div><div class="ims-cl"></div>';
		
		$output .= apply_filters( 'ims_gallery_navigation', $nav, $post );
		
		$wp_query->is_single = true;
		$this->visited_gallery(); //register visit
		
		return $output;
	}
	
	/**
	 * Display Order form
	 *
	 * @return void
	 * @since 3.1.7
	 */
	function display_order_form( ) {
		if ( !$this->active_store )
			return;
			
		include( apply_filters( 'ims_order_form_path', IMSTORE_ABSPATH . '/_store/order-form.php' ) );
		return $form;
	}
	
	/**
	 * Display secure galleries login form
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function get_login_form( ) {
		
		$gallery_label = "ims-galbox-{$this->galid}";
		$password_label = "ims-pwdbox-{$this->galid}";
		$nonce = wp_create_nonce( 'ims_access_form' );
		
		$output = '<form action="' . get_permalink( $this->galid ) . '" method="post">
			<p class="message login">' . __( "To view your images please enter your login information below:", 'ims' ) . '</p>
			<div class="ims-fields">
				<label for="' . $gallery_label . '">' . __("Gallery ID:", 'ims') . '</label> 
				<input type="text" id="' . $gallery_label . '" name="' . $gallery_label . '" /><span class="linebreak"></span>
				<label for="' . $password_label . '">' . __("Password:", 'ims') . '
				</label> <input name="' . $password_label . '" id="' . $password_label . '" type="password" />
				<span class="linebreak"></span>
				<input type="submit" name="login-imstore" value="' . esc_attr__("log in", 'ims') . '" />
				<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '" />
				' . apply_filters( 'ims_after_login_form', '' ) . '
			</div>
		</form>
		';
		return apply_filters( 'ims_login_form', $output, $this->gal );
	}
	
	/*
	 * Display image for attachment pages
	 *
	 * @param string $content
	 * @return string
	 * @since 3.0.0
	 */
	function ims_image_content( ) {
		global $post;
		
		$next_post = get_adjacent_post( false, false, false );

		if ( empty( $next_post ) ) {
			$attachments = get_children( array(
				'post_parent' => $post->post_parent,
				'post_status' => 'publish',
				'post_type' => 'ims_image',
				'order' => $this->order,
				'orderby' => $this->sortby,
				'numberposts' => 1,
			) );
			foreach ( $attachments as $attachment ) {
				$next_post = $attachment;
				break;
			}
		}
		
		$title = get_the_title( );
		$caption = $post->post_excerpt;
		$meta = (array) get_post_meta( $post->ID, '_wp_attachment_metadata', true );
		
		if( $this->opts['titleascaption'] )
			$caption = $title;
		
		$meta += array( 'link' => get_permalink( $next_post->ID ), 'alt' => $title, 'class' => array(), 'caption' => wptexturize( $caption ), 'title' => $title );
		
		$mcss = '';
		if ( $this->error )
			$mcss = ' ims-error';
		if ( $this->message )
			$mcss = ' ims-success';
		
		$output = '<div class="ims-message' . $mcss . '">' . $this->message . $this->error . '</div>';
		$output .= $this->image_tag( $post->ID, $meta, 1 );
		
		if( $this->opts['favorites'] )
			$output .= '<div class="add-to-favorite-single"><a href="#" role="button" rel="nofollow">' . __( 'Add to favorites', 'ims' ) . '</a></div>' . "\n";
		
		if ( $this->active_store ) 
			$output .= '<div class="add-images-to-cart-single"><a href="#" role="button" rel="nofollow">' . __( 'Add to cart', 'ims' ) . '</a></div>' . "\n";
	
		return $output .= $this->display_order_form( );
	}
	
	/* Display taxonomy post description
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function taxonomy_description( ){
		global $post;
		return $post->post_excerpt;
	}
	
	/* Display taxonomy content
	 *
	 * @return string
	 * @since 3.1.7
	 */
	function taxonomy_content( ){
		global $post;
				
		$meta = false;
		$this->gal = $post; 
		$classes = array( );
		$this->is_taxonomy = true;
		$this->galid = (int) $post->ID;
		$this->meta = get_post_custom( $this->galid );
		
		if( !empty( $this->meta['_ims_sortby'][0] ) )
			$this->sortby = $this->meta['_ims_sortby'][0];
		
		if( !empty( $this->meta['_ims_order'][0] ) )
			$this->order = $this->meta['_ims_order'][0];
		
		$images = get_children( array(
			'numberposts' => 1,
			'post_type'=>'ims_image', 
			'post_parent' => $post->ID,
			'orderby' => $this->sortby,
			'order' => $this->order,
		)); 
		
		foreach( $images as  $image )
			$meta = wp_get_attachment_metadata( $image->ID );
		
		if( empty( $meta ) ) return;
			$title = get_the_title( $post->ID );
			
		if( post_password_required( $post->ID ) )
			$classes[] = 'ims-protected';
		
		$meta += array( 'link' => get_permalink( ), 'alt' => $title, 'caption' => $title, 'class' => $classes, 'title' => sprintf( __( 'View &quot;%s&quot; gallery', 'ims' ), $title ) );
		return $this->image_tag( $image->ID, $meta );
	}
	
	/**
	 * Empty orginal query
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function empty_query( ){
		global $wp_query;
						
		$this->posts = $wp_query->posts;
		$this->post_count = $wp_query->post_count;
		$this->found_posts = $wp_query->found_posts;
		
		remove_filter( 'loop_start', array( &$this, 'empty_query' ) );

		$wp_query->post_count = 1;
		$wp_query->found_posts = 1;
		
		$post = new stdClass( );
		$post->ID = 0;
		$post->post_author = 0;
		$post->post_parent = 0;
		$post->post_date = 0;
		$post->post_date_gmt = 0;
		$post->post_modified = 0;
		$post->post_modified_gmt = 0;
		$post->post_content  = "[ims-taxonomy]";
		$post->post_title = $this->term->name;
		$post->post_name = $this->term->name;
		$post->post_type = $this->term->taxonomy;
		$post->post_excerpt  = '';
		$post->post_content_filtered = '';
		$post->post_mime_type = '';
		$post->post_password = '';
		$post->post_status = 'publish';
		$post->guid = '';
		$post->menu_order = 0;
		$post->pinged = '';
		$post->to_ping = '';
		$post->ping_status = '';
		$post->comment_status = 'closed';
		$post->comment_count = 0;
	
		$wp_query->post = $post;
		$wp_query->posts = array( $post ); 
	}
	
	/**
	 * Search image title and caption
	 *
	 * @param $where string
	 * @param $query object
	 * @return string
	 * @since 3.2.1
	 */
	function search_images( $where, $query ) {
		global $wp_query;
		
		//only affect the main query
		if( $wp_query !== $query )
			return $where;
			
		if ( !is_search( ) || empty( $query->query_vars['s'] ) )
			return $where;
			
		$q = $query->query_vars;
		$n = empty( $q['exact'] ) ? '%' : '';
		
		global $wpdb;
		foreach ( $q['search_terms'] as $term ) {
			$term = esc_sql( like_escape( $term ) );
			$search = " ( ( $wpdb->posts.post_title LIKE '{$n}{$term}{$n}' )
				OR ( $wpdb->posts.post_content LIKE '{$n}{$term}{$n}' )
				OR ( $wpdb->posts.post_excerpt LIKE '{$n}{$term}{$n}' )
				OR ( $wpdb->posts.post_excerpt LIKE '{$n}{$term}{$n}' ) )";
		}
		
		if ( empty( $q['sentence'] ) && count( $q['search_terms'] ) > 1 && $q['search_terms'][0] != $q['s'])
			$search .= " OR ($wpdb->posts.post_title LIKE '{$n}{$term}{$n}') OR ($wpdb->posts.post_content LIKE '{$n}{$term}{$n}')";
		
		return " $where OR ( ID IN ( SELECT post_parent FROM $wpdb->posts
		WHERE 1=1 AND $search AND $wpdb->posts.post_status = 'publish' ) )";
	}
	
	/**
	 * Get gallery price list
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function get_price_list( ) {

		if( !$this->galid )
			return;
			
		$sizes = array( );
		$list_data = wp_cache_get( 'ims_pricelist_' . $this->galid, 'ims' );
		
		if ( false == $list_data ) {
			global $wpdb;
			
			$list_data = $wpdb->get_results( $wpdb->prepare("
				SELECT meta_value meta, post_id FROM $wpdb->postmeta
				WHERE post_id = ( SELECT meta_value FROM $wpdb->postmeta
				WHERE post_id = %s AND meta_key = '_ims_price_list ' LIMIT 1 )
				AND meta_key = '_ims_sizes' ", $this->galid
			) );
			
			wp_cache_set( 'ims_pricelist_' . $this->galid, $list_data, 'ims' );
		}
		
		if( isset( $list_data[0]->post_id ) )
			$this->pricelist_id = $list_data[0]->post_id;
		else $this->pricelist_id = $this->get_option( 'ims_pricelist' );
		
		if ( empty( $list_data[0]->meta ) )
			return array( );
		
		$data = maybe_unserialize( $list_data[0]->meta );
		unset( $data['random'] );
		
		//remove unsave charecters
		foreach ( $data as $size ){
			$key = str_replace( array( '|','\\','.',' ' ), '', $size['name'] );
			$this->sizes[$key] = $size;
		}
	}
	
	/**
	 * Get gallery images
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function get_gallery_images( ) {
	
		if( !empty( $this->attachments ) )
			return $this->attachments;
			
		global $wpdb, $paged;
		
		$limit = ''; $offset = 0;
		
		$order = $this->order;
		$sortby = $this->sortby;
		
		if( !empty( $this->meta['_ims_sortby'][0] ) )
			$sortby = $this->meta['_ims_sortby'][0];
		
		if( !empty( $this->meta['_ims_order'][0] ) )
			$order = $this->meta['_ims_order'][0];
		
		if( $sortby != 'menu_order' )
			$sortby = "post_" . str_replace( "post_", '', $sortby );
		
		if( $this->imspage == 'slideshow' )
			$this->posts_per_page = -1;
		elseif( $this->opts['imgs_per_page'] )
			$this->posts_per_page = $this->opts['imgs_per_page'];
			
		if( $this->posts_per_page > 0 ){
			if( $paged ) 
				$offset = ( $this->posts_per_page * $paged ) - $this->posts_per_page;
			$limit = "LIMIT $offset, $this->posts_per_page";
		}
			
		do_action( 'ims_get_gallery_images', $this, $this->posts_per_page, $offset );
	
		$this->attachments = $wpdb->get_results(  $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS p.*, pm.meta_value meta
			FROM $wpdb->posts AS p LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id
			WHERE p.post_type = 'ims_image'
			AND pm.meta_key = '_wp_attachment_metadata'
			AND p.post_status = 'publish' AND p.post_parent = %d
			ORDER BY p.{$sortby} {$order} $limit"
		, $this->galid ) );
		
		if( empty( $this->attachments ) )
			return $this->attachments;
		
		if ( $this->imspage == 'photos' && is_singular( "ims_gallery" ) ) {
			global $wp_query;
			$wp_query->found_posts = $wpdb->get_var( 'SELECT FOUND_ROWS( )' );
			$wp_query->max_num_pages = ceil( $wp_query->found_posts / $this->posts_per_page );
		}
		
		foreach ( $this->attachments as $key => $post ) {
			$this->attachments[$key]->meta = maybe_unserialize( $post->meta );
			if ( empty( $this->attachments[$key]->meta['image_meta']['author'] ) )
				$this->attachments[$key]->meta['image_meta']['author'] = get_the_author_meta( 'display_name', $post->post_author );
		}
		
		return $this->attachments;
	}
	
	/**
	 * Get favorites
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function get_favorite_images( ) {
		
		if ( empty( $this->favorites_ids ) )
			return array( );
		
		global $wpdb;
		$ids = $wpdb->escape( $this->favorites_ids );
		
		if( in_array( $this->opts['imgsortorder'] , array( 'date', 'title', 'excerpt' ) ))
			$order = "post_" . $this->opts['imgsortorder'];
		else $order =  $this->opts['imgsortorder'];
		
		$this->attachments = $wpdb->get_results(
			"SELECT  p.*, meta_value meta FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm ON p.ID = pm.post_id WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata' AND p.ID IN ( $ids ) GROUP BY ID
			ORDER BY " . $wpdb->escape( $order ) . " " . 
			$wpdb->escape( $this->opts['imgsortdirect'] )
		);
		
		if ( empty( $this->attachments ) )
			return $this->attachments;
		
		foreach ( $this->attachments as $key => $post )
			$this->attachments[$key]->meta = maybe_unserialize( $post->meta );
	}
	
	/**
	 * Get gallery images
	 *
	 * @param $atts array
	 * @return array
	 * @since 2.0.0
	 */
	function get_galleries( $atts ) {
		
		global $wpdb, $paged;
		
		extract( wp_parse_args( $atts, array(
			'order' => 'DESC', 'orderby' => 'post_date', 'offset' => 0, 'taxid' => false, 'secure' => '',
			'album' => false, 'tag' => false, 'count' => $this->posts_per_page, 'all' => false, 'limit' => '' )
		) );
				
		if ( $count > 1 ) $limit = "LIMIT %d, %d";
		if ( !$all ) $secure =  "AND post_password = ''";
		if ( $paged ) $offset = ( ( $count * $paged ) - $count );
		
		do_action( 'ims_before_get_galleries', $atts, $this );
		
		if( $album || $tag ){
			
			$taxid = ( $album ) ? $album : $tag;
			$tax = ( $album ) ? 'ims_album' : 'ims_tags' ;	
			
			$type = "SELECT tr.object_id FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id
			INNER JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE t.term_id = %d AND tt.taxonomy = '$tax' GROUP BY tr.object_id ";
		
		}else{
			
			$type = " SELECT ID FROM $wpdb->posts WHERE 0 = %d AND
			post_type = 'ims_gallery' AND post_status = 'publish' $secure";
			
		}
		
		$this->attachments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS  im.ID, im.post_title, p.comment_status,
				pm.meta_value meta, im.post_excerpt, im.post_parent, im.post_type, p.post_author
				FROM ( SELECT * FROM $wpdb->posts ORDER BY
				 " . $wpdb->escape( $this->opts['imgsortorder'] ) . " " . $wpdb->escape( $this->opts['imgsortdirect'] ) . " )  AS im
				 
				LEFT JOIN $wpdb->postmeta AS pm ON pm.post_id = im.ID
				LEFT JOIN $wpdb->posts AS p ON p.ID =  im.post_parent
				
				WHERE im.post_type = 'ims_image' AND pm.meta_key = '_wp_attachment_metadata'
				AND im.post_status = 'publish' AND p.post_status = 'publish' AND im.post_parent IN ( $type )
				GROUP BY im.post_parent ORDER BY p.{$orderby} $order, p.post_date DESC $limit
				", $taxid, $offset, $count
			)
		);
		
		if ( empty( $this->attachments ) )
			return;
		
		do_action( 'ims_after_get_galleries', $atts, $this );
		
		foreach ( $this->attachments as $key => $post ) 
			$this->attachments[$key]->meta = maybe_unserialize($post->meta);
	}
	
	/**
	 * Fix pagination order to attachment (im_image) page
	 *
	 * @param $order string
	 * @return string
	 * @since 3.0.1
	 */
	function adjacent_post_sort( $order ) {
		$dir = ( $this->direct == '<' ) ? 'DESC' : 'ASC';
		return " ORDER BY p.{$this->sortby} $dir, p.ID $dir";
	}
	
	/**
	 * Fix pagination to attachment (im_image) page
	 *
	 * @param $where string
	 * @return string
	 * @since 3.0.1
	 */
	function adjacent_post_where( $where ) {

		global $post, $wpdb;
		
		$this->order = get_post_meta( $post->post_parent, '_ims_order', true );
		$this->sortby = get_post_meta( $post->post_parent, '_ims_sortby', true );

		if( empty( $this->order ) ) $this->order = $this->opts['imgsortdirect'];
		if( empty( $this->sortby ) ) $this->sortby = $this->opts['imgsortorder'] ;
	
		if( $this->order == "ASC")
			 $this->direct = ( preg_match( '/\>/', $where ) ) ? '>' : '<';
		else $this->direct = ( preg_match( '/\>/', $where ) ) ? '<' : '>';
		
		$where = preg_replace( array( '/\>/', '/\</' ), array( '>=', '<=' ), $where );

		switch ( $this->sortby ) {
			case 'menu_order':
				if ( $post->menu_order ) 
					$where = $wpdb->prepare( "WHERE p.post_type = 'ims_image' 
					AND p.post_status = 'publish' AND p.menu_order $this->direct %d ", $post->menu_order );
				else $where = $where . " AND p.ID $this->direct $post->ID";
				break;
			case 'title':
				$this->sortby = "post_title";
				$where = $wpdb->prepare("WHERE p.post_type = 'ims_image' 
				AND p.post_status = 'publish' AND p.post_title $this->direct %s", $post->post_title);
				break;
			case 'date':
				$this->sortby = "post_date";
				$where = $where . " AND p.ID $this->direct $post->ID";
				break;
			case 'excerpt':
				$this->sortby = "post_excerpt";
				$where = $wpdb->prepare("WHERE p.post_type = 'ims_image' 
				AND p.post_status = 'publish' AND p.post_excerpt $this->direct %s", substr( $post->post_excerpt, 0, 10 ) );
				break;
			default:
		}
		return $where . " AND p.post_parent = $post->post_parent";
	}
}