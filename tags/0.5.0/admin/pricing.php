<?php 

/**
 * Pricing page
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since version 0.5.0
*/

// Stop direct access of the file
if( preg_match( '#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'] ) ) 
	die( );

if( !current_user_can( 'ims_change_pricing' ) ) 
	die( );
	
	
//clear cancel post data
if( isset( $_POST['cancel'] ) )
	wp_redirect( $pagenowurl );	


//create new list	
if( isset( $_POST['newpricelist'] ) ){
	check_admin_referer( 'ims_newpricelist' );
	$errors = create_ims_list( );
}

//create new package
if( isset( $_POST['newpackage'] ) ){
	check_admin_referer( 'ims_newpackage' );
	$errors = create_ims_package( );
}

//update list
if( isset( $_POST['updatelist'] )){
	check_admin_referer( 'ims_pricelist' );
	$errors = update_ims_list( );
}

//update packages
if( isset( $_POST['updatepackage'] )){
	check_admin_referer( 'ims_newpackages' );
	$errors = update_ims_package( );
}

//update images
if( isset( $_POST['updateimglist'] ) ){
	check_admin_referer( 'ims_imagesizes' );
	$sizes = $this->array_filter_recursive( $_POST['sizes'] ); 
	update_option( 'ims_sizes', $sizes );
	wp_redirect( $pagenowurl . "&ms=5" );
}

//new/update promotion
if( isset( $_POST['newpromotion'] ) || isset( $_POST['updatepromotion'] ) ){
	check_admin_referer( 'ims_promotion' );
	$errors = add_ims_promotion( );
}

//bulk action
if( isset( $_POST['doaction'] ) ){
	check_admin_referer( 'ims_promotions' );
	switch( $_POST['action'] ){
		case 'delete':
			$errors = delete_ims_promotions( );
			break;
		default:
	}
}

//new/update promotion
if( isset( $_GET['delete'] ) ){
	check_admin_referer( 'ims_link_promo' );
	$_POST['promo'] = array( $_GET['delete'] );
	$errors = delete_ims_promotions( );
}


$x 			= 0; 
$sym 		= $this->opts['symbol']; 
$loc 		= $this->opts['clocal'];
$packages 	= get_ims_packages( );
$default_list = get_option( 'ims_pricelist' );
$nonce 		= '_wpnonce=' . wp_create_nonce( 'ims_link_promo' );
$format 	= array( '', "$sym%s", "$sym %s", "%s$sym", "%s $sym"); 
$columns 	= get_column_headers( 'image-store_page_ims-pricing' );
$hidden 	= implode( '|', get_hidden_columns('image-store_page_ims-pricing') ) ;
$promos 	= get_ims_promos( );

$type[1]	= __( 'Percent', ImStore::domain); 
$type[2] 	= __( 'Amount', ImStore::domain ); 
$type[3] 	= __( 'Free Shipping', ImStore::domain );

$message[1] = __( 'Promotion updated.', ImStore::domain );
$message[2] = __( 'Promotion deleted.', ImStore::domain );
$message[3] = __( 'New promotion added.', ImStore::domain );
$message[4] = __( 'A package was updated.', ImStore::domain);
$message[5] = __( 'Price list was updated.', ImStore::domain);
$message[6] = __( 'The new package was created.', ImStore::domain) ;
$message[7] = __( 'A new image size was created.', ImStore::domain);
$message[8] = __( 'Image size list was updated.', ImStore::domain );
$message[9] = __( 'New list was created successfully.', ImStore::domain);
$message[10] = sprintf( __( '%d promotions deleted.', ImStore::domain ), $_GET['c'] );

