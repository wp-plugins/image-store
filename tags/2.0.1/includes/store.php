<?php 

/**
 *ImStoreFront - Fontend display 
 *
 *@package Image Store
 *@author Hafid Trujillo
 *@copyright 20010-2011
 *@since 0.5.0 
*/


class ImStoreFront{
	
	/**
	 *Constructor
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function __construct(){
		$this->opts = get_option('ims_front_options');
		$this->page_front= get_option('page_on_front');
		
		add_action('get_header',array(&$this,'ims_init'));
		add_action('wp_head',array(&$this,'print_ie_styles'));
		add_action('pre_get_posts',array(&$this,'pre_get_posts'));
		add_filter('pre_get_posts',array(&$this,'dis_custom_types'),30,1);
		add_filter('query_vars',array(&$this,'add_var_for_rewrites'),10,1);
		add_filter('template_include',array(&$this,'taxonomy_template'),50,1);
		add_action('wp_enqueue_scripts',array(&$this,'load_scripts_styles'));
		add_shortcode('image-store',array(&$this,'imstore_shortcode'));
		add_shortcode('ims-gallery-content',array(&$this,'ims_gallery_shortcode'));
	}

	/**
	 *Display gallery 
	 *
	 *@param $atts array
	 *@return void
	 *@since 2.0.0
	 */ 
	function ims_gallery_shortcode($atts){
		if(!is_single()) return;
		
		//add images to cart
		if(!empty($_POST['add-to-cart']))
			$this->add_to_cart();
		
		//update cart
		if(!empty($_POST['applychanges']))
			$this->upate_cart();	
		
		//checkout email notification only get user info	
		if(!empty($_POST['enotification'])) 
			$this->imspage = 7; 
			
		//submit notification order
		if(!empty($_POST['enoticecheckout'])){
			$this->imspage = 7; 
			check_admin_referer('ims_submit_order');
			$this->validate_user_input();
		}
		
		//redirect photo disable
		if($this->opts['hidephoto']) 
			$this->imspage = 2;
			
		global $post;
		echo '<div id="ims-mainbox" class="ims-'.sanitize_title($this->pages[$this->imspage]).'">';
		$this->store_nav(); 

		
		echo '<div class="ims-labels">';
		if($post->post_expire != '0000-00-00 00:00:00')'<span class="expires">'.
			__("Expires: ",ImStore::domain).date_i18n(get_option('date_format'),strtotime($post->post_expire)).'</span>';
		echo '</div>';
		
		if($this->error)$error = ' ims-error';
		if($this->message)$error = ' ims-success';
		$this->message = ($this->error)?$this->error:$this->message;
		
		echo '<div class="ims-message'.$error.'">'.$this->message.'</div>';
		 
		echo '<div class="ims-innerbox">';
		switch($this->imspage){
			case "2":
				$this->get_gallery_images();
				include_once(dirname(__FILE__).'/slideshow.php');
				break;
			case "3":
				$post->comment_status = false;
				include_once(dirname(__FILE__).'/pricelist.php');
				break;
			case "4":
				$post->comment_status = false;
				$this->get_favorite_images();
				if(!$this->opts['disablestore']) $this->store_subnav();
				$this->display_galleries();
				break;
			case "5":
				$post->comment_status = false;
				include_once(dirname(__FILE__).'/cart.php');
				break;
			case "6":
				$post->comment_status = false;
				include_once(dirname(__FILE__).'/receipt.php');
				break;
			case "7":
				$post->comment_status = false;
				include_once(dirname(__FILE__).'/checkout.php');
				break;
			default:
				$this->get_gallery_images();
				if(!$this->opts['disablestore']) $this->store_subnav();
				$this->display_galleries();
		}
		echo '</div>';
		if(!$this->opts['disablestore']) $this->display_list_form();
		echo '<div class="ims-cl"></div></div>';
	}
	
	/**
	 *Core fuction display store
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function imstore_shortcode($atts){
		if($atts['secure']){
			//try to login
			if(!empty($_REQUEST["login-imstore"])){
				if(!wp_verify_nonce($_REQUEST["_wpnonce"],'ims_access_form')) return; 
				$errors = $this->validate_user();
				if(isset($errors) && is_wp_error($errors)){
					foreach($errors->get_error_messages() as $err)
						$output .= '<span class="error">'.$err.'<span>';
					echo '<div class="ims-message ims-error">'. $output .'</div>';
				} 
			}
			if(empty($_COOKIE['wp-postpass_'.COOKIEHASH]) 
			|| empty($_COOKIE['ims_galid_'.COOKIEHASH]))
				$this->get_login_form();
			elseif(isset($_COOKIE['wp-postpass_'.COOKIEHASH])) 
				wp_redirect(get_permalink($_COOKIE['ims_galid_'.COOKIEHASH]));			
		}elseif($atts['album']){
			$this->get_galleries($atts['album']); 
			$this->display_galleries();
		}else{
			$this->get_galleries(); 
			$this->display_galleries();
		}
	}
	
	/**
	 *Populate query to allow the use
	 *of the gallery as home page
	 *
	 *@return void
	 *@since 1.1.0
	 */ 
	function pre_get_posts(){ 
		global $wp_query; 
		if($wp_query->query_vars['imsgalid'] && 
		$wp_query->is_home && empty($wp_query->is_singular)){
			$wp_query->is_page = 1;
			$wp_query->is_posts_page = 1;
			$wp_query->query_vars['p'] = $this->page_front;
			$wp_query->query_vars['page_id'] = $this->page_front;
		}
	}
	
