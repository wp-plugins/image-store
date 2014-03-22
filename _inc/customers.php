<?php

/**
 * Image Store - Customers
 *
 * @file customers.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/customers.php
 * @since 3.2.1
 */
 
class ImStoreCustomers extends ImStoreAdmin {
	
	/**
	 * Public variables
	 */
	public $status = false;
	public $hidden = array( );
	public $columns = array( );

	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreCustomers( $page, $action ) {
		
		$this->ImStoreAdmin( $page, $action );
		
		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
			
		$this->status = isset( $_GET['status'] ) ? trim( $_GET['status'] ) : 'active';
		
		add_filter( 'auth_redirect', array( &$this, 'user_actions' ), 5 );
		add_filter( 'paginate_links', array( &$this, 'user_page_links' ), 20 );
		add_filter( 'pre_user_search', array( &$this, 'customer_search_query' ), 20 );
		
		add_action( 'ims_before_user_list', array( &$this, 'edit_user_form' ), 20, 2 );
		add_action( 'admin_print_styles', array( &$this, 'register_screen_columns' ), 10 );
	}
	
	/**
	 * User pages actions
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function user_actions( ){
		
		//clear cancel post data
		if ( isset( $_POST['cancel'] ) ) {
			wp_redirect( $this->pageurl );
			die( );
		}
		
		//add/update customer
		if ( isset( $_POST['add_customer'] ) || isset( $_POST['update_customer'] ) ) {
			check_admin_referer( 'ims_update_customer' );
			$errors = $this->update_customer( );
		}
		
		//update user status
		if ( !empty( $_GET['imsaction'] ) ) {
			check_admin_referer( 'ims_update_customer' );
			$errors = $this->update_customer_status( );
		}

		//display error message
		if ( isset($errors) && is_wp_error( $errors ) )
			$this->error_message( $errors );
	}
	
	/**
	 * Filter user results by status in 
	 * the image store customer screen.
	 *
	 * @param obj $query
	 * @return void
	 * @since 3.2.1
	 */
	function customer_search_query( &$query ) {
		if ( $this->page != 'ims-customers' )
			return;
			
		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'active';
		$s = " meta_value LIKE '%$query->search_term%' OR display_name";
		
		global $wpdb;

		if ( $query->search_term )
			$query->query_where = str_ireplace( 'display_name', $s, $query->query_where );
			
		else $query->query_where .= $wpdb->prepare( " AND $wpdb->usermeta.user_id IN (
			SELECT u.user_id FROM $wpdb->usermeta u 
			WHERE meta_key = 'ims_status' AND meta_value = %s GROUP by u.user_id )", $status );
		
		if ( isset( $this->per_page ) ) {
			$page = ( $query->page - 1 );
			$limit = ( $page ) ? ( $this->per_page * $page ) : $this->per_page;
			$query->query_limit = " LIMIT $page, $limit";
			$query->users_per_page = $this->per_page;
		}
	}
	
	/**
	 * Modify paging link for customers
	 *
	 * @return string
	 * @since 3.0.0
	 */
	function user_page_links( $link ) {
		if ( $this->page != 'ims-customers' )
			return $link;
		return str_replace('users.php?', 'edit.php?post_type=ims_gallery&page=ims-customers&', $link);
	}
	
