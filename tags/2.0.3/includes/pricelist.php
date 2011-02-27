<?php 

/**
*Pricelist page
*
*@package Image Store
*@author Hafid Trujillo
*@copyright 20010-2011
*@since 0.5.0 
*/

// Stop direct access of the file
if(preg_match('#'.basename(__FILE__).'#',$_SERVER['PHP_SELF'])) 
	die();

$sym	= $this->opts['symbol']; 
$loc 	= $this->opts['clocal'];
$format = array('',"$sym%s","$sym %s","%s$sym","%s $sym"); 
?>

<table class="ims-table">
		<thead>
			<tr>
				<th scope="col" colspan="2" class="ims-size"><?php _e('Image size',ImStore::domain)?></th>
				<th scope="col" class="blank">&nbsp;</th>
				<th scope="col" class="ims-price"><?php _e('Price',ImStore::domain)?></th>
				<th scope="col" class="ims-download"><?php _e('Download',ImStore::domain)?></th>
			</tr>
		</thead>
		<tbody>
			<?php if($sizes = $this->sizes){ 
				unset($sizes['random']); 
				$counter = 0; foreach($sizes as $size){
					$css = ($css == ' alternate')?'':' alternate'; 
					echo "<tr{$css}>\n";	
					if($size['ID']){
						echo '<td scope="row" colspan="2" class="ims-size"><span class="ims-size-name">'.$size['name'].": </span> "; $package_sizes = '';
						foreach((array)get_post_meta($size['ID'],'_ims_sizes',true) as $package_size => $count){
							if(is_array($count)) $package_sizes .= $package_size .' '. $count['unit'].'('.$count['count'].'),'; 
							else $package_sizes .= $package_size .'('.$count.'),'; 
						}
						echo rtrim($package_sizes,',').' </td>
						<td class="blank">&nbsp;</td>
						<td class="ims-price">'.sprintf($format[$loc],get_post_meta($size['ID'],'_ims_price',true)).'</td>';
					}else{
						echo '<td scope="row" colspan="2" class="ims-size"><span class="ims-size-name">'.$size['name'].' '.$size['unit'].'</span></td>
							 <td class="blank">&nbsp;</td>
							 <td class="ims-price">'.sprintf($format[$loc],$size['price']).'</td>';
					}
					$download = ($size['download'])?__('Included',ImStore::domain):'';
					echo '<td class="ims-download">'.$download.'</td>';
					echo "</tr>\n";	
				}
			}?>
		</tbody>
		<?php $meta = get_post_meta($this->pricelist_id,'_ims_list_opts',true);?>
		<tfoot>
			<tr class="divider-row"><td colspan="5">&nbsp;</td></tr>
			<tr class="subhead-row">
				<td scope="col" colspan="2" class="subhead"><?php _e('Shipping',ImStore::domain)?></td>
				<td scope="col" class="subhead">&nbsp;</td>
				<td scope="col" colspan="2" class="subhead">
				<?php if(!$this->opts['disablebw'] || !$this->opts['disablesepia']) _e('Color Options',ImStore::domain)?>&nbsp;
				</td>
			</tr>
			<tr>
				<td><?php _e('Local',ImStore::domain)?></td>
				<td><?php printf($format[$loc],$meta['ims_ship_local']);?></td>
				<td class="subhead">&nbsp;</td>
				<td scope="row"><?php if(!$this->opts['disablesepia']) _e('Sepia',ImStore::domain)?>&nbsp;</td>
				<td><?php if(!$this->opts['disablesepia']) printf($format[$loc],$meta['ims_sepia'])?>&nbsp;</td>
			</tr>
			<tr>
				<td class="ims-price"><?php _e('International',ImStore::domain)?></td>
				<td><?php printf($format[$loc],$meta['ims_ship_inter'])?></td>
				<td class="subhead">&nbsp;</td>
				<td scope="row" class="ims-size"><?php if(!$this->opts['disablebw']) _e('Black & White',ImStore::domain)?>&nbsp;</td>
				<td><?php if(!$this->opts['disablebw']) printf($format[$loc],$meta['ims_bw'])?>&nbsp;</td>
			</tr>
		</tfoot>
	</table>
