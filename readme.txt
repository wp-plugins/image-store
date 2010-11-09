=== Image Store ===
Contributors: Hax
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8SJEQXK5NK4ES
Tags: e-commerce,shop,store,photo,picture,image,galleries,imstore,image-store,slideshow,gallery,sale,photographers,shop,online
Requires at least: 3.0.0
Tested up to: 3.0.1
Stable tag: 1.2.2

Image Store (ImStore) is a image gallery and store/shop plugin for WordPress with a slideshow and paypal integration.

== Description ==
Image Store (ImStore) is a image gallery and store plugin for WordPress with a slideshow and paypal integration. This 
plugin was created because I saw the need of the worpress community to have a images store that didn't required the installation of multiple
plugins to get this accomplished or to go through a lot of settings. :@)

The plugin fully integrated with the WordPress database the only thing that is created is a post_expire column on the posts table.
so you will not find extra tables on your database ( Cool!.. at least for me I hate extra tables ).

* Example http://imstore.xparkmedia.com/image-store/
* Laguages http://imstore.xparkmedia.com/languages/
* Shortcode guide http://imstore.xparkmedia.com/usage/

= Features =

* Paypal Cart integration
* Payment notification
* WP edit image integration
* Image RSS
* Promotions
* Gallery widget
* Galley shortcode
* Gallery expiration
* Sort images feature
* CSV sales download
* CSV customer download
* Customer Mailpress integration.
* Sales menu: To keep track of you sales
* Image upload: Via a zip-file ( no zip-mod required )
* Dynamic generation of sepia and black & white images
* Watermark function: You can add a watermark image or text 
* Image download: Allow user to download image after purchase
* Disable Store: Use it just like a gallery manager and not a store
* Folder import: add galleries by just uploading image through FTP
* Public Galleries: display your photos so that anybody can buy them
* Hidden image url: so that users don't know where your images are store
* Secure Galleries: Secure clients photos so that only they can see them
* User Permissions: Give access to users to specific sections of the plugin
* Customer menu: Keep track of your galleries and customers
* Pugin uninstall: Remove all entries added by the plugin 

= To Come =
* Google checkout integration

== Installation ==

* Download the latest version of the plugin to your computer.
* With an FTP program, access your site's server.
* Upload the plugin folder to the /wp-content/plugins folder.
* In the WordPress administration panels, click on plugins from the menu on the left side.
* You should see the "Image Store" plugin listed.
* To turn the plugin on, click "activate" on the bottom of the plugin name.
* You should have now a new menu item called "Image Store".

= Tested on =

* MySQL 5.1.26 
* Apache 2.2.11
* Linux
* Explorer 8
* Safari 4.1
* Firefox 3.5
* Chrome 5.1
* Opera 9.6

= Recomendations =

* Change your upload folder "Gallery folder path" for security purpose Image Store > settings > gallery settings.
* Before installing the plugin set "Thumbnail size" setting to the decired size Wordpress admin > settings > media.
* DON'T provide download option for print size images use this option only for pixel sizes. 


== Frequently Asked Questions ==

* http://imstore.xparkmedia.com/troubleshooting/


== Changelog ==

= 1.2.2 =
* Fixed: new installation and capabilities problem.
* Fixed: settings reset after plugin update.

= 1.2.1 =
* Fixed: installation problem.

= 1.2.0 =
* Added: User gallery screen.
* Added: Option for admin to edit gallery id.
* Added: Status label on gallery list and edit screen.
* Added: Allow to move core files to sub directory.
* Added: feature to allow user keep track of their galleries.
* Fixed: Image creation date.
* Fixed: Add images to favorite not working.
* Fixed: Price list not seving image size unit.
* Fixed: Issue of not been able to uplaod small images.
* Changed: Flash image upload notification.
* Changed: Gallery permissions.

= 1.1.1 =
* Fixed: Sales CSV download permissions.
* Fixed: Total price format when using email notification only.
* Fixed: Serialize data showing on the image-size-dropdown menu when adding images to car.
* Admin: CSS modifications.
* Added: Image title on sale reports.

= 1.1.0 =
* Front-end: CSS modifications.
* Updates: Spanish translation.
* Admin: CSS modifications.
* Admin: HTML clean up.
* Added: Option not to expire galleries.
* Added: Feature to use gallery on the home page.
* Added: Feature use color box on wp galleries.
* Added: Image size units ( in. cm. px.)
* Added: Settings for the required fields on the checkout page.
* Added: Feature recreate images after image settings have been changed.
* Fixed: Image cache after browser's cache is cleared.
* Changed: create new galleries with pending status instead of publish.

= 1.0.2 =
* Fixed: Paypal IPN issues.
* Fixed: Disable image rss.
* Fixed: Incorrect paypal cart currency type.
* Fixed: "mini" image size showing instead of preview.
* Added: Orders by email notification only (disable paypal).

= 1.0.1 =
* Fixed: Translation issues.

= 1.0.0 =
* Improved dynamic image cache.
* Fixed: misspells.
* Fixed: save gallery settings.
* Fixed: double slash on permalinks.
* Removed "add to favorites" link from unsecure galleries.
* Added: Spanish translation
* Fixed: WP thumbnail preview conflict.
* Fixed: file not being deleted from server when image was deleted.

= 0.5.5 =
* Added: drag and drop image sort (admin).
* Security fix: image url.
* Fixed: Image edit didn't create new image when thumb only was selected.
* Fixed: php error on dynamic css file for IE colorbox support.


= 0.5.4 =
* Fixed: Flash image upload
* Fixed: Preview size settings not saving when updated.
* Fixed: Add new menu "Save into" not displaying galleries for selection.


= 0.5.3 =
* Added: widget.
* Added: image rss.
* Added: gallery shortcode.
* Fixed: permalink confict.
* Fixed: js error with new slideshow options.
* Fixed: admind displaying wrong expiration date.
* Removed: columns setting, not needed controled by css.


= 0.5.2 =
* CSS compression.
* CSS modifications.
* Added: Slideshow options
* Added: colorbox gallery feature.
* Fixed: js errors on IE.
* Fixed: watermark text location.
* Fixed: expire gallery query/cron
* Fixed: CSS AlphaImageLoader image url for (color box)IE.
* Text change: Inside USA to Local.
* Relocated colorbox styles and images.


= 0.5.1 =
* HTML clean up
* CSS modifications.
* Add image cache( htaccess ).
* Fixed: permalinks admin/frontend.
* Fixed: images displaying on the frontend with trash status.
* Remove: login link from unsecure galleries.
* Increase RAM memory for swfupload to process big images.

= 0.5.0 =
* Beta release

== CREDITS ==

= Galleriffic =
Trent Foley ( http://www.twospy.com/galleriffic/ )

= Colorbox =
Jack Moore, Alex Gregory ( http://colorpowered.com/colorbox/ )

= Uploadfy =
Ronnie Garcia, Benj Arriola, RonnieSan ( http://www.uploadify.com/ )


== Upgrade Notice ==
* Upgrade from 1.0.2 and previous price lists need to bee updated to use the image unit. 
* Upgrade from 0.5.2 and previous slideshow options will be added or reset setting to update options. 
* Upgrade from 0.5.0 to 0.5.0 may change your permalinks. 


== Screenshots ==

http://imstore.xparkmedia.com/