<?php 

/**
 * Image store - admin settings
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/

class ImStoreAdmin extends ImStore{
	
	//store options
	var $opts;
	var $useropts;
	var $per_page;

	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function __construct( ){
		global $pagenow;
		
		$this->units = array(
			'in' => __( 'in', ImStore::domain ),
			'cm' => __( 'cm', ImStore::domain ),
			'px' => __( 'px', ImStore::domain ),
		);

		//ad a unique Gallery IDentifier to make sure that the actions come from this widget
		if ( ( 'media-upload.php' == $pagenow || 'async-upload.php' == $pagenow ) && isset( $_GET[ 'imstore' ] ) ){
			add_filter( 'media_send_to_editor', array( &$this, 'media_send_to_editor'), 1, 3 );
			add_filter( 'media_upload_form_url', array( &$this, 'media_upload_form_url' ), 1, 1 ); 
		}
		
		add_filter( 'intermediate_image_sizes', array( &$this, 'ims_image_sizes' ), 15, 1 );
		add_filter( 'get_attached_file', array( &$this, 'return_ims_attached_file' ), 15, 2 );
		add_filter( 'load_image_to_edit_path', array( &$this, 'load_ims_image_path' ), 15, 2 ); 
		add_filter( 'wp_update_attachment_metadata', array( &$this, 'update_attachment_metadata' ), 15, 2 );
		
		$this->opts = (array)get_option( 'ims_front_options' );
		if( $this->opts['imswidget'] ) include_once ( dirname (__FILE__) . '/widget.php' );		
		
		//speed up ajax we don't need this
		if( defined('DOING_AJAX') ) return;

		add_action( 'admin_menu', array( &$this, 'add_menu'), 10 );	
		add_action( 'set_current_user', array( &$this, 'apply_user_caps') );	
		add_filter( 'editable_roles', array( &$this, 'remove_role_display'), 10, 1 );
		
		// Add the script and style files only on plugin pages
		if( $_GET['page'] == ( 'ims-import' || 'ims-customers' || 'ims-sales' || 'ims-pricing' ||
			'ims-permissions' || 'ims-settings' || 'ims-edit-galleries' )){

			add_action( 'admin_print_styles', array( &$this, 'load_admin_ims_styles' ) );
			add_action( 'admin_print_styles', array( &$this, 'register_screen_columns' ) );
			add_action( 'admin_print_scripts', array( &$this, 'load_admin_ims_scripts' ) );
			add_filter( 'current_screen', array( &$this, 'change_current_screen_name' ), 10, 1 );
			
			if( $_GET['page'] == 'ims-customers' || $_GET['page'] == 'ims-sales' || $_GET['page'] == IMSTORE_FOLDER )
				add_filter( 'screen_settings', array( &$this, 'screen_settings' ), 15, 2 ); 
		}
	}
	
	
	/**
	 * Add ims mages sizes to 
	 * be updated by ajax image edit.
	 *
	 * @param array $sizes
	 * @return array
	 * @since 0.5.0 
	 */
	function ims_image_sizes( $sizes ){
		
		$img_sizes = get_option( 'ims_dis_images' );
		$downloadsizes = get_option( 'ims_download_sizes' );
		if( is_array( $downloadsizes ) ) $img_sizes += $downloadsizes;
		foreach( $img_sizes as $name => $values ) $sizes[] = $name;
			
		return $sizes;
	}
	
	
	/**
	 * Display unit sizes
	 *
	 * @return void
	 * @since 1.1.0
	 */
	function dropdown_units( $name,  $selected ){
		$output = '<select name="'. $name .'" class="unit">';
		foreach( $this->units as $unit => $label ){
			$select = ( $selected == $unit ) ? ' selected="selected"' : '' ;
			$output .= '<option value="'.$unit.'" '.$select.'>'.$label.'</option>';
		}
		echo $output .= '</select>';
	}
	
	/**
	 * Add url and path to the attachment data for "ims_image" post type
	 *
	 * @param array $data
	 * @param unit $post_id
	 * @return array
	 * @since 0.5.0 
	 */	
	function update_attachment_metadata( $data, $post_id ){
		
		if( $data['sizes']['mini'] && !stristr( $data['file'], date('Y/m') ) ){
			
			$cont_dir = str_replace( "\\", "/", WP_CONTENT_DIR );
			$dir = str_replace( "\\", "/", dirname( $data['file'] ) );
	
			if( stristr( $dir, $cont_dir ) ){
				$relative = str_replace( $cont_dir, "", $dir ); 
				$path = $dir; 
				$url  = trim( WP_CONTENT_URL, '/') . '/' . $relative;
				$data['file'] = $relative . '/' . basename( $data['file'] );
			}else{
				$path = $condir . $dir; 
				$url  = trim( WP_CONTENT_URL, '/') . '/' . $dir;
			}
			
			foreach( $data['sizes'] as $size => $filedata ){
				if( !$filedata['path'] ) 
					$data['sizes'][$size]['path'] = $path . '/' . $filedata['file'];
				if( !$filedata['url'] ) 
					$data['sizes'][$size]['url'] = $url . '/' . $filedata['file'];
			}
		}
		
		return $data;
	}
		
	
	/**
	 * Return image path post meta "_wp_attached_file"
	 *
	 * @param string $filepath
	 * @param unit $attachment_id
	 * @return string
	 * @since 0.5.0 
	 */	
	function return_ims_attached_file( $file, $attachment_id ){
		global $wpdb;
		if( 'ims_image' == $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM $wpdb->posts WHERE ID = %s", $attachment_id ) ) ){
			$data = get_post_meta( $attachment_id , '_wp_attachment_metadata' ) ;
			$file = str_replace( "\\", "/", WP_CONTENT_DIR . $data[0]['file'] );
		}
		return $file;
	}
	
	
	/**
	 * Return image path for ims_images to be edited
	 *
	 * @param string $filepath
	 * @param unit $post_id
	 * @return string
	 * @since 0.5.0 
	 */	
	function load_ims_image_path( $filepath, $post_id ){
		global $wpdb;
		
		if( 'ims_image' == $wpdb->get_var( $wpdb->prepare( "SELECT post_type FROM $wpdb->posts WHERE ID = %s", $post_id ) ) ){
			$imagedata = get_post_meta( $post_id, '_wp_attachment_metadata' ); 
			$filepath = str_replace( "\\", "/", WP_CONTENT_DIR . $imagedata[0]['file'] );
		}
		return $filepath;
	}
	
	
	/**
	 * Change screen name on edit singel gallery to supprt 
	 * custom screen uptions on the same page to not add an extra menu
	 *
	 * @param object $current_screen
	 * @return object
	 * @since 0.5.0 
	 */	
	function change_current_screen_name( $current_screen ){
		if( $_REQUEST['edit'] == 1 ){
			$current_screen->id = 'toplevel_page_' . IMSTORE_FOLDER . '-edit'; 
			$current_screen->base = 'toplevel_page_' . IMSTORE_FOLDER . '-edit'; 
		}
		return $current_screen;
	}
	
	
	/**
	 * Use a custom js function to to process the send image request
	 * to prevent conficts with other plugins, accept images only
	 *
	 * @param string $html link to image file( html formated )
	 * @param unit $id image/post id
	 * @param array $attachment image data
	 * @return string html formated
	 * @since 0.5.0 
	 */
	function media_send_to_editor( $html, $id, $attachment ){
		
		$imagurl = str_ireplace( get_option('home'), '', $attachment['url'] );
		
		?><script type="text/javascript">
			/* <![CDATA[ */
			var win = window.dialogArguments || opener || parent || top;
			win.add_watermark_url( '<?php echo addslashes( $imagurl ); ?>' );
			/* ]]> */
		</script><?php
		return $html;
	}
	
	
	/**
	 * Add $imstore to the media upload url
	 *
	 * @param string $form_action_url default url 
	 * @return string $imstore and default url 
	 * @since 0.5.0 
	 */	
	function media_upload_form_url( $form_action_url ) {
		return str_replace( 'media-upload.php?', 'media-upload.php?imstore=1&', $form_action_url);
	}
		
	
	/**
	 * Change media upload icons links
	 *
	 * @param string $src 
	 * @return string url 
	 * @since 0.5.0 
	 */
	function change_media_upload_src( $src ){
		global $post_type;
		
		if( $post_type == 'ims_gallery' )
			return str_replace( 'media-upload.php?', 'media-upload.php?imstore=1&', $src );
		else return $src;
	}


	/**
	 * Apply capabilities to current user
	 *
	 * @return void
	 * @since 0.5.0
	 */
	function apply_user_caps( ){
		global $current_user;
		
		if( $current_user->has_cap( 'administrator' ) )
			return;
		
		$ims_user_caps = get_usermeta( $current_user->ID, 'ims_user_caps' );
		if( !is_array( $ims_user_caps ) ) return; 
		
		foreach( $ims_user_caps as $cap => $value )
			$current_user->allcaps[$cap] = $value;
	}
	
	
	/**
	 * Don't allow creation of new customers 
	 * trough the wp add new interface 
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function remove_role_display( $all_roles ){
		unset( $all_roles['customer'] );
		return $all_roles;
	}
	
	
	/**
	 * Return Image Store options
	 *
	 * @parm string $option option name
	 * @parm string $key key name if option value is an array
	 * @return string/int
	 * @since 0.5.0
	 */
	function _vr( $option, $key = '' ){
		global $ImStore;
		if( !empty( $key ) ) return esc_attr( $this->opts[$option][$key] );
		else return esc_attr( $this->opts[$option] );
	}
	
	
	/**
	 * display Image Store options
	 *
	 * @parm string $option option name
	 * @parm string $key key name if option value is an array
	 * @return void
	 * @since 0.5.0
	 */
	function _v( $option, $key = '' ){
		if( !empty( $key ) ) echo esc_attr( $this->opts[$option][$key] );
		else echo esc_attr( $this->opts[$option] );
	}
	
	
	/**
	 * Create a Gallery ID 
	 * for user to login
	 *
	 * @param unit $length
	 * @return string
	 * @since 0.5.0
	 */
	function unique_linkid( $length = 12 ){
		$salt		= "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$len		= strlen($salt);
		$makepass	= '';
		mt_srand( 10000000 *(double) microtime( ) );
		for ( $i = 0; $i < $length; $i++)
			$makepass .= $salt[ mt_rand(0,$len - 1)];
		
		return $makepass;
	}
	
	
	/**
	 * Remove empty entries form array recursively
	 *
	 * @parm array $input 
	 * @return array
	 * @since 0.5.0
	 */
	function array_filter_recursive( $input ){ 
		foreach ( $input as &$value ){ 
			if ( is_array( $value ) ) 
				$value = $this->array_filter_recursive($value); 
		} 
		return array_filter( $input ); 
	} 
	
	
	/**
	 * Merge arrays recursively
	 *
	 * @parm array $input 
	 * @return array
	 * @since 1.0.0
	 */
	function array_merge_recursive_distinct( &$array1, &$array2 ){
		$merged = $array1;
		foreach ( $array2 as $key => &$value ){
			if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) ){
				$merged [$key] = $this->array_merge_recursive_distinct ( $merged [$key], $value );
			}else{
				$merged [$key] = $value;
			}
		}
		return $merged;
	}


	/**
	 * Load admin styles
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_admin_ims_styles( ) {
		wp_enqueue_style( 'adminstyles', IMSTORE_URL .'_css/admin.css', false, '0.5.0', 'all' );
		wp_enqueue_style( 'datepicker', IMSTORE_URL .'_css/jquery-ui-datepicker.css', false, '0.5.0', 'all' );
		
		if( $_GET['page'] == 'ims-sales' ) wp_enqueue_style( 'dashboard' );
		if( $_GET['page'] == ( 'ims-settings' || IMSTORE_FOLDER ) ) wp_enqueue_style( 'thickbox' );
	}
	
	
	/**
	 * Get all customers
	 *
	 * @since 0.5.0
	 * return array
	 */
	function get_ims_active_customers( ){
		global $wpdb;
		$users = $wpdb->get_results(
			"SELECT DISTINCT ID, user_login FROM $wpdb->users AS u
			INNER JOIN $wpdb->usermeta AS um ON u.ID = um.user_id 
			WHERE um.meta_key = 'ims_status' 
			AND um.meta_value IN ( 'active',
				( SELECT DISTINCT meta_value 
				 FROM $wpdb->usermeta 
				 WHERE meta_value LIKE '%customer%' ) 
			)"
		);
		return $users;
	}
	
	
	/**
	 * Get all price list
	 *
	 * @return array
	 * @since 0.5.0
	 */
	function get_ims_pricelists( ){
		global $wpdb;
		return $wpdb->get_results( "SELECT DISTINCT ID, post_title FROM $wpdb->posts WHERE post_type = 'ims_pricelist'" );
	}
	
	
	/**
	 * Register screen columns
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function register_screen_columns( ) {
		$this->useropts = get_option( 'ims_back_options' );

		//remove column if mailpress is not install
		if ( !class_exists('MailPress') ) unset( $this->useropts['customerscols']['enewsletter'] );
		
		switch( $_GET['page'] ){
			case 'ims-sales':
				register_column_headers( 'image-store_page_ims-sales', $this->useropts['salescols'] );
				break;
			case 'ims-pricing':
				register_column_headers( 'image-store_page_ims-pricing', $this->useropts['promocols'] );
				break;
			case 'ims-customers':
				register_column_headers( 'image-store_page_ims-customers', $this->useropts['customerscols'] );
				break;
			default:
				if( $this->opts['disablestore'] ) unset( $this->useropts['galleriescols']['tracking'] );
				if( $_GET['edit'] == 1 ) register_column_headers( 'toplevel_page_' . IMSTORE_FOLDER . '-edit', $this->useropts['imagescols'] );
				else register_column_headers( 'toplevel_page_' . IMSTORE_FOLDER , $this->useropts['galleriescols'] );
		}
	}
	
	
	
	/**
	 * paging function for events reports
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function imstore_paging( $perpage, $all ){
		global $wpdb, $pagenowurl;
		
		$all	= ( $all ) ? $all : 1;
		$s		= $wpdb->escape( $_GET['s'] );	
		$page	= ( empty( $_GET['p'] ) ) ? '1' : intval( $wpdb->escape( $_GET['p'] ) );
		$from	= ( ( $page - 1 ) * $perpage ) + 1;
		$last	= ceil( $all / $perpage );
		
		if( $all > $perpage ){
	
			echo '<div class="tablenav-pages">';
			echo '<span class="displaying-num">' . " Displaying $from &#8211; $to of $all" . '</span>';
			
			//prev
			if( ( $p = $page-1 ) >= 1 )
				echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=$p&amp;s=$s".'" class="next page-numbers">&laquo;</a>';
			
			
			//first
			if( $page != 1 )
				echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=1&amp;s=$s".'" class="next page-numbers">1</a>';
	
			if( $page > 4 )
				echo '<span class="page-numbers dots">...</span>';
			
			for( $i = $page-2; $i < $page; $i++ ){
				if( $i < $page && $i >1 )
					echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=$i&amp;s=$s".'" class="next page-numbers">'.$i.'</a>';
			}
			
			//current
			echo '<span class="current page-numbers">'.$page.'</span>';
			
			for( $i = $page+1; $i < ( $page + 3 ); $i++ ){
				if( $i < $last )
					echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=$i&amp;s=$s".'" class="next page-numbers">'.$i.'</a>';
			}
			
			if( $i < $last )
				echo '<span class="page-numbers dots">...</span>';
			
			//last
			if( $page != $last )
				echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=$last&amp;s=$s".'" class="next page-numbers">'.$last.'</a>';
				
			//next
			if( ( $p = $page + 1 ) <= $last )
				echo '<a href="'."$pagenowurl$url&amp;status=$status&amp;p=$p&amp;s=$s".'" class="next page-numbers">&raquo;</a>';
	
			echo '</div>';
			
		}
	
	}
	
	
	
	/**
	 * Load admin scripts
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_admin_ims_scripts( ) {
		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'swfobject', IMSTORE_URL . '_js/swfobject.js', array('jquery'), '0.5.0');		
		wp_enqueue_script( 'datepicker', IMSTORE_URL . '_js/jquery-ui-datepicker.js', array('jquery'), '0.5.0');
		wp_enqueue_script( 'uploadify', IMSTORE_URL . '_js/jquery.uploadify.js', array('jquery', 'swfobject'), '2.1.0');
		wp_enqueue_script( 'ims-admin', IMSTORE_URL . '_js/admin.js', array( 'jquery', 'datepicker', 'uploadify', 'postbox' ), '0.5.0');
		
		if( $_GET['page'] == ( 'ims-settings' || IMSTORE_FOLDER ) ) wp_enqueue_script( 'thickbox' );
		
		$jquery = array( 'dd', 'D', 'd', 'DD', '*', '*', '*', 'o', '*', 'MM', 'mm', 'M', 'm', '*', '*', '*', 'yy', 'y' );
		$php 	= array( '/d/', '/D/', '/j/', '/l/', '/N/', '/S/', '/w/', '/z/', '/W/', '/F/', '/m/', '/M/', '/n/', '/t/', '/L/', '/o/', '/Y/', '/y/');
		$format = preg_replace( $php, $jquery, get_option( 'date_format' ) );

		wp_localize_script( 'ims-admin', 'imslocal', array(
			'dateformat'	=> $format,
			'imsurl'		=> IMSTORE_URL,
			'imsajax' 		=> IMSTORE_ADMIN_URL . 'ajax.php',
			'pixels'		=> __( 'Pixels', ImStore::domain ),
			'flastxt'		=> __( 'Select files.', ImStore::domain ),
			'exists'		=> __( ' files existed.', ImStore::domain ),
			'uploaded'		=> __( ' files uploaded. ', ImStore::domain ),
			'selectgal' 	=> __( 'Please, select a gallery!', ImStore::domain ),
			'deletelist' 	=> __( 'Are you sure that you want to delete this list?', ImStore::domain ),
			'deletepackage' => __( 'Are you sure that you want to delete package?', ImStore::domain ),
			'deleteentry'	=> __( 'Are you sure that you want to delete this entry?', ImStore::domain ),
			'nonceajax'		=> wp_create_nonce( 'ims_ajax' )
		));
	}
	
	
	/**
	 * Add per page setting to screen options
	 *
	 * @return string html
	 * @since 0.5.0 
	 */
	function screen_settings( $none = '', $screen ){
		global $user;
		
		switch ( $_GET['page'] ){
			case "ims-customers":
				$option = 'ims_customers_per_page';
				$per_page_label = __( 'Customers', ImStore::domain );
				break;
			case "ims-sales":
				$option = 'image-store_page_ims-sales';
				$per_page_label = __( 'Orders', ImStore::domain );
				break;
			default :
				$option = 'ims_galleries_per_page';
				$per_page_label = __( 'Galleries', ImStore::domain );
		}
		
		$this->per_page = (int) get_user_option( $option );
		if ( empty( $this->per_page ) || $this->per_page < 1 ) $this->per_page = 20;
		
		if( empty( $_GET['edit'] ) ){
			$return = "<div class='screen-options'>\n";
			$return .= '<h5>'.__( 'Show per page', ImStore::domain ).'</h5>';
			$return .= "<label>$per_page_label: <input type='text' class='inputxm' name='screen_options[value]' value='$this->per_page' /></label>\n";
			$return .= "<input type='submit' class='button' value='" . esc_attr__( 'Apply' ) . "' />";
			$return .= "<input type='hidden' name='screen_options[option]' value='" . esc_attr( $option ) . "' />";
			$return .= "</div>\n";
			return $return;
		}
	}
	
	
	
	/**
	 * ImStore admin menu	
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function add_menu( ) { 

		add_object_page( __( 'Image Store', ImStore::domain ), __( 'Image Store', ImStore::domain ), 
					'ims_manage_galleries', IMSTORE_FOLDER, array(&$this, 'show_menu'), IMSTORE_URL .'_img/imstore.png' );
		
		add_submenu_page( IMSTORE_FOLDER, __('Galleries', ImStore::domain ), __( 'Galleries', ImStore::domain ), 
					'ims_manage_galleries', IMSTORE_FOLDER , array (&$this, 'show_menu'));	
				
		add_submenu_page( IMSTORE_FOLDER, __( 'Add New', ImStore::domain ), __( 'Add New', ImStore::domain ), 
					'ims_import_images', 'ims-import', array ( &$this, 'show_menu' ));
		
		//if store is enable
		if( !$this->opts['disablestore'] ){
			add_submenu_page( IMSTORE_FOLDER, __( 'Sales', ImStore::domain ), __( 'Sales', ImStore::domain ), 
						'ims_read_sales', 'ims-sales', array ( &$this, 'show_menu' ));
			
			add_submenu_page( IMSTORE_FOLDER, __( 'Pricing', ImStore::domain ), __( 'Pricing', ImStore::domain ), 
						'ims_change_pricing', 'ims-pricing', array ( &$this, 'show_menu' ));
		}
		
		add_submenu_page( IMSTORE_FOLDER, __( 'Customers', ImStore::domain ), __( 'Customers', ImStore::domain ), 
					'ims_manage_customers', 'ims-customers', array ( &$this, 'show_menu' ));
		
		add_submenu_page( IMSTORE_FOLDER, __( 'Settings', ImStore::domain ), __( 'Settings', ImStore::domain ), 
					'ims_change_settings', 'ims-settings', array ( &$this, 'show_menu' ));
	
	}
	
	
	/**
	 * Display the pages 
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function show_menu( ){
		global $pagenowurl, $user_ID;
		
		$this->permalinks 	= get_option( 'permalink_structure' );
		$pagenowurl = admin_url( ) . 'admin.php?page=' . $_GET['page'];
		
		//upgrade function 1.0.2 = 1.1.0
		if( empty($this->opts['checkoutfields']) )
			ImStore::add_checkout_options( );
		
		// display page
		switch ( $_GET['page'] ){
			case "ims-import":
				include_once ( dirname (__FILE__) . '/import.php' );
				break;
			case "ims-sales":
				if( $_GET['details'] == 1 ) include_once ( dirname (__FILE__) . '/sales-details.php' );
				else include_once ( dirname (__FILE__) . '/sales.php' );
				break;
			case "ims-pricing":
				include_once ( dirname (__FILE__) . '/pricing.php' );
				break;
			case "ims-customers":
				include_once ( dirname (__FILE__) . '/customers.php' );
				break;
			case "ims-settings":
				include_once ( dirname (__FILE__) . '/settings.php' );
				break;
			default :
				if( $_GET['edit'] == 1 ) include_once ( dirname (__FILE__) . '/gallery-edit.php' );
				else include_once ( dirname (__FILE__) . '/galleries.php' );
			
		}
	}
	
}

?>