<?php

/*
  Plugin Name: Image Store
  Plugin URI: http://xparkmedia.com/plugins/image-store/
  Description: Your very own image store within wordpress "ImStore"
  Author: Hafid R. Trujillo Huizar
  Version: 3.4.1
  Author URI:http://www.xparkmedia.com
  Requires at least: 3.0.0
  Tested up to: 3.8
  Text Domain: ims

  Copyright 2010-2013 by Hafid Trujillo http://www.xparkmedia.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License,or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not,write to the Free Software
  Foundation,Inc.,51 Franklin St,Fifth Floor,Boston,MA 02110-1301 USA
 */


// Stop direct access of the file
if ( !defined( 'ABSPATH' ) )
	die( );
	
if ( ! class_exists( 'ImStore' ) && ! defined( 'IMSTORE_ABSPATH' ) ) {

	//define constants
	define( 'IMSTORE_FILE_NAME', plugin_basename( __FILE__ ) );
	define( 'IMSTORE_FOLDER', plugin_basename( dirname( __FILE__ ) ) );
	define( 'IMSTORE_ABSPATH', str_replace( "\\", "/", dirname( __FILE__ ) ) );
	
	//include core class
	include_once( IMSTORE_ABSPATH . "/_inc/core.php");
	
	global $ImStore;
	
	//back end
	if ( is_admin( ) ) {
		
		global $pagenow;
		include_once( IMSTORE_ABSPATH . "/_inc/admin.php" );
		
		// set $page now if empty
		if ( empty( $pagenow ) )
			$pagenow = basename( $_SERVER['SCRIPT_NAME'] );
			
		// set post type
		if( isset( $_GET['post_type'] ) )
			$post_type = $_GET['post_type'];
		else if ( isset( $_GET['post'] ) )
			$post_type = get_post_type( $_GET['post'] );
		else if ( isset( $_REQUEST['post_ID'] ) )
			$post_type = get_post_type( $_REQUEST['post_ID'] );
		else $post_type = false;
		
		// set page and page action
		$page = isset( $_GET['page'] ) ? $_GET['page'] : false;
		$action = isset( $_GET['action'] ) ? $_GET['action'] : false;
		
				
		// edit / new gallery
		if ( $pagenow == 'upload-img.php' || ( $post_type == 'ims_gallery' 
			&& in_array( $pagenow, array( 'post.php', 'post-new.php') ) ) ) {
			include_once( IMSTORE_ABSPATH . "/_inc/galleries.php" );
			$ImStore = new ImStoreGallery( $page, $action ); 
			
		//setting page	
		} elseif ( $post_type == 'ims_gallery' && $page == 'ims-settings' ) {
			include_once( IMSTORE_ABSPATH . "/_inc/settings.php" );
			$ImStore = new ImStoreSet( $page, $action ); 
		
		// pricing page
		} elseif ( $post_type == 'ims_gallery' && $page == 'ims-pricing' ) {
			include_once( IMSTORE_ABSPATH . "/_inc/pricing.php" );
			$ImStore = new ImStorePricing( $page, $action ); 
		
		// customer page
		} elseif ( $post_type == 'ims_gallery' && $page == 'ims-customers' ) {
			include_once( IMSTORE_ABSPATH . "/_inc/customers.php" );
			$ImStore = new ImStoreCustomers( $page, $action ); 
		
		// sales page
		} elseif ( $page == 'ims-sales' ) {
			include_once( IMSTORE_ABSPATH . "/_inc/sales.php" );
			$ImStore = new ImStoreSales( $page, $action ); 
		
		// everywhere esle in admin area
		} else $ImStore = new ImStoreAdmin( $page, $action );
		
	
	//front end
	} else{
		include_once( IMSTORE_ABSPATH . "/_inc/store.php" );
		$ImStore = new ImStoreFront( );
	} 
}