<?php
/**
 * Pricing page
 *
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2012
 * @since 0.5.0
 */
 
if (!current_user_can('ims_change_pricing'))
	die();

//clear cancel post data
if (isset($_POST['cancel']))
	wp_redirect($this->pageurl);

//create new pricelist
if (isset($_POST['newpricelist'])) {
	check_admin_referer('ims_new_pricelist');
	$errors = $this->create_pricelist();
}

//update list
if (isset($_POST['updatelist'])) {
	check_admin_referer('ims_pricelist');
	$errors = $this->update_pricelist();
}

//create new package
if (isset($_POST['newpackage'])) {
	check_admin_referer('ims_new_packages');
	$errors = $this->create_package();
}

//update packages
if (isset($_POST['updatepackage'])) {
	check_admin_referer('ims_update_packages');
	$errors = $this->update_package();
}

//new/update promotion
if (isset($_POST['promotion'])) {
	check_admin_referer('ims_promotion');
	$errors = $this->add_promotion();
}

//delete promotion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
	check_admin_referer('ims_link_promo');
	$errors = $this->delete_promotions();
}

//update images sizes
if (isset($_POST['updateimglist'])) {
	check_admin_referer('ims_imagesizes');
	$sizes = (array) $_POST['sizes'];
	update_option('ims_sizes', $sizes);
	wp_redirect($this->pageurl . "&ms=37");
}

//update color options
if (isset($_POST['updatecolors'])) {
	check_admin_referer('ims_colors');
	$colors = (array) $_POST['colors'];
	update_option('ims_color_options', $colors);
	wp_redirect($this->pageurl . "&ms=42");
}

//update color filters
if (isset($_POST['updatefilters'])) {
	check_admin_referer('ims_filters');
	$filters = (array) $_POST['filters'];
	foreach( $filters as $filter )
		$processed[$filter['code']] = $filter;
	update_option('ims_cache_time', date());
	update_option('ims_color_filters', $processed);
	wp_redirect($this->pageurl . "&ms=45");
}

//update shippping options
if (isset($_POST['updateshipping'])) {
	check_admin_referer('ims_shipping');
	$shipping = (array) $_POST['shipping'];
	update_option('ims_shipping_options', $shipping);
	wp_redirect($this->pageurl . "&ms=43");
}

//update finishes 
if (isset($_POST['updatefinishes'])) {
	check_admin_referer('ims_finishes');
	$finishes = (array)$_POST['finishes'];
	update_option('ims_print_finishes', $finishes);
	wp_redirect($this->pageurl . "&ms=44");
}

$tabs = apply_filters('ims_pricing_tabs', array(
	'price-list' => __('Price lists', $this->domain),
	'packages' => __('Packages', $this->domain),
	'promotions' => __('Promotions', $this->domain),
));

//display error message
if (isset($errors) && is_wp_error($errors))
	$this->error_message($errors);

add_action('ims_pricing_price-list_tab', 'ims_pricelist_tab', 1, 2);
add_action('ims_pricing_packages_tab', 'ims_packages_tab', 1, 2);
add_action('ims_pricing_promotions_tab', 'ims_promotions_tab', 1, 2);

add_meta_box('image_sizes', __('Image sizes', $this->domain), 'ims_image_sizes', 'ims_pricelists', 'side', 'default', array('tabid'=>'price-list'));
add_meta_box('image_sizes', __('Image sizes', $this->domain), 'ims_image_sizes', 'ims_packages', 'side', 'default', array('tabid'=>'packages'));

add_meta_box('color_options', __('Color options', $this->domain), 'ims_color_options', 'ims_pricelists', 'side');
add_meta_box('shipping_options', __('Shipping options', $this->domain), 'ims_shipping_options', 'ims_pricelists', 'side');

add_meta_box('price-list-new', __('New pricelist', $this->domain), 'ims_new_pricelist', 'ims_pricelists', 'normal');
add_meta_box('price-list-box', __('Price lists', $this->domain), 'ims_price_lists', 'ims_pricelists', 'normal');
add_meta_box('print-finishes-box', __('Print finishes', $this->domain), 'ims_print_finishes', 'ims_pricelists', 'normal');
add_meta_box('price-list-package', __('Packages', $this->domain), 'ims_price_lists_packages', 'ims_pricelists', 'normal');
add_meta_box('color_filters', __('Color filters', $this->domain), 'ims_color_filters', 'ims_pricelists', 'normal');

add_meta_box('new_package', __('New Package', $this->domain), 'ims_new_package', 'ims_packages', 'normal');
add_meta_box('packages-list', __('Packages', $this->domain), 'ims_package_list', 'ims_packages', 'normal');
add_meta_box('new_promo', __('Promotion', $this->domain), 'ims_new_promotion', 'ims_promotions', 'normal');

?>
<ul class="ims-tabs add-menu-item-tabs">
	<?php
	foreach ($tabs as $tabid => $tab) 
		echo '<li class="tabs"><a href="#' . $tabid . '">' . $tab . '</a></li>';
	?>
</ul>

<?php
foreach ($tabs as $tabid => $tabname) {
	echo '<div id="' . $tabid . '" class="ims-box pricing" >';
	do_action( "ims_pricing_{$tabid}_tab", &$this);
	echo '</div>';
}

/**
 * Function  to display 
 * pricelist metabox region
 *
 * @return void
 * @since 3.0.0
 */
