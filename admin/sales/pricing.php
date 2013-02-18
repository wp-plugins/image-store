<?php

	/**
	 * Image Store - Admin Pricing 
	 *
	 * @file pricing.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource  wp-content/plugins/image-store/admin/sales/pricing.php
	 * @since 0.5.0
	 */
	 
	 if ( !current_user_can( 'ims_change_pricing' ) )
		die( );
	
	?>
		
    <ul class="ims-tabs add-menu-item-tabs">
        <?php
        foreach ( $this->tabs as $tabid => $tab ) 
            echo '<li class="tabs"><a href="#' . $tabid . '">' . $tab . '</a></li>';
        ?>
    </ul>
    
    <?php
    foreach ( $this->tabs as $tabid => $tabname) {
        echo '<div id="' . $tabid . '" class="ims-box pricing" >';
        do_action( "ims_pricing_{$tabid}_tab", $this);
        echo '</div>';
    }