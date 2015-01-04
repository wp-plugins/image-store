<?php

/**
 * Image Store - Admin
 *
 * @file admin.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/admin.php
 * @since 0.5.0
 */
 
class ImStoreAdmin extends ImStore {
	
	/**
	 * Public variables
	*/
	public $galid = 0;
	public $pageurl = '';
	public $per_page = 20;
	
	public $uid = false;
	public $page = false;
	public $action = false;
	public $spageid = false;
	public $pagenow = false;
	public $screen_id = false;
	public $ajaxnonce = false;
	
	public $uopts = array( );
	public $screens = array( );
	public $user_fields = array( );
	public $user_status = array( );
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function ImStoreAdmin( $page, $action ) {
		
		$this->ImStore( );
		global $pagenow;
		
		$this->page = trim( $page );
		$this->action = trim( $action );
		$this->pagenow = trim( $pagenow );
		
		add_action( 'init', array( &$this, 'min_init' ), 0, 2 );
		add_action( 'init', array( &$this, 'save_image_ipc_data' ), 6 );
		add_filter( 'admin_body_class', array( &$this, 'admin_body_class' ) );
		add_filter( 'current_screen', array( &$this, 'set_screen_id' ), 50 );
		//add_filter('posts_request', array(&$this, 'posts_request'));
		
		//image load processing
		add_filter( 'image_save_pre', array( &$this, 'image_save_pre' ), 15, 2 );
		add_filter( 'get_attached_file', array( &$this, 'load_ims_image_path' ), 15, 2 );
		add_filter( 'intermediate_image_sizes', array( &$this, 'alter_image_sizes' ), 50 );
		add_filter( 'load_image_to_edit_path', array( &$this, 'load_ims_image_path' ), 15, 2 );
		add_filter( 'image_make_intermediate_size', array( &$this, 'move_resized_file' ), 10 );
		
		//metadata generation
		add_filter( 'wp_read_image_metadata', array( &$this, 'extra_image_meta' ), 100, 2 );
		add_filter( 'wp_update_attachment_metadata', array( &$this, 'generate_image_metadata' ), 50, 2 );
		add_filter( 'wp_generate_attachment_metadata', array( &$this, 'generate_image_metadata' ), 50, 2 );
		
		//delete post / backwards compatibility
		add_action( 'delete_post', array( &$this, 'delete_post' ), 1 );
		add_action( 'before_delete_post', array( &$this, 'delete_post' ), 1 );
		
		//taxonomy row actions
		add_filter( 'ims_tags_row_actions', array( &$this, 'add_taxonomy_link' ), 1, 2 );
		add_filter( 'ims_album_row_actions', array( &$this, 'add_taxonomy_link' ), 1, 2 );
		
		//taxonomy columns
		add_action( 'manage_edit-ims_album_columns', array( &$this, 'add_id_column' ) );
		add_action( 'manage_edit-ims_album_sortable_columns', array( &$this, 'add_id_column' ) );
		add_action( 'manage_edit-ims_tags_columns', array( &$this, 'add_id_column' ) );
		add_action( 'manage_edit-ims_tags_sortable_columns', array( &$this, 'add_id_column' ) );
		
		add_filter( 'manage_ims_album_custom_column', array( &$this, 'show_cat_id' ), 10, 3 );
		add_filter( 'manage_ims_tags_custom_column', array( &$this, 'show_cat_id' ), 10, 3 );
		
		//add galleries columns
		add_filter( 'manage_edit-ims_gallery_columns', array( &$this, 'add_columns' ), 10 );
		add_filter( 'manage_edit-ims_gallery_sortable_columns', array( &$this, 'add_columns' ), 15 );
		add_filter( 'manage_edit-ims_gallery_sortable_columns', array( &$this, 'remove_select_column' ), 20 );
		add_filter( 'manage_posts_custom_column', array( &$this, 'add_columns_gallery_val' ), 15, 2 );
		
		//ad columns
		add_filter( 'manage_users_columns', array( &$this, 'add_columns' ), 10 );
		add_filter( 'manage_users_sortable_columns', array( &$this, 'add_columns' ), 10 );
		add_filter( 'manage_users_sortable_columns', array( &$this, 'remove_select_column' ), 20 );
		add_filter( 'manage_users_custom_column', array( &$this, 'add_columns_user_val' ), 15, 3 );
		
		//sort galleries columns
		add_filter( 'request', array( &$this, 'galleries_column_orderby' ) );
		add_filter( 'posts_join_paged', array( &$this, 'galleries_column_join' ) );
		add_filter( 'posts_fields_request', array( &$this, 'posts_fields_request' ) );
		add_filter( 'posts_where_request', array( &$this, 'posts_where_request' ) );
		add_filter( 'posts_groupby_request', array( &$this, 'posts_groupby_request' ) );
		add_filter( 'posts_orderby_request', array( &$this, 'posts_orderby_request'), 5, 2 );

		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
			
		//register hooks
		register_activation_hook( IMSTORE_FILE_NAME, array( &$this, 'activate' ) );
		register_deactivation_hook( IMSTORE_FILE_NAME, array( &$this, 'deactivate' ) );
		
		add_action( 'init', array( &$this, 'admin_init' ), 1 );
		add_action( 'init', array( &$this, 'save_screen_option' ), 5 );
		
		//upgrade messages
		add_action( 'in_admin_header', array( &$this, 'in_admin_header' ) );
		
		//script styles
		add_action( 'admin_print_styles', array( &$this, 'load_styles' ), 1 );
		add_action( 'admin_print_scripts', array( &$this, 'load_admin_scripts' ), 1);
		add_action( 'admin_print_styles', array( &$this, 'register_screen_columns' ) );
		
		//user registration
		add_action( 'user_register', array( &$this, 'update_user' ), 1 );
		add_action( 'edit_user_profile', array( &$this, 'profile_fields' ), 1 );
		add_action( 'show_user_profile', array( &$this, 'profile_fields' ), 1 );
		add_action( 'edit_user_profile_update', array( &$this, 'update_user' ), 1 );
		
		//pricelist options
		add_action( 'ims_pricelist_options', array( &$this, 'ims_pricelist_options' ), 10 );
		
		if ( is_multisite( ) ) {
			add_action( 'wpmu_options', array( &$this, 'wpmu_options' ) );
			add_action( 'activated_plugin', array( &$this, 'activated_plugin' ), 1, 2 );
			add_action( 'wpmu_new_blog', array( &$this, 'wpmu_create_blog' ), 1 );
			add_action( 'wpmu_upgrade_page', array( &$this,'network_update_button' ) );
			add_action( 'update_wpmu_options', array( &$this, 'update_wpmu_options' ) );
		}
		
		//admin menus
		add_action( 'admin_menu', array( &$this, 'add_menu' ), 20 );
	}
	
	
	/**
	 * Minial Initial actions
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function min_init ( ){
		global $user_ID;
		
		if( isset( $_GET['post']  ) )
		$this->galid = ( int ) $_GET['post'];
			
		$this->uid = $user_ID;
		$this->ajaxnonce = wp_create_nonce( 'ims_ajax' );
		$this->uopts = $this->get_option( 'ims_user_options' );
	}
	
	/**
	 * Deactivate 
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function deactivate( ) {
		wp_clear_scheduled_hook( 'imstore_expire' );
	}
	
	/**
	 * Activite and save default options
	 * Activite the expire cron 
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function activate( ) {
		
		include_once( IMSTORE_ABSPATH . '/admin/install.php' );
		
		$ImStoreInstaller = new ImStoreInstaller( );
		$ImStoreInstaller->init( );
		
		wp_schedule_event( strtotime( "tomorrow 1 hours" ), 'twicedaily', 'imstore_expire' );
	}
	
	/**
	 * Add taxonomy name body class
	 *
	 * @param array $classes
	 * @return array
	 * @since 3.2.1
	 */
	function admin_body_class( $classes ){
		$classes .= " wp-". sanitize_title( $this->wp_version  );
		
		if( empty( $_GET[ 'taxonomy' ] ) )
			return $classes;
			
		if( $this->in_array( $_GET[ 'taxonomy' ], 
			array( 'ims_tags', 'ims_album' ) ) 
		) $classes .=  $_GET[ 'taxonomy' ];
			
		return $classes;
	}
	