	/**
	 * Register user columns
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function register_screen_columns( ){
		
		if ( $this->screen_id  != 'ims_gallery_page_ims-customers' )
			return;
			
		$this->columns = array(
			'cb' => '<input type="checkbox">', 'name' => __( 'First Name', 'ims' ),
			'lastname' => __( 'Last Name', 'ims' ), 'email' => __( 'E-Mail', 'ims' ),
			'phone' => __( 'Phone', 'ims' ), 'city' => __( 'City', 'ims' ), 'state' => __( 'State', 'ims' ),
		);
		
		if ( class_exists( 'MailPress' ) ) 
			$this->columns['newsletter'] = __( 'eNewsletter', 'ims' );
		
		add_filter( 'screen_settings', array( &$this, 'screen_settings' ), 15, 2 );

		register_column_headers( 'ims_gallery_page_ims-customers', $this->columns );
		$this->hidden = ( array) get_hidden_columns( 'ims_gallery_page_ims-customers' );
	}
	
	/**
	 * Save edit customer
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function edit_user_form( $action, $userid ){
		if( empty( $action ) )
			return;
		
		$data = array( );
		
		$user_box_title = array(
			'new' => __( 'New Customer', 'ims' ),
			'edit' => __( 'Edit Customer', 'ims' ),
		);
		
		$default = array(
			'first_name' => false, 'last_name'  => false, 'ims_address' => false,
			'ims_city' => false, 'ims_state' => false, 'ims_phone' => false,
			'ims_zip' => false, 'user_email' => false, '_MailPress_sync_wordpress_user'  => false,
		);
			
		if( $action == 'edit' && $userid && empty( $_POST ) ){
			$user = get_user_meta( $userid, false );
			foreach( $user as $meta => $value )
				$data[$meta] = $value[0];
			$data['user_email'] = get_the_author_meta(  'user_email', $userid );
		} else if( !empty( $_POST ) ) $data = $_POST;
		
		
		extract( wp_parse_args( $data, $default ) );
		$this->include_file( 'customer-edit', 'admin/customers' );
	}
	
	/**
	 * Update a customer status
	 *
	 * @since 3.2.1
	 * return array errors
	 */
	function update_customer_status( ){

		if( empty( $_GET['customer'] )  || empty( $_GET['imsaction'] ) )
			return false;
		
		foreach( ( array ) $_GET['customer'] as $customer )
			$customers[] = intval( $customer );
			
		$count = count( $customers );
		$customers = implode( ', ', $customers );
		
		$ms = '';
		$action = $_GET['imsaction'];
		
		global $wpdb;
		
		if ( $action == 'delete' ) {
			$ms = "15&status=inative";
			$updated = $wpdb->query(  
				"DELETE u, um FROM $wpdb->users u JOIN $wpdb->usermeta um 
				ON ( u.id = um.user_id ) AND u.id IN ( $customers ) " 
			);
		} else if( !empty( $action ) ) {
			$ms = 14;
			$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE $wpdb->usermeta SET meta_value = '%s' 
			WHERE meta_key = 'ims_status' AND user_id IN( $customers )"
			, $action ) );
		}
		
		if ( empty( $updated ) )
			return false;
		
		do_action( 'ims_update_users', $customers );
		wp_redirect( $this->pageurl . "&ms={$ms}&c=$count" );
		die( );
	}
	
	/**
	 * Insert/update a customer
	 *
	 * @since 3.2.1
	 * return array errors
	 */
	function update_customer( ) {
		
		$errors = new WP_Error( );
		
		$userid = isset( $_POST['userid'] ) ? $_POST['userid'] : false;
		$action = empty( $_POST['useraction'] ) ? false : $_POST['useraction'];
		
		if ( empty( $_POST['first_name'] ) )
			$errors->add( 'empty_first_name', __( 'The first name is required.', 'ims' ) );
		
		if ( empty( $_POST['last_name'] ) )
			$errors->add('empty_last_name', __( 'The last name is required.', 'ims' ) );

		if ( empty( $_POST['last_name'] ) || !is_email( $_POST['user_email'] ) )
			$errors->add( 'valid_email', __( 'A valid email is required.', 'ims' ) );
		
		if ( !empty( $errors->errors ) )
			return $errors;
		
		$user = get_user_by( 'id', $userid );
		
		if ( email_exists( $_POST['user_email'] ) && $action != 'edit' )
			$errors->add( 'email_exists', __( 'This email is already registered, please choose another one.', 'ims' ) );
		
		if ( $action == 'edit' && $user->user_email != $_POST['user_email'] && email_exists( $_POST['user_email'] ) )
			$errors->add( 'email_exists', __( 'This email is already registered, please choose another one.', 'ims' ) );
		
		$user_name = sanitize_user( $_POST['first_name'] . ' ' . $_POST['last_name'] );
		
		if ( username_exists( $user_name) && $action != 'edit' )
			$errors->add( 'customer_exists', __( 'That customer already exists.', 'ims' ) );
		
		$errors = apply_filters( 'ims_save_user_errors', $errors, $_POST, $user );
		
		if ( !empty( $errors->errors ) )
			return $errors;
			
		$userdata = array(
			'ID' => $userid,
			'user_login' => $user_name,
			'role' => $this->customer_role,
			'user_nicename' => $user_name,
			'user_email' => $_POST['user_email'],
			'first_name' => $_POST['first_name'],
			'last_name' => $_POST['last_name'],
			//'user_pass' => wp_generate_password(12, false),
		);
		
		$user_id = wp_insert_user( $userdata );
		
		if ( is_wp_error( $user_id) )
			return $user_id;
		
		if ( $action == 'new' || !get_user_meta( $user_id, 'ims_status') )
			update_user_meta( $user_id, 'ims_status', 'active' );
		
		$meta = array( 'ims_zip', 'ims_city', 'ims_phone', 'ims_state', 'ims_address', '_MailPress_sync_wordpress_user' );
		
		foreach ( $meta as $key ){
			$val = isset( $_POST[$key] ) ? $_POST[$key] : false;
			update_user_meta( $user_id, $key, $val );
		}
		
		do_action( 'ims_update_user', $user_id, $action );
		
		$msid = ( $action == 'new' ) ? 10 : 2;
		wp_redirect( $this->pageurl . "&ms=$msid");
		die( );
	}
}