?>
<div class="wrap imstore">

	<?php screen_icon( 'pricing' )?>
	<h2><?php _e( 'Pricing', ImStore::domain )?></h2>

	<?php if ( isset( $errors ) && is_wp_error( $errors ) ) : ?>
	<div class="error"><?php
		foreach ( $errors->get_error_messages( ) as $err )
				echo "<p><strong>$err</strong></p>\n"; ?>
	</div>
	<?php endif; ?>

	<ul class="ims-tabs add-menu-item-tabs">
		<li class="tabs"><a href="#price-list"><?php _e('Price lists', ImStore::domain )?></a></li>
		<li class="tabs"><a href="#packages"><?php _e('Packages', ImStore::domain )?></a></li>
		<li class="tabs"><a href="#promotions"><?php _e('Promotions', ImStore::domain )?></a></li>
	</ul>
	
	<div id="poststuff" class="metabox-holder">
	<?php if( !empty($_GET['ms']) ){ ?>
		<div class="updated fade" id="message"><p><?php echo $message[$_GET['ms']] ?></p></div><?php }?>
	
	<!-- Price Listings -->

	<div id="price-list" class="ims-box" >
		<div class="inside-col2">
		
			<div class="postbox">
				<div class="handlediv" ><br /></div>
				<h3 class='hndle'><span><?php _e( 'New Price List', ImStore::domain ) ?></span></h3> 
				<div class="inside">
					<form method="post" action="<?php echo $pagenowurl ?>" >
						<?php wp_nonce_field( 'ims_newpricelist' ) ?>
						<p><label><?php _e( 'Name', ImStore::domain )?> <input name="list_name" type="text" class="inputlg" /></label> 
						<input name="newpricelist" type="submit" value="<?php _e( 'Add List', ImStore::domain ) ?>" class="button" /></p>
				 </form>
				</div>
			</div>
			
			<div class="postbox price-list-box">
				<div class="handlediv" ><br /></div>
				<h3 class='hndle'><span><?php _e( 'Price Lists', ImStore::domain ) ?></span></h3>
				<div class="inside">
					<?php foreach( (array)get_ims_pricelists( ) as $list ): 
						$meta = get_post_meta( $list->ID, '_ims_list_opts', true ) ?>
					<form method="post" id="ims-list-<?php echo $list->ID ?>" action="<?php echo $pagenowurl . '#price-list' ?>" >
					<?php wp_nonce_field( 'ims_pricelist' ) ?>
					<table class="ims-table price-list"> 
						<thead>
							<tr class="bar">
								<?php if( $list->ID == $default_list ) : ?> 
								<th class="default"><input name="listid" type="hidden" class="listid" value="<?php echo $list->ID ?>" /></th> 
								<?php else:?>
								<th class="trash"><a href="#">x</a><input name="listid" type="hidden" class="listid" value="<?php echo $list->ID ?>" /></th>
								<?php endif; ?>
								<th colspan="4" class="itemtop inactive"><?php echo $list->post_title ?><a href="#">[+]</a></th>
							</tr>
						</thead>
						<tbody class="content">
						<?php if( $sizes = get_post_meta( $list->ID, '_ims_sizes', true ) ) : unset( $sizes['random'] ); 
						foreach( $sizes as $size ): ?>
							<tr class="alternate size">
								<td class="x" scope="row" title="<?php _e( 'Delete', ImStore::domain )?>">x</td>
								<td>
								<?php
								if( $size['ID'] ){
									echo $size['name'] . ': '; $package_sizes = '';
									foreach( (array)get_post_meta( $size['ID'], '_ims_sizes', true ) as $package_size => $count )
										$package_sizes .= $package_size .'('.$count.'), '; 
									echo rtrim ( $package_sizes , ', ');
								}else{ 
									echo $size['name'];	
								}
								?>
								</td>
								<td width="15%" align="right">
								<?php 
									if( $size['ID'] ){
										printf( $format[$loc], get_post_meta( $size['ID'], '_ims_price', true ) );
									?><input type="hidden" name="sizes[<?php echo $x?>][ID]" value="<?php echo $size['ID'] ?>"/>
									<input type="hidden" name="sizes[<?php echo $x?>][name]" value="<?php echo $size['name'] ?>"/> <?php
									}else{
										printf( $format[$loc], $size['price'] );
									?><input type="hidden" name="sizes[<?php echo $x?>][name]" value="<?php echo $size['name'] ?>"/>
									<input type="hidden" name="sizes[<?php echo $x?>][price]" value="<?php echo $size['price'] ?>"/><?php
									}
								?>
								</td>
								<td>
									<input type="checkbox" name="sizes[<?php echo $x?>][download]" value="1" <?php checked( '1', $size['download'] )?> title="<?php _e( 'downloadable', ImStore::domain )?>" />
								</td>
								<td class="move" title="<?php _e( 'Sort', ImStore::domain )?>">&nbsp;</td>
							</tr> 
						<?php $x++; endforeach; endif ?>
							<tr class="filler">
								<td scope="row" colspan="5"><?php _e( 'Add options by dragging image sizes here', ImStore::domain ) ?></td>
							</tr>
						</tbody>
						<tfoot class="content">
							<tr>
								<td scope="row" colspan="5"><label><?php _e( 'Name', ImStore::domain ) ?>
									<input name="list_name" type="text" value="<?php echo $list->post_title ?>" class="inputmd" /></label>
								</td>
							</tr>
							<tr class="label">
								<td colspan="5" scope="row"><label><?php _e( 'BW', ImStore::domain )?>
									<input type="text" name="_ims_bw" value="<?php echo $meta['ims_bw'] ?>"/></label>							
									<label><?php _e('Sepia', ImStore::domain )?>
										<input type="text" name="_ims_sepia" value="<?php echo $meta['ims_sepia'] ?>" /></label>						
									<label><?php _e('Local Shipping', ImStore::domain )?>
										<input type="text" name="_ims_ship_local" value="<?php echo $meta['ims_ship_local'] ?>" /></label>					
									<label><?php _e('Internacional Shipping', ImStore::domain )?>
										<input type="text" name="_ims_ship_inter" value="<?php echo $meta['ims_ship_inter'] ?>" /></label>
								</td>
								</tr>
							<tr class="submit">
								<td scope="row" colspan="5" align="right">
									<!--input use to avoid caching and to update image order-->
									<input name="sizes[random]" type="hidden" value="<?php echo rand( 0 , 3000 ) ?>"/>
									<input name="updatelist" type="submit" value="<?php _e( 'Update', ImStore::domain ) ?>" class="button-primary" />
								</td>
							</tr>
						</tfoot>
					</table>
					</form>
					<?php endforeach?>
				</div>
			</div>
			
			<div class="postbox">
				<div class="handlediv"><br /></div>
				<h3 class='hndle'><span><?php _e( 'Packages', ImStore::domain )?></span></h3> 
				<div class="inside">
					<form method="post" action="<?php echo $pagenowurl . '#price-list'?>" >
						<table class="ims-table package-list"> 
							<tbody>
							<?php foreach( (array)$packages as $package ): ?>
							<tr class="package size alternate">
								<td class="x" scope="row" title="<?php _e( 'Delete', ImStore::domain )?>">x</td>
								<td><?php echo $package->post_title ?>:
								<?php $sizes = ''; foreach( (array)get_post_meta( $package->ID, '_ims_sizes', true ) as $size => $count )
									 $sizes .= $size .'('.$count.'), '; echo rtrim ( $sizes , ', ');?>
								</td>
								<td align="right">
									<?php printf( $format[$loc], get_post_meta( $package->ID, '_ims_price', true ) )?>
									<input name="sizes[<?php echo $x?>][ID]" type="hidden" value="<?php echo $package->ID ?>"/>
									<input name="sizes[<?php echo $x?>][name]" type="hidden" value="<?php echo $package->post_title ?>"/>
								</td>
								<td class="hidden">
									<input type="checkbox" name="sizes[<?php echo $x?>][download]" value="1" title="<?php _e( 'downloadable', ImStore::domain )?>" />
								</td>
								<td class="move" title="<?php _e( 'Move to list', ImStore::domain )?>">&nbsp;</td>
							</tr>
							<?php $x++; endforeach?>
							</tbody>
						</table>
					</form>
				</div>
			</div>
			
		</div>
		
		<div class="inside-col1">
			<div class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class='hndle'>
					<span><?php _e( 'Image Sizes', ImStore::domain )?></span>
					<a href="#" class="add-image-size"><?php _e( 'Add image size', ImStore::domain )?></a>
				</h3>
				<div class="inside">
					<form method="post" action="<?php echo $pagenowurl . '#price-list'?>" >
					<?php wp_nonce_field( 'ims_imagesizes' ) ?>
					<table class="ims-table sizes-list"> 
						<tbody>
						<tr class="alternate">
							<td scope="row">&nbsp;</td>
							<td><?php _e( 'Name', ImStore::domain )?></td>
							<td><?php _e( 'Price', ImStore::domain )?></td>
							<td class="col-hide">&nbsp;</td>
							<td>&nbsp;</td>
						</tr>
						<?php foreach( (array)get_option( 'ims_sizes' ) as $size ): $price = $size['price'] ?>
							<tr class="imgsize size alternate">
								<td scope="row" class="x" title="<?php _e( 'Delete', ImStore::domain )?>">x</td>
								<td><span class="hidden"><?php echo $size['name'] ?></span>
									<input name="sizes[<?php echo $x ?>][name]" type="text" value="<?php echo $size['name'] ?>" />
								</td>
								<td align="right">
									<span class="hidden"><?php printf( $format[$loc], $size['price'] ) ?></span>
									<input name="sizes[<?php echo $x ?>][price]" type="text" value="<?php echo $size['price'] ?>" />
								</td>
								<td class="col-hide"><input type="checkbox" name="sizes[<?php echo $x?>][download]" value="1" title="<?php _e( 'downloadable', ImStore::domain )?>" /></td>
								<td class="move" title="<?php _e( 'Move to list', ImStore::domain )?>">&nbsp;</td>
							</tr>
						<?php $x++; endforeach?>
						</tbody>
						<tfoot>
							<tr class="copyrow">
								<td scope="row">&nbsp;</td>
								<td><input value="<?php echo $x ?>" class="name" type="text" /></td>
								<td><input class="price" type="text" /></td>
								<td class="col-hide">&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
							<tr class="addrow">
								<td scope="row" colspan="3" align="right">
									<input name="updateimglist" type="submit" value="<?php _e( 'Update sizes', ImStore::domain )?>" class="button-primary" />
								</td>
								<td class="col-hide">&nbsp;</td>
								<td>&nbsp;</td>
							</tr>
						</tfoot>
					</table>
					</form>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div>
	
	<!-- Packages -->
	
	<div id="packages" class="ims-box" >
		<div class="inside-col2">
		
			<div class="postbox">
				<div class="handlediv"><br /></div>
				<h3 class='hndle'><span><?php _e( 'New Package', ImStore::domain ) ?></span></h3> 
				<div class="inside">
					<form method="post" action="<?php echo $pagenowurl . '#packages' ?>" >
						<?php wp_nonce_field( 'ims_newpackage' ) ?>
						<p><label><?php _e( 'Name', ImStore::domain )?> <input name="package_name" type="text" class="inputlg" /></label> 
						<input name="newpackage" type="submit" value="<?php _e( 'Add Package', ImStore::domain ) ?>" class="button" /></p>
					 </form>
				</div>
			</div>
			
			<div class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class='hndle'><span><?php _e( 'Packages', ImStore::domain )?></span></h3> 
				<div class="inside">
					<?php foreach( (array)$packages as $package ): $price = get_post_meta( $package->ID, '_ims_price', true );?>
					<form method="post" id="package-list-<?php echo $package->ID ?>" action="<?php echo $pagenowurl . '#packages'?>" >
					<?php wp_nonce_field( 'ims_newpackages' ) ?>
						<table class="ims-table package-list"> 
							<thead>
								<tr class="bar">
									<th class="trash">
										<a href="#">x</a><input name="packageid" type="hidden" class="packageid" value="<?php echo $package->ID ?>" />
									</th>
									<th colspan="3" class="itemtop inactive"><?php echo $package->post_title ?><a href="#">[+]</a></th>
								</tr>
							</thead>
							<tbody class="content">
							<?php $sizes = get_post_meta( $package->ID, '_ims_sizes', true ); if( $sizes ) : ; 
								foreach( $sizes as $size => $count ): if( is_numeric( $size ) ) continue; ?>
								<tr class="package size alternate">
									<td scope="row" class="x">x</td>
									<td><?php echo $size ?></td>
									<td align="right">
										<input name="sizes[<?php echo $x ?>][count]" type="text" value="<?php echo $count ?>" class="inputsm" />
										<input name="sizes[<?php echo $x ?>][name]" type="hidden" value="<?php echo $size ?>" class="inputsm" />
									</td>
									<td class="move" title="<?php _e( 'Sort', ImStore::domain )?>">&nbsp;</td>
								</tr>
							<?php $x++; endforeach; endif?>
								<tr class="filler">
									<td scope="row" colspan="3"><?php _e( 'Add options by dragging image sizes here', ImStore::domain ) ?></td>
								</tr>
							</tbody>
							<tfoot class="content">
								<tr class="inforow">
									<td scope="row">&nbsp;</td>
									<td>
										<label><?php _e( 'Name', ImStore::domain )?>
										<input name="packagename" type="text" value="<?php echo $package->post_title ?>" class="inputmd" /></label>
									</td>
									<td>
										<label><?php _e( 'Price', $ImStore->domain)?>
										<input type="text" name="packageprice" value="<?php echo $price ?>" class="inputsm" /></label>
									</td>
								</tr>
								<tr class="inforow submit">
									<td scope="row" colspan="3" align="right">
										<!--input use to avoid caching and to update image order-->
										<input name="sizes[random]" type="hidden" value="<?php echo rand( 0 , 3000 ) ?>"/>
										<input name="updatepackage" type="submit" value="<?php _e( 'Update', ImStore::domain ) ?>" class="button-primary" />
									</td>
								</tr>
							</tfoot>
						</table>
					</form>
					<?php endforeach?>
				</div>
			</div>
		</div>
		
		<div class="inside-col1">
			<div class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
				<h3 class='hndle'>
					<span><?php _e( 'Image Sizes', ImStore::domain )?></span>
					<a href="#" class="add-image-size"><?php _e( 'Add image size', ImStore::domain )?></a>
				</h3>
				<div class="inside">
					<form method="post" action="<?php echo $pagenowurl . '#packages'?>" >
					<?php wp_nonce_field( 'ims_imagesizes' ) ?>
					<table class="ims-table sizes-list"> 
						<tbody>
							<tr class="alternate">
								<td scope="row">&nbsp;</td>
								<td><?php _e( 'Name', ImStore::domain )?></td>
								<td><?php _e( 'Price', ImStore::domain )?></td>
								<td>&nbsp;</td>
							</tr>
						<?php foreach( (array)get_option( 'ims_sizes' ) as $size ): $price = $size['price'] ?>
							<tr class="imgsize size alternate">
								<td scope="row" class="x">x</td>
								<td><span class="hidden"><?php echo $size['name'] ?></span>
									<input name="sizes[<?php echo $x ?>][name]" type="text" value="<?php echo $size['name'] ?>" class="input" />
								</td>
								<td align="right">
									<input name="sizes[<?php echo $x ?>][count]" type="text" class="inputsm hidden" />
									<input name="sizes[<?php echo $x ?>][price]" type="text" value="<?php echo $size['price'] ?>" class="price" />
								</td>
								<td class="move" title="<?php _e( 'Move to list', ImStore::domain )?>">&nbsp;</td>
							</tr>
						<?php $x++; endforeach?>
						</tbody>
						<tfoot>
							<tr class="copyrow">
								<td scope="row">&nbsp;</td>
								<td><input value="<?php echo $x ?>" class="name" type="text" /></td>
								<td><input class="price" type="text" /></td>
								<td>&nbsp;</td>
							</tr>
							<tr class="addrow">
								<td scope="row" colspan="3" align="right">
									<input name="updateimglist" type="submit" value="<?php _e( 'Update sizes', ImStore::domain )?>" class="button-primary" />
								</td>
								<td>&nbsp;</td>
							</tr>
						</tfoot>
					</table>
					</form>
				</div>
			</div>
		</div>
		<div class="clear"></div>
	</div> 
	
	<!-- Promotions -->
	
	<div id="promotions" class="ims-box" >
	
		<?php if( isset( $_GET['newpromo'] ) || isset( $_GET['edit'] ) || isset( $_POST['newpromotion'] ) ):?>
		<div class="postbox">
			<div class="handlediv"><br /></div>
			<h3 class='hndle'><span><?php 
				if( $_GET['edit'] ) _e( 'Promotion Information', ImStore::domain ); 
				else _e( 'New Promotion', ImStore::domain ); ?>
			</span></h3> 
			<div class="inside<?php echo $css ?>">
				<form method="post" class="new-promo" action="<?php echo $pagenowurl . '#promotions' ?>" >
				<?php wp_nonce_field( 'ims_promotion' ) ?>
				<?php if( isset($_GET['edit']) ){
					foreach( $promos as $promo ){
						if( $promo->ID == $_GET['edit'] ){
							$_POST = (array)$promo;
							$_POST['starts'] = date_i18n( 'M j, Y', $promo->starts );
							$_POST['start_date'] = date_i18n( 'Y-m-d', $promo->starts );
							$_POST['expires'] = date_i18n( 'M j, Y', $promo->expires );
							$_POST['expiration_date'] = date_i18n( 'Y-m-d', $promo->expires );
						}
					}
				}?>
					<table class="ims-table"> 
						<tbody>
							<tr>
								<td colspan="7">
									<label><?php _e( 'Type', ImStore::domain )?>
									<select name="promo_type" id="promo_type">
										<?php foreach( $type as $key => $label ){ ?>
										<option value="<?php echo $key ?>"<?php selected( $_POST['promo_type'], $key )?>><?php echo $label?></option>
										<?php } ?>
									</select>
									</label>
								</td>
							</tr>
							<tr>
								<td>
									<label><?php _e( 'Name', ImStore::domain )?>
										<input name="promo_name" type="text" class="inputxl" value="<?php echo $_POST['promo_name'] ?>"/>
									</label>
								</td>
								<td>
									<label> <?php _e( 'Code', ImStore::domain )?>
										<input name="promo_code" type="text" class="inputxl" value="<?php echo $_POST['promo_code'] ?>" />
									</label>
								</td>
								<td>
									<label><?php _e( 'starts', ImStore::domain )?> 
										<input type="text" name="starts" id="starts" class="inputxl" value="<?php echo $_POST['starts'] ?>" />
									</label>
									<input type="hidden" name="start_date" id="start_date" value="<?php echo $_POST['start_date'] ?>" />
								</td>
								<td>
									<label><?php _e( 'Expire', ImStore::domain )?> 
										<input type="text" name="expires" id="expires" class="inputxl" value="<?php echo $_POST['expires'] ?>" />
									</label>
									<input type="hidden" name="expiration_date" id="expiration_date" value="<?php echo $_POST['expiration_date'] ?>" />
								</td>
								<!--<td>
									<label class="show-free"><?php _e( 'Shipping', ImStore::domain )?>
									<input type="radio" name="free-type" value="1" class="shipping" <?php checked( $_POST['shipping'], '1' )?>/></label>
								</td>
								<td>
									<label class="show-free"><?php _e( 'Downlaod', ImStore::domain )?>
									<input type="radio" name="free-type" value="2" class="promo-download" <?php checked( $_POST['download'], '1' )?>/></label>
								</td>-->
								<td>
									<label class="hide-free"> <?php _e( 'Discount', ImStore::domain )?>
										<input type="text" name="discount" class="inputxl" value="<?php echo $_POST['discount'] ?>" <?php echo ( $_POST['promo_type'] == 3 ) ? 'disabled="disabled"' : '' ?> /> 
									</label>

								</td>
							</tr>
							<tr>
								<td colspan="4">
									<?php _e( 'Conditions', ImStore::domain )?> 
									<select name="rules[property]">
										<option value="items"<?php selected( $_POST['rules']['property'], 'items' )?>><?php _e( 'Item quantity', ImStore::domain )?></option>
										<option value="total"<?php selected( $_POST['rules']['property'], 'total' )?>><?php _e( 'Total amount', ImStore::domain )?></option>
										<option value="subtotal"<?php selected( $_POST['rules']['property'], 'subtotal' )?>><?php _e( 'Subtotal amount', ImStore::domain )?></option>
										</select>
									<select name="rules[logic]">
										<option value="equal"<?php selected( $_POST['rules']['logic'], 'equal' )?>><?php _e( 'Is equal to', ImStore::domain )?></option>
										<option value="more"<?php selected( $_POST['rules']['logic'], 'more' )?>><?php _e( 'Is greater than', ImStore::domain )?></option>
										<option value="less"<?php selected( $_POST['rules']['logic'], 'less' )?>><?php _e( 'Is less than', ImStore::domain )?></option>
										</select>
									<input name="rules[value]" type="text" class="inpsm" value="<?php echo $_POST['rules']['value'] ?>"/>
								</td>
								<td colspan="3" align="right">
									<input type="submit" name="cancel" value="<?php _e( 'Cancel', $ImStore->domain)?>" class="button" />
									<?php if( isset($_GET['edit']) ):?>
									<input type="hidden" name="promotion_id" value="<?php echo $_GET['edit'] ?>"/>
									<input type="submit" name="updatepromotion" value="<?php _e( 'Update', $ImStore->domain)?>" class="button-primary" />
									<?php else:?>
									<input type="submit" name="newpromotion" value="<?php _e( 'Add promotion', $ImStore->domain)?>" class="button-primary" />
									<?php endif;?>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<?php endif; ?>
		
		
		<form method="post" action="<?php echo $pagenowurl . '#promotions' ?>" >
		<?php wp_nonce_field( 'ims_promotions' )?>
		
		<div class="tablenav">
			<div class="alignleft actions">
			<select name="action">
				<option value="" selected="selected"><?php _e( 'Bulk Actions', ImStore::domain )?></option>
				<option value="delete"><?php _e( 'Delete', ImStore::domain )?></option>
			</select>
			<input type="submit" value="<?php _e( 'Apply' ); ?>" name="doaction" class="button-secondary" /> |
			<a href="<?php echo $pagenowurl ."&amp;$nonce&amp;newpromo=1#promotions" ?>" class="button"><?php _e( 'New Promotion' ); ?></a>
			</div>
		</div>
		
		<table class="widefat post fixed imstore-table">
			<thead>
				<tr><?php print_column_headers( 'image-store_page_ims-pricing' )?></tr>
			</thead>
			<tbody>
				<?php $counter = 0; foreach( $promos as $promo ): ?>
				<tr id="item-<?php echo $id?>" class="iedit<?php if( !$counter%2 ){?> alternate<?php }?>">
				<?php foreach( $columns as $key => $column ): ?> 
				<?php if( $hidden ) $class = ( preg_match( "/($hidden)/i", $key ) )? ' hidden' : '';?>
				<?php switch( $key ){
						
						case 'cb':?>
						<th scope="row" class="column-<?php echo $key . $class?> check-column">
						<input type="checkbox" name="promo[]" value="<?php echo $promo->ID ?>" /> </th>
						<?php break;
					
						case 'name':?>
						<td class="column-<?php echo $key?>" > 
							<?php echo $promo->promo_name ?>
							<div class="row-actions">
								<span><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;edit=$promo->ID#promotions" ?>"><?php _e( "Edit", ImStore::domain )?></a></span> |
								<span class="delete"><a href="<?php echo $pagenowurl ."&amp;$nonce&amp;delete=$promo->ID#promotions"?>"><?php _e( "Delete", ImStore::domain )?></a></span>
							</div>
						</td>
						<?php break;
						
						case 'code':?>
						<td class="column-<?php echo $key?>" > <?php echo $promo->promo_code ?></td>
						<?php break;
						
						case 'starts':?>
						<td class="column-<?php echo $key?>" > <?php echo date_i18n('M j, Y', $promo->starts ) ?></td>
						<?php break;
						
						case 'expires':?>
						<td class="column-<?php echo $key?>" > <?php echo date_i18n('M j, Y', $promo->expires )?></td>
						<?php break;
						
						case 'type':?>
						<td class="column-<?php echo $key?>" > <?php echo $type[$promo->promo_type] ?></td>
						<?php break;
						
						case 'discount': ?>
						<td class="column-<?php echo $key?>" > <?php echo $promo->discount . $promo->items ?></td>
						<?php break;
						
						default:?>
      <td class="column-<?php echo $key?>" >&nbsp;</td>
      
					<?php }?>
				<?php endforeach?>
				</tr>
				<?php endforeach?>
			</tbody>
		</table>
		</form>
	</div>
	</div>
	