	/**
	 * Display the pages 
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function show_menu( ) {
		global $wpdb;
		$this->include_file( 'template', 'admin' );
	}
	
	/**
	 * Initial actions
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function admin_init ( ) {
		
		if ( $this->action ) 
			$url = $this->pagenow . "?post=$this->galid&action=" . $this->action;
		elseif ( $this->page ) 
			$url = $this->pagenow . '?post_type=ims_gallery&page=' . $this->page;
		else $url = $this->pagenow . '?post_type=ims_gallery';

		$this->pageurl = admin_url( $url );
		
		$user_status = array(
			'active' => __( 'Active', 'ims' ),
			'inative' => __( 'Inative', 'ims' ),
		);
		
		$this->screens = array(
			'tags' => 'edit-ims_tags',
			'gallery' => 'ims_gallery', 
			'edit-gallery' => 'edit-ims_gallery',
			'edit-album' => 'edit-ims_album', 
		);
		
		$user_fields = array(
			'ims_address' => __( 'Address', 'ims' ),
			'ims_city' => __( 'City', 'ims' ),
			'ims_state' => __( 'State', 'ims' ),
			'ims_zip' => __( 'Zip', 'ims' ),
			'ims_phone' => __( 'Phone', 'ims' ),
		);
		
		$this->user_fields = apply_filters( 'ims_user_fields', $user_fields );
		$this->user_status = apply_filters( 'ims_user_status', $user_status );
		
		do_action( 'ims_admin_init', $this );	
	}
	
	/**
	 * ImStore admin menu	
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function add_menu( ) {
		$menu ='edit.php?post_type=ims_gallery';
		
		if ( $this->opts['store'] ) {
			$this->screens['sales'] = add_submenu_page( $menu, __( 'Sales', 'ims' ), __( 'Sales', 'ims' ), 'ims_read_sales', 'ims-sales', array( &$this, 'show_menu' ) );
			$this->screens['pricing'] = add_submenu_page( $menu, __( 'Pricing', 'ims' ), __( 'Pricing', 'ims' ), 'ims_change_pricing', 'ims-pricing', array( &$this, 'show_menu' ) );
			$this->screens['customers'] = add_submenu_page( $menu, __( 'Customers', 'ims' ), __( 'Customers', 'ims' ), 'ims_manage_customers', 'ims-customers', array( &$this, 'show_menu' ) );
		}

		$this->screens['settings'] = add_submenu_page( $menu, __( 'Settings', 'ims' ), __( 'Settings', 'ims' ), 'ims_change_settings', 'ims-settings', array( &$this, 'show_menu' ) );
		if ( current_user_can( 'ims_read_galleries' ) && $this->opts['store'] && !current_user_can( 'administrator' ) ){
			$this->screens['user-galleries'] =	add_users_page( __( 'Image Store', 'ims' ), __( 'My Galleries', 'ims' ), 'ims_read_galleries', 'user-galleries', array( &$this, 'show_menu' ) );
			$this->screens['user-images'] =	add_users_page( __( 'Image Store', 'ims' ), __( 'My Images', 'ims' ), 'ims_read_galleries', 'user-images', array( &$this, 'show_menu' ) );
		}
	}
	
	/**
	 * Display upgrade messages
	 *
	 * @return void
	 * @since 3.2.0
	 */
	function in_admin_header( ){
		
		//display network sucessfull upgrade message
		if( isset( $_REQUEST['ims-network-updated'] ) )
			echo '<div class="updated fade"><p>'.__( "Image Store has been updated across the network." ).'</p></div>';
		
		//display single sucessfull upgrade message
		if( isset( $_REQUEST['ims-updated'] ) )
			echo '<div class="updated fade"><p>'.__( "Image Store has been updated." ).'</p></div>';
		
		global $blog_id;
		
		//display upgrade message
		$message = sprintf( 
			__( 'Click to run <a href="%s">Image Store\'s</a> updates','ims' ), 
			IMSTORE_ADMIN_URL . '/update.php?single=' . (is_multisite() ? $blog_id : 1)
		); 
		
		//multisite installed message
		if( current_user_can( 'manage_network' ) && is_plugin_active_for_network( IMSTORE_FILE_NAME ))
			$message = sprintf( __( 'Apply <a href="%s">Image Store updates</a> across the network.', 'ims' ), network_site_url( 'wp-admin/network/upgrade.php' ) ); 
		
		if ( get_option( 'imstore_version' ) < $this->version && current_user_can( 'install_plugins' ) ) 
			echo '<div class="error fade"><p>' . $message . '</p></div>';
	}
	
