<?php 
/*
Plugin Name: Image Store
Plugin URI: http://imstore.xparkmedia.com
Description: Your very own image store within wordpress "ImStore"
Author: Hafid R. Trujillo Huizar
Version: 0.5.2
Author URI: http://www.xparkmedia.com
Requires at least: 3.0.0
Tested up to: 3.0.1

Copyright 2009-2010 by Hafid Trujillo http://www.xparkmedia.com

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/ 


// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );
	
if ( !class_exists( 'ImStore' ) ) {

class ImStore{
	
	/**
	 * Variables
	 *
	 * @param $domain plugin Gallery IDentifier
	 * Make sure that new language(.mo) files have 'ims-' as base name
	 */
	const domain	= 'ims';
	const version	= '0.5.0';
	
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function __construct( ){
		
		$this->load_text_domain( );
		$this->define_constant( );
		
		// register options
		register_activation_hook( IMSTORE_FILE_NAME, array( &$this, 'activate' ) );
		register_deactivation_hook( IMSTORE_FILE_NAME, array( &$this, 'deactivate' ) );
		
		//create imstore custom pages
		add_filter( 'query_vars', array( &$this, 'add_var_for_rewrites' ), 10, 1 );
		add_filter( 'wp_insert_post_data', array( &$this, 'insert_post_data' ), 12, 2 );
		
		add_action( 'init', array( &$this, 'ims_int_actions' ), 12 );
		add_action( 'wp_logout', array( &$this, 'logout_ims_user' ), 10 );
		add_action( 'imstore_expire', array( &$this, 'expire_galleries' ) );
		add_action( 'generate_rewrite_rules', array( &$this, 'add_rewrite_rules' ), 10, 1 );
		$this->load_dependencies( );
		
	}
	
	
	/**
	 * logout user 
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function logout_ims_user( ){
		setcookie( 'ims_cookie_' . COOKIEHASH, ' ', time( ) - 31536000, COOKIEPATH, COOKIE_DOMAIN );
		setcookie( 'ims_orderid_' . COOKIEHASH, ' ', time( ) - 31536000, COOKIEPATH, COOKIE_DOMAIN );
	}
	
	
	/**
	 * Allow wp_insert_post to ad expiration date 
	 * on the custom "post_expire "column
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function insert_post_data( $data, $postarr ){
		if( !empty( $postarr['post_expire'] ))
			$data['post_expire'] = $postarr['post_expire'];
		return $data;
	}
	
	
	/**
	 * Register localization/language file
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_text_domain( ) {
		if ( function_exists( 'load_plugin_textdomain' ) ){
			$plugin_dir = basename( dirname( __FILE__ ) ) . '/langs';
			load_plugin_textdomain( $this->domain, 'wp-content/plugins/' . $plugin_dir, $plugin_dir );
		}
	}
	
	
	/**
	 * Define contant variables
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function define_constant( ) {
		ob_start( ); //fix redirection problems
		define( 'IMSTORE_FILE_NAME', plugin_basename(__FILE__) );
		define( 'IMSTORE_FOLDER', plugin_basename( dirname(__FILE__) ) );
		define( 'IMSTORE_ABSPATH', str_replace( "\\", "/", WP_PLUGIN_DIR . '/' . plugin_basename( dirname(__FILE__) ) . '/' ) );
		define( 'IMSTORE_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) . '/' );
		define( 'IMSTORE_ADMIN_URL', IMSTORE_URL . 'admin/' );
		if(!defined( 'WP_CONTENT_URL' )) 
			define( 'WP_CONTENT_URL', get_bloginfo( 'url' ) . '/wp-content' );
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
		wp_schedule_event( strtotime( "tomorrow 1 hours" ) , 'twicedaily', 'imstore_expire' );
		include_once ( dirname (__FILE__) . '/admin/install.php' );
		new ImStoreInstaller( );
	}
	
	
	/**
	 * Initial actions
	 * flush rewrites
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function ims_int_actions( ){
		global $wp_rewrite;
		$wp_rewrite->flush_rules( );
	}
	
	
	/**
	 * Add rewrite vars
	 *
	 * @param array $vars
	 * @return array
	 * @since 0.5.0 
	 */
	function add_var_for_rewrites( $vars ){
		array_push( $vars, 'imsgalid', 'imspage', 'imsmessage', 'imslogout' );
		return $vars;
	}
	
	
	/**
	 * Rewrites for custom page managers
	 *
	 * @param array $wp_rewrite
	 * @return array
	 * @since 0.5.0 
	 */
	function add_rewrite_rules( $wp_rewrite ) {	
	
		$wp_rewrite->add_rewrite_tag( '%imspage%', '([^/]+)', 'imspage=');
		$wp_rewrite->add_rewrite_tag( '%imsgalid%', '([0-9]+)', 'imsgalid=');
		$wp_rewrite->add_rewrite_tag( '%imslogout%', '([^/]+)', 'imslogout=');
		$wp_rewrite->add_rewrite_tag( '%imsmessage%', '([0-9]+)', 'imsmessage=');
		
		$new_rules = array(
			"(.+?)/([^/]+)/gal-([0-9]+)/ms/?([0-9]+)/?$" => 
			"index.php?pagename=" . $wp_rewrite->preg_index(1).
			'&imspage=' . $wp_rewrite->preg_index(2).
			'&imsgalid=' . $wp_rewrite->preg_index(3).
			'&imsmessage=' . $wp_rewrite->preg_index(4),
			"(.+?)/([^/]+)/gal-([0-9]+)/?$" => 
			"index.php?pagename=" . $wp_rewrite->preg_index(1).
			'&imspage=' . $wp_rewrite->preg_index(2).
			'&imsgalid=' . $wp_rewrite->preg_index(3),
			"(.+?)/logout/?([^/]+)?$" => 
			"index.php?pagename=" . $wp_rewrite->preg_index(1).
			'&imslogout=' . $wp_rewrite->preg_index(2),
			"(.+?)/([^/]+)/?$" => 
			"index.php?pagename=" . $wp_rewrite->preg_index(1).
			'&imspage=' . $wp_rewrite->preg_index(2),

		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		
	}
	
	
	/**
	 * Set galleries to expired
	 * and delete unprocess orders
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function expire_galleries( ){
		global $wpdb;
		$wpdb->query( 
			"UPDATE $wpdb->posts SET post_status = 'expire' 
			WHERE post_expire <= '" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "'
			AND post_type = 'ims_gallery'"
		);
		$wpdb->query( 
			"DELETE p,pm FROM $wpdb->posts p 
			LEFT JOIN $wpdb->postmeta pm ON ( p.ID = pm.post_id ) 
			WHERE post_expire <='" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "'
			AND post_type = 'ims_order' AND post_status = 'draft'"
		);
		$wpdb->query( "OPTIMIZE TABLE $wpdb->terms, $wpdb->postmeta, $wpdb->posts, $wpdb->term_relationships, $wpdb->term_taxonomy" );
	}
	
	
	/**
	 * Load what is needed where is needed
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_dependencies( ){
		if ( is_admin( ) && !class_exists( 'ImStoreAdmin' ) ) {
			if ( !class_exists( 'ImStoreAdmin' ) ) {
				require_once ( dirname ( __FILE__ ) . '/admin/admin.php' );
				$this->admin = new ImStoreAdmin( );
			}else{
				echo '<div class="updated fade" id="message"><p>' . __( "There is a conflict with other plugin", ImStore::domain ).'</p></div>';
			}
		}else{
			if ( !class_exists( 'ImStoreFront' ) ) {
				require_once ( dirname (__FILE__) . '/includes/store.php' );
				$this->store = new ImStoreFront( );
			}else{
				$this->dis_error( '<p>' . __( "There is a conflict with other plugin", ImStore::domain ) . '</p>' ) ;
			}
		}
	}
	

	/**
	 * temporaty upgrade function
	 * will be remove on nex release
	 *
	 * @return void
	 * @since 0.5.2 
	 */
	 function add_slideshow_options( ){
		$ims_ft_opts = get_option( 'ims_front_options' );
		$ims_ft_opts['numThumbs']		= 8;
		$ims_ft_opts['maxPagesToShow']	= 5;
		$ims_ft_opts['transitionTime']	= 1000;
		$ims_ft_opts['slideshowSpeed']	= 3200;
		$ims_ft_opts['autoStart']		= 'false';
		$ims_ft_opts['playLinkText']	= __( 'Pay', ImStore::domain );
		$ims_ft_opts['pauseLinkTex']	= __( 'Pause', ImStore::domain );
		$ims_ft_opts['closeLinkText']	= __( 'Close', ImStore::domain );
		$ims_ft_opts['prevLinkText']	= __( 'Previous', ImStore::domain );
		$ims_ft_opts['nextLinkText']	= __( 'Next', ImStore::domain );
		$ims_ft_opts['nextPageLinkText']= __( 'Next &rsaquo;', ImStore::domain );
		$ims_ft_opts['prevPageLinkText']= __( '&lsaquo; Prev', ImStore::domain );
		update_option( 'ims_front_options', $ims_ft_opts );
	 }
	 

}


// Do that thing that you do!!!
global $ImStore;
$ImStore = new ImStore( );
	
}
?>