</div>

<?php 

/**
 * Get all packages
 *
 * @return array
 * @since 1.1.0
 */
function get_ims_packages( ){
	global $wpdb;
	return $wpdb->get_results( "SELECT DISTINCT ID, post_title FROM $wpdb->posts WHERE post_type = 'ims_package'" );
}


/**
 * Get all price list
 *
 * @return array
 * @since 1.1.0
 */
function get_ims_pricelists( ){
	global $wpdb;
	return $wpdb->get_results( "SELECT DISTINCT ID, post_title FROM $wpdb->posts WHERE post_type = 'ims_pricelist'" );
}


/**
 * Get promotions
 *
 * @return array
 * @since 1.1.0
 */
function get_ims_promos( ){
	global $wpdb;
	
	$r = $wpdb->get_results( 
		"SELECT ID, post_title AS promo_name, 
		UNIX_TIMESTAMP( post_expire ) AS expires,
		UNIX_TIMESTAMP( post_date ) AS starts 
		FROM $wpdb->posts
		WHERE post_type = 'ims_promo' " 
	);
	
	if( empty( $r) )
		return $r;
	
	foreach( $r as $promo ){
		foreach ( get_post_meta( $promo->ID, '_ims_promo_data', true ) as $akey => $aval ) 
   $promo->{$akey} = $aval;
		$promos[] = $promo;
	}
	return $promos;
	
}


