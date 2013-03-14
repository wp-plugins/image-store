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
		
		if ( !is_multisite( ) )
			wp_die( __( 'Multisite support is not enabled.' ) );
			
		if ( !current_user_can( 'manage_network' ) )
			wp_die(  __( 'You do not have permission to access this page.' ) );
			
		$n = ( isset( $_GET['n'] ) ) ? intval( $_GET['n'] ) : 0;
		
		
		//using site sync only update main site
		if( get_site_option( 'ims_sync_settings') && $n == 0 ){

			include_once( IMSTORE_ABSPATH . '/admin/install.php' );
			new ImStoreInstaller( );
			
			wp_redirect( admin_url( 'network/upgrade.php?ims-network-updated' ) );
			die( );
		}
		
		$blogs = $wpdb->get_results( 
			"SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' 
			AND spam = '0' AND deleted = '0' AND archived = '0'
			ORDER BY registered DESC LIMIT {$n}, 5", 'ARRAY_A' 
		);
		
		if( $blogs ){
			include_once( IMSTORE_ABSPATH . '/admin/install.php' );
			foreach ( (array) $blogs as $details ) {
				switch_to_blog( $details['blog_id'] );
				new ImStoreInstaller( );
			}
			wp_redirect( IMSTORE_ADMIN_URL . '/update.php?n=' . (  $n + 5 ) );
			die( );
		}
		
		wp_redirect( admin_url( 'network/upgrade.php?ims-network-updated' ) );
		die( );