	/**
	*Encrypt image ID for downlaods
	*
	*@return void
	*@since 2.0.1
	*/
	function encrypt_id($int) {
    	$HashedChecksum = substr(sha1("imstore".$int.SECURE_AUTH_KEY),0,6);
    	$hex = dechex($int);
    	return urlencode(base64_encode($HashedChecksum.$hex));
    }
	
	/**
	*Dencrypt image ID for downlaods
	*
	*@return void
	*@since 2.0.1
	*/
	function decrypt_id($string) {
   		$parts 	= base64_decode(urldecode($string));
		$hex 	= substr($parts,6);
		$int 	= hexdec($hex);
		$part1  = substr($parts,0,6);
		return (substr(sha1("imstore".$int.SECURE_AUTH_KEY),0,6) === $part1) ? $int : false;
    }

	/**
	 *Outputs html selected attribute.
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function selected($helper,$current){
		if((string)$helper === (string)$current) 
			echo $result = ' selected="selected"';
	}
		
	/**
	 *Display albums(taxonomy)
	 *
	 *@param $query obj
	 *@return void
	 *@since 2.0.0
	 */
	function dis_custom_types($query) { 
		if(is_tax() && $query->query_vars['post_type'] != 'nav_menu_item')
			$query->set('post_type',array('ims_gallery'));
	}
	
	/**
	*Add rewrite vars
	*
	*@param array $vars
	*@return array
	*@since 0.5.0 
	*/
	function add_var_for_rewrites($vars){
		array_push($vars,'imspage','imsmessage','imslogout','paypalipn');
		return $vars;
	}
	