function ims_pricelist_tab($ims) {
	echo '<div class="inside-col2">';
	do_meta_boxes('ims_pricelists', 'normal', $ims);
	echo '</div><div class="inside-col1">';
	do_meta_boxes('ims_pricelists', 'side', $ims);
	echo '</div><div class="clear"></div>';
}

/**
 * Function  to display 
 * packages metabox region
 *
 * @return void
 * @since 3.0.0
 */
function ims_packages_tab($ims) {
	echo '<div class="inside-col2">';
	do_meta_boxes('ims_packages', 'normal', $ims);
	echo '</div><div class="inside-col1">';
	do_meta_boxes('ims_packages', 'side', $ims);
	echo '</div><div class="clear"></div>';
}

/**
 * Function  to display 
 * new pricelist metabox region 
 *
 * @return void
 * @since 3.0.0
 */
function ims_new_pricelist($ims) {
	echo '<form method="post" action="#price-list" >
		<p><label>' . __('Name', $ims->domain) . ' <input type="text" name="pricelist_name" class="regular-text" /></label>
		<input type="submit" name="newpricelist" value="' . esc_attr__('Add List', $ims->domain) . '" class="button" /></p>';
	wp_nonce_field('ims_new_pricelist');
	echo '</form>';
}

/**
 * Function  to display 
 * new package metabox region
 *
 * @return void
 * @since 3.0.0
 */
function ims_new_package($ims) {
	echo '<form method="post" action="#packages" >
		<p><label>' . __('Name', $ims->domain) . ' <input type="text" name="package_name" class="regular-text" /></label>
		<input type="submit" name="newpackage" value="' . esc_attr__('Add Package', $ims->domain) . '" class="button" /></p>';
	wp_nonce_field('ims_new_packages');
	echo '</form>';
}