/**
 * Create new list
 *
 * @return array on error
 * @since 1.1.0
 */
function create_ims_list( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['list_name'] ) ){
		$errors->add( 'empty_name', __( 'A name is required.', ImStore::domain ) );
		return $errors;
	}
		
	$price_list = array( 
			'post_status'	=> 'publish',
			'post_type' 	=> 'ims_pricelist', 
			'post_title' 	=> $_POST['list_name'],
	);
	
	$list_id = wp_insert_post( $price_list );
	
	if( empty( $list_id ) ){
		$errors->add( 'list_error', __( 'There was a problem creating the list.', ImStore::domain ) );
		return $errors;
	}

	wp_redirect( $pagenowurl . "&ms=9" );
}


/**
 * Create package
 *
 * @return array on error
 * @since 1.1.0
 */
function create_ims_package( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['package_name'] ) ){
		$errors->add( 'empty_name', __( 'A name is required.', ImStore::domain ) );
		return $errors;
	}

	$price_list = array( 
			'post_status'	=> 'publish',
			'post_type' 	=> 'ims_package', 
			'post_title' 	=> $_POST['package_name'],
	);
	
	$list_id = wp_insert_post( $price_list );
	
	if( empty( $list_id ) ){
		$errors->add( 'list_error', __( 'There was a problem creating the package.', ImStore::domain ) );
		return $errors;
	}

	wp_redirect( $pagenowurl . "&ms=6#packages" );
}