	/**
	 *Print IE styles
	 *needed for colorbox
	 *
	 *@return void
	 *@since 0.5.2 
	 */
	function print_ie_styles(){
		if(isset($_SERVER['HTTP_USER_AGENT']) &&(strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') !== false))
			echo '<!--[if IE]><link rel="stylesheet" id="colorboxie-css" href="'.IMSTORE_URL.'_css/colorbox.ie.php?url='.IMSTORE_URL.'&amp;ver=0.5.0" type="text/css" media="all" /><![endif]-->';
	}
	
	/**
	 *Redirect taxonomy template
	 *to display album galleries
	 *
	 *@param $template string
	 *@return string
	 *@since 2.0.0
	 */
	function taxonomy_template($template){
		global $wp_query,$post;
		if(is_tax() && $wp_query->query_vars['taxonomy'] == 'ims_album'){
			if(file_exists(WP_TEMPLATE_DIR."/page.php")){
				$post->ID				= NULL;
				$post->post_password	= NULL;
				$post->post_title 		= $wp_query->queried_object->name;
				$post->post_content 	= '[image-store album="'.$wp_query->queried_object->term_id.'"]';
				$wp_query->init();
				$wp_query->post_count	= 1;
				$wp_query->posts[] 		= $wp_query->post = $post;
				return WP_TEMPLATE_DIR."/page.php";
			}
		}
		return $template; 
	}

	/**
	 *Populate object variables
	 *
	 *@return void
	 *@since 2.0.0
	 */
	function ims_init(){
		global $wpdb,$post;
		
		$this->gateway = array(
			'paypalprod' => 'https://www.paypal.com/cgi-bin/webscr',
			'paypalsand' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			'googleprod' => 'https://google.com/checkout/api/checkout/v2/checkoutForm/Merchant/'.$this->opts['googleid'],
			'googlesand' => 'https://sandbox.google.com/checkout/api/checkout/v2/checkoutForm/Merchant/'.$this->opts['googleid'],
		);
		
		$this->pages[1] = __('Photos',ImStore::domain);
		$this->pages[2] = __('Slideshow',ImStore::domain);
		if(!$this->opts['disablestore']){
			$this->pages[3] = __('Price List',ImStore::domain);
			$this->pages[4] = __('Favorites',ImStore::domain);
			$this->pages[5] = __('Shopping Cart',ImStore::domain);
			$this->pages[6] = __('Receipt',ImStore::domain);
			$this->pages[7] = __('Shipping',ImStore::domain);
		}

		//dont change array order
		$this->subtitutions = array(
			$_POST['payment_gross'],$_POST['payment_status'],
			$wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$_POST['custom']."' "),
			$_POST['mc_shipping1'],$data['tracking'],$data['gallery_id'],$_POST['txn_id'],
			$_POST['last_name'],$_POST['first_name'],$_POST['payer_email'],
		);
		$messages = array(
			'1' => __('Successfully added to cart',ImStore::domain),
			'2' => __('Cart successfully updated',ImStore::domain),
			'3' => __('Your transaction has been cancel!!',ImStore::domain)
		);
		$this->secure_page	= get_option('ims_page_secure');
		$this->permalinks 	= get_option('permalink_structure');
		$this->imspage		= ($page = get_query_var('imspage'))?$page:1;
		if($this->permalinks)
			foreach($this->pages as $id => $link)
				if($this->imspage == sanitize_title($link))
					$this->imspage = $id;
		//logout gallery
		if(get_query_var('imslogout')){
			ImStore::logout_ims_user();
			wp_redirect(get_permalink($this->secure_page)); 
		}
		$this->gallery_id	= $post->ID;
		$this->sizes 		= $this->get_price_list();
		$this->message		= $messages[get_query_var('imsmessage')];
		$this->query_id 	= ($id = get_query_var('imsgalid'))?$id:get_query_var('p');
		$this->listmeta 	= get_post_meta($this->pricelist_id,'_ims_list_opts',true);
		$this->order		= ($sort = get_post_meta($this->gallery_id,'_ims_order',true))?$sort:$this->opts['imgsortdirect'];
		$this->sortby 		= ($sortby = get_post_meta($this->gallery_id,'_ims_sortby',true))?$sortby:$this->opts['imgsortorder'];
		$this->cart 		= get_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_ims_order_data',true);

		//process paypal IPN 
		if(isset($_POST['txn_id']) && isset($_POST['custom']) && $this->imspage == 1) 
			include_once(dirname(__FILE__).'/paypal-ipn.php');
			
		//process google notification
		if(isset($_POST['google-order-number']) && isset($_POST['shopping-cart_merchant-private-data']) && $this->imspage == 1)
			include_once(dirname(__FILE__).'/google-notice.php');
		
		//checkout
		if(!empty($_POST['checkout'])) $this->redirect_form_post_data($this->gateway[$this->opts['gateway']]);
	}
	
	/**
	 * Send post data to a url
	 *
	 * @return void
	 * @since 0.5.0 
	 */
	function redirect_form_post_data($addr){
		if(!wp_verify_nonce($_POST["_wpnonce"],"ims_submit_order")) return; 
		foreach($_POST as $k => $v)
			if(!is_array($v)) $req .= "&$k=".urlencode($v);
		$this->cart['tracking'] = get_post_meta($this->gallery_id,'ims_tracking',true);
		$this->cart['gallery_id'] = get_post_meta($this->gallery_id,'_ims_gallery_id',true);
		update_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_ims_order_data',$this->cart);
		header("Location: $addr \r\n",TRUE,307);
		header("Content-Length: ". strlen($req) ."\r\n");
		header("Content-Type: application/x-www-form-urlencoded\r\n");
		exit();
	}
	
	/**
	 *Load frontend js/css
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function load_scripts_styles(){
		global $post;
		wp_enqueue_style('colorbox',IMSTORE_URL.'_css/colorbox.css',NULL,'1.1.6');
		wp_enqueue_script('colorbox',IMSTORE_URL.'_js/jquery.colorbox.js',array('jquery'),'1.1.6',true); 	
		wp_enqueue_script('galleriffic',IMSTORE_URL.'_js/jquery.galleriffic.js',array('jquery'),'1.3.6 ',true); 
		if($this->opts['stylesheet']) wp_enqueue_style('imstore',IMSTORE_URL.'_css/imstore.css',NULL,ImStore::version);
		wp_enqueue_script('imstorejs',IMSTORE_URL.'_js/imstore.js',array('jquery','colorbox','galleriffic'),ImStore::version,true);
		
		wp_localize_script('imstorejs','imstore',array(
			'galid'				=> $post->ID,			  
			'imstoreurl'		=> IMSTORE_ADMIN_URL,												  
			'colorbox'			=> $this->opts['colorbox'],									  
			'numThumbs'			=> $this->opts['numThumbs'],
			'wplightbox'		=> $this->opts['wplightbox'],
			'autoStart'			=> $this->opts['autoStart'],
			'playLinkText'		=> $this->opts['playLinkText'],
			'pauseLinkTex'		=> $this->opts['pauseLinkTex'],
			'prevLinkText'		=> $this->opts['prevLinkText'],
			'nextLinkText' 		=> $this->opts['nextLinkText'],
			'closeLinkText' 	=> $this->opts['closeLinkText'],
			'maxPagesToShow'	=> $this->opts['maxPagesToShow'],
			'slideshowSpeed'	=> $this->opts['slideshowSpeed'],
			'transitionTime'	=> $this->opts['transitionTime'],
			'nextPageLinkText' 	=> $this->opts['nextPageLinkText'],
			'prevPageLinkText'	=> $this->opts['prevPageLinkText'],
			'ajaxnonce'			=> wp_create_nonce("ims_ajax_favorites")						  
		));

	}
	
	/**
	 *Display secure galleries login form
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function get_login_form(){
		global $post;
		
		$plabel	= "ims-pwdbox-{$post->ID}";
		$glabel = "ims-galbox-{$post->ID}";
		$nonce 	= wp_create_nonce('ims_access_form');

		$output = '<form action="'.get_permalink($post->ID).'" method="post">
		<p class="message login">'.__("To view your images please enter your login information below:").'</p>
			<div class="ims-fields"
				<label for="'.$glabel.'">'.__("Gallery ID:",ImStore::domain).'</label> <input name="'.$glabel.'" id="'.$glabel.'"" />
				<spam class="linebreak"></spam>
				<label for="'.$plabel.'">'.__("Password:",ImStore::domain).'</label> <input name="'.$plabel.'" id="'.$plabel.'" type="password" />
				<spam class="linebreak"></spam>
				<input type="submit" name="login-imstore" value="'.esc_attr(__("Log In",ImStore::domain)).'" />
				<input type="hidden" name="_wpnonce" value="'.$nonce.'" />
			</div>
		</form>
		';
		echo $output;
	}
	
	/**
	*Display store navigation
	*
	*@return void
	*@since 0.5.0 
	*/
	function store_nav(){
		global $post;
		$nav = '<ul id="imstore-nav" class="imstore-nav" >'. "\n";
		foreach($this->pages as $key => $page){
			if($key == 6 || $key == 7) continue;
			if($key == 1 && $this->opts['hidephoto']) continue;
			if($key == 2 && $this->opts['hideslideshow']) continue;
			$title 	= sanitize_title($page);
			$count 	= ($key == 5  && $this->cart['items'] &&  $this->imspage != 6)? "<span>(".$this->cart['items'].")</span>":'';
			$css 	= ($key == $this->imspage ||($key == 1 && empty($this->imspage)))?' current':'';
			$nav 	.= '<li class="ims-menu-'.$title.$css.'"><a href="'.$this->get_permalink($key).'">'.$page."</a> $count</li>"."\n";
		}
		if($post->post_password && isset($_COOKIE['wp-postpass_'.COOKIEHASH]) && $this->permalinks)
			$nav .= '<li class="ims-menu-logout"><a href="'.get_permalink().'/logout/true">'.__("Exit Gallery",ImStore::domain).'</a></li>'."\n";
		elseif($post->post_password && isset($_COOKIE['wp-postpass_'.COOKIEHASH]))
			$nav .= '<li class="ims-menu-logout"><a href="'.get_permalink().'&amp;imslogout=true">'.__("Exit Gallery",ImStore::domain).'</a></li>'."\n";			
		echo $nav."</ul>\n";
	}
	
	/**
	*Display store sub-navigation
	*
	*@return void
	*@since 2.0.0
	*/
	function store_subnav(){
		$nav = '<div class="ims-toolbar"><ul class="ims-tools-nav">
		<li class="ims-select-all"><a href="#" rel="nofollow">'.__("Select all",ImStore::domain).'</a></li>
		<li class="ims-unselect-all"><a href="#" rel="nofollow">'.__("Unselect all",ImStore::domain).'</a></li>';
		if($this->imspage == 1) $nav .= '<li class="add-to-favorite"><a href="#" rel="nofollow">'.__("Add to favorites",ImStore::domain).'</a></li>';
		if($this->imspage == 4) $nav .= '<li class="remove-from-favorite"><a href="#" rel="nofollow">'.__("Remove",ImStore::domain).'</a></li>';
		$nav .= '<li class="add-images-to-cart"><a href="#" rel="nofollow">'.__("Add to cart",ImStore::domain).'</a></li>';
		echo $nav .= '</ul></div>';
	}
	
	/**
	*Get imstore permalink
	*
	*@param string $page
	*@since 0.5.0 
	*return void
	*/
	function get_permalink($page = ''){
		$link = '';
		if($this->permalinks){
			$link .= "/". sanitize_title($this->pages[$page]);
			if($this->success) $link .= '/ms/'.$this->success;
		}else{
			$link = '&imspage='.$page;
			if(is_front_page()) $link .= '?page_id='.$this->page_front;
			if($this->success) $link .= '&imsmessage='.$this->success; 
		} return trim(get_permalink(),'/').str_replace('//','/',$link);
	}
	
	/**
	*Display galleries
	*
	*@return array
	*@since 0.5.0 
	*/
	function display_galleries(){ 
		
		$itemtag 	= 'ul';
		$icontag 	= 'li';
		$captiontag = 'div';
		$columns 	= intval($this->opts['displaycolmns']);
		//$nonce 		= '_wpnonce='.wp_create_nonce('ims_secure_img');
		
		$output = "<{$itemtag} class='ims-gallery'>";
		foreach($this->attachments as $image){
			if($image->post_parent){
				$title = $caption = get_the_title($image->post_parent);
				$link 		= get_permalink($image->post_parent);
				$tagatts	= ' class="ims-image" rel="image" ';
			}else{
				$title 		= $image->post_title;
				$tagatts	= ' class="ims-colorbox" rel="gallery" ';
				$caption	= ($this->is_galleries)?$title:$image->post_excerpt ;
				$link 		= IMSTORE_URL."image.php?$nonce&amp;img={$enc}&amp;w=".$this->opts['watermark'];
			}
			$enc = $this->encrypt_id($image->ID);	
			$imagetag = '<img src="'.IMSTORE_URL."image.php?$nonce&amp;img={$enc}&amp;thumb=1".'" alt="'.$title.'" />'; 
			
			$output .= "<{$icontag}>";
			if(!$this->opts['disablestore'] &&($this->query_id || $this->is_secure)) 
				$output .= '<input name="imgs[]" type="checkbox" value="'.$enc.'" />';
			
			$output .= '<a href="'.$link.'"'.$tagatts.'title="'.esc_attr($caption).'">'.$imagetag.'</a>';
			if($this->is_galleries){
				$output .= "
					<{$captiontag} class='gallery-caption'>
					".wptexturize($title)."
					</{$captiontag}>";
			}
			$output .= "</{$icontag}>";
		}
		echo $output .= "</{$itemtag}>";
		
		if(!isset($_COOKIE['ims_gal_'.$this->gallery_id.'_'.COOKIEHASH])){
			update_post_meta($this->gallery_id,'_ims_visits',get_post_meta($this->gallery_id,'_ims_visits',true)+1);
			setcookie('ims_gal_'.$this->gallery_id.'_'.COOKIEHASH,true,0,COOKIEPATH);
		}
	}
	
	/**
	*Get gallery price list
	*
	*@return array
	*@since 0.5.0 
	*/
	function get_price_list(){
		global $wpdb;
		$sizes = $wpdb->get_results($wpdb->prepare("
			SELECT meta_value,post_id FROM $wpdb->postmeta 
			WHERE post_id = (SELECT meta_value FROM $wpdb->postmeta 
				WHERE post_id = %s AND meta_key = '_ims_price_list') 
			AND meta_key = '_ims_sizes' "
		,$this->gallery_id));
		$this->pricelist_id = $sizes[0]->post_id;
		foreach($sizes as $size)
			return $gallery_sizes = maybe_unserialize($size->meta_value);
	}
	
	/**
	*Get gallery images
	*
	*@return array
	*@since 0.5.0 
	*/
	function get_gallery_images(){
		global $wpdb;
		$this->attachments = $wpdb->get_results($wpdb->prepare(
			"SELECT ID,post_title,guid,
			meta_value,post_excerpt,post_expire
			FROM $wpdb->posts AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND post_status = 'publish'
			AND post_parent = %d
			ORDER BY $this->sortby $this->order " 
		,$this->gallery_id));
		if(empty($this->attachments)) return;
		foreach($this->attachments as $post){
			$post->meta_value = unserialize($post->meta_value);
			$images[] = $post;
		} $this->attachments = $images;
	}
	
	/**
	*Get favorites
	*
	*@return array
	*@since 0.5.0 
	*/
	function get_favorite_images(){
		global $wpdb,$user_ID;
		if(is_user_logged_in()) $ids = trim(get_user_meta($user_ID,'_ims_favorites',true),','); 
		else $ids = trim($_COOKIE['ims_favorites_'.COOKIEHASH],',');
		$this->attachments = $wpdb->get_results(
			"SELECT ID,post_title,guid,post_excerpt
			FROM $wpdb->posts AS p WHERE post_type = 'ims_image'
			AND ID IN($ids) ORDER BY $this->sortby $this->order " 
		);
		if(empty($this->attachments)) return;
		foreach($this->attachments as $post){
			$post->meta_value = unserialize($post->meta_value);
			$images[] = $post;
		} $this->attachments = $images;
	}
	
	/**
	*Get gallery images
	*
	*@return array
	*@since 2.0.0
	*/
	function get_galleries($album=''){
		global $wpdb;
		$this->is_galleries = true;
		$type = ($album)?"
			SELECT DISTINCT object_id FROM $wpdb->terms AS t 
			INNER JOIN $wpdb->term_taxonomy tt ON t.term_id = tt.term_id 
			INNER JOIN $wpdb->term_relationships tr ON tt.term_taxonomy_id = tr.term_taxonomy_id 
			WHERE t.term_id = $album"
		: 	"SELECT DISTINCT ID
			FROM $wpdb->posts
			WHERE post_type = 'ims_gallery'
			AND post_status = 'publish' AND post_password = ''" ;
		$this->attachments = $wpdb->get_results(
			"SELECT ID,post_title,
			meta_value,post_excerpt,post_parent
			FROM(SELECT *FROM $wpdb->posts 
				ORDER BY menu_order ASC,post_date DESC) AS p 
			LEFT JOIN $wpdb->postmeta AS pm
			ON pm.post_id = p.ID
			WHERE post_type = 'ims_image'
			AND meta_key = '_wp_attachment_metadata'
			AND post_status = 'publish'
			AND post_parent IN($type)
			GROUP BY p.post_parent
			ORDER BY p.menu_order ASC,p.post_date DESC "
		);
		if(empty($this->attachments)) return;
		foreach($this->attachments as $post){
			$post->meta_value = unserialize($post->meta_value);
			$images[] = $post;
		} $this->attachments = $images;
	}
	
	/**
	*Display Order form
	*
	*@return void
	*@since 0.5.0 
	*/
	function display_list_form(){?>
		<form id="ims-pricelist" action="" method="post">
		<div class="ims-image-count"><?php _e('Selected',ImStore::domain)?></div>
		<div class="ims-instructions"><?php _e('These preferences will be apply to all the selected images',ImStore::domain)?></div>
		<div class="ims-add-error"><?php _e('There are no images selected',ImStore::domain)?></div>
		
		<div class="ims-field"> 
			<label for="ims-quantity"><?php _e('Quantity',ImStore::domain)?> </label>
			<input name="ims-quantity" type="text" class="inputsm" id="ims-quantity" value="1" />
		</div>
		
		<div class="ims-field">
			<label for="ims-image-size"><?php _e('Size',ImStore::domain)?> </label>
			<?php if($sizes = $this->sizes){
				unset($sizes['random']); ?>
			<select name="ims-image-size" id="ims-image-size" class="select">
				<option value=""><?php _e('Image size',ImStore::domain)?></option>
				<?php foreach($sizes as $size){
					echo '<option value="';
					if($size['ID']){
						echo $size['name'].'">'.$size['name'] .': '; $package_sizes = '';
						foreach((array)get_post_meta($size['ID'],'_ims_sizes',true) as $package_size => $count){
							if(is_array($count)) $package_sizes .= $package_size .' '. $count['unit'].'('.$count['count'].'),'; 
							else $package_sizes .= $package_size .'('.$count.'),'; 
						}
						echo rtrim($package_sizes,',').'</option>';
					}else{ 
						echo $size['name'].'">'.$size['name'].' </option>';	
					}
				}?>
			</select>
			<?php }?>
		</div>
		<?php if(!$this->opts['disablebw'] || !$this->opts['disablesepia']){?>
		<div class="ims-field">
			<label for="_imstore-color"><?php _e('Color',ImStore::domain)?> </label>
			<select name="_imstore-color" id="_imstore-color" class="select">
				<option value="color"><?php _e('Full Color',ImStore::domain)?></option>
				<?php if(!$this->opts['disablesepia']){?>
				<option value="ims_sepia"><?php _e('Sepia',ImStore::domain)?>+<?php echo $this->listmeta['ims_bw']?></option>
				<?php }?>
				<?php if(!$this->opts['disablebw']){?>
				<option value="ims_bw"><?php _e('Black &amp; White',ImStore::domain)?>+<?php echo $this->listmeta['ims_sepia']?></option>
				<?php }?>
			</select>
		</div>
		<?php }?>
		<div class="ims-field ims-submit">
			<input name="add-to-cart" type="submit" value="<?php _e('Add to cart',ImStore::domain)?>" class="button" />
			<input type="hidden" name="ims-to-cart-ids" id="ims-to-cart-ids" />
		</div>
	</form>
	<?php }
	
	/**
	*Add items to cart
	 *
	*@return void
	*@since 0.5.0 
	*/
	function add_to_cart(){
		if(!is_numeric($_POST['ims-quantity']) || empty($_POST['ims-quantity']))
			$this->error = __('Please,enter a valid image quantity',ImStore::domain);
	
		if(empty($_POST['ims-image-size']))
			$this->error = __('Please,select an image size.',ImStore::domain);

		if(empty($_POST['ims-to-cart-ids']))
			$this->error = __('There was a problem adding the images to the cart.',ImStore::domain);
		
		if(!empty($this->error)) return;
		
		$images = explode(',',$_POST['ims-to-cart-ids']);
		$color	= (empty($_POST['_imstore-color']))?'color':$_POST['_imstore-color'];
		
		foreach($images as $id){
			$id = $this->decrypt_id($id);
			foreach($this->sizes as $size){
				if($size['name'] != $_POST['ims-image-size']) continue;
				if($size['ID']) $this->cart['images'][$id][$_POST['ims-image-size']][$color]['price'] = get_post_meta($size['ID'],'_ims_price',true);
				else $this->cart['images'][$id][$_POST['ims-image-size']][$color]['price'] = $size['price']; 
				$this->cart['images'][$id][$_POST['ims-image-size']][$color]['unit'] = $size['unit'];
				if($size['download']) $this->cart['images'][$id][$_POST['ims-image-size']][$color]['download'] = $size['download'];
				else $this->cart['shippingcost'] = 1;
				continue;
			}
			$this->cart['items'] += $_POST['ims-quantity'];
			$this->cart['images'][$id][$_POST['ims-image-size']][$color]['gallery'] = $this->gallery_id;
			$this->cart['images'][$id][$_POST['ims-image-size']][$color]['color'] = $this->listmeta[$color];
			$this->cart['images'][$id][$_POST['ims-image-size']][$color]['quantity'] += $_POST['ims-quantity'];
			$this->cart['images'][$id][$_POST['ims-image-size']][$color]['subtotal'] = 
			(($this->cart['images'][$id][$_POST['ims-image-size']][$color]['price']+$this->listmeta[$color])*$_POST['ims-quantity']);
			$this->cart['subtotal'] += $this->cart['images'][$id][$_POST['ims-image-size']][$color]['subtotal'];
		}
		
		if($this->cart['shippingcost'] ) $this->cart['shipping'] = ($this->cart['shipping'])?$this->cart['shipping']:$this->listmeta['ims_ship_local'];
		$this->cart['total'] = $this->cart['subtotal']+$this->cart['shipping'];
		
		if($this->cart['promo']['code']){
			switch($this->cart['promo']['promo_type']){
				case 1: $this->cart['promo']['discount'] = ($this->cart['total']*($this->cart['promo']['discount']/100)); break;
				case 2: $this->cart['promo']['discount']; break;
				case 3: $this->cart['promo']['discount'] = $this->cart['shipping']; break;
			}
			$this->cart['discounted'] = $this->cart['total'] - $this->cart['promo']['discount'];
		}
		
		$this->cart['total'] = ($this->cart['discounted'])?$this->cart['discounted']:$this->cart['total'];
		if($this->opts['taxamount']){
			if($this->opts['taxtype'] == 'percent') $this->cart['tax'] = ($this->cart['total']*($this->opts['taxamount']/100));
			else $this->cart['tax'] = $this->opts['taxamount']; $this->cart['total'] += $this->cart['tax']; 
		}
		
		if(empty($_COOKIE['ims_orderid_'.COOKIEHASH])){
			$orderid = wp_insert_post(array(
				'ping_status' 	=> 'close',
				'post_status' 	=> 'draft',
				'post_type' 	=> 'ims_order',
				'post_parent' 	=> $this->gallery_id,
				'post_expire' 	=> date('Y-m-d H:i',current_time('timestamp')+86400),
				'post_title' 	=> 'Ims Order - '.date('Y-m-d H:i',current_time('timestamp')),
			));
			if(!empty($orderid) && !empty($this->cart)){
				add_post_meta($orderid,'_ims_order_data',$this->cart);
				setcookie('ims_orderid_'.COOKIEHASH,$orderid,0,COOKIEPATH);
				update_post_meta($orderid,'_ims_order_data',$this->cart);
			}
		}else update_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_ims_order_data',$this->cart);
		$this->success = '1';
		wp_redirect($this->get_permalink($this->imspage)); 
	}
	
	/**
	 *update cart information
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function upate_cart(){
		if(!wp_verify_nonce($_REQUEST["_wpnonce"],"ims_submit_order")) 
			die('Security check failed!!'); 
		
		if(is_array($_POST['ims-remove'])){
			foreach($_POST['ims-remove'] as $delete){
				$values = explode('|',$delete);
				$values[0] = $this->decrypt_id($values[0]);
				unset($this->cart['images'][$values[0]][$values[1]][$values[2]]);
				if(empty($this->cart['images'][$values[0]][$values[1]]))
					unset($this->cart['images'][$values[0]][$values[1]]);
				if(empty($this->cart['images'][$values[0]]))
					unset($this->cart['images'][$values[0]]);
				unset($this->cart['shippingcost']);
			}
		}
				
		if(empty($this->cart['images'])){ update_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_ims_order_data',''); return;}
		$this->cart['items'] = 0; $this->cart['subtotal'] = 0;
		foreach($this->cart['images'] as $id => $sizes){
			foreach($sizes as $size => $colors){
				foreach($colors as $color => $values){
					$this->cart['items'] += $_POST['ims-quantity'][$id][$size][$color];
					$this->cart['subtotal'] += 
						(($this->cart['images'][$id][$size][$color]['price'] 
						+ $this->cart['images'][$id][$size][$color]['color']) * $_POST['ims-quantity'][$id][$size][$color]);
					$this->cart['images'][$id][$size][$color]['subtotal'] = 
						(($this->cart['images'][$id][$size][$color]['price'] 
						+ $this->cart['images'][$id][$size][$color]['color']) * $_POST['ims-quantity'][$id][$size][$color]);
					$this->cart['images'][$id][$size][$color]['quantity'] = $_POST['ims-quantity'][$id][$size][$color];
				}
			}
		}
		
		if($this->cart['shippingcost']) $this->cart['shipping'] = $_POST['shipping_1'];
		$this->cart['total'] = $this->cart['subtotal']+$this->cart['shipping'];
		
		if($this->validate_code($_POST['promocode'])){
			switch($this->cart['promo']['promo_type']){
				case 1: $this->cart['promo']['discount'] = ($this->cart['total']*($this->cart['promo']['discount']/100)); break;
				case 2: $this->cart['promo']['discount']; break;
				case 3: $this->cart['promo']['discount'] = $this->cart['shipping']; break;
			}
			$this->cart['promo']['code'] = $_POST['promocode'];
			$this->cart['discounted'] = $this->cart['total'] - $this->cart['promo']['discount'];
		}else{ unset($this->cart['discounted']); unset($this->cart['promo']['code']);}
		
		$this->cart['total'] = ($this->cart['discounted'])?$this->cart['discounted']:$this->cart['total'];
		if($this->opts['taxamount']){
			if($this->opts['taxtype'] == 'percent') $this->cart['tax'] = ($this->cart['total'] *($this->opts['taxamount']/100));
			else $this->cart['tax'] = $this->opts['taxamount']; $this->cart['total'] += $this->cart['tax']; 
		}
		$this->success = '2';
		update_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_ims_order_data',$this->cart);
		wp_redirect($this->get_permalink($this->imspage)); 
	}
	
	/**
	 *User login function
	 *
	 *@return void
	 *@since 0.5.0 
	 */
	function validate_user(){
		global $post,$wpdb;
		$errors = new WP_Error();
		
		if(empty($_REQUEST["ims-galbox-".$post->ID]))
			$errors->add('emptyid',__('Please enter a gallery id. ',ImStore::domain));
		
		if(empty($_REQUEST["ims-pwdbox-".$post->ID]))
			$errors->add('emptypswd',__('Please enter a password. ',ImStore::domain));
			
		if(!empty($errors->errors)) return $errors;

		$gallery = $wpdb->get_results($wpdb->prepare(
			"SELECT post_id,post_password FROM $wpdb->postmeta AS pm 
			LEFT JOIN $wpdb->posts AS p ON pm.post_id = p.ID 
			WHERE meta_key = '_ims_gallery_id' 
			AND meta_value = '%s' ",$_REQUEST["ims-galbox-".$post->ID]
		));
		if($gallery[0]->post_password === $_REQUEST["ims-pwdbox-".$post->ID]){
			setcookie('ims_galid_'.COOKIEHASH,"{$gallery[0]->post_id}",0,COOKIEPATH);
			setcookie('wp-postpass_'.COOKIEHASH,"{$gallery[0]->post_password}",0,COOKIEPATH);
			update_post_meta($gallery[0]->post_id,'_ims_visits',get_post_meta($gallery[0]->post_id,'_ims_visits',true)+1);
			wp_redirect(get_permalink($gallery[0]->post_id));
		}else{
			$errors->add('nomatch',__('Gallery ID or password is incorrect. Please try again. ',ImStore::domain));
			return $errors;
		}
	}
	
	/**
	 *Validate promotion code
	 *
	 *@return bool
	 *@since 0.5.0 
	 */
	function validate_code($code){
		global $wpdb;
		if(empty($code)) return false;

		$promo_id = $wpdb->get_var($wpdb->prepare(
			"SELECT ID FROM $wpdb->posts AS p
			INNER JOIN $wpdb->postmeta AS pm
			ON p.ID = pm.post_id
			WHERE meta_key = '_ims_promo_code' 
			AND meta_value = BINARY '%s'
			AND post_status = 'publish' 
			AND post_date <= '".date('Y-m-d',current_time('timestamp'))."'
			AND post_expire >= '".date('Y-m-d',current_time('timestamp'))."' "
		,$code));
		
		if(empty($promo_id)){
			$this->error = __("Invalid promotion code",ImStore::domain);
			return false;
		}
		
		$data = get_post_meta($promo_id,'_ims_promo_data',true);
		$this->cart['promo']['discount'] = $data['discount'];
		$this->cart['promo']['promo_type'] = $data['promo_type'];

		switch($data['rules']['logic']){
			case 'equal':
				if($this->cart[$data['rules']['property']] > $data['rules']['value'])
					return true;
				break;
			case 'more':
				if($this->cart[$data['rules']['property']] > $data['rules']['value'])
					return true;
				break;
			case 'less':
				if($this->cart[$data['rules']['property']] > $data['rules']['value'])
					return true;
				break;
		}
		$this->error = __("Your current purchase doesn't meet the promotion requirements.",ImStore::domain);
		return false;
	}
	
	/**
	 *Validate user input from 
	 *shipping information
	 *
	 *@since 1.0.2
	 *return array|errors
	 */
	function validate_user_input(){
		$req = implode(' ',(array)$this->opts['requiredfields']); 
		foreach($this->opts['checkoutfields'] as $key => $label){
			if(preg_match("/$key/i",$req) && empty($_POST[$key]))
			$this->error .= sprintf(__('The %s is required.',ImStore::domain),$label)."<br />";
		}
		
		if(!empty($_POST['user_email']) && !is_email($_POST['user_email']))
			$this->error .= __('Wrong email format.',ImStore::domain);
		
		if(!empty($this->error)) return;
		if($_POST['payment_total'] != $this->cart['total']) return false;
		if($_POST['mc_currency'] != $this->opts['currency']) return false;
	
		wp_update_post(array(
			'post_expire' => '0',
			'post_status' => 'pending',
			'post_date' => current_time('timestamp'),
			'ID' => $_COOKIE['ims_orderid_'.COOKIEHASH],
		));
		update_post_meta($_COOKIE['ims_orderid_'.COOKIEHASH],'_response_data',$_POST);
			
		$to 		= $this->opts['notifyemail'];
		$subject 	= $this->opts['notifysubj'];
		$message 	= preg_replace($this->opts['tags'],$this->subtitutions,$this->opts['notifymssg']);
		$headers 	= 'From: "Image Store" <imstore@'.$_SERVER['HTTP_HOST'].">\r\n";
		wp_mail($to,$subject,$message,$headers);
		
		$this->imspage = 6;
	}
	
}
$this->store = new ImStoreFront()
?>