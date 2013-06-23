<?php

	/**
	 * Image Store - Customer CSV Download
	 *
	 * @file customers-csv.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/customers/customers-csv.php
	 * @since 0.5.0
	 */
	 
	//define constants
	define( 'WP_ADMIN', true );
	define( 'DOING_AJAX', true );
	
	$_SERVER['PHP_SELF'] = "/wp-admin/customers-csv.php";

	//load wp
	require_once ( "../../../../../wp-load.php" );
	
	check_admin_referer( 'ims_update_customer' );
	
	//check that a user has the right permisssion
	if( !current_user_can( 'ims_manage_customers' ) )
		die( );
	
	$enco = get_bloginfo( 'charset' );
	
	//don't cache file
	header( 'Cache-control:private' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Last-Modified:' . gmdate( 'D,d M Y H:i:s' ) . ' GMT' );
	header( 'Cache-control:no-cache,no-store,must-revalidate,max-age=0' );
	
	header( 'Content-Description:File Transfer' );
	header( 'Content-Transfer-Encoding: binary' ); 
	header( 'Content-type: application/vnd.ms-excel;  charset=' . "$enco; encoding=$enco" );
	header( 'Content-Disposition:attachment; filename=image-store-customers.csv' );
	
	$query = apply_filters( 'ims_customers_csv_query', 
		"SELECT ID FROM $wpdb->users AS u
		INNER JOIN $wpdb->usermeta AS um ON u.ID = um.user_id 
		WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%\"". $wpdb->escape( $this->customer_role )  ."\"%' 
		GROUP BY u.ID"
	);
		
	$results = $wpdb->get_results( $query, 'ARRAY_N' );
	if( empty( $results ) ) die( );
	
	$columns = apply_filters( 'ims_customers_csv_columns', array(
			'first_name'	=> __( 'First Name', 'ims'),
			'last_name'	=> __( 'Last Name', 'ims'),
			'user_email'	=> __( 'E-mail', 'ims'),
			'ims_address'	=> __( 'Address', 'ims'),
			'ims_city'		=> __( 'City', 'ims'),
			'ims_state'		=> __( 'State', 'ims'),
			'ims_zip'		=> __( 'Zip', 'ims'),
			'ims_phone' 	=> __( 'Phone', 'ims'),
			'ims_status' 	=> __( 'Status', 'ims'),
		)
	);
	
	$str = '';
	foreach( $columns as $column ) $str .= $column ."\t"; $str .= "\n";
	foreach( $results as $result ){
		$customer = get_userdata( $result[0] );
		foreach( $columns as $key => $column )
			$str .= isset( $customer->$key ) ? str_replace( ', ', '', $customer->$key ) . "\t" : "\t";
		$str .= "\n";
	}
	
	echo  chr( 255 ) . chr( 254 ) . mb_convert_encoding( $str . "\n",  'UTF-16LE', $enco ) ;
	die( );