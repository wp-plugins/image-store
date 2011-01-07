<?php 

/**
 * Read galleries page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 1.2.0
*/


// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_read_galleries' ) ) 
	die( );

$columns = array(
	'gallery'	=> __( 'Gallery', ImStore::domain ), 
	'galleryid' => __( 'Gallery ID', ImStore::domain ), 
	'images' 	=> __( 'Images', ImStore::domain ), 
	'expire' 	=> __( 'Expires', ImStore::domain ),
);

$counter 	= 0; 
$pageid		= get_option( 'ims_page_secure' );
$galleries 	= get_ims_galleries( $this->per_page );
$nonce 		= '&_wpnonce=' . wp_create_nonce( 'ims_galleries_link' );
$pagenowurl = admin_url( ) . 'profile.php?page=' . $_GET['page'];

?>
<div class="wrap imstore">
	<?php screen_icon( 'galleries' )?>
	<h2><?php _e( 'Galleries', ImStore::domain )?>
	<?php if ( !empty( $_GET['s'] ) )
		printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', ImStore::domain) . '</span>', esc_html( $_GET['s'] ) )?>
	</h2>
	
	<div id="poststuff" class="metabox-holder">
		
		<form method="get" action="<?php echo $pagenowurl?>">
		<div class="tablenav">
			<p class="search-box">
			<input type="hidden" name="page" value="<?php echo $_GET['page']?>" />
			<input type="text" id="media-search-input" name="s" value="<?php echo esc_attr( $_GET['s'] )?>" />
			<input type="submit" value="<?php _e( 'Search Galleries', ImStore::domain )?>" class="button" />
			</p>
		</div>
		
		
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr>
				<?php foreach( $columns as $key => $column ): ?> 	
					<th scope="col" id="<?php echo $key ?>" class="manage-column column-<?php echo $key?>"><?php echo $column?></th>
				<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
			<?php 
				foreach( $galleries as $gallery ):
				$id 	= $gallery->ID;
			?>
				<tr class="iedit<?php if( ($counter%2) ) echo ' alternate'; $counter++ ?>">
				<?php foreach( $columns as $key => $column ): ?> 
				<?php switch( $key ){
					
					case 'gallery':?>
					<td class="column-<?php echo $key . $class?>" >
					<strong><a href="<?php echo get_bloginfo( 'url' ) . "?page_id=$pageid&imsgalid=$id{$nonce}"?>"><?php echo $gallery->post_title?></a></strong>	 
					</td>
					<?php break;	
					
					case 'galleryid':?>
					<td class="column-<?php echo $key . $class?>" > <?php echo get_post_meta( $id, '_ims_gallery_id', true ) ?></td>
					<?php break;
					
					case 'images':?>
					<td class="column-<?php echo $key . $class?>" > <?php echo $gallery->_ims_image_count ?></td>
					<?php break;
					
					case 'expire':?>
						<td class="column-<?php echo $key . $class?>" >
							<?php echo ( $gallery->post_expire != '0000-00-00 00:00:00' ) ? date_i18n( $date_format, strtotime( $gallery->post_expire ) ) : ''?>
						</td>
					<?php break;
					
					default:?>
					<td class="column-<?php echo $key . $class?>" >&nbsp;</td>	
											
				<?php }?>
				<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		
		<div class="tablenav"><?php $this->imstore_paging( $this->per_page, ims_gallery_count( ))?></div>
		
		</form>
		
	</div>
</div>

<?php 

/**
 * Get all galleries
 *
 * @param unit $perpage 
 * @since 1.2.0
 * return array
 */
function get_ims_galleries( $perpage ){
	global $wpdb, $user_ID; 
	
	$search = $wpdb->escape( $_GET['s'] );	
	$page	= ( empty( $_GET['p'] ) ) ? '1' : $wpdb->escape( $_GET['p'] );
	$limit	= ( $_GET['p'] ) ? ( ( $_GET['p'] - 1 ) * $perpage ) : 0;
	$srch	= ( $search )? " AND ( post_title LIKE '%$search%' OR post_excerpt LIKE '%$search%' ) " : '';
	
	$r = $wpdb->get_results(
		"SELECT ID, post_title, 
		post_status, post_date, post_expire
		FROM $wpdb->posts AS p $join
		WHERE post_type = 'ims_gallery' 
		AND post_status = 'publish'
		$datef $srch
		GROUP BY ID
		ORDER BY post_date DESC 
	 LIMIT $limit, $perpage"
	);
	
	if( empty( $r ) )
		return $r;
	
	foreach( $r as $post ){
		$custom_fields = get_post_custom( $post->ID );
		foreach ( $custom_fields as $key => $value ){
			if( is_serialized( $value[0] ) ) 
				$post->$key = unserialize( $value[0] );
			else  $post->$key = $value[0];
		}
		if( is_array( $post->_ims_customer ) ){
			if( ImStore::fast_in_array( $user_ID, $post->_ims_customer ) )
				$galleries[] = $post;
		}else{
			if( $user_ID == $post->_ims_customer )
				$galleries[] = $post;
		}
	}
	return (array)$galleries;
}


/**
 * Display/Return galleries count by status
 *
 * @since 0.5.0
 * return unit
 */
function ims_gallery_count( ){
	global $wpdb; 
	
	$count = $wpdb->get_var(
		"SELECT count(post_status) AS count 
		FROM $wpdb->posts
		WHERE post_type = 'ims_gallery' 
		AND post_status = 'publish'
		GROUP by post_status"
	);
		
	if( $s = $_GET['s']){
		$search	= $wpdb->escape( $s );
		$count = $wpdb->get_var(
			"SELECT COUNT(ID)
			FROM $wpdb->posts AS p 
			JOIN $wpdb->postmeta AS pm ON ( p.ID = pm.post_id )
			WHERE post_type = 'ims_gallery' 
			AND post_status = 'publish'
			AND ( post_title LIKE '%$search%' 
				 OR post_excerpt LIKE '%$search%' 
				 OR pm.meta_value LIKE '%$search%' 
			)
			GROUP BY ID "
		);
	}
	return $count;
}

?>