/**
 * Function  to display 
 * price lists metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_price_lists($ims) {
	$dlist = get_option('ims_pricelist');
	?>

	<p>
		<small>	
			<?php _e('Add options by dragging image sizes or packages into the desired list.', $ims->domain) ?>
	<?php _e('Check the box next to the price to make size downloadable, or image will have to be shipped.', $ims->domain) ?>
		</small>
	</p>

	<?php
	foreach ($ims->get_pricelists() as $key => $list):
		$meta = get_post_meta($list->ID, '_ims_list_opts', true);
		
			if( empty( $meta['colors'] ) ) 
				$meta['colors'] = array();
			
			if( empty( $meta['finishes'] ) ) 
				$meta['finishes'] = array();
		?>

		<form method="post" id="ims-list-<?php echo $list->ID ?>" action="<?php echo $ims->pageurl . "#price-list" ?>" >
			<table class="ims-table price-list">
				
				<thead>
					<tr class="bar">
						<?php if ($list->ID == $dlist): ?>
							<th class="default">
								<input name="listid" type="hidden" class="listid" value="<?php echo esc_attr($list->ID) ?> " /> 
							</th>
						<?php else: ?>
							<th class="trash">
								<a href="#">x</a>
								<input type="hidden" name="listid" class="listid" value="<?php echo esc_attr($list->ID) ?>" />
							</th>
						<?php endif ?>
						<th colspan="3" class="itemtop inactive name">
							<label>
								<span class="list-name"><?php echo $list->post_title ?></span>
								<input type="text" name="list_name" value="<?php echo esc_attr($list->post_title) ?>" class="regular-text" />
							</label>
						</th>
						<th colspan="2" class="itemtop plid"><?php echo 'ID: ' . $list->ID ?></th>
						<th colspan="2" class="itemtop toggle"><a href="#">[+]</a></th>
					</tr>
				</thead>

				<tbody class="sizes content">
					<?php if ($sizes = get_post_meta($list->ID, '_ims_sizes', true)): ?>
						<?php unset($sizes['random']); ?>
							<?php foreach ($sizes as $key => $size): ?>
								<?php if (empty($size['name'])) continue; ?>
							<tr class="size row alternate">
								<td class="move" title="<?php _e('Move', $ims->domain) ?>">&nbsp;</td>
								<td colspan="3" class="name" >
									<?php
									if (isset($size['ID'])) {
										echo $size['name'] . ': ';
										$package_sizes = '';
										foreach ((array) get_post_meta($size['ID'], '_ims_sizes', true) as $package_size => $count) {
											if (is_array($count))
												$package_sizes .= $package_size . ' ' . $count['unit'] . ' ( ' . $count['count'] . ' ), ';
											else
												$package_sizes .= $package_size . ' ( ' . $count . ' ), ';
										}
										echo rtrim($package_sizes, ', ');
									} else {
										echo $size['name'];
										if (isset($size['unit']) && isset($ims->units[$size['unit']]))
											echo ' ' . $ims->units[$size['unit']];
										if (isset($size['download']))
											echo " <em>" . __('Downloadable.', $ims->domain) . "</em>";
									}
									?>
								</td>
								<td class="price">
									<?php
									if (isset($size['ID'])) {
										echo $ims->format_price(get_post_meta($size['ID'], '_ims_price', true));
										?>
										<input type="hidden" name="sizes[<?php echo $key ?>][ID]" class="id" value="<?php echo esc_attr($size['ID']) ?>"/>
										<input type="hidden" name="sizes[<?php echo $key ?>][name]" class="name" value="<?php echo esc_attr($size['name']) ?>"/> <?php
									} else {
										echo $ims->format_price($size['price']);
										?>
										<input type="hidden" name="sizes[<?php echo $key ?>][name]" class="name"value="<?php echo esc_attr($size['name']) ?>"/>
										<input type="hidden" name="sizes[<?php echo $key ?>][price]" class="price" value="<?php echo esc_attr($size['price']) ?>"/><?php
									}
									?>
								</td>
								<td >
								<?php if( isset( $size['unit'] ) ): ?>
									<input type="hidden" class="unit" name="sizes[<?php echo $key ?>][unit]" value="<?php echo esc_attr($size['unit']) ?>" />
								<?php endif ?>
								</td>
								<td title="<?php _e('Check to make size downloadable', $ims->domain) ?>" class="download">
									<input type="checkbox" name="sizes[<?php echo $key ?>][download]" class="downloadable" value="1" <?php checked(true, isset($size['download'])) ?> />
								</td>
								<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
							</tr>
						<?php endforeach ?>
					<?php endif ?>
					<tr class="filler alternate"><td colspan="8"><?php _e('Add options by dragging image sizes here', $ims->domain) ?></td></tr>
				</tbody>

				<tbody class="colors content">
					<tr class="header"> <th colspan="8"><?php _e('Colors', $ims->domain) ?></td> </tr>
					<?php foreach ((array) $meta['colors'] as $key => $color): ?>
						<tr class="color row alternate"> 
							<td class="move" title="<?php _e('Move', $ims->domain) ?>">&nbsp;</td>
							<td colspan="3">
								<?php echo $color['name'] ?>
								<input type="text" name="colors[<?php echo $key ?>][name]" value="<?php echo $color['name'] ?>" class="name" />
							</td>
							<td>
								<?php echo $ims->format_price($color['price']) ?>
								<input type="text" name="colors[<?php echo $key ?>][price]" value="<?php echo $color['price'] ?>" class="price" />
							</td>
							<td colspan="2">
								<?php echo $color['code'] ?>
								<input type="text" name="colors[<?php echo $key ?>][code]" value="<?php echo $color['code'] ?>" class="code" />
							</td>
							<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
						</tr>
					<?php endforeach; ?>
					<tr class="filler alternate"><td colspan="8"><?php _e('Add options by dragging colors here', $ims->domain) ?></td></tr>
				</tbody><!--.colors-->


				<tbody class="finishes content">
					<tr class="header"> <th colspan="8"><?php _e('Finishes', $ims->domain) ?></td> </tr>
					<?php foreach ((array) $meta['finishes'] as $key => $finish): ?>
						<tr class="finish row alternate">
							<td class="move" title="<?php _e('Move', $ims->domain) ?>">&nbsp;</td>
							<td colspan="3" class="name">
								<span class="hidden"><?php echo $finish['name'] ?></span>
								<input type="text" name="finishes[<?php echo $key ?>][name]" value="<?php echo esc_attr($finish['name']) ?>" class="name" />
							</td>
							<td colspan="2" class="cost">
								<span class="hidden"><?php echo $ims->format_price($finish['price']) ?></span>
								<input type="text" name="finishes[<?php echo $key ?>][price]" value="<?php echo $finish['price'] ?>" class="price">
							</td>
							<td class="type">
								<span class="hidden"><?php echo $finish['type'] ?></span>
								<select name="finishes[<?php echo $key ?>][type]" class="type">
									<option value="amount" <?php selected('amount', $finish['type']) ?>><?php _e('Amount', $ims->domain) ?></option>
									<option value="percent" <?php selected('percent', $finish['type']) ?>><?php _e('Percent', $ims->domain) ?></option>
								</select>
							</td>
							<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
						</tr>
					<?php endforeach; ?>
					<tr class="filler alternate"><td colspan="8"><?php _e('Add options by dragging finishes here', $ims->domain) ?></td></tr>
				</tbody><!--finishes-->

				<tfoot class="content">
					<tr><td colspan="8" align="right">
						<input type="hidden" name="size[random]" value="<?php echo rand(0,3000)?>"/>
						<input type="submit" name="updatelist" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td></tr>
				</tfoot>

			</table>
		<?php wp_nonce_field('ims_pricelist') ?>
		</form><!--ims-list-#-->

		<?php
	endforeach;
}

/**
 * Function  to display 
 * finishes metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_print_finishes($ims) {
	?>
	<form method="post" action="<?php echo $ims->pageurl . "#price-list" ?>" >
		<table class="ims-table print-finishes">
			
			<tbody>
			<?php foreach ((array)get_option('ims_print_finishes') as $key => $finish) : ?>
					<tr class="finish row alternate">
						<td class="move" title="<?php _e('Move to list', $ims->domain) ?>">&nbsp;</td>
						<td colspan="3" class="name">
							<span class="hidden"><?php echo $finish['name'] ?></span>
							<input type="text" name="finishes[<?php echo $key ?>][name]" value="<?php echo esc_attr($finish['name']) ?>" class="name" />
						</td>
						<td colspan="2" class="price">
							<span class="hidden"><?php
							 if($finish['type'] == 'amount') 
							 	echo $ims->format_price($finish['price']);
							 else echo $finish['price'] . "%";
							 ?></span>
							<input type="text" name="finishes[<?php echo $key ?>][price]" value="<?php echo esc_attr($finish['price']) ?>" class="price" />
						</td>
						<td class="type">
							<span class="hidden"><?php echo $finish['type'] ?></span>
							<select name="finishes[<?php echo $key ?>][type]" class="type">
								<option value="amount" <?php selected('amount', $finish['type']) ?>><?php _e('Amount', $ims->domain) ?></option>
								<option value="percent" <?php selected('percent', $finish['type']) ?>><?php _e('Percent', $ims->domain) ?></option>
							</select>
						</td>
						<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
					</tr><!--.row-->
			<?php endforeach; ?>
			</tbody><!--.finish-->
			<tfoot>
			
				<tr class="copyrow" title="finishes">
					<td>&nbsp;</td>
					<td colspan="3"><input type="text" class="name" /></td>
					<td colspan="2"><input type="text" class="price" /></td>
					<td><select  class="type">
							<option value="amount" <?php selected('amount', $finish['type']) ?>><?php _e('Amount', $ims->domain) ?></option>
							<option value="percent" <?php selected('percent', $finish['type']) ?>><?php _e('Percent', $ims->domain) ?></option>
						</select></td>
					<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
				</tr><!--.copyrow-->
				
				<tr class="inforow"><td colspan="5"></td>&nbsp;</tr>
				<tr>
					<td>&nbsp;</td>
					<td><a class="button addfinish"><?php _e('Add finish', $ims->domain) ?></a></td>
					<td colspan="6" align="right">
						<input type="submit" name="updatefinishes" value="<?php esc_attr_e('Update', $ims->domain) ?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
		</table>
		<?php wp_nonce_field('ims_finishes') ?>
	</form>
	<?php
}

/**
 * Function  to display 
 * color options metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_color_options($ims){
	?>
	<form method="post" action="<?php echo $ims->pageurl."#price-list"?>" >
		<table class="ims-table color-options">
		
			<thead>
				<tr class="alternate">
					<td>&nbsp;</td>
					<td colspan="3" class="name"><?php _e( 'Name', $ims->domain )?></td>
					<td colspan="2" class="price"><?php _e( 'Price', $ims->domain )?></td>
					<td><?php _e( 'Code', $ims->domain )?></td>
					<td>&nbsp;</td>
				</tr>
			</thead>
			
			<tbody>
			<?php foreach ((array)get_option('ims_color_options') as $key => $color) : ?>
				<tr class="color row alternate">
					<td class="move" title="<?php _e('Move to list', $ims->domain) ?>">&nbsp;</td>
					<td colspan="3" class="name">
						<span class="hidden"><?php echo $color['name'] ?></span>
						<input type="text" name="colors[<?php echo $key ?>][name]" value="<?php echo esc_attr($color['name']) ?>"  class="name" />
					</td>
					<td colspan="2" class="price">
						<span class="hidden"><?php echo $ims->format_price($color['price']) ?></span>
						<input type="text" name="colors[<?php echo $key ?>][price]" value="<?php echo esc_attr($color['price']) ?>" class="price" />
					</td>
					<td class="code">
						<span class="hidden"><?php echo $color['code'] ?></span>
						<input type="text" name="colors[<?php echo $key ?>][code]" value="<?php echo esc_attr($color['code']) ?>" class="code" />
					</td>
					<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
				</tr><!--.row-->
			<?php endforeach; ?>
			</tbody>
			
			<tfoot>
				<tr class="copyrow" title="colors">
					<td>&nbsp;</td>
					<td colspan="3" class="name"><input type="text" class="name"/></td>
					<td colspan="2" class="price"><input type="text" class="price" /></td>
					<td class="code"><input type="text" class="code" /></td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr><!--.copyrow-->
				
				<tr class="addrow">
					<td colspan="4" align="left"><a href="#" class="addcoloropt"><?php _e( 'Add color option', $ims->domain )?></a></td>
					<td colspan="4" align="right">
						<input type="submit" name="updatecolors" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
		</table>
		<?php wp_nonce_field('ims_colors') ?>
	</form>
	<?php
}

function ims_color_filters($ims){
?>
	<form method="post" action="<?php echo $ims->pageurl."#price-list"?>" >
		<table class="ims-table color-filters">
			
			<thead>
				<tr class="alternate">
					<td class="name"><?php _e( 'Name', $ims->domain )?></td>
					<td class="code"><?php _e( 'Code', $ims->domain )?></td>
					<td class="grayscale"><?php _e( 'Grayscale', $ims->domain )?></td>
					<td class="contrast"><?php _e( 'Contrast', $ims->domain )?></td>
					<td class="brightness"><?php _e( 'Brightness', $ims->domain )?></td>
					<td class="colorize"><?php _e( 'Colorize(r,g,b,a)', $ims->domain )?></td>
					<td>&nbsp;</td>
				</tr>
			</thead>
			
			<tbody>
			<?php foreach ((array)get_option('ims_color_filters') as $code => $filter) : ?>
			<tr class="filters row alternate">
				<td class="name">
					<input type="text" name="filters[<?php echo $code ?>][name]" value="<?php echo esc_attr($filter['name']) ?>" class="name" />
				</td>
				<td class="code">
					<input type="text" name="filters[<?php echo $code ?>][code]" value="<?php echo esc_attr($filter['code']) ?>" class="code" />
				</td>
				<td class="grayscale">
					<input type="text" name="filters[<?php echo $code ?>][grayscale]" value="<?php echo esc_attr($filter['grayscale']) ?>" class="grayscale" />
				</td>
				<td class="contrast">
					<input type="text" name="filters[<?php echo $code ?>][contrast]" value="<?php echo esc_attr($filter['contrast']) ?>" class="contrast" />
				</td>
				<td class="brightness">
					<input type="text" name="filters[<?php echo $code ?>][brightness]" value="<?php echo esc_attr($filter['brightness']) ?>" class="brightness" />
				</td>
				<td class="colorize">
					<input type="text" name="filters[<?php echo $code ?>][colorize]" value="<?php echo esc_attr($filter['colorize']) ?>" class="colorize" />
				</td>
				<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
			</tr><!--.row-->
			<?php endforeach; ?>
			</tbody>
			
			<tfoot>
				<tr class="copyrow" title="filters">
					<td class="name"><input type="text" class="name"/></td>
					<td class="code"><input type="text" class="code" /></td>
					<td class="grayscale"><input type="text" class="grayscale" /></td>
					<td class="contrast"><input type="text" class="contrast" /></td>
					<td class="brightness"><input type="text" class="brightness" /></td>
					<td class="colorize"><input type="text" class="colorize" /></td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr><!--.copyrow-->
				
				<tr class="addrow">
					<td colspan="4" align="left"><a href="#" class="addcolorfilter"><?php _e( 'Add a filter', $ims->domain )?></a></td>
					<td colspan="4" align="right">
						<input type="submit" name="updatefilters" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
			
		</table>
		<?php wp_nonce_field('ims_filters') ?>
	</form>
<?php
}

/**
 * Function  to display 
 * Shipping options metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_shipping_options($ims){
	?>
	<form method="post" action="<?php echo $ims->pageurl."#price-list"?>" >
		<table class="ims-table shipping-options">
		
			<tbody>
			<?php foreach ((array)get_option('ims_shipping_options') as $key => $option) : ?>
				<tr class="shipping row alternate">
					<td colspan="3" class="name">
						<span class="hidden"><?php echo $option['name'] ?></span>
						<input type="text" name="shipping[<?php echo $key ?>][name]" value="<?php echo esc_attr($option['name']) ?>"  class="name" />
					</td>
					<td colspan="3" class="price">
						<span class="hidden"><?php echo $ims->format_price($option['price']) ?></span>
						<input type="text" name="shipping[<?php echo $key ?>][price]" value="<?php echo esc_attr($option['price']) ?>" class="price" />
					</td>
					<td class="x" title="<?php _e('Delete', $ims->domain) ?>">x</td>
				</tr><!--.row-->
			<?php endforeach; ?>
			</tbody>
			
			<tfoot>
				<tr class="copyrow" title="shipping">
					<td colspan="3" class="name"><input type="text" class="name"/></td>
					<td colspan="3" class="price"><input type="text" class="price" /></td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr><!--.copyrow-->
				
				<tr class="addrow">
					<td colspan="3" align="left"><a href="#" class="addshipping"><?php _e( 'Add shipping option', $ims->domain )?></a></td>
					<td colspan="4" align="right">
						<input type="submit" name="updateshipping" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
		</table>
		<?php wp_nonce_field('ims_shipping') ?>
	</form>
	<?php
}


/**
 * Function  to display 
 * image sizes metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_image_sizes($ims,$args) {
	$tabid = $args['args']['tabid'];
	?>
	<form method="post" action="<?php echo $ims->pageurl."#{$tabid}"?>" >
		<table class="ims-table sizes-list">
		 
			<thead>
				<tr class="alternate">
					<td>&nbsp;</td>
					<td colspan="3" class="name"><?php _e( 'Name', $ims->domain )?></td>
					<td class="price"><?php _e( 'Price', $ims->domain )?></td>
					<td><?php _e( 'Width', $ims->domain )?></td>
					<td><?php _e( 'Height', $ims->domain )?></td>
					<td><?php _e( 'Unit', $ims->domain )?></td>
					<td class="download">&nbsp;</td>
					<td>&nbsp;</td>
				</tr>
			</thead>
			
			<tbody>
				<?php 
				foreach((array)get_option( 'ims_sizes') as $key => $size ): 
					$sizedata = array( );
					$price = isset( $size['price'] ) ? $size['price'] : false;
					$sizedata = isset( $size['w'] ) ? array( $size['w'], $size['h'] ) : explode("x",strtolower($size['name']));
				?>
				<tr class="size row alternate">
					<td class="move" title="<?php _e( 'Move to list', $ims->domain )?>">&nbsp;</td>
					<td colspan="3" class="name"><span class="hidden"><?php echo $size['name']?></span>
					<span class="hidden"><?php echo $ims->units[$size['unit']]?></span>
					<input type="text" name="sizes[<?php echo $key ?>][name]" class="name" value="<?php echo esc_attr( $size['name'] )?>" />
					</td>
					
					<td class="price">
						<span class="hidden price"><?php echo $ims->format_price( $price ) ?></span>
						<input type="text" name="sizes[<?php echo $key ?>][count]" value="" class="count" />
						<input type="text" name="sizes[<?php echo $key ?>][price]" value="<?php echo esc_attr( $price )?>" class="price" />
					</td>
					
					<td class="d"><input type="text" name="sizes[<?php echo $key ?>][w]" value="<?php echo esc_attr( $sizedata[0] )?>" /></td>
					<td class="d"><input type="text" name="sizes[<?php echo $key ?>][h]" value="<?php echo esc_attr( $sizedata[1] )?>" /></td>
					
					<td><?php $ims->dropdown_units( "sizes[$key][unit]", $size['unit'] )?></td>
					
					<td title="<?php _e('Check to make size downloadable', $ims->domain) ?>" class="download">
						<input type="checkbox" name="sizes[<?php echo $key ?>][download]" class="downloadable" value="1"  />
					</td>
		
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr><!--.row-->
				<?php endforeach?>
			</tbody>
			
			<tfoot>
				<tr class="copyrow" title="sizes">
					<td>&nbsp;</td>
					<td colspan="3"><input type="text" class="name"/></td>
					<td><input type="text" class="price" /></td>
					<td><input type="text" class="width" /></td>
					<td><input type="text" class="height" /></td>
					<td><?php $ims->dropdown_units( '', '')?></td>
					<td class="download"></td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr>
				<tr>
					<td colspan="9"><small><?php _e( 'in:inches &bull; cm:centimeters &bull; px:pixels', $ims->domain )?></small></td>
				</tr>
				<tr class="addrow">
					<td colspan="5" align="left"><a href="#" class="addimagesize"><?php _e( 'Add image size', $ims->domain ) ?></a></td>
					<td colspan="4" align="right">
						<input type="submit" name="updateimglist" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
		</table>
		<?php wp_nonce_field( 'ims_imagesizes' )?>
	</form>
	<?php
}

/**
 * Function  to display 
 * pricelist pagackes metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_price_lists_packages($ims) {
	?>
	<form method="post" action="<?php echo $ims->pageurl."#packages"?>" >
		<table class="ims-table package-list"> 
			<tbody>
			<?php foreach( $ims->get_packages( ) as $key => $package ):?>
				<tr class="packages row alternate">
					<td class="move" title="<?php _e( 'Move to list', $ims->domain )?>">&nbsp;</td>
					<td colspan="3" class="name"><?php echo $package->post_title?>: 
					<?php $sizes = ''; 
						foreach((array) get_post_meta( $package->ID, '_ims_sizes', true) as $size => $count){
							if( is_array($count)) $sizes .= $size.' '.$count['unit'].' ( '.$count['count'].' ), ';
							else $sizes .= $size.' ( '.$count.' ), '; 
						} echo rtrim($sizes, ', ' );
					?>
					</td>
					<td align="right" class="price">
						<?php echo $ims->format_price( get_post_meta($package->ID, '_ims_price', true) )?>
						<input type="hidden" name="packages[][ID]" class="id" value="<?php echo esc_attr( $package->ID )?>"/>
						<input type="hidden" name="packages[][name]" class="name" value="<?php echo esc_attr( $package->post_title )?>"/>
					</td>
					<td class="hidden">&nbsp;</td>
					<td title="<?php _e('Check to make size downloadable', $ims->domain) ?>" class="download">
						<input type="checkbox" name="packages[<?php echo $key ?>][download]" class="downloadable" value="1"  />
					</td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr>
			<?php endforeach?>
			</tbody>
		</table>
	</form>
	<?php
}

/**
 * Function  to display 
 * new price list metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_package_list($ims,$tabid) {
	?>
	<p><small><?php _e( 'Add options by dragging image sizes into the desired package.', $ims->domain )?></small></p>
	
	<?php foreach( $ims->get_packages( ) as $key => $package ): ?>
	<?php $price = get_post_meta( $package->ID, '_ims_price', true ) ?>
	<form method="post" id="package-list-<?php echo $package->ID?>" action="<?php echo $ims->pageurl."#{$tabid}"?>" >
		<table class="ims-table package-list"> 
			<thead>
				<tr class="bar">
					<th class="trash">
						<a href="#">x</a>
						<input type="hidden" name="packageid" class="packageid" value="<?php echo esc_attr( $package->ID )?>" />
					</th>
					<th colspan="3" class="itemtop inactive">
						<input type="text" name="packagename" value="<?php echo esc_attr( $package->post_title )?>" class="regular-text" />
					</th>
					<th><label><?php _e( 'Price', $ims->domain )?>
						<input type="text" name="packageprice" value="<?php echo esc_attr( $price )?>" class="inputsm" /></label></th>
					<th>&nbsp;</th>
					<th class="itemtop toggle"><a href="#">[+]</a></th>
				</tr>
			</thead>
			
			<tbody class="packages content">
			<?php if( $sizes = get_post_meta( $package->ID, '_ims_sizes', true ) ) : ?>
			<?php foreach( $sizes as $size => $count ) : ?>
				<?php if( is_numeric($size) ) continue;  ?>
				
				<tr class="package row alternate">
					<td class="move">&nbsp;</td>
					<td colspan="3" class="pck-name"><?php echo "$size ".$count['unit']?></td>
					<td class="price">
					<?php $count_val = ( is_array( $count) ) ?  $count['count'] : $count ?>
					<input type="hidden" name="packages[<?php echo $size ?>][name]" class="name" value="<?php echo esc_attr( $size )?>" />
					<input type="text" name="packages[<?php echo $size ?>][count]" value="<?php echo esc_attr( $count_val )?>" class="count" title="<?php _e( 'Quantity', $ims->domain )?>" />
					</td>
					<td><input type="hidden" name="packages[<?php echo $size ?>][unit]" class="unit" value="<?php echo esc_attr( $count['unit'] )?>" /></td>
					<td class="x" title="<?php _e( 'Delete', $ims->domain )?>">x</td>
				</tr><!--.row-->
			<?php endforeach ?>
			<?php endif ?>
				<tr class="filler"><td colspan="7"><?php _e( 'Add options by dragging image sizes here', $ims->domain )?></td></tr>
			</tbody>
			<tfoot class="content">
				<tr>
					<td colspan="7" align="right">
						<input type="hidden" vname="packages[random]" alue="<?php echo rand(0,3000)?>"/>
						<input type="submit" name="updatepackage" value="<?php esc_attr_e( 'Update', $ims->domain )?>" class="button-primary" />
					</td>
				</tr>
			</tfoot>
		</table>
		<?php wp_nonce_field( 'ims_update_packages' )?>
	</form>
	<?php
	endforeach;
}

/**
 * Function  to display 
 * new promotion metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_new_promotion($ims) {
	 
	 if( $_GET['action'] != 'new' ) {
		$promo = get_post( $_GET['action'] );
		$date 	= strtotime( $promo->post_date );
		$expire	= strtotime( $promo->post_expire );
		
		$_POST  = get_post_meta( $_GET['action'] , '_ims_promo_data' , true );
		
		$_POST['startdate'] = date_i18n( 'Y-m-d', $date );
		$_POST['starts'] = date_i18n( $ims->dformat, $date );
		$_POST['expires'] = date_i18n( $ims->dformat, $expire );
		$_POST['expiration_date'] = date_i18n( 'Y-m-d', $expire );
		$_POST['promo_name'] = $promo->post_title;
	}
		
	 $defaults =  array( 
	 	'promo_name'=>false, 'promo_code'=>false, 'starts'=>false, 'startdate'=>false, 
	 	'expires'=>false, 'expiration_date'=>false, 'promo_type'=>false, 'discount'=>false
	 );
	 
	 $data = wp_parse_args($_POST, $defaults );
	 extract( $data );
	 ?>
	 
	 <form method="post" class="new-promo" action="#promotions" >
		<table class="ims-table">
			<tbody><tr>
				<td colspan="5">
					<label><?php _e( 'Type', $ims->domain )?>
						<select name="promo_type" id="promo_type">
							<?php foreach( $ims->promo_types as $key => $label ){?>
							<option value="<?php echo esc_attr( $key ) ?>"<?php selected( $promo_type, $key )?>><?php echo $label?></option>
							<?php }?>
						</select>
					</label>
				</td>
			</tr>
			<tr>
				<td>
					<label><?php _e( 'Name',$ims->domain )?> <input name="promo_name" type="text" class="regular-text" value="<?php echo esc_attr( $promo_name ) ?>"/></label>
				</td>
				<td>
					<label> <?php _e( 'Code',$ims->domain )?>	 <input name="promo_code" type="text" class="regular-text" value="<?php echo esc_attr( $promo_code ) ?>" /></label>
				</td>
				<td>
					<label><?php _e( 'Starts',$ims->domain )?> <input type="text" name="starts" id="starts" class="regular-text" value="<?php echo esc_attr( $starts )?>" /></label>
					<input type="hidden" name="start_date" id="start_date" value="<?php echo $startdate?>" />
				</td>
				<td>
					<label><?php _e( 'Expire',$ims->domain )?> <input type="text" name="expires" id="expires" class="regular-text" value="<?php echo esc_attr( $expires )?>" /></label>
					<input type="hidden" name="expiration_date" id="expiration_date" value="<?php echo esc_attr( $expiration_date ) ?>" />
				</td>
				<td>
					<label class="hide-free"> <?php _e( 'Discount', $ims->domain )?>
						<input type="text" name="discount" class="regular-text" value="<?php echo esc_attr( $discount ) ?>" <?php if( $promo_type == 3) echo ' disabled="disabled"' ?> /> 
					</label>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<?php 
					$logic = ( isset( $_POST['rules']['logic'] ) ) ? $_POST['rules']['logic'] : false ;
					$property = ( isset( $_POST['rules']['property'] ) ) ? $_POST['rules']['property'] : false;
					?>
					<?php _e( 'Conditions', $ims->domain )?> 
					<select name="rules[property]">
						<?php foreach( $ims->rules_property as $val => $label ) 
							echo '<option value="', esc_attr( $val ), '"', selected( $property, $val, false ), '>',$label, '</option>';
						?>
					</select>
					<select name="rules[logic]">
							<?php foreach( $ims->rules_logic as $val => $label ) 
							echo '<option value="', esc_attr( $val ), '"', selected( $logic, $val, false ), '>',$label, '</option>';
						?>
					</select>
					<input name="rules[value]" type="text" class="inpsm" value="<?php if( isset($_POST['rules']['value']) ) echo esc_attr( $_POST['rules']['value'] ) ?>"/>
				</td>
				<td width="25%" align="right">
					<?php $action = ( $_GET['action'] == 'new' ) ? __( 'Add promotion', $ims->domain ) : __( 'Update', $ims->domain ) ?>
					<input type="submit" name="cancel" value="<?php esc_attr_e( 'Cancel', $ims->domain )?>" class="button" />
					<input type="hidden" name="promotion_id" value="<?php if( $_GET['action'] != 'new' ) echo esc_attr($_GET['action'])?>"/>
					<input type="submit" name="promotion" value="<?php echo esc_attr( $action )?>" class="button-primary" />
				</td>
			</tr></tbody>
		</table>
		<?php wp_nonce_field( 'ims_promotion' )?>
	</form>
	
	<?php 
}



/**
 * Function  to display 
 * promotions metabox content 
 *
 * @return void
 * @since 3.1.0
 */
