<?php

	/**
	 * Image Store - Admin Settings Fields
	 *
	 * @file settings-fields.php
	 * @package Image Store
	 * @author Hafid Trujillo
	 * @copyright 20010-2013
	 * @filesource wp-content/plugins/image-store/admin/settings/settings-fields.php
	 * @since 3.0.0
	 */
	 
	 if ( ! current_user_can( 'ims_change_settings' ) )
		die( );
	
	//page option
	$pages = ( array ) get_pages( );
	$templates = function_exists( 'get_page_templates' ) ? get_page_templates( ) : false;
	
	//general settings
	$settings['general'] = array( 
		'deletefiles' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Delete image files', 'ims' ),
			'desc' => __( 'Delete files from server, when deleting a gallery/images', 'ims' ),
		 ),
		'mediarss' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Media RSS feed', 'ims' ),
			'desc' => __( 'Add RSS feed the blog header for unsecured galleries. Useful for CoolIris/PicLens', 'ims' ),
		 ),
		'stylesheet' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Use CSS', 'ims' ),
			'desc' => __( 'Use the default Image Store look', 'ims' ),
		 ),
		'imswidget' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Widget', 'ims' ),
			'desc' => __( 'Enable the use of the Image Store Widget', 'ims' ),
		 ),
		'widgettools' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Tools Widget', 'ims' ),
			'desc' => __( 'Disable default store navigation and use a widget instead', 'ims' ),
		 ),
		'store' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Store features', 'ims' ),
			'desc' => __( 'Uncheck to use as a gallery manager only, not a store.', 'ims' ),
		 ),
		'photos' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Show "Photo" link', 'ims' ),
			'desc' => __( 'Uncheck to hide Photo link from the store navigation.', 'ims' ),
		 ),
		'slideshow' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Show "Slideshow" link', 'ims' ),
			'desc' => __( 'Uncheck to hide Slideshow link from the store navigation.', 'ims' ),
		 ),
		'favorites' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Show "Favorites" link', 'ims' ),
			'desc' => __( 'Uncheck to hide Favorites link from the store navigation.', 'ims' ),
		 ),
		'ims_searchable' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Searchable galleries', 'ims' ),
			'desc' => __( 'Allow galleries to show in search results.', 'ims' ),
		 ),
		 'voting_like' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Activate voting', 'ims' ),
			'desc' => __( 'Enable voting/like feature.', 'ims' ),
		 ),
		 'columns' => array( 
			'type' => 'select',
			'label' => __( 'Columns', 'ims' ),
			'desc' => __( 'Change the column display.', 'ims' ),
			'opts' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', ),
		 ),
	 );
	 
	// Taxonomies
	$settings['taxonomies'] = array(
		'album_level' => array(
			'val' => 1, 
			'type' => 'checkbox',
			'label' => __( 'One level', 'ims' ),
			'desc' => __( 'Display only one level on albums, do not display child albums.', 'ims' ),
		 ),
		'album_slug' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Albums url base', 'ims' ),
			'desc' => __( 'Change the url base for albums.', 'ims' ),
		 ),
		'album_template' => array( 
			'type' => 'select',
			'label' => __( 'Album Template', 'ims' ),
			'desc' => __( 'Select the template that should be used to display albums.', 'ims' ),
			'opts' => array( 
			'0' => __( 'Default template', 'ims' ),
				'page.php' => __( 'Page template', 'ims' ),
			 ) + array_flip( ( array ) $templates ),
		 ),
		'album_per_page' => array( 
			'val' => '',
			'type' => 'number',
			'label' => __( 'Galleries per album', 'ims' ),
			'desc' => __( 'How many galleries to display per page on albums.', 'ims' ),
		 ),
		 'tag_slug' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Tag url base', 'ims' ),
			'desc' => __( 'Change the url base for tags.', 'ims' ),
		 ),
		 'tag_template' => array( 
			'type' => 'select',
			'label' => __( 'Tags Template', 'ims' ),
			'desc' => __( ' Select the template that should be used to display tags.', 'ims' ),
			'opts' => array( 
			'0' => __( 'Default template', 'ims' ),
				'page.php' => __( 'Page template', 'ims' ),
			 ) + array_flip( ( array ) $templates ),
		 ),
		'tag_per_page' => array( 
			'val' => '',
			'type' => 'number',
			'label' => __( 'Galleries per tag', 'ims' ),
			'desc' => __( 'How many galleries to display per page on tags.', 'ims' ),
		 ),
	);
	

	// Gallery
	$settings['gallery'] = array( 
		'galleriespath' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Galleries folder path', 'ims' ),
			'desc' => __( 'Default folder path for all the galleries images', 'ims' ),
		 ),
		'securegalleries' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Secure galleries', 'ims' ),
			'desc' => __( 'Secure all new galleries with a password by default.', 'ims' ),
		 ),
		'titleascaption' => array(
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Use title as caption', 'ims' ),
			'desc' => __( 'Use title field as caption instead of caption field.', 'ims' ),
		),
		'wplightbox' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Ligthbox for WP galleries', 'ims' ),
			'desc' => __( 'Use lightbox on WordPress Galleries.', 'ims' ),
		 ),
		'downloadorig' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Download Original', 'ims' ),
			'desc' => __( 'Allow users to download original image if image size selected is not available.', 'ims' ),
		 ),
		'attchlink' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Link image to attachment', 'ims' ),
			'desc' => __( 'Link image to image page instead of image file.', 'ims' ),
		 ),
		'ims_page_secure' => array( 
			'type' => 'select',
			'label' => __( 'Secure galleries page', 'ims' ),
			'desc' => __( ' Page used to display the gallery login form', 'ims' ),
		 ),
		'gallery_template' => array( 
			'type' => 'select',
			'label' => __( 'Gallery template', 'ims' ),
			'desc' => __( ' Select the template that should be used to display the galleries.', 'ims' ),
			'opts' => array( '0' => __( 'Default template', 'ims' ) ) + array_flip( ( array ) $templates ),
		 ),
		'gallery_slug' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Gallery url base', 'ims' ),
			'desc' => __( 'Change the url base for galleries.', 'ims' ),
		 ),
		'imgs_per_page' => array( 
			'val' => '',
			'type' => 'number',
			'label' => __( 'Images per page', 'ims' ),
			'desc' => __( 'How many images to display per page on the front-end.', 'ims' ),
		 ),
		'galleryexpire' => array( 
			'val' => '',
			'type' => 'number',
			'label' => __( 'Galleries expire after', 'ims' ),
			'desc' => __( 'In days, set to 0 to remove expiration default.', 'ims' ),
		 ),
		'imgsortorder' => array( 
			'type' => 'select',
			'label' => __( 'Sort images', 'ims' ),
			'opts' => array( 
				'menu_order' => __( 'Custom order', 'ims' ),
				'excerpt' => __( 'Caption', 'ims' ),
				'title' => __( 'Image title', 'ims' ),
				'date' => __( 'Image date', 'ims' ),
			 ),
		 ),
		'imgsortdirect' => array( 
			'type' => 'select',
			'label' => __( 'Sort direction', 'ims' ),
			'opts' => array( 
				'ASC' => __( 'Ascending', 'ims' ),
				'DESC' => __( 'Descending', 'ims' ),
			 ),
		 ),
	 );
	 
	foreach ( $pages as $page )
	$settings['gallery']['ims_page_secure']['opts'][$page->ID] = $page->post_title;
	
	
	// Image
	$settings['image'] = array( 
		'image_slug' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Image url base', 'ims' ),
			'desc' => __( 'Change the url base for images.', 'ims' ),
		 ),
		'preview_size_' => array( 
			'multi' => true,
			'label' => __( 'Image preview size( pixels )', 'ims' ),
			'desc' => __( 'After changing the size, images for old galleries need to be regenerated using scan folder.', 'ims' ),
			'opts' => array( 
				'w' => array( 
					'val' => '',
					'type' => 'number',
					'label' => __( 'Max Width', 'ims' ),
				 ),
				'h' => array( 
					'val' => '',
					'type' => 'number',
					'label' => __( 'Max Height', 'ims' ),
				 ),
				'q' => array( 
					'val' => '',
					'label' => __( 'Quality', 'ims' ),
					'type' => 'number',
					'desc' => '( 1-100 )',
				 ),
			 ),
		 ),
		'watermark' => array( 
			'type' => 'radio',
			'label' => __( 'Watermark', 'ims' ),
			'opts' => array( 
				'0' => __( 'No watermark', 'ims' ),
				'1' => __( 'Use text as watermark', 'ims' ),
				'2' => __( 'Use image as watermark', 'ims' ),
			 ),
		 ),
		'watermark_' => array( 
			'multi' => true,
			'type' => 'text',
			'label' => __( 'Watermark options', 'ims' ),
			'opts' => array( 
				'text' => array( 
					'val' => '',
					'type' => 'text',
					'label' => __( 'Text', 'ims' ),
				 ),
				'color' => array( 
					'val' => '',
					'type' => 'text',
					'label' => __( 'Color', 'ims' ),
					'desc' => ' #Hex'
				 ),
				'size' => array( 
					'val' => '',
					'type' => 'number',
					'label' => __( 'Font size', 'ims' )
				 ),
				'trans' => array( 
					'val' => '',
					'type' => 'text',
					'label' => __( 'Transparency', 'ims' ),
					'desc' => ' ( 0-100 )'
				 ),
			 ),
		 ),
		'watermarkurl' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Watermark URL', 'ims' ),
			'desc' => __( 'Path relative to wp-content or full URL to image, PNG with transparency recommended', 'ims' ),
		 ),
		 'watermarktile' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Tile Watermark', 'ims' ),
			'desc' => __( 'Tile image or text watermark, it will disable the watermark location option', 'ims' ),
		 ),
	 );
	
	//slideshow
	$settings['slideshow'] = array( 
		array( 
			'col' => true,
			'opts' => array( 
				'numThumbs' => array( 
					'type' => 'number',
					'label' => __( 'Number of thumbnails to show', 'ims' ),
				 ),
				'maxPagesToShow' => array( 
					'type' => 'number',
					'label' => __( 'Maximun number of pages', 'ims' ),
				 )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'transitionTime' => array( 
					'type' => 'number',
					'label' => __( 'Transition time', 'ims' ),
					'desc' => __( '1000 = 1 second', 'ims' ),
				 ),
				'slideshowSpeed' => array( 
					'type' => 'number',
					'label' => __( 'Slideshow speed', 'ims' ),
					'desc' => __( '1000 = 1 second', 'ims' ),
				 )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'playLinkText' => array( 
					'type' => 'text',
					'label' => __( 'Play link text', 'ims' ),
				 ),
				'pauseLinkTex' => array( 
					'type' => 'text',
					'label' => __( 'Pause link text', 'ims' ),
				 )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'nextLinkText' => array( 
					'type' => 'text',
					'label' => __( 'Next link text', 'ims' ),
				 ),
				'prevLinkText' => array( 
					'type' => 'text',
					'label' => __( 'Previous link text', 'ims' ),
				 )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'nextPageLinkText' => array( 
					'type' => 'text',
					'label' => __( 'Next page link text', 'ims' ),
				 ),
				'prevPageLinkText' => array( 
					'val' => '',
					'type' => 'text',
					'label' => __( 'Previous page link text', 'ims' ),
				 )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'closeLinkText' => array( 
					'type' => 'text',
					'label' => __( 'Close link text', 'ims' ),
				 ),
				'empty' => array( 'label' => '&nbsp;' )
			 ),
		 ),
		array( 
			'col' => true,
			'opts' => array( 
				'bottommenu' => array( 
					'val' => 1,
					'type' => 'checkbox',
					'label' => __( 'Menu at the bottom', 'ims' ),
				 ),
				'autoStart' => array( 
					'val' => 1,
					'type' => 'checkbox',
					'label' => __( 'Auto start?', 'ims' ),
				 )
			 ),
		 ),
	 );

	//payment
	$settings['payment'] = array( 
		'symbol' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Currency Symbol', 'ims' ),
		 ),
		'shipping' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Apply shipping', 'ims' ),
			'desc' => __( 'Uncheck to disable shopping cart shipping option.', 'ims' ),
		 ),
		'decimal' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Show decimal point', 'ims' ),
			'desc' => __( 'Uncheck to disable auto format prices with a decimal points.', 'ims' ),
		 ),
		'clocal' => array( 
			'type' => 'radio',
			'label' => __( 'Currency Symbol Location', 'ims' ),
			'opts' => array( 
				'1' => __( '&#036;100', 'ims' ),
				'2' => __( '&#036; 100', 'ims' ),
				'3' => __( '100&#036;', 'ims' ),
				'4' => __( '100 &#036;', 'ims' ),
			 ),
		 ),
		'currency' => array( 
			'type' => 'select',
			'label' => __( 'Default Currency', 'ims' ),
			'opts' => array( 
				'0' => __( 'Please Choose Default Currency', 'ims' ),
				'AUD' => __( 'Australian Dollar', 'ims' ),
				'BRL' => __( 'Brazilian Real', 'ims' ),
				'CAD' => __( 'Canadian Dollar', 'ims' ),
				'CZK' => __( 'Czech Koruna', 'ims' ),
				'DKK' => __( 'Danish Krone', 'ims' ),
				'EUR' => __( 'Euro', 'ims' ),
				'HKD' => __( 'Hong Kong Dollar', 'ims' ),
				'HUF' => __( 'Hungarian Forint', 'ims' ),
				'ILS' => __( 'Israeli New Sheqel', 'ims' ),
				'JPY' => __( 'Japanese Yen', 'ims' ),
				'MYR' => __( 'Malaysian Ringgit', 'ims' ),
				'MXN' => __( 'Mexican Peso', 'ims' ),
				'NOK' => __( 'Norwegian Krone', 'ims' ),
				'NZD' => __( 'New Zealand Dollar', 'ims' ),
				'PHP' => __( 'Philippine Peso', 'ims' ),
				'PLN' => __( 'Polish Zloty', 'ims' ),
				'GBP' => __( 'Pound Sterling', 'ims' ),
				'SGD' => __( 'Singapore Dollar', 'ims' ),
				'ZAR' => __( 'South African Rands', 'ims' ),
				'SEK' => __( 'Swedish Krona', 'ims' ),
				'CHF' => __( 'Swiss Franc', 'ims' ),
				'TWD' => __( 'Taiwan New Dollar', 'ims' ),
				'THB' => __( 'Thai Baht', 'ims' ),
				'TRY' => __( 'Turkish Lira', 'ims' ),
				'USD' => __( 'U.S. Dollar', 'ims' ),
			 ),
		 ),
		'gateway' => array( 
			'multi' => true,
			'label' => __( 'Payment gateway', 'ims' ),
			'opts' => array( 
				'enotification' => array( 'val' => 1, 'label' => __( 'Email notification only', 'ims' ), 'type' => 'checkbox' ),
				'paypalsand' => array( 'val' => 1, 'label' => __( 'Paypal Cart Sanbox (test)', 'ims' ), 'type' => 'checkbox' ),
				'paypalprod' => array( 'val' => 1, 'label' => __( 'Paypal Cart Production (live)', 'ims' ), 'type' => 'checkbox' ),
				'googlesand' => array( 'val' => 1, 'label' => __( 'Google Checkout Sandbox (test)', 'ims' ), 'type' => 'checkbox' ),
				'googleprod' => array( 'val' => 1, 'label' => __( 'Google Checkout Production (live)', 'ims' ), 'type' => 'checkbox' ),
				'wepaystage' => array( 'val' => 1, 'label' => __( 'WePay Stage (test)', 'ims' ), 'type' => 'checkbox' ),
				'wepayprod' => array( 'val' => 1, 'label' => __( 'WePay Production (live)', 'ims' ), 'type' => 'checkbox' ),
				'pagsegurosand' => array( 'val' => 1, 'label' => __( 'Pago Seguro Sandbox (test)', 'ims' ), 'type' => 'checkbox' ),
				'pagseguroprod' => array( 'val' => 1, 'label' => __( 'Pago Seguro Production (live)', 'ims' ), 'type' => 'checkbox' ),
				'sagepaydev' => array( 'val' => 1, 'label' => __( 'sagePay Sandbox (test)', 'ims' ), 'type' => 'checkbox' ),
				'sagepay' => array( 'val' => 1, 'label' => __( 'sagePay Production (live)', 'ims' ), 'type' => 'checkbox' ),
				'custom' => array( 'val' => 1, 'label' => __( 'Other', 'ims' ), 'type' => 'checkbox' ),
			 )
		 ),
	 );

	//checkout
	$settings['checkout'] = array( 
		'taxamount' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Tax', 'ims' ),
			'desc' => __( 'Set tax to zero (0) to remove tax calculation.', 'ims' ),
		 ),
		 'loginform' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Checkout login form', 'ims' ),
			'desc' => __( 'Add the login / register form at  the end of the receipt page.', 'ims' ),
		 ),
		  'downloadlinks' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Email only downloads', 'ims' ),
			'desc' => __( 'Display download link on email only checkout receipt even if payment has not been verified.', 'ims' ),
		 ),
		'taxtype' => array( 
			'type' => 'select',
			'label' => __( 'Tax calculation type', 'ims' ),
			'opts' => array( 
				'percent' => __( 'Percent', 'ims' ),
				'amount' => __( 'Amount', 'ims' ),
			 ),
		 ),
		'notifyemail' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Order Notification email(s)', 'ims' ),
		 ),
		'notifysubj' => array( 
			'val' => '',
			'type' => 'text',
			'label' => __( 'Order Notification subject', 'ims' ),
		 ),
		'notifymssg' => array( 
			'type' => 'textarea',
			'label' => __( 'Order Notification message', 'ims' ),
			'desc' => __( 'Tags: ', 'ims' ) . str_replace( '/', '', implode( ', ', ( array ) $this->opts['tags'] ) ),
		 ),
		'emailreceipt' => array( 
			'val' => 1,
			'type' => 'checkbox',
			'label' => __( 'Email receipt', 'ims' ),
			'desc' => __( 'Email purchase reciept to customers if they provide an email.', 'ims' ),
		 ),
		'receiptname' => array( 
			'val' => 'Image Store',
			'type' => 'text',
			'label' => __( 'Receipt From', 'ims' ),
			'desc' => __( 'Display name where the receipt comes from', 'ims' ),
		 ),
		'receiptemail' => array( 
			'val' => 'imstore@' . $_SERVER['HTTP_HOST'],
			'type' => 'text',
			'label' => __( 'Receipt From email', 'ims' ),
			'desc' => __( 'This is the email address that will be display to the user in the "From" field', 'ims' ),
		 ),
		'thankyoureceipt' => array( 
			'type' => 'textarea',
			'label' => __( 'Purchase Receipt', 'ims' ),
			'desc' => __( 'Thank you message and receipt information', 'ims' ),
		 ),
		'termsconds' => array( 
			'type' => 'textarea',
			'label' => __( 'Terms and Conditions', 'ims' ),
			'desc' => __( 'Shown below the shopping cart', 'ims' ),
		 ),
	 );
	
	//permissions
	$settings['permissions'] = array( 
		'userid' => array( 
			'type' => 'select',
			'label' => __( 'Select User', 'ims' ),
			'opts' => $this->get_users( ),
		 ),
	 );

	//reset
	$settings['reset'] = array( 
		
		'empty1' => array( 'type' => 'empty' ),
		
		'resetsettings' => array( 
			'type' => 'submit',
			'label' => __( 'Reset', 'ims' ),
			'val' => __( 'Reset all settings to defaults', 'ims' ),
		 ),
		
		'empty2' => array( 'type' => 'empty' ),
		
		'uninstall' => array( 
			'type' => 'uninstall',
			'label' => __( 'Uninstall', 'ims' ),
			'val' => __( 'Uninstall Image Store', 'ims' ),
			'desc' => __( '<p><strong>UNINSTALL IMAGE STORE WARNING.</strong></p>
					 <p>Once uninstalled,this cannot be undone.<strong> You should backup your database </strong> 
					 and image files before doing this, Just in case. If you are not sure what are your doing,please don not do anything</p>', 'ims' ),
		 ),
	
	 );
	
	$settings = apply_filters( 'ims_setting_fields', $settings );
