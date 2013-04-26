<?php

/**
 * Image Store - Sales
 *
 * @file sales.php
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_inc/sales.php
 * @since 3.2.1
 */
 
class ImStoreSales extends ImStoreAdmin {
	
	/**
	 * Public variables
	 */
	public $cdate = false;
	public $status = false;
	public $search = false;
	public $is_trash = false;
	
	public $hidden = array( );
	public $columns = array( );
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function ImStoreSales( $page, $action ) {
	
		$this->ImStoreAdmin( $page, $action );
		
		//speed up wordpress load
		if ( defined( 'DOING_AJAX' ) || defined( 'DOING_AUTOSAVE' ) || SHORTINIT )
			return;
		
		if( isset( $_GET['m'] ) )
			$this->cdate = ( int ) $_GET['m'];
		
		if( isset( $_GET['status'] ) )
			$this->status = trim( $_GET['status'] );
			
		if( isset( $_GET['osearch'] ) )
			$this->search  = trim( $_GET['osearch'] );
	
		$this->is_trash = isset( $_GET['status' ] ) && ( $_GET['status'] == 'trash' );
	
		add_filter( 'auth_redirect', array( &$this, 'user_actions' ), 5 );
		add_filter( 'ims_order_status',  array( &$this, 'order_status' ) );
		add_filter( 'posts_where',  array( &$this, 'order_status_query' ) );
		
		add_action( 'admin_print_styles', array( &$this, 'register_screen_columns' ), 10 );
	}
	
	/**
	 * User pages actions
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function user_actions( ){
				
		if ( empty( $_GET['orders'] ) || empty( $_GET['doaction'] ) )
			return;
			
		$this->orders = (array) $_GET['orders'] ;
		$this->count = count( $this->orders );
		
		if ( $_GET['order-action'] == 'delete' ) 
			$this->delete_orders( );
		else $this->change_order_status( );
	}
	
	/**
	 * Return sales detail link
	 *
	 * @param unit $id
	 * @return string
	 * @since 3.2.1
	 */
	function sales_link( $id ){
		return $this->pageurl . "&amp;details=1&amp;id={$id}" ;
	}
	
	/**
	 * Filter user status
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function order_status( $status ){
		if( !$this->is_trash && isset( $status['delete'] ) )
			unset( $status['delete'] );
		return $status;
	}
	
	/**
	 * Register sales columns
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function register_screen_columns( ){
		
		wp_enqueue_style( 'ims-sales', IMSTORE_URL . '/_css/sales.css', false, $this->version, 'all' );
		wp_enqueue_style( 'ims-print', IMSTORE_URL . '/_css/print.css', false, $this->version, 'print' );

		if ( $this->screen_id  != 'ims_gallery_page_ims-sales' || isset( $_GET['details'] ) )
			return;
		
		$this->columns = array(
			'cb' => '<input type="checkbox">',
			'ordernum' => __( 'Order number', 'ims'), 'orderdate' => __( 'Date', 'ims' ),
			'amount' => __( 'Amount', 'ims' ), 'customer' => __( 'Customer', 'ims' ),
			'images' => __( 'Images', 'ims' ), 'paystatus' => __( 'Payment status', 'ims' ),
			'orderstat' => __( 'Order Status', 'ims' )
		);
			
		add_filter( 'screen_settings', array( &$this, 'screen_settings' ), 15, 2 );

		register_column_headers( 'ims_gallery_page_ims-sales', $this->columns );
		$this->hidden = ( array) get_hidden_columns( 'ims_gallery_page_ims-sales' );
	}
	
	/**
	 * Delete orders
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function delete_orders( ) {
		
		if( empty( $this->orders ) )
			return;
			
		global $wpdb;
		$wpdb->query(
			"DELETE p, pm FROM $wpdb->posts p 
			LEFT JOIN $wpdb->postmeta pm ON( p.ID = pm.post_id ) 
			WHERE ID IN( " . $wpdb->escape( implode( ',', $this->orders ) ) . ")
			AND post_type = 'ims_order'"
		);
		
		$a = ( $this->count < 2 ) ? 31 : 39;
		wp_redirect( $this->pageurl . "&ms=$a&c=$this->count");
		die( );
	}
	
	/**
	 * Change order status
	 *
	 * @return void
	 * @since 3.2.1
	 */
	function change_order_status( ){
		
		if( empty( $this->orders ) )
			return;
			
		$s = false;
		if ( !empty( $_GET['payment-action'] ) ){
			foreach ( $this->orders as $id ) {
				$data = get_post_meta( $id, '_response_data', true );
				$data['payment_status'] = trim( $_GET['payment-action'] );
				update_post_meta( $id, '_response_data', $data );
			}
		}
		
		if ( !empty( $_GET['order-action'] ) ) {
			
			global $wpdb;
			$action = trim( $_GET['order-action'] );
			
			$wpdb->query( $wpdb->prepare( 
				"UPDATE $wpdb->posts SET post_status = %s
				WHERE ID IN( " . $wpdb->escape( implode( ',', $this->orders ) ) . ")"
			, $action ) );
			
			$s = $action == 'trash' ;
		}
		
		if ( $this->count > 1 && !$s )
			$a = 25;
		elseif ( $this->count > 1 && $s)
			$a = 26;
		elseif ( $s )
			$a = 22;
		else
			$a = 23;
		
		wp_redirect( $this->pageurl . "&ms=$a&c=$this->count" );
		die( );
	}
	
	/**
	 * Get order archive
	 *
	 * @return array
	 * @since 3.2.1
	 */
	function order_archive( ) {
		
		global $wpdb;
		$status = empty( $this->status ) ? " NOT IN ( 'draft', 'trash' ) " : " = '". $wpdb->escape( $this->status ) ."' ";
		$r = wp_cache_get( 'ims_order_archive_' . $this->status, 'ims' );
	
		if ( false == $r ) {
			
			$r = $wpdb->get_results("
				SELECT  YEAR( post_date ) y, MONTH ( post_date )  m, UNIX_TIMESTAMP( post_date ) t
				FROM $wpdb->posts WHERE post_status $status  AND post_status != 'draft' 
				AND post_type = 'ims_order' AND post_date != 0 group by y, m");
			
			wp_cache_set( 'ims_order_archive_' . $this->status, $r, 'ims' );
		}
		
		return $r;
	}
		
	/**
	 * Filter post status query
	 *
	 * @return string
	 * @since 3.2.1
	 */
	function order_status_query( $where ) {
		
		global $wpdb;
		
		if ( $this->status )
			$where .= " AND $wpdb->posts.post_status = '" . $wpdb->escape( $this->status ) . "' ";
		else $where = str_ireplace( "status = 'draft'", "status NOT IN ( 'draft', 'trash' )", $where );
		
		if ( $this->search ){
			$search = $wpdb->escape( $this->search );
			$where .= " AND ( $wpdb->posts.post_title LIKE '%$this->search%'
			OR  $wpdb->posts.post_excerpt LIKE '%$search%' 
			OR $wpdb->postmeta.meta_value LIKE '%$search%' ) ";
		}
		
		if ( $this->cdate ) {
			$month = substr( $this->cdate , 4 );
			$year 	= substr( $this->cdate, 0, 4 );
			$where .= " AND YEAR ( $wpdb->posts.post_date ) = '$year' 
			AND MONTH ( $wpdb->posts.post_date ) = '$month' ";
		}
		
		return $where;
	}
}