function ims_promotions_tab($ims) {
	
	if( isset($_GET['action']) ) 
		do_meta_boxes( 'ims_promotions', 'normal', $ims );
	
	$css			= ' alternate';
	$page		= ( isset($_GET['p'] ) ) ? $_GET['p'] : 1;
	$columns 	= (array)get_column_headers( 'ims_gallery_page_ims-pricing' );
	$hidden 	= (array)get_hidden_columns( 'ims_gallery_page_ims-pricing' );
	$promos 	= new WP_Query( array( 'post_type' => 'ims_promo', 'paged' => $page, 'posts_per_page' => $ims->per_page ) );
	$nonce 		= '_wpnonce='.wp_create_nonce( 'ims_link_promo' );
	
	//page links
	$start = ($page - 1) * $ims->per_page;
	$page_links = paginate_links( array(
		'base' => $ims->pageurl . '%_%#promotions',
		'format' => '&p=%#%',
		'prev_text' => __( '&laquo;' ),
		'next_text' => __( '&raquo;' ),
		'total' => $promos->max_num_pages,
		'current' => $page,
	));
	
	?>
	<form method="post" action="#promotions" >
		<div class="tablenav">
			<div class="alignleft actions">
			<select name="action">
				<option value="" selected="selected"><?php _e( 'Bulk Actions', $ims->domain )?></option>
				<option value="delete"><?php _e( 'Delete', $ims->domain )?></option>
			</select>
			<input type="submit" value="<?php esc_attr_e( 'Apply', $ims->domain );?>" name="doaction" class="button-secondary" /> |
			<a href="<?php echo $ims->pageurl ."&amp;$nonce&amp;action=new#promotions"?>" class="button"><?php _e( 'New Promotion', $ims->domain )?></a>
			</div><!--.actions-->
		</div><!--.tablenav-->
		
		<table class="widefat post fixed imstore-table">
			<thead><tr><?php print_column_headers( 'ims_gallery_page_ims-pricing' )?></tr></thead>
			<tbody>
			<?php foreach( $promos->posts as $promo){
				$css = ( $css == ' alternate') ? '' : ' alternate';
				$r = '<tr id="item-' . $promo->ID . '" class="iedit' . $css . '">';
				foreach( $columns as $column_id => $column_name ){
					$hide = ( $ims->in_array( $column_id, $hidden ) ) ? ' hidden':'' ;
					$meta = get_post_meta( $promo->ID , '_ims_promo_data', true );
					switch( $column_id ){
						case 'cb':
							$r .= '<th class="column-' . $column_id . ' check-column">';
							$r .= '<input type="checkbox" name="promo[]" value="' . esc_attr( $promo->ID ) . '" /> </th>';
							break;
						case 'name':
							$r .= '<td class="column-' . $column_id . '" > ' . $promo->post_title . '<div class="row-actions">' ;
							$r .= '<span><a href="' . $ims->pageurl . "&amp;$nonce&amp;action=$promo->ID#promotions" . '">' . __( "Edit", $ims->domain ) . '</a></span> |';
							$r .= '<span class="delete"><a href="' . $ims->pageurl . "&amp;$nonce&amp;delete=$promo->ID#promotions" . '"> ' . __( "Delete", $ims->domain ) . '</a></span>';
							$r .= '</div></td>';
							break;
						case 'code':
							$r .= '<td class="column-' . $column_id . $hide . '" > ' ;
							if( isset( $meta['promo_code'] ) ) $r .= $meta['promo_code'];
							$r .= '</td>' ;
							break;
						case 'starts':
							$r .= '<td class="column-' . $column_id . $hide .'" > ' . date_i18n( $ims->dformat, strtotime( $promo->post_date ) ) . '</td>' ;
							break;
						case 'expires':
							$r .= '<td class="column-' . $column_id . $hide . '" > ';
							if( isset( $promo->post_expire ) ) $r .= date_i18n( $ims->dformat, strtotime( $promo->post_expire ) );
							$r .= '</td>' ;
							break;
						case 'type':
							$r .= '<td class="column-' . $column_id . $hide . '" > ' ;
							if( isset( $meta['promo_type'] ) ) $r .= $ims->promo_types[$meta['promo_type'] ] ;
							$r .= '</td>' ;
							break;
						case 'discount':
							$r .= '<td class="column-' . $column_id . $hide . '" > ' ;
							if( isset( $meta['discount'] ) ) $r .= $meta['discount'];
							if( isset( $meta['items'] ) ) $r .= $meta['items'];
							$r .= '</td>' ;
							break;
						}
				}
				echo $r .= '</tr>';
			}?>
			</tbody>
		</table>
		
		<div class="tablenav">
			<?php if ( $page_links ) : ?>
			<div class="tablenav-pages">
			<?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s' ) . '</span>%s',
				number_format_i18n( $start + 1 ),
				number_format_i18n( min( $page * $ims->per_page, $promos->found_posts ) ),
				'<span class="total-type-count">' . number_format_i18n( $promos->found_posts ) . '</span>',
				$page_links
			); echo $page_links_text; ?></div><!--.tablenav-pages-->
			<?php endif ?>
		</div><!--.tablenav-->
	</form>
	<?php
}
