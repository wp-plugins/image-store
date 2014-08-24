<?php

	/**
	 * Image Store - Update Multisite Script
	 *
	 * @file update.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/update.php
	 * @since 3.2.0
	 */
		
		//define constants
		define( 'WP_ADMIN', true );
		$_SERVER['PHP_SELF'] = "/wp-admin/imstore-update.php";
		
		//load wp
		require_once '../../../../wp-load.php';
		
		
		if( isset( $_GET['single'] ) && is_numeric( $_GET['single']  ) ){
			
			if ( is_multisite( ) )
				switch_to_blog( $_GET['single'] );
			
			include_once( IMSTORE_ABSPATH . '/admin/install.php' );
			$ImStoreInstaller = new ImStoreInstaller( );
			$ImStoreInstaller->init( );
			
			if ( is_multisite( ) )
				wp_redirect( network_admin_url( '?post_type=ims_gallery&ims-updated' ) );
			else wp_redirect( admin_url( '/edit.php?post_type=ims_gallery&ims-updated' ) );
			die( );
		}
		
		if ( ! is_multisite( ) )
			wp_die( __( 'Multisite support is not enabled.' ) );
			
		if ( ! current_user_can( 'manage_network' ) )
			wp_die(  __( 'You do not have permission to access this page.' ) );
			
		$n = ( isset( $_GET['n'] ) ) ? intval( $_GET['n'] ) : 0;
		
		$blogs = $wpdb->get_results( 
			"SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' 
			AND spam = '0' AND deleted = '0' AND archived = '0'
			ORDER BY registered DESC LIMIT {$n}, 5", 'ARRAY_A' 
		);
		
		if( $blogs ){
			include_once( IMSTORE_ABSPATH . '/admin/install.php' );
			foreach ( (array) $blogs as $details ) {
				switch_to_blog( $details['blog_id'] );
				$ImStoreInstaller = new ImStoreInstaller( );
				$ImStoreInstaller->init( );
			}
			wp_redirect( IMSTORE_ADMIN_URL . '/update.php?n=' . (  $n + 5 ) );
			die( );
		}
		
		global $wp_version;
		if( $wp_version < 3.1 )  wp_redirect ( admin_url( "/ms-upgrade-network.php" ) );
		else wp_redirect( network_admin_url( 'upgrade.php?ims-network-updated' ) );
		die( );