/**
 * Update list
 *
 * @return array on error
 * @since 1.1.0
 */
/**
 * Update list
 *
 * @return array on error
 * @since 1.1.0
 */
function update_ims_list( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error() ;
	
	if( empty( $_POST['list_name'] ) ){
		$errors->add( 'empty_name', __( 'A name is required.', ImStore::domain ) );
		return $errors;
	}

	// price list
	$options = array( 
		'ims_bw' 		=> $_POST['_ims_bw'],
		'ims_sepia' 	=> $_POST['_ims_sepia'],
		'ims_ship_local' => $_POST['_ims_ship_local'],
		'ims_ship_inter' => $_POST['_ims_ship_inter']
	);
	
	update_post_meta( $_POST['listid'], '_ims_list_opts', $options );
	update_post_meta( $_POST['listid'], '_ims_sizes', $_POST['sizes'] );
	wp_update_post( array( 'ID' => $_POST['listid'], 'post_title' => $_POST['list_name'] ) );
	
	wp_redirect( $pagenowurl . "&ms=8" );
}



/**
 * Update package
 *
 * @return array on error
 * @since 1.1.0
 */
function update_ims_package( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error( ) ;
	
	if( empty( $_POST['packagename'] ) ){
		$errors->add( 'empty_name', __( 'A name is required.', ImStore::domain ) );
		return $errors;
	}
	
	foreach( $_POST['sizes'] as $size )
		$sizes[$size['name']] = $size['count'];
	
	$id = intval( $_POST['packageid'] );
	
	update_post_meta( $id, '_ims_sizes', $sizes );
	update_post_meta( $id, '_ims_price', $_POST['packageprice'] );
	wp_update_post( array( 'ID' => $id, 'post_title' => $_POST['packagename'] ) );
															 
	wp_redirect( $pagenowurl . "&ms=4#packages" );

}