	/**
	 * Set crrent screen id
	 *
	 * @param object $current_screen
	 * @return object
	 * @since 3.2.1
	 */
	function set_screen_id( $current_screen ){
		if( isset( $current_screen->id ) )
			$this->screen_id = $current_screen->id;
			
		if( isset( $_REQUEST['post'] ) && $this->screen_id == 'post'
		&& 'ims_gallery' == get_post_type( $_REQUEST['post'] ) )
			$this->screen_id = 'ims_gallery';

		return $current_screen;
	}
	
	/**
	 * Load admin styles
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function load_styles( ) {
		
		if( $this->in_array( $this->pagenow, array( 'post-new.php', 'post.php' ) ) ){
			
			add_filter( 'mce_css', array( &$this, 'mce_css' ) );
			add_filter( 'mce_buttons_2', array( &$this, 'register_ims_button' ) );
			add_filter( 'mce_external_plugins', array( &$this, 'add_ims_tinymce_plugin' ) );
		
			wp_enqueue_style( 'ims-tinymce', IMSTORE_URL . '/_css/tinymce.css', false, $this->version, 'all' );
		}
		
		if ( !$this->in_array( $this->screen_id, $this->screens ) )
			return;
		
		if ( is_multisite() && empty( $this->opts ) )
			echo '<div class="error fade"><p>Image Store: ' . 
			__( "Options not available, please reset all settings under the reset tab.", 'ims' ) . '</p></div>';
		
		wp_enqueue_style( 'ims-admin', IMSTORE_URL . '/_css/admin.css', false, $this->version, 'all' );
	}
	
	/**
	 * Load admin scripts
	 *
	 * @return void
	 * @since 2.0.0
	 */
	function load_admin_scripts( ) {
		if ( !$this->in_array( $this->screen_id, $this->screens ) )
			return;
		
		if ( $this->screen_id == 'ims_gallery_page_ims-pricing' ) {
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-draggable' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
		}
		
		$jquery = array( 'dd', 'D', 'd', 'DD', '*', '*', '*', 'o', '*', 'MM', 'mm', 'M', 'm', '*', '*', '*', 'yy', 'y' );
		$php = array( '/d/', '/D/', '/j/', '/l/', '/N/', '/S/', '/w/', '/z/', '/W/', '/F/', '/m/', '/M/', '/n/', '/t/', '/L/', '/o/', '/Y/', '/y/' );
		$format = preg_replace( $php, $jquery, get_option( 'date_format' ) );

		wp_enqueue_script( 'ims-admin', IMSTORE_URL . '/_js/admin.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( 'ims-admin', 'imslocal', array(
			'dateformat' 	=> $format,
			'nonceajax' 	=> $this->ajaxnonce,
			'download' 		=> __( 'Downloadable', 'ims' ),
			'imsajax' 		=> IMSTORE_ADMIN_URL . '/ajax.php', 
			'deletelist' 		=> __( 'Are you sure that you want to delete this list?', 'ims' ),
			'deletepackage' => __( 'Are you sure that you want to delete this package?', 'ims' ),
		));
	}
	
	/**
	 * Add js for tinymce support
	 *
	 * @param array $plugins
	 * @return array
	 * @since 3.0.0
	 */
	function add_ims_tinymce_plugin( $plugins) {
		$plugins['imstore'] = IMSTORE_URL . '/_js/tinymce/imstore.js';
		return $plugins;
	}
	
	/**
	 * Add css for tinymce support
	 *
	 * @param string $css
	 * @return string
	 * @since 3.0.0
	 */
	function mce_css( $css ) {
		return $css . ', ' . IMSTORE_URL . "/_css/tinymce.css";
	}

	/**
	 * Add imstore button to the 
	 * second tinymce button bar
	 *
	 * @param array $buttons
	 * @return array
	 * @since 3.0.0
	 */
	function register_ims_button( $buttons ) {
		array_push($buttons, "separator", "imstore");
		return $buttons;
	}
	
	/**
	 * Display album link 
	 *
	 * @param array $actions
	 * @param object $tag
	 * @return array
	 * @since 3.0.0
	 */
	function add_taxonomy_link( $actions, $tag ) {
		if ( isset( $actions['view' ] ) )
			return $actions;

		$actions['view'] = '<a href="' . get_term_link( $tag, $tag->taxonomy ) . '" title="' .
		sprintf( __( 'View %s', 'ims' ), $tag->name ) . '">' . __( 'View', 'ims' ) . '</a>';
		return $actions;
	}
	
	/**
	 * Add ID Column
	 *
	 * @param array $columns
	 * @return array
	 * @since 2.1.1
	 */
	function add_id_column( $columns ) {
		if ( current_user_can( 'manage_categories' ) )
			$columns['id'] = 'ID';
		return $columns;
	}
	
	/**
	 * Add value to ID album Column
	 *
	 * @param null $none
	 * @param string $column_name
	 * @param unit $postid
	 * @return unit
	 * @since 2.1.1
	 */
	function show_cat_id( $none, $column_name, $id ) {
		if ( $column_name == 'id' )
			return $id;
	}
	
	/**
	 * Sanitize gallery path
	 * added by the user
	 *
	 * @param string $path
	 * @param bool $lefttrim
	 * @return array
	 * @since 3.2.9
	 */
	function sanitize_path( $path, $lefttrim = false ){
		$path = str_replace( array(" ", '"', "'", '$', '`', "&", "~", "^", "?", "#"), '', $path );
		
		if( $lefttrim == 'notrim' ) return remove_accents( ltrim( str_replace( array( '../', './', '\'', '\\', '//' ), '/', $path ) , ".," ) );
		else if( $lefttrim ) return remove_accents( ltrim( str_replace( array( '../', './', '\'', '\\', '//' ), '/', $path ) , ".,/" ) );
		else return remove_accents( trim( str_replace( array( '../', './', '\'', '\\', '//' ), '/', $path ) , ".,/" ) );
	}
	
	/**
	 * Remove input column from
	 * sort columns
	 *
	 * @param array $columns
	 * @return array
	 * @since 3.4.0
	 */
	function remove_select_column( $columns ){
		if( isset( $columns['cb'] ) )
			unset( $columns['cb'] );
		return $columns;
	}
	
	/**
	 * Display aditional colums for 
	 * cutomer status
	 *
	 * @param array $columns
	 * @return array
	 * @since 2.0.0
	 */
	function add_columns( $columns ) {
		switch ( $this->screen_id ) {
			case false:
			case "edit-ims_gallery":
				return array(
					'cb' => '<input type="checkbox">',
					'title' => __( 'Gallery', 'ims' ), 'galleryid' => __( 'ID', 'ims' ),
					'visits' => __( 'Visits', 'ims' ), 'tracking' => __( 'Tracking', 'ims' ),
					'images' => __( 'Images', 'ims' ), 'author' => __( 'Author', 'ims' ),
					'expire' => __( 'Expires', 'ims' ), 'date' => __( 'Date', 'ims' )
				);
			case "users":
				if ( !isset( $_GET['role'] ) || $_GET['role'] != $this->customer_role )
					return $columns;
				return array(
					'cb' => '<input type="checkbox">', 'username' => __( 'Username', 'ims' ),
					'fistname' => __( 'First Name', 'ims' ), 'lastname' => __( 'Last Name', 'ims' ),
					'email' => __( 'E-mail', 'ims' ), 'city' => __( 'City', 'ims' ),
					'phone' => __( 'Phone', 'ims' ), 'status' => __( 'Status', 'ims' )
				);
			default: return $columns;
		}
	}
	
	/**
	 * Add status column to users screen
	 *
	 * @param null $null
	 * @param array $column_name
	 * @param unit $user_id
	 * @return string
	 * @since 3.2.1
	 */
	function add_columns_user_val( $value, $column_name, $user_id ) {
		$data = get_userdata( $user_id );
		switch ( $column_name ) {
			case 'fistname':
				return $data->first_name;
				break;
			case 'lastname':
				return $data->last_name;
				break;
			case 'city':
				return isset( $data->ims_city ) ? $data->ims_city : false;
				break;
			case 'phone':
				return isset( $data->ims_phone ) ? $data->ims_phone : false;
				break;
			case 'status':
				return isset( $data->ims_status ) ? $this->user_status[$data->ims_status] : false;
				break;
			default:
				return $value;
		}
	}
	
	/**
	 * Add status column to galleries
	 *
	 * @param null $null
	 * @param array $column_name
	 * @param unit $user_id
	 * @return string
	 * @since 3.2.1
	 */
	function add_columns_gallery_val( $column_name, $postid ){
		switch ( $column_name ) {
			case 'galleryid':
				echo esc_html(get_post_meta( $postid, '_ims_gallery_id', true));
				break;
			case 'visits':
				echo esc_html( get_post_meta( $postid, '_ims_visits', true ));
				break;
			case 'tracking':
				echo esc_html( get_post_meta( $postid, '_ims_tracking', true ) );
				break;
			case 'images':
				global $wpdb;
				echo $wpdb->get_var( $wpdb->prepare( 
				"SELECT COUNT(*) FROM $wpdb->posts WHERE post_parent = %d AND post_status = 'publish'", $postid ));
				break;
			case 'expire':
				if ( $post_expire = get_post_meta( $postid, '_ims_post_expire', true ) )
					echo mysql2date( $this->dformat, $post_expire, true );
				break;
			default:
		}
	}
	
	/**
	 * Allow wp_insert_post to ad expiration date 
	 * on the custom "post_expire "column
	 *
	 * @param array $data
	 * @return array
	 * @since 0.5.0 
	 */
	function insert_post_data( $data ) {
		_deprecated_function( __FUNCTION__, '3.4' );
	}
	
	/**
	 * Save iptc metadata
	 *
	 * @since 3.2.1
	 * return void
	 */
	function save_image_ipc_data( ){
		if ( isset( $_POST['save-metadata'] ) && isset( $_POST['imageid'] ) ) {
			
			$id = (int) $_POST['imageid'];
			
			unset( $_POST['imageid'] );
			unset( $_POST['save-metadata'] );
			
			$nonce = isset( $_POST['imgnonce']  ) ? $_POST['imgnonce']  : false;
			$meta = (array) get_post_meta( $id, '_wp_attachment_metadata', true );
		
			foreach ( $_POST as $key => $val )
				$meta['image_meta'][$key] = $val;
				
			update_post_meta( $id, '_wp_attachment_metadata', $meta );
			
			wp_redirect( 
				IMSTORE_ADMIN_URL . '/galleries/image-edit.php?height=520&width=782&editimage=' . $id . '&_wpnonce=' .$nonce ."#attachment-meta" 
			);
			die();
		}
	}
	
	/**
	 * Save user screen settings
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function save_screen_option( ) {
		if ( !isset( $_POST['ims_screen_options'] ) ||
		!isset( $this->uid ) || !is_numeric( $this->uid ) )
			return;

		$o = trim( $_POST['ims_screen_options']['option'] );
		$v = ( int ) trim( $_POST['ims_screen_options']['value'] );
		
		update_user_meta( $this->uid, $o, $v  );
		do_action( 'ims_update_screen_settings', $this->pageurl );
		
		wp_redirect( $this->pageurl . "&ms=40" );
		die( );
	}
	
	/* Register screen columns
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function register_screen_columns( ){
		switch( $this->screen_id ){
			case 'profile_page_user-galleries':
				register_column_headers( 'profile_page_user-galleries', array(
					'gallery' => __( 'Gallery', 'ims' ),
					'galleryid' => __( 'Gallery ID', 'ims' ),
					'password' => __( 'Password', 'ims' ),
					'expire' => __( 'Expires', 'ims' ),
					'images' => __( 'Images', 'ims' ),
				) );
				add_filter( 'screen_settings', array( &$this, 'screen_settings' ) );
				break;
			case 'profile_page_user-images':
				register_column_headers( 'profile_page_user-images', array(
					'image' => __( 'Image', 'ims' ),
					'gallery' => __( 'Gallery', 'ims' ),
					'size' => __( 'Size', 'ims' ),
					'color' => __( 'Color', 'ims' ),
					'finish' => __( 'Finish', 'ims' ),
					'download' => __( 'Download', 'ims' ),
				) );
				break;
			default:
				return;
		}
	}
	
	/**
	 * Delete image folder
	 *
	 * @param unit $postid
	 * @return void
	 * @since 2.0.0
	 */
	function delete_post( $postid ) {
		if ( !$this->opts['deletefiles'] 
		||  'ims_gallery' != get_post_type( $postid )
		|| !current_user_can( 'ims_manage_galleries' ) )
			return $postid;

		if ( $folderpath = get_post_meta( $postid, '_ims_folder_path', true ) )
			$this->delete_folder( $this->content_dir . $folderpath );
		return $postid;
	}
	
	/**
	 * Generate aditions metadata for image
	 *
	 * @param array $metadata
	 * @param unit $attachment_id
	 * @return array
	 * @since 3.0.0
	 */
	function generate_image_metadata( $metadata, $attachment_id ) {
		if ( 'ims_image' != get_post_type( $attachment_id ) || empty( $metadata['file'] ) )
			return $metadata;
			
		$filename = basename( $metadata['file'] );
		$path = $this->sanitize_path( dirname( str_ireplace( $this->content_dir, '', $metadata['file'] ) ), true);
		
		$metadata['file'] = "$path/$filename";
		if ( !preg_match(" /(_resized)/i", $path ) ){
			$path = "$path/_resized";
			
			if ( isset( $_REQUEST['target'] ) && 'thumbnail' != $_REQUEST['target'] ) 
			@copy( $this->content_dir . "/$path/" . $filename, $this->content_dir . '/' . $metadata['file'] );
		}
		
		if ( !file_exists( $path ) )
			@mkdir( $path, 0751, true );
		
		//generate mini image for thumbnail edit
		if ( isset( $_REQUEST['target'] ) && 'thumbnail' == $_REQUEST['target'] ) {
			
			$resized_file = false;
			$width = $this->get_option("mini_size_w");
			$height = $this->get_option("mini_size_h");
			$file_path = $this->content_dir . "/$path/" . $metadata['sizes']['thumbnail']['file'];
			
			if( function_exists( 'wp_get_image_editor') ){
				$editor = wp_get_image_editor( $file_path );
				if ( ! is_wp_error( $editor ) && ! is_wp_error( $editor->resize( $width, $height, true ) ) )
					$resized_file = $editor->save( );
				if ( ! is_wp_error( $resized_file ) && $resized_file )
					$metadata['sizes']['mini'] = $resized_file;
			} else { 
				$resized_file = image_resize( $file_path, $width, $height, true );
				if ( ! is_wp_error( $resized_file ) && $resized_file && $info = getimagesize( $resized_file ) )
					$metadata['sizes']['mini'] = array( 'file' => basename( $resized_file ), 'width' => $info[0], 'height' => $info[1] );
			}
		}
		
		// if original is smaller than mini use mini as original
		if ( empty($metadata['sizes']['mini'] ) || empty( $metadata['sizes']['preview'] ) || empty( $metadata['sizes']['thumbnail'] ) ) {
			$orginal_data = array( 'file' => $filename, 'width' => $metadata['width'], 'height' => $metadata['height'] );
			if ( ! file_exists( $this->content_dir . "/$path/" . $filename ) )
				@copy( $this->content_dir . '/' . $metadata['file'], $this->content_dir . "/$path/" . $filename );
		}
		
		if (empty($metadata['sizes']['mini']))
			$metadata['sizes']['mini'] = $orginal_data;

		if (empty($metadata['sizes']['preview']))
			$metadata['sizes']['preview'] = $orginal_data;

		if (empty($metadata['sizes']['thumbnail']))
			$metadata['sizes']['thumbnail'] = $orginal_data;
		
		foreach ( $metadata['sizes'] as $size => $sizedata ) {
			$metadata['sizes'][$size]['url'] = $this->content_url . "/$path/" . $sizedata['file'];
			$metadata['sizes'][$size]['path'] = $this->content_dir . "/$path/" . $sizedata['file'];
		}
		
		return apply_filters( 'ims_generate_image_metadata', $metadata, $attachment_id, $path );
	}
	
	/**
	 * Save additional IPC data
	 *
	 * @param array $meta
	 * @param string $file
	 * @return array
	 * @since 2.0.0
	 */
	function extra_image_meta( $meta, $file ) {
		
		if ( !is_callable( 'iptcparse' ) )
			return $meta;

		$keywords = $info = '';
		getimagesize( $file, $info );

		if ( empty( $info['APP13'] ) )
			return $meta;

		$iptc = iptcparse( $info["APP13"] );

		if ( isset( $iptc["2#025"] ) ) {
			foreach( (array) $iptc["2#025"] as $words)
				$keywords .= "$words ";

			$iptcTags = array(
				"2#005" => 'title',
				"2#007" => 'status',
				"2#012" => 'subject',
				"2#015" => 'category',
				"2#020" => "supplemental_category",
				"2#055" => 'created_date',
				"2#060" => 'created_time',
				"2#065" => 'program_used',
				"2#070" => 'iptc_program_version',
				"2#080" => 'author',
				"2#085" => 'position',
				"2#090" => 'city',
				"2#092" => 'location',
				"2#095" => 'state',
				"2#100" => 'country_code',
				"2#101" => 'country',
				"2#105" => 'headline',
				"2#110" => 'credit',
				"2#115" => 'source',
				"2#116" => 'copyright',
				"2#118" => 'contact',
				"2#131" => "image_orientation",
				'2#135' => 'languague',
			);

			foreach ( apply_filters('ims_image_iptc_meta', $iptcTags, $file) as $key => $label ) {
				if ( isset( $iptc[$key][0] ) )
					$meta[$label] = $iptc[$key][0];
			}
		}
		return $meta;
	}
	
	/**
	 * Make images interlace when they are risized
	 *
	 * @param resouce $image
	 * @param unit $post_id
	 * @return resource
	 * @since 3.3.0 
	 */
	function image_save_pre( $image, $post_id ){
		if ( 'ims_image' != get_post_type( $postid ) )
			return $image;
		imageinterlace( $image, true );
		return $image;
	}
	
	/**
	 * Return image path for image (ims_image) to be edited
	 *
	 * @param string $filepath
	 * @param unit $postid
	 * @return string
	 * @since 0.5.0 
	 */
	function load_ims_image_path( $filepath, $postid ) {
		if ( 'ims_image' != get_post_type( $postid ) )
			return $filepath;

		$imagedata = get_post_meta( $postid, '_wp_attachment_metadata', true );

		if ( stristr( $imagedata['file'], 'wp-content' ) !== false )
			return str_ireplace( '_resized/', '', $imagedata['file'] );
		else return $this->content_dir . "/" . str_ireplace( '_resized/', '', $imagedata['file'] );
	}
	
	/**
	 * Add additional image sizes for gallery images
	 *
	 * @param array $size
	 * @return array
	 * @since 3.0.0
	 */
	function alter_image_sizes( $sizes ) {
		$postid = isset( $_REQUEST['postid'] ) ? $_REQUEST['postid'] : false;
		if ( $this->pagenow == 'upload-img.php' ||  'ims_image' == get_post_type( $postid ) )
			$sizes = apply_filters( 'ims_aternative_image_sizes', array('mini', 'thumbnail', 'preview' ) );
		return $sizes;
	}
	
	/* Movie resized images to a subfolder
	 *
	 * @param string $file
	 * @return string
	 * @since 3.0.0
	 */
	function move_resized_file( $file ) {
		if ( preg_match(" /(_resized)/i", $file ) )
			return $file;
		
		$postid = isset( $_REQUEST['postid'] ) ? $_REQUEST['postid'] : false;
		if ( $this->pagenow == 'upload-img.php' ||  'ims_image' == get_post_type( $postid ) ){
			$pathinfo = pathinfo( $file );
			$despath = $this->sanitize_path( $pathinfo['dirname'], 'notrim' ) . "/_resized/";
			
			if ( !file_exists( $despath ) )
				@mkdir( $despath, 0751, true );
			if ( copy( $file, $despath . $pathinfo['basename'] ) ) {
				@unlink( $file );
				$file = $despath . $pathinfo['basename'];
			}
		}
		return $file;
	}
	
	/**
	 * Save customer information using
	 * wordpress edit profile screen
	 *
	 * @param unit $user_id
	 * @return void
	 * @since 3.0.0
	 */
	function update_user( $user_id ) {
		if ( empty( $_REQUEST['role'] ) || $_REQUEST['role'] != $this->customer_role )
			return;

		foreach ( $this->user_fields as $key => $label ) {
			$data = isset( $_POST[$key] ) ? $_POST[$key] : '';
			update_user_meta( $user_id, $key, $data );
		}

		if ( !get_user_meta( $user_id, 'ims_status' ) )
			update_user_meta( $user_id, 'ims_status', 'active' );
	}
	
	/**
	 * Set settings when the pluigin
	 * is activated in the entire network 
	 *
	 * @param string $plugin
	 * @param boll $network_wide
	 * @return void
	 * @since 0.5.0 
	 */
	function activated_plugin( $plugin, $network_wide = false ) {
		
		if ( !$network_wide || $plugin != IMSTORE_FOLDER )
			return;

		$opts = get_site_option( $this->optionkey );
		
		if ( get_site_option( 'ims_sync_settings') && empty( $opts ) ) {
			
			include_once( IMSTORE_ABSPATH . '/admin/install.php' );
			
			$ImStoreInstaller = new ImStoreInstaller();
			$ImStoreInstaller->imstore_default_options( );
			
		} else {
			
			global $wpdb;
			$blogs = $wpdb->get_results(
				"SELECT blog_id id FROM $wpdb->blogs WHERE public = '1' AND archived = '0' AND deleted = '0'"
			);
			
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog->id );
				$customer = @get_role( $this->customer_role );
				
				if ( empty( $customer ) )
					add_role( $this->customer_role, 'Customer', array( 'read' => 1, 'ims_read_galleries' => 1 ) );
			}
			restore_current_blog( );
		}
	}
	
	/**
	 * Add cutomer role and expire column
	 * to blogs under wpmu
	 *
	 * @param unit $blog_id
	 * @return void
	 * @since 3.0.2
	 */
	function wpmu_create_blog( $blog_id ) {
		
		if ( !is_plugin_active_for_network( IMSTORE_FILE_NAME ) )
			return;

		switch_to_blog( $blog_id );
		include_once( IMSTORE_ABSPATH . '/admin/install.php' );
		
		new ImStoreInstaller( ); restore_current_blog( );
	}

	/**
	 * Update WPMU options
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function update_wpmu_options( ) {
		check_admin_referer( 'siteoptions' );
		update_site_option( 'ims_sync_settings', !empty( $_POST['ims_sync_settings'] ) );
	}
	
	/**
	 * Add WPMU opitons
	 *
	 * @return void
	 * @since 3.0.0
	 */
	function wpmu_options( ) {
		$sync = get_site_option( 'ims_sync_settings' );
		echo '<table class="form-table">
			<tr valign="top">
				<th scope="row"><label for="ims_settings">' . __( 'Image Store settings sync', 'ims' ) . '</label></td>
				<td>
					<label>
						<input type="checkbox" name="ims_sync_settings" id="ims_settings" value="1" ' . checked( 1, $sync, false ) . ' />
						' . __( 'Check to use the master settings for all the sites', 'ims' ) . '
					</label>
				</td>
			</tr>
		</table>';
	}
	
	/**
	 * Display network update button
	 *
	 * @param unit $blog_id
	 * @return void
	 * @since 3.2.0
	 */
	function network_update_button( $blog_id ){
		if( is_plugin_active_for_network( IMSTORE_FILE_NAME ) )
			echo '<p><a class="button" href="'. IMSTORE_ADMIN_URL . '/update.php">' . __( "Update Image Store" ) . '</a></p>';
	}
	
	/**
	 * Add screen settings to 
	 * image store screens
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function screen_settings( ) {
		
		$output = '';
		
		switch ( $this->screen_id ) {
			case 'ims_gallery':
				$option['ims_gallery'] = __( 'Images', 'ims' );
				break;
			case 'profile_page_user-galleries':
				$option['ims_user_galleries_per_page'] = __( 'Galleries', 'ims' );
				break;
			case 'profile_page_user-images':
				$option['ims_user_images_per_page'] = __( 'Images', 'ims' );
				break;
			case 'ims_gallery_page_ims-customers':
				$option['ims_sales_per_page'] = __( 'Customers', 'ims' );
				break;
			case 'ims_gallery_page_ims-pricing':
				$option['ims_pricing_per_page'] = __( 'Promotions', 'ims' );
				break;
			case 'ims_gallery_page_ims-sales':
				if ( isset( $_REQUEST['details'] ) )
					return;
				$option['ims_user_sales_per_page'] = __( 'Sales', 'ims' );
				break;
		}
		
		foreach ( $option as $key => $label ) {
			if ( $perpage =  get_user_option( $key ) )
				$this->per_page = esc_attr( ( int ) $perpage );
			$output = '<div class="screen-options"><h5>' . __( 'Show per page', 'ims' ) . '</h5>';
			$output .= '<input type="text" id="' . $key .
							'" class="screen-per-page" name="ims_screen_options[value]" maxlength="3" value="' . $this->per_page . '" > ';
			$output .= '<label for="' . $key . '"> ' . $label . '</label> ';
			$output .= '<input type="submit" class="button" value="' . esc_attr__( 'Apply', 'ims' ) . '">';
			$output .= '<input type="hidden" name="ims_screen_options[option]" value="' . esc_attr( $key ) . '" />';
			$output .= "</div>";
		}
		
		return $output;
	}
	
	/**
	 * Display additional customer roloe
	 * profile fields in edit profile screens
	 *
	 * @param obj $profileuser
	 * @return void
	 * @since 2.0.0
	 */
	function profile_fields( $profileuser ) {
		if ( empty( $profileuser->caps[ $this->customer_role ] ) )
			return;

		echo '<h3>', __( 'Address Information', 'ims' ), '</h3>';
		echo '<table class="form-table">';
		foreach ( $this->user_fields as $key => $label )
			echo '<tr>
					<th><label for="', $key, '">', $label, '</label></th>
					<td><input type="text" name="', $key, '" id="', $key, '" value="', 
					( isset( $profileuser->$key ) ? esc_attr( $profileuser->$key ) : '' ), '" class="regular-text" /></td>
				</tr>';
		echo '</table>';
	}
	
	/**
	 * Add aditional options to the price lists
	 *
	 * @param unit $list_id
	 * @return void
	 * @since 3.0.9
	 */
	function ims_pricelist_options( $list_id ) {
		$post = get_post( $list_id );
		$data = isset( $post->post_excerpt ) ? $post->post_excerpt : '';
		echo '<tr class="label"><td colspan="6"><label for="list_post_excerpt">' . __('Notes', 'ims') . '</label>
		<textarea id="list_post_excerpt" name="post_excerpt">' . esc_html ( $data ) . '</textarea> </td></tr>';
	}
 	
	/**
	 * Get all customers
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function get_active_customers( ) {
		
		$customers = wp_cache_get( 'ims_customers', 'ims' );
		if ( false == $customers ) {
			global $wpdb;

			$customers = $wpdb->get_results(
				"SELECT  ID, user_login FROM $wpdb->users AS u 
				LEFT JOIN $wpdb->usermeta um ON u.ID = um.user_id
				LEFT JOIN $wpdb->usermeta ur ON u.ID = ur.user_id 
				WHERE um.meta_key = 'ims_status' AND um.meta_value = 'active' 
				AND ( ur.meta_key = '{$wpdb->prefix}capabilities' AND ur.meta_value 
				LIKE '%\"". esc_sql( $this->customer_role) ."\"%' ) 
				GROUP BY u.id ORDER BY user_login+0 ASC" 
			);
			
			wp_cache_set( 'ims_customers', $customers, 'ims' );
		}
		return $customers;
	}
	
	/**
	 * Get all price list
	 *
	 * @return array
	 * @since 3.0.0
	 */
	function get_pricelists( ) {
		
		$pricelists = wp_cache_get( 'ims_pricelists', 'ims' );
		if ( false == $pricelists ) {
			global $wpdb;
			
			$pricelists = $wpdb->get_results( "SELECT ID, post_title FROM $wpdb->posts WHERE post_type = 'ims_pricelist'" );
			wp_cache_set( 'ims_pricelists', $pricelists, 'ims' );
		}
		return $pricelists;
	}
	
	/**
	 * Return link count status by type
	 *
	 * @param array $status
	 * @param array $args
	 * @return void
	 * @since 3.0.0
	 */
	function count_links( $status, $args = array() ) {
		
		extract( wp_parse_args( $args, array(
			'postid' => 0,
			'all' => false,
			'type' => false,
			'active' => false,
			'default_status' => 'active'
		) ) );
		
		if ( !$type ) return false;
		
		global $wpdb; $query = '';
		
		switch ( $type ) {
			case 'customer':
				$query = "SELECT um.meta_value status, count(um.meta_value) count 
				FROM $wpdb->usermeta um LEFT JOIN $wpdb->usermeta ur ON um.user_id = ur.user_id 
				WHERE um.meta_key = 'ims_status'  
				AND ( ur.meta_key =  '{$wpdb->prefix}capabilities' AND ur.meta_value LIKE '%\"". esc_sql( $this->customer_role) ."\"%' ) GROUP by um.meta_value";
				break;
			case 'order':
				$query = "SELECT post_status AS status, count(post_status) AS count FROM $wpdb->posts
				WHERE post_type = 'ims_{$type}' AND post_status != 'draft' GROUP by post_status";
				break;
			case 'image':
				$query = "SELECT post_status AS status, count( post_status ) AS count FROM $wpdb->posts 
				WHERE post_type = 'ims_image' AND post_status != 'auto-draft' AND post_parent = $postid GROUP by post_status";
				break;
		}
		
		$r = $wpdb->get_results( $query );
		if ( empty( $r ) ) return false;
		
		$total = 0;
		$links = array( );

		foreach ( $r as $obj ) {
			
			if( $obj->status != 'trash' && $all )
				$total += $obj->count;
			
			$current = ( $active == $obj->status ) ? ' class="current"' : false;
			
			$links[] = '<li class="status-' . $obj->status . '">
			<a href="' . $this->pageurl . '&amp;status=' . $obj->status . '"' . $current . '>' .
			$status[$obj->status] . ' <span class="count">(<span>' . $obj->count . '</span>)</span></a>';
			
		}
		
		if( $all ) {
			$current = ( !$active ) ? ' class="current"' : false;
			array_unshift( $links, '<li class="status-all"><a href="' . $this->pageurl. '"' . $current . ' >' . __( 'All', 'ims') . '
			<span class="count">(<span>' . $total . '</span>)</span></a></li>' );
		}
		
		$links = apply_filters( "ims_{$type}_status_links", $links, $r, $this->pageurl );
		echo implode('</li>', $links ) . '</li>';
	}
	
	/**
	 * Debugin function for sort columns
	 *
	 * @param string $query
	 * @return void
	 * @since 3.0.7
	 */
	function posts_request( $query ) {
		echo $query;
	}
	
	/**
	 * Verify columns sort
	 *
	 * @return bool
	 * @since 3.0.7
	 */
	function if_column( ) {
		global $wp_query;

		if ( empty( $wp_query->query['orderby'] ) )
			return false;

		if ( $wp_query->query['orderby'] == 'image_count' )
			return true;

		return false;
	}
	
	/**
	 * Control gallery sort columns
	 *
	 * @param string $sortby
	 * @param object $query
	 * @return string
	 * @since 3.0.7
	 */
	function posts_orderby_request( $sortby, $query ) {
		if ( empty( $_REQUEST['orderby'] ) )
			return $sortby;
		
		global $wpdb;
		switch ( $_REQUEST['orderby'] ) {
			case 'Expires':
				return "({$wpdb->postmeta}.meta_value+0) " . $query->query['order'];
			case 'Images':
				return "image_count " . $query->query['order'];
			case 'Visits':
				return "({$wpdb->postmeta}.meta_value+0) " . $query->query['order'];
			default: return $sortby;
		}
	}

	/**
	 * Select image count to sort image count column
	 *
	 * @param string $distinct
	 * @return string
	 * @since 3.0.7
	 */
	function posts_fields_request( $distinct ) {
		if ( ! $this->if_column( ) )
			return $distinct;
		return $distinct . ", (COUNT(images.ID)+0) image_count";
	}

	/**
	 * Join clause to sort image count column
	 *
	 * @param string $join
	 * @return string
	 * @since 3.0.7
	 */
	function galleries_column_join( $join ) {
		if ( ! $this->if_column( ) )
			return $join;
		global $wpdb;
		return $join . " LEFT JOIN $wpdb->posts images ON images.post_parent = $wpdb->posts.ID ";
	}

	/**
	 * Where clause to sort image count column
	 *
	 * @param string $where
	 * @return string
	 * @since 3.0.7
	 */
	function posts_where_request( $where ) {
		if ( ! $this->if_column( ) )
			return $where;
		return $where . " AND images.post_type = 'ims_image' AND images.post_status IN( 'publish', 'draft', 'trash') ";
	}

	/**
	 * Group by image to sort image count column
	 *
	 * @param string $groupby
	 * @return string
	 * @since 3.0.7
	 */
	function posts_groupby_request( $groupby ) {
		if ( ! $this->if_column( ) )
			return $groupby;
		return "images.post_parent";
	}
	
	/**
	 * Add values to sort columns
	 *
	 * @param array $vars
	 * @return array
	 * @since 3.0.7
	 */
	function galleries_column_orderby( $vars ) {
		if ( empty( $vars['orderby'] ) )
			return $vars;
		switch ( $vars['orderby'] ) {
			case 'Expires':
				$vars['orderby'] = 'post_expire';
				$vars['meta_key'] = '_ims_post_expire';
				break;
			case 'Visits':
				$vars['orderby'] = 'meta_value';
				$vars['meta_key'] = '_ims_visits';
				break;
			case 'Images':
				$vars['orderby'] = 'image_count';
				break;
			case 'ID':
				$vars['orderby'] = 'meta_value';
				$vars['meta_key'] = '_ims_gallery_id';
				break;
			case 'Tracking':
				$vars['orderby'] = 'meta_value';
				$vars['meta_key'] = '_ims_tracking';
				break;
			default:
				return $vars;
		}
	}
	
	/**
	 * Delete folder
	 *
	 * @param string $dir 
	 * @since 2.0.0
	 * return boolean
	 */
	function delete_folder( $dir ) {
		if ( $dh = @opendir( $dir ) ) {
			while ( false !== ( $obj = readdir( $dh ) ) ) {
				if ( $obj == '.' || $obj == '..' ) continue;
				if ( is_dir( "$dir/$obj" ) )
					$this->delete_folder( "$dir/$obj" );
				else @unlink("$dir/$obj");
			}
			closedir($dh);
			return rmdir( $dir );
		}
	}
	
	/**
	 * Deprecated
	 */
	function add_columns_val_gal( $column_name, $postid ) {
		$this->add_columns_gallery_val( $column_name, $postid );
	}
	
	/**
	 * Deprecated
	 */
	function add_columns_val( $column_name, $postid ) {
		$this->add_columns_user_val( $column_name, $postid );
	}
	
}