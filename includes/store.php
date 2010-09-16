<?php 

/**
 * ImStoreFront - Fontend display 
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0 
*/

class ImStoreFront{

	var $opts;
	var $cart;
	var $order;
	var $error;	
	var $pages;
	var $sizes;
	var $sortby;
	var $expire;	
	var $gallery;
	var $message;
	var $success;	
	var $imspage;
	var $query_id;	
	var $is_secure;	
	var $permalinks;
	var $gallery_id;
	var $attachments;
	var $cart_cookie;
	var $pricelist_id;
	
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function __construct( ){
		$this->opts = get_option( 'ims_front_options' );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_scripts_styles' ) );
		add_shortcode( 'image-store', array( &$this, 'imstore_shortcode' ) );
	}
	
		
	/**
	 * load frontend js/css
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_scripts_styles( ){
		wp_enqueue_style( 'colorbox', IMSTORE_URL .'_css/colorbox/colorbox.css', NULL, '0.5.0' );
		if( $this->opts['stylesheet'] ) wp_enqueue_style( 'imstore', IMSTORE_URL .'_css/imstore.css', NULL, '0.5.0' );
		wp_enqueue_script( 'colorbox', IMSTORE_URL .'_js/jquery.galleriffic.js', array( 'jquery' ), '1.3.6 ', true); 
		wp_enqueue_script( 'galleriffic', IMSTORE_URL .'_js/jquery.colorbox.js', array( 'jquery' ), '1.1.6 ', true); 	
		wp_enqueue_script( 'imstorejs', IMSTORE_URL .'_js/imstore.js', array( 'jquery', 'colorbox', 'galleriffic' ), '0.5.0', true); 
	}
	
		
	/**
	 * Outputs html selected attribute.
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function selected( $helper, $current ) {
		if ( (string)$helper === (string)$current ) 
			echo $result = ' selected="selected"';
	}


	/**
	 * Get imstore permalink
	 *
	 * @param string $page
	 * @since 0.5.0 
	 * return void
	 */
	function get_permalink( $galid, $page = '' ){
		if( $this->permalinks ){
			$link = get_permalink( ) . "/imstore/". sanitize_title( $this->pages[$page] ) . "/$galid";
			if( $this->success ) $link .= '/ms/' . $this->success;
		}else{
			$link = get_permalink( ) . '&imspage=' . $page . '&imsgalid=' . $galid ;
			if( $this->success ) $link .= '&imsmessage=' . $this->success; 
		}
		return $link;
	}
	
	
	/**
	 * Send post data to a url
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function redirect_form_post_data( $addr, $data ){
		
		if( !wp_verify_nonce( $_POST["_wpnonce"], "ims_submit_order" ) ) return; 
		
		foreach( $data as $k => $v ) $req .= "&$k=" . urlencode( $v );
		
		$this->cart['tracking'] = get_post_meta( $this->gallery_id, 'ims_tracking', true );
		$this->cart['gallery_id'] = get_post_meta( $this->gallery_id, '_ims_gallery_id', true );
		
		update_post_meta( $_COOKIE[ 'ims_orderid_' . COOKIEHASH ], '_ims_order_data', $this->cart );
		
		header( "Method: POST\r\n");
		header( "Host: " . $_SERVER['HTTP_HOST'] . "\r\n" );
		header( "Content-Type: application/x-www-form-urlencoded\r\n");
		header( "Content-Length: ". strlen( $req ) ."\r\n");
		header( "Location: $addr?$req \r\n" );
		
	}
		
	
	/**
	 * Core fuction display store
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function imstore_shortcode( $atts ) {
		
		$this->load_variables( );
		
		//process paypal IPN
		if( $this->imspage == 6 )
			include_once ( dirname (__FILE__) . '/paypal-ipn.php' );
		
		//add images to cart
		if ( !empty( $_POST['add-to-cart'] ) )
			$this->add_to_cart( );
			
		//update cart
		if ( !empty( $_POST['applychanges'] ) )
			$this->upate_cart( );
		
		//checkout
		if ( !empty( $_POST['checkout'] ) )
			$this->redirect_form_post_data( $this->gateway[$this->opts['gateway']], $_POST );
		
		//logout user
		if ( $_REQUEST['logout'] == true ){
			ImStore::logout_ims_user( );
			wp_redirect( get_permalink( ) ); 
		}
		
		//shopping cancel
		if( $this->imspage == 7 )
			$this->error = __( 'Your transaction has been cancel!!', ImStore::domain );

		
		if( !$atts['secure'] ){ //not secure
			unset( $this->pages[3] );
			if( !$this->query_id ){
				$this->get_unsecure_galleries( ); 
				$this->display_galleries( );
			} else {
				$this->get_gallery_images( ); 
				$this->display_ims_page( );
			}
		}else{ //secure
			//try to login
			if( !empty( $_REQUEST["login-imstore"] ) ){
				if( !wp_verify_nonce( $_REQUEST["_wpnonce"], 'ims_access_form') ) 
					return; 
				$errors = $this->validate_user( );
				if ( isset( $errors ) && is_wp_error( $errors ) ){
					foreach ( $errors->get_error_messages( ) as $err )
						$output .= '<span class="error">'.$err.'<span>';
					echo '<div class="ims-message error">'. $output .'</div>';
				} 
			}
			//not login
			if( !current_user_can( 'ims_manage_galleries' ) && empty( $_COOKIE['ims_cookie_' . COOKIEHASH] ) ){
				$this->get_login_form( );
			}else{
				$this->is_secure = 1;
				$this->get_gallery_images( ); 
				$this->display_ims_page( );
			}
		}
		
	}
	
	
	
	/**
	 * Populate object variables
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function load_variables( ) {
		global $wpdb;
		
		$this->pages[1] = __( 'Photos', ImStore::domain );
		$this->pages[2] = __( 'Slideshow', ImStore::domain );
		
		if( !$this->opts['disablestore'] ){
			$this->pages[3] = __( 'Favorites', ImStore::domain );
			$this->pages[4] = __( 'Price List', ImStore::domain );
			$this->pages[5] = __( 'Shopping Cart', ImStore::domain );
			$this->pages[6] = $this->opts['listener'];
			$this->pages[7] = $this->opts['cancelpage'];
			$this->pages[8] = $this->opts['returnpage'];
		}
		
		$this->gateway = array(
			'googleprod' => ' https://google.com/checkout',
			'googlesand' => 'https://sandbox.google.com/checkout',
			'paypalprod' => 'https://www.paypal.com/cgi-bin/webscr',
			'paypalsand' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
		);
		
		//dont change array order
		$this->subtitutions = array( 
			$_POST['payment_gross'] ,
			$_POST['payment_status'],
			$wpdb->get_var( "SELECT post_title FROM $wpdb->posts WHERE ID = '" . $_POST['custom'] . "' " ),
			$_POST['mc_shipping1'],
			$data['tracking'],
			$data['gallery_id'],
			$_POST['txn_id'],
			$_POST['last_name'],
			$_POST['first_name'],
			$_POST['payer_email'],
		);
		
		
		$messages = array(
			'1' => __( 'Successfully added to cart', ImStore::domain ),
			'2' => __( 'Cart successfully updated', ImStore::domain ),
			'3' => __( 'Your transaction has been cancel!!',ImStore::domain)
		);
		
		$this->imspage		= get_query_var( 'imspage' );
		$this->query_id 	= get_query_var( 'imsgalid' );
		$this->permalinks 	= get_option( 'permalink_structure' );
		$this->message		= $messages[get_query_var( 'imsmessage' )];
				
		if( $this->permalinks ){
			foreach( $this->pages as $pid => $plink ){
				if( $this->imspage == sanitize_title ( $plink ) )
					$this->imspage = $pid;
			}
		} 
		
		$this->order		= ( $_sort = get_post_meta( $this->gallery_id, '_ims_order', true ) ) ? $_sort : $this->opts['imgsortdirect'];
		$this->sortby 		= ( $_sortby = get_post_meta( $this->gallery_id, '_ims_sortby', true ) ) ? $_sortby : $this->opts['imgsortorder'];
		$this->gallery_id 	= ( $this->query_id ) ? intval( $this->query_id ) : intval( $_COOKIE['ims_cookie_' . COOKIEHASH] );
		
		$this->sizes 		= $this->get_price_list( );
		$this->listmeta 	= get_post_meta( $this->pricelist_id, '_ims_list_opts', true );

		
		if( $this->cart_cookie = $_COOKIE[ 'ims_orderid_' . COOKIEHASH ] )
			$this->cart = get_post_meta( $this->cart_cookie, '_ims_order_data', true );
		
	}
	
	
	/**
	 * User login function
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function validate_user( ){
		global $post, $wpdb;
		$errors = new WP_Error();
		
		if( empty($_REQUEST["ims-galbox-".$post->ID]) )
			$errors->add( 'emptyid', __( 'Please enter a gallery id. ', ImStore::domain ) );
		
		if( empty($_REQUEST["ims-pwdbox-".$post->ID]) )
			$errors->add( 'emptypswd', __( 'Please enter a password. ', ImStore::domain ) );
			
		if( !empty( $errors->errors ) )
			return $errors;

		$gallery = $wpdb->get_results( $wpdb->prepare( 
			"SELECT post_id, post_password FROM $wpdb->postmeta AS pm 
			LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID 
			WHERE meta_key = '_ims_gallery_id' 
			AND meta_value = '%s' ", $_REQUEST["ims-galbox-".$post->ID]
		));
		
		if( $gallery[0]->post_password === $_REQUEST["ims-pwdbox-".$post->ID] ){
			setcookie( 'ims_cookie_' . COOKIEHASH, "{$gallery[0]->post_id}", 0, COOKIEPATH );
			update_post_meta( $gallery[0]->post_id, 'ims_visits', get_post_meta( $gallery[0]->post_id, 'ims_visits', true ) + 1 );
			wp_redirect( get_permalink( $post->ID ) );
		}else{
			$errors->add( 'nomatch', __( 'Gallery ID or password is incorrect. Please try again. ', ImStore::domain ) );
			return $errors;
		}
	}
	
	
	/**
	 * validate promotion code
	 *
	 * @return bool
	 * @since 0.5.0 
	 */
	function validate_code( $code ){
		global $wpdb;
		
		if( empty( $code )) 
			return false;

		$promo_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE meta_key = '_ims_promo_code' 
			AND meta_value = BINARY '%s'
			AND post_status = 'publish' 
			AND post_date <= '" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "'
			AND post_expire >= '" . date( 'Y-m-d', current_time( 'timestamp' ) ) . "' "
		, $code ));
		
		if( empty( $promo_id ) ) {
			$this->error = __( "Invalid promotion code", ImStore::domain );
			return false;
		}
		
		$data = get_post_meta( $promo_id, '_ims_promo_data', true );
		$this->cart['promo']['discount'] = $data['discount'];
		$this->cart['promo']['promo_type'] = $data['promo_type'];

		switch( $data['rules']['logic'] ){
			case 'equal':
				if( $this->cart[$data['rules']['property']] > $data['rules']['value'] )
					return true;
				break;
			case 'more':
				if( $this->cart[$data['rules']['property']] > $data['rules']['value'] )
					return true;
				break;
			case 'less':
				if( $this->cart[$data['rules']['property']] > $data['rules']['value'] )
					return true;
				break;
		}
		
		$this->error = __( "Your current purchase doesn't meet the promotion requirements.", ImStore::domain );
		return false;
		
	}
	
	
	/**
	 * Get gallery price list
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function get_price_list( ){
		global $wpdb;
		
		$sizes = $wpdb->get_results( $wpdb->prepare( "
			SELECT meta_value, post_id FROM $wpdb->postmeta 
			WHERE post_id = ( SELECT meta_value FROM $wpdb->postmeta 
				WHERE post_id = %s AND meta_key = '_ims_price_list' ) 
			AND meta_key = '_ims_sizes' "
		, $this->gallery_id ));
		
		$this->pricelist_id = $sizes[0]->post_id;
		
		foreach( $sizes as $size )
			return $gallery_sizes = @unserialize( $size->meta_value );
	}
	
	
	
	/**
	 * Get unsecure galleries
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function get_unsecure_galleries( ) {
		global $wpdb;
		
		$this->is_galleries = 1;
		
		$posts = $wpdb->get_results( 
			"SELECT ID, post_title, 
			meta_value, post_excerpt, post_parent
			FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON pm.post_id = p.ID
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND post_status = 'publish'
			AND post_parent IN ( SELECT ID
				FROM $wpdb->posts
				WHERE post_type = 'ims_gallery'
				AND post_status = 'publish'
				AND post_password = '' )
			GROUP BY p.post_parent
			ORDER BY menu_order, p.post_date DESC "
		);
		
		if( empty( $posts ) ){
			$this->attachments = $posts;
			return;
		}
		
		foreach( $posts as $post ){
			$post->meta_value = unserialize( $post->meta_value );
			$images[] = $post;
		}
		
		$this->attachments = $images;
	}
	
		
	/**
	 * Get gallery images
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function get_gallery_images( ){
		global $wpdb;
		
		$posts = $wpdb->get_results( $wpdb->prepare(
			"SELECT ID, post_title, guid,
			meta_value, post_excerpt
			FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND post_status = 'publish'
			AND post_parent = %d
			ORDER BY $this->sortby $this->order " 
		, $this->gallery_id ));
		
		if( empty( $posts ) ){
			$this->attachments = $posts;
			return;
		}
		
		foreach( $posts as $post ){
			$post->meta_value = unserialize( $post->meta_value );
			$images[] = $post;
		}
		
		$this->attachments = $images;
	}
	
	
	/**
	 * Get favorites
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function get_favorite_images( ){
		global $wpdb;
		
		$ids = get_post_meta( $this->gallery_id, '_ims_favorites', true );
		$ids = ( is_array($ids) ) ? $wpdb->escape( implode( ',', $ids ) ) : 0;
		$posts = $wpdb->get_results(
			"SELECT ID, post_title, guid,
			meta_value, post_excerpt
			FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND ID IN ( $ids )
			ORDER BY $this->sortby $this->order " 
		);
		
		if( empty( $posts ) ){
			$this->attachments = $posts;
			return;
		}
		
		foreach( $posts as $post ){
			$post->meta_value = unserialize( $post->meta_value );
			$images[] = $post;
		}
		
		$this->attachments = $images;
	}
	
	
	/**
	 * Display galleries
	 *
	 * @return array
	 * @since 0.5.0 
	 */
	function display_galleries( ){ 
		
		$itemtag 	= 'dl';
		$icontag 	= 'dt';
		$captiontag = 'dd';
		$columns 	= intval( $this->opts['displaycolmns'] );
		$itemwidth	= $columns > 0 ? floor(100/$columns) : 100;
		$nonce 		= '_wpnonce=' . wp_create_nonce( 'ims_secure_img' );
		
		$output = "<{$itemtag} class='ims-gallery'>";
		foreach ( $this->attachments as $image ){
			if( $image->post_parent ){
				$title = get_the_title( $image->post_parent );
				$link = $this->get_permalink( $image->post_parent );
			}else{
				$link = IMSTORE_URL . "image.php?$nonce&amp;img={$image->ID}";
				$title = $image->post_title;
			}
			$imagetag = '<img src="' . $image->meta_value['sizes']['thumbnail']['url'] . '" width="' . $itemwidth . '%" alt="' . $title . '" />'; 			$title_att = ( $this->is_galleries ) ? $title : $image->post_excerpt ;
			
			$output .= "<{$icontag}>";
			if( !$this->opts['disablestore'] && ( $this->query_id || $this->is_secure ) ) 
				$output .= '<input name="imgs[]" type="checkbox" value="' . $image->ID . '" />';
			$output .= '<a href="' . $link . '" class="ims-colorbox" title="' . $title_att . '">' . $imagetag . '</a>';
			$output .= "</{$icontag}>";
			if ( $this->is_galleries ) {
				$output .= "
					<{$captiontag} class='gallery-caption'>
					" . wptexturize( $title ) . "
					</{$captiontag}>";
			}
		}
		
		echo $output .= "</{$itemtag}>";
	
	}
	
	
	/**
	 * Display store navigation
	 * 
	 * @return void
	 * @since 0.5.0 
	 */
	function store_nav( ){
		$nav = '<ul id="imstore-nav" class="imstore-nav" >'. "\n";
		foreach( $this->pages as $key => $page ){
			if( $key == 6 || $key == 7 || $key == 8 ) continue;
			$title = sanitize_title( $page );
			$css = ( $key == $this->imspage || ( $key == 1 && empty( $this->imspage ) ) ) ? ' current': '';
			$nav .= '<li class="imsmenu-' . $title . $css .'"><a href="' . $this->get_permalink( $this->gallery_id, $key ) .'">' . $page . '</a></li>' . "\n";
		}
		if( $this->is_secure && !is_user_logged_in( ) )
			$nav .= '<li class="imsmenu-' . $title . $css .'"><a href="' . $this->get_permalink( $this->gallery_id ) .'&amp;logout=true">' . __( "Log Out Gallery", ImStore::domain ) . '</a></li>' . "\n";
		else
			$nav .= '<li class="imsmenu-' . $title . $css .'">' . wp_loginout( get_permalink( ), false ) . '</li>' . "\n";
		echo $nav . "<ul>\n";
	}
	
	
	
	/**
	 * Display secure galleries login form
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function get_login_form( ){
		global $post;
		
		$pwdlabel	= "ims-pwdbox-{$post->ID}";
		$galabel 	= "ims-galbox-{$post->ID}";
		$nonce 		= wp_create_nonce( 'ims_access_form' );
		
		$output = '<form action="' . get_permalink( $post->ID ) . '" method="post">
		<p class="message login">' . __("To view your images please enter your login information below:") . '</p>
			<div class="ims-fields"
				<label for="' . $galidlabel . '">' . __( "Gallery ID:", ImStore::domain ) . '</label> <input name="' . $galabel . '" id="' . $galabel . '"" />
				<spam class="linebreak"></spam>
				<label for="' . $pwdlabel . '">' . __( "Password:", ImStore::domain ) . '</label> <input name="' . $pwdlabel . '" id="' . $pwdlabel . '" type="password" />
				<spam class="linebreak"></spam>
				<input type="submit" name="login-imstore" value="' . esc_attr( __( "Submit", ImStore::domain ) ) . '" />
				<input type="hidden" name="_wpnonce" value="'.$nonce.'" />
			</div>
		</form>
		';
		echo $output;
	}
	
	
	
	/**
	 * Display Order form
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function display_list_form( ){?>
		<form id="ims-pricelist" action="" method="post">
		<div class="ims-image-count"><?php _e( 'Selected', ImStore::domain )?></div>
		<div class="ims-instructions"><?php _e( 'These preferences will be apply to all the selected images',ImStore::domain )?></div>
		<div class="ims-add-error"><?php _e( 'There are no images selected',ImStore::domain )?></div>
		
		<div class="ims-field"> 
			<label for="ims-quantity"><?php _e( 'Quantity', ImStore::domain )?> </label>
			<input name="ims-quantity" type="text" class="inputsm" id="ims-quantity" value="1" />
		</div>
		
		<div class="ims-field">
			<label for="ims-image-size"><?php _e( 'Size', ImStore::domain )?> </label>
			<?php if( $sizes = $this->sizes ){?>
			<select name="ims-image-size" id="ims-image-size">
				<option value=""><?php _e( 'Image size', ImStore::domain )?></option>
				<?php foreach( $sizes as $size ){
					echo '<option value="';
					if( $size['ID'] ){
						echo $size['name'] . '">' . $size['name'] .': '; $package_sizes = '';
						foreach( (array)get_post_meta( $size['ID'], '_ims_sizes', true ) as $package_size => $count )
							$package_sizes .= $package_size .'('.$count.'), '; 
						echo rtrim ( $package_sizes, ', ') . '</option>';
					}else{ 
						echo $size['name'] . '">' . $size['name'] . ' </option>';	
					}
				}?>
			</select>
			<?php }?>
		</div>
		
		
		<div class="ims-field">
			<label for="_imstore-color"><?php _e( 'Color', ImStore::domain )?> </label>
			<select name="_imstore-color" id="_imstore-color">
				<option value="color"><?php _e( 'Full Color', ImStore::domain )?></option>
				<option value="ims_bw"><?php _e( 'Sepia', ImStore::domain )?> + <?php echo $this->listmeta['ims_bw']?></option>
				<option value="ims_sepia"><?php _e( 'Black &amp; White', ImStore::domain )?> + <?php echo $this->listmeta['ims_sepia']?></option>
			</select>
		</div>
		<div class="ims-field ims-submit">
			<input name="add-to-cart" type="submit" value="<?php _e( 'Add to cart', ImStore::domain )?>" />
			<input type="hidden" name="ims-to-cart-ids" id="ims-to-cart-ids" />
			<input type="hidden" name="gallery-id" id="gallery-id" value="<?php echo $this->gallery_id?>" />
			<input type="hidden" name="imstore-url" id="imstore-url" value="<?php echo IMSTORE_ADMIN_URL?>" />
			<input type="hidden" name="_wpnonce" id="_wpnonce" value="<?php echo wp_create_nonce( "ims_ajax_favorites" )?>" />
		</div>
	</form>
	<?php }
	
	
	/**
	 * Add items to cart
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function add_to_cart( ){
		
		if( !is_numeric( $_POST['ims-quantity'] ) || empty( $_POST['ims-quantity'] ) ){
			$this->error = __( 'Please, enter a valid image quantity', ImStore::domain );
			return;
		}
		
		if( empty( $_POST['ims-image-size'] ) ){
			$this->error = __( 'Please, select and image size', ImStore::domain );
			return;
		}
			
		if( empty( $_POST['ims-to-cart-ids'] ) ){
			$this->error = __( 'There was an error adding images to cart', ImStore::domain );
			return;
		}
		
		$images = explode( ',', $_POST['ims-to-cart-ids'] );
		
		foreach( $images as $id ){
			foreach( $this->sizes as $size ){
				if( $size['name'] == $_POST['ims-image-size'] ){
					if( $size['ID'] ) $this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['price'] = get_post_meta( $size['ID'], '_ims_price', true );
					else $this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['price'] = $size['price']; 
					$this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['download'] = $size['download'];
					continue;
				}
			}
			$this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['quantity'] += $_POST['ims-quantity'];
			$this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['color'] = $this->listmeta[$_POST['_imstore-color']];
			
			$this->cart['items'] += $_POST['ims-quantity'];
			$this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['subtotal'] = 
				( ( $this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['price'] 
				+ $this->listmeta[$_POST['_imstore-color']] ) * $_POST['ims-quantity'] );
			$this->cart['subtotal'] += $this->cart['images'][$id][$_POST['ims-image-size']][$_POST['_imstore-color']]['subtotal'];
		}
		
		$this->cart['shipping'] = ( $this->cart['shipping'] ) ? $this->cart['shipping'] : $this->listmeta['ims_ship_local'] ;
		$this->cart['total'] = $this->cart['subtotal'] + $this->cart['shipping'];
		
		if( $this->cart['promo']['code'] ){
			if( $this->cart['promo']['promo_type'] == '1' )
				$this->cart['promo']['discount'] = ( $this->cart['total'] * ( $this->cart['promo']['discount']/100 ) );
			elseif( $this->cart['promo']['promo_type'] == '2' )
				$this->cart['promo']['discount'];
			elseif( $this->cart['promo']['promo_type'] == '3' )
				$this->cart['promo']['discount'] = $this->cart['shipping'];
				
			$this->cart['discounted'] = $this->cart['total'] - $this->cart['promo']['discount'];
		}
		
		$this->cart['total'] = ( $this->cart['discounted'] ) ? $this->cart['discounted'] : $this->cart['total'];
		
		if( $this->opts['taxamount'] ){
			if( $this->opts['taxtype'] == 'percent' )
				$this->cart['tax'] = ( $this->cart['total'] * ( $this->opts['taxamount']/100 ) );
			else $this->cart['tax'] = $this->opts['taxamount'];
			$this->cart['total'] += $this->cart['tax']; 
		}
		
		if( empty( $_COOKIE[ 'ims_orderid_' . COOKIEHASH ] ) ){
			$orderid = wp_insert_post( array(
				'ping_status' 	=> 'close', 
				'post_status' 	=> 'draft', 
				'post_type' 	=> 'ims_order',
				'post_parent' 	=> $this->gallery_id,
				'post_expire' 	=> date( 'Y-m-d H:i', current_time( 'timestamp' ) + 86400 ),
				'post_title' 	=> 'Ims Order - ' . date( 'Y-m-d H:i', current_time( 'timestamp' ) ),
			));
			if( !empty( $orderid ) && !empty( $this->cart ) ){
				add_post_meta( $orderid, '_ims_order_data', $this->cart );
				setcookie( 'ims_orderid_' . COOKIEHASH, $orderid, 0, COOKIEPATH );
			}
		}else{
			update_post_meta( $_COOKIE[ 'ims_orderid_' . COOKIEHASH ], '_ims_order_data', $this->cart );
		}
		
		if( empty( $this->error ) ){
			$this->success = '1';
			wp_redirect( $this->get_permalink( $this->gallery_id, $this->imspage ) ); 
		}
	}
	
	
	/**
	 * update cart information
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function upate_cart( ){
		
		if( !wp_verify_nonce( $_REQUEST["_wpnonce"], "ims_submit_order" ) ) 
		die( 'Security check failed!!' ); 
		
		if(is_array( $_POST['ims-remove'] )){
			foreach( $_POST['ims-remove'] as $delete ){
				$values = explode('|', $delete );
				unset( $this->cart['images'][$values[0]][$values[1]][$values[2]] );
				
				if( empty( $this->cart['images'][$values[0]][$values[1]] ) )
					unset( $this->cart['images'][$values[0]][$values[1]] );
				
				if( empty( $this->cart['images'][$values[0]] ) )
					unset( $this->cart['images'][$values[0]] );
			}
		}
		
		if( empty( $this->cart['images'] ) ) {
			update_post_meta( $_COOKIE[ 'ims_orderid_' . COOKIEHASH ], '_ims_order_data', '' );
			return;
		}
		
		$this->cart['items'] = 0;
		$this->cart['subtotal'] = 0;
		
		foreach( $this->cart['images'] as $id => $sizes ){
			foreach( $sizes as $size => $colors ){
				foreach( $colors as $color => $values ){
					$this->cart['items'] += $_POST['ims-quantity'][$id][$size][$color];
					$this->cart['subtotal'] += 
						( ( $this->cart['images'][$id][$size][$color]['price'] 
						+ $this->cart['images'][$id][$size][$color]['color'] ) * $_POST['ims-quantity'][$id][$size][$color] );
					$this->cart['images'][$id][$size][$color]['subtotal'] = 
						( ( $this->cart['images'][$id][$size][$color]['price'] 
						+ $this->cart['images'][$id][$size][$color]['color'] ) * $_POST['ims-quantity'][$id][$size][$color] );
					$this->cart['images'][$id][$size][$color]['quantity'] = $_POST['ims-quantity'][$id][$size][$color];
				}
			}
		}
		
		$this->cart['shipping'] = $_POST['shipping_1'];
		$this->cart['total'] = $this->cart['subtotal'] + $this->cart['shipping'];
				
		if( $this->validate_code( $_POST['promocode'] ) ){
			if( $this->cart['promo']['promo_type'] == '1' )
				$this->cart['promo']['discount'] = ( $this->cart['total'] * ( $this->cart['promo']['discount']/100 ) );
			elseif( $this->cart['promo']['promo_type'] == '2' )
				$this->cart['promo']['discount'];
			elseif( $this->cart['promo']['promo_type'] == '3' )
				$this->cart['promo']['discount'] = $this->cart['shipping'];
				
			$this->cart['promo']['code'] = $_POST['promocode'];
			$this->cart['discounted'] = $this->cart['total'] - $this->cart['promo']['discount'];

		}else{
			unset( $this->cart['discounted'] ) ;
			unset( $this->cart['promo']['code'] );
		}
		
		$this->cart['total'] = ( $this->cart['discounted'] ) ? $this->cart['discounted'] : $this->cart['total'];
		if( $this->opts['taxamount'] ){
			if( $this->opts['taxtype'] == 'percent' )
				$this->cart['tax'] = ( $this->cart['total'] * ( $this->opts['taxamount']/100 ) );
			else $this->cart['tax'] = $this->opts['taxamount'];
			$this->cart['total'] += $this->cart['tax']; 
		}
		
		if( empty( $this->error ) ){ 
			$this->success = '2';
			update_post_meta( $_COOKIE[ 'ims_orderid_' . COOKIEHASH ], '_ims_order_data', $this->cart );
			wp_redirect( $this->get_permalink( $this->gallery_id, $this->imspage ) ); 
		}
	}
	
		
		
	/**
	 * Display pages
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function display_ims_page( ){ 
	
		$this->gallery = get_post( $this->gallery_id );
			
		$css = ( $this->error ) ? ' error' : '';
		$css = ( $this->message ) ? ' success' : $css;
		
		switch ( $this->imspage ){
			case "2":
				include_once ( dirname (__FILE__) . '/slideshow.php' );
				break;
			case "3":
				include_once ( dirname (__FILE__) . '/favorites.php' );
				break;
			case "4":
				include_once ( dirname (__FILE__) . '/pricelist.php' );
				break;
			case "5":
				include_once ( dirname (__FILE__) . '/shoppingcart.php' );
				break;
			case "7":
				include_once ( dirname (__FILE__) . '/shoppingcart.php' );
				break;
			case "8":
				include_once ( dirname (__FILE__) . '/complete.php' );
				break;
			default:
			include_once ( dirname (__FILE__) . '/photos.php' );
		}
		
	}
	
	
	
}

?>