/**
 * add/update promotions
 *
 * @return void
 * @since version 0.5.0
 */
function add_ims_promotion( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error( ) ;
	
	if( empty( $_POST['promo_name'] ) )
		$errors->add( 'empty_name', __( 'A promotion name is required.', ImStore::domain ) );
		
	if( empty( $_POST['discount'] ) && $_POST['promo_type'] != 3 )
		$errors->add( 'discount', __( 'A discount is required', ImStore::domain ) );	
		
	if( !empty( $errors->errors ) )
		return $errors;
		
	$promotion = array(
			'post_status'	=> 'publish',
			'post_type' 	=> 'ims_promo', 
			'post_title' 	=> $_POST['promo_name'],
			'post_date'		=> $_POST['start_date'],	 
			'post_expire'	=> $_POST['expiration_date'],
	);
	
	if( isset($_POST['updatepromotion']) )
		$promotion['ID'] = intval( $_POST['promotion_id'] );
		
	$promo_id = wp_update_post( $promotion );
	
	if( empty( $promo_id ) ){
		$errors->add( 'promo_error', __( 'There was a problem creating the promotion.', ImStore::domain ) );
		return $errors;
	}

	$data = array(
		'promo_code' => $_POST['promo_code'],
		'promo_type' => $_POST['promo_type'],
		'free-type'	 => $_POST['free-type'],
		'discount' 	 => intval( $_POST['discount'] ),
		'items' 	 => $_POST['items'],
		'rules' 	 => $_POST['rules'],
	);
	
	update_post_meta( $promo_id , '_ims_promo_data', $data );
	update_post_meta( $promo_id , '_ims_promo_code', $_POST['promo_code'] );
	
	$a = ( $_POST['updatepromotion'] ) ? 1 : 3;
	wp_redirect( $pagenowurl . "&ms=$a#promotions" );
}


/**
 * delete promotions
 *
 * @return void
 * @since version 0.5.0
 */
function delete_ims_promotions( ){
	global $wpdb, $pagenowurl;
	
	$errors = new WP_Error( );
	
	if( empty( $_POST['promo'] ) ){
		$errors->add( 'nothing_checked', __( 'Please select a promo to be deleted.', ImStore::domain ) );
		return $errors;
	}
		
	$ids = $wpdb->escape( implode( ',', $wpdb->escape( $_POST['promo'] )) );
	$count = $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN ($ids)" );
	
	if( !empty( $deleted ) )
		$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($ids)" );
	
	$a = ( $count< 2 ) ? 2 : 10 ;
	wp_redirect( $pagenowurl . "&ms=$a&c=$count#promotions" );
}

?>