<?php

	/**
	 * Image Store - Customer List
	 *
	 * @file customers.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/customers/customers.php
	 * @since 0.5.9
	 */
	 
	if ( !current_user_can( 'ims_manage_customers' ) )
		die( );
		
	$style = '';
	$nonce = '_wpnonce=' . wp_create_nonce( 'ims_update_customer' );
	$edit_userid = empty( $_GET['userid'] ) ? false : ( int ) $_GET['userid'];
	$userspage = isset( $_GET['userspage'] ) ? $_GET['userspage'] : false;
	$usersearch = isset( $_GET['usersearch'] ) ? $_GET['usersearch'] : false;
	$user_action = empty( $_GET['useraction'] ) ? false : $_GET['useraction'];
		
	$user_status = array(
		'active' => __( 'Active', 'ims' ),
		'inative' => __( 'Inative', 'ims' ),
	);
	
	if ( $this->status == 'inative' )
		$user_status['delete'] = __( 'Delete', 'ims' );
	
	if( $usersearch ) 
		$this->status = false;
	
	$user_status = apply_filters( 'ims_user_status', $user_status, $this->status );
	
	//search users
	if( class_exists( 'WP_User_Query' ) ){
		
		$args = array( 
			'number' => $this->per_page, 
			'role' => $this->customer_role,
			'offset' => ( $userspage * $this->per_page ),
			'search_columns' => array( 'email', 'nicename', 'login' ),
		) ;
		
		$args['meta_query'] = array( array(
				'key' => 'ims_status',
				'value' => $this->status,
				'compare' => '='
			)
		);
		
		if( $usersearch && is_email( $usersearch ) )
			$args['search'] =  $usersearch;
			
		else if ( $usersearch )
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key' => 'last_name',
					'value' => $usersearch,
					'compare' => 'LIKE'
				),
				array(
					'key' => 'first_name',
					'value' => $usersearch,
					'compare' => 'LIKE'
				),
			);
			
		$wp_user_search = new WP_User_Query( $args );
		
		if( isset( $_REQUEST['usersearch'] ) ) 
			$wp_user_search->search_term = $_REQUEST['usersearch'];
		
	} else  $wp_user_search = new WP_User_Search( $usersearch, $userspage, $this->customer_role );
	
	$page_links = false;
	if( !isset( $wp_user_search->search_term ) ){
		
		$start = ( $userspage - 1 ) * $this->per_page;
		$wp_user_search->search_term = $wp_user_search->get( 'search' );
		
		$page_links = paginate_links( array(
			'current' => $userspage,
			'base' => $this->pageurl . '%_%',
			'format' => '&userspage=%#%',
			'prev_text' => __( '&laquo;', 'ims'),
			'next_text' => __( '&raquo;', 'ims'),
			'total' => ceil( $wp_user_search->total_users / $this->per_page ),
		) );
	}
	
	do_action( 'ims_before_user_list', $user_action, $edit_userid );
	
	?>
    
    <div class="filter">
        <form id="list-filter" action="" method="get">
            <ul class="subsubsub">
                <?php $this->count_links( $user_status, array( 'type' => 'customer', 'active' => $this->status ) ) ?>
            </ul>
        </form>
    </div><!--.filter-->
    
    
    <form class="search-form" action="<?php echo admin_url( 'edit.php?' ) ?>" method="get">
        <p class="search-box">
            <label class="screen-reader-text" for="user-search-input"><?php _e('Search Users', 'ims'); ?>:</label>
            <input type="text" name="usersearch" id="user-search" value="<?php echo esc_attr( $wp_user_search->search_term ); ?>" />
            <input type="submit" value="<?php esc_attr_e('Search Users', 'ims'); ?>" class="button" />
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']) ?>" />
            <input type="hidden" name="post_type" value="<?php echo esc_attr($_GET['post_type']) ?>" />
            <?php wp_nonce_field( 'ims_update_customer' ) ?>
        </p>
    </form><!--.search-form-->
    
    
    <form id="posts-filter" action="<?php echo $this->pageurl ?>" method="get">
        <div class="tablenav">
    
            <select name="imsaction">
                <option selected="selected"><?php _e( 'Bulk Actions', 'ims' ) ?></option>
                <?php
                foreach ($user_status as $status => $label) {
                    if ( $this->status == $status )  continue;
                    echo '<option value="', esc_attr( $status ), '">', esc_html( $label ), '</option>';
                }
                ?>
            </select>
            <input type="submit" value="<?php esc_attr_e('Apply', 'ims'); ?>" name="doaction" class="button-secondary" /> |
    
            <a href="<?php echo IMSTORE_ADMIN_URL, "/customers/customers-csv.php?$nonce" ?>" class="button"><?php _e( 'Download CSV', 'ims' ) ?></a> 
            <a href="<?php echo $this->pageurl . "&amp;$nonce&amp;useraction=new" ?>" class="button"><?php _e( 'New Customer', 'ims' )  ?></a>
    
            <br class="clear" />
        </div><!--.tablenav-->
        
        
        <?php if ( $usersearch )
			echo  '<p><a href="' . $this->pageurl . '">' . __( '&larr; Back to all customers', 'ims' ) . '</a></p>' ?>	
          
                
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr class="thead">
				<?php print_column_headers( 'ims_gallery_page_ims-customers' ) ?>
				</tr>
			</thead>
            <tbody id="users" class="list:user user-list">
            <?php
				foreach ( $wp_user_search->get_results( ) as $userid ) {
					
					if( is_object( $userid ) )
						$userid = $userid->ID;
					
					$user = get_userdata( $userid );
					$style = ( ' alternate' == $style ) ? '' : ' alternate';
					
					$r = "<tr id='user-$userid' class='u-edit{$style}'>";
					
					foreach ( $this->columns as $columnid => $column_name ) {
						
						$hide = ( $this->in_array( $columnid, $this->hidden ) ) ? ' hidden' : '';
						
						switch ( $columnid ) {
							case 'cb':
								$r .= "<th scope='row' class='check-column'><input type='checkbox' name='customer[]' value='" . esc_attr( $userid ) . "' /></th>";
								break;
							case 'lastname':
								$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $user->last_name ) ? '&nbsp;' : $user->last_name ) . "</td>";
								break;
							case 'email':
								$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $user->user_email ) ? '&nbsp;' : $user->user_email ) . "</td>";
								break;
							case 'phone':
								$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $user->ims_phone ) ? '&nbsp;' : $user->ims_phone ) . "</td>";
								break;
							case 'city':
								$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $user->ims_city ) ? '&nbsp;' : $user->ims_city ) . "</td>";
								break;
							case 'state':
								$r .= "<td class='column-{$columnid}{$hide}'>" . ( empty( $user->ims_state ) ? '&nbsp;' : $user->ims_state ) . "</td>";
								break;
							case 'newsletter':
								$r .= "<td class='column-{$columnid}{$hide}'>" .
								( ( class_exists( 'MailPress' ) && $customer->_MailPress_sync_wordpress_user ) ? __( "Yes", 'ims' ) : __( "no", 'ims' ) ) . "</td>";
								break;
							case 'name':	
								
								$stat = ( $this->status == 'inative' ) ? 'active' : 'inative';
							
								$r .= "<td class='column-{$columnid}{$hide}'>$user->first_name<div class='row-actions'>";
								
								$r .= "<a href='$this->pageurl&amp;$nonce&amp;useraction=edit&amp;userid=$userid' title='" . 
								__( "Edit information", 'ims' ) . "'>" . __( "Edit", 'ims' ) . "</a>";
								
								if ( !$usersearch )
									$r .= " | <a href='$this->pageurl&amp;$nonce&amp;imsaction={$stat}&amp;customer=$userid' title='" . 
									$user_status[$stat] . "'>" . $user_status[$stat] . "</a>";
								
								if ( $this->status == 'inative' )
									$r .= " | <span class='delete'><a href='$this->pageurl&amp;$nonce&amp;imsaction=delete&amp;customer=$userid' title='" .
									$user_status['delete'] . "'>" . $user_status['delete'] . "</a></span>";
									
								$r .= "</div></td>";
								break;
							default:
								$r .= "<td class='column-{$columnid}{$hide}'>" . 
								apply_filters( 'manage_ims_customers_custom_column', '', $column_name, $user ) . "</td>";
						}
					}
					
					echo $r .= "</tr>";
				}
			?>
            </tbody>
         </table><!--.imstore-table-->
         
         
        <div class="tablenav">
            <div class="tablenav-pages">
            
			<?php 
			if ( isset( $wp_user_search->page ) ) 
				$wp_user_search->page_links( ); 
				
			else if( $page_links ) echo sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
				number_format_i18n( $start + 1 ),
				number_format_i18n( min( $userspage * $this->per_page, $wp_user_search->total_users ) ),
				'<span class="total-type-count">' . number_format_i18n( $wp_user_search->total_users ) . '</span>',
				$page_links
			) ?>
                
            </div><!--.tablenav-pages-->
        </div><!--.tablenav-pages-->
        
		<?php wp_nonce_field( 'ims_update_customer' ) ?>
        
        <input type="hidden" name="post_type" value="ims_gallery" />
        <input type="hidden" name="page" value="<?php echo esc_attr( $this->page ) ?>" />
        
    </form><!--.posts-filter-->