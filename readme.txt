=== Image Store ===
Contributors: Hafid R. Trujillo, hax
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8SJEQXK5NK4ES
Tags: e-commerce,shop,store,photo,picture,image,galleries,web2.0,imstore,image-store,slideshow,gallery,sale,photographers,shop
Requires at least: 3.0.0
Tested up to: 3.0.1
Stable tag: 0.5.3

Image Store (ImStore) is a image gallery and store plugin for WordPress with a slideshow and paypal integration.

== Description ==

NOTE: BETA RELEASE (0.5.0) - (0.5.3)
This version shouldn't be install on live/production website untill is fully tested. please let me know of any issues on the
<a href="http://wordpress.org/tags/image-store?forum_id=10">wordpres forums posts</a> or
<a href="http://imstore.xparkmedia.com/">Plugin site</a> 

Image Store (ImStore) is a image gallery and store plugin for WordPress with a slideshow and paypal integration. This 
plugin was created because I saw the need of the worpress community to have a images store that didn't required the installation of multiple
plugins to get this accomplished or to go through a lot of settings. :@)

The plugin fully integrated with the wordpress database the only thing that is created is a post_expire column on the posts table.
so you will not find extra tables on your database ( Cool!.. at least for me I hate extra tables ).


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
* Disable Store: Use just as a gallery manager and not a store
* Folder import: add galleries by just uploading image through FTP
* Public Galleries: display your photos so that anybody can buy them.
* Hidden image url: so that users don't know where your images are store
* Secure Galleries: Secure clients photos so that they can only see them
* User Permissions: Give access to users to specific sections of the plugin
* Customer menu: Keep track of your galleries and customers
* Pugin uninstall: Remove all entries added by the plugin 


= To Come =
* Google checkout integration
* Add image size units ( in. cm. px.)
* Sales Dashboard: display monthly highlihts on sales
* Customer/User sync: allow user keep track of their downloads and galleries


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


= Shortcode Option =
v0.5.3 and older
[ims-gallery] will display recent images added from all galleries.
Defaults: [ims-gallery id="0" slideshow="0" caption="1" orderby="0" order="0" number="false" ]

Options:
$id: gallery id.
$slideshow:
	(1/true) display gallery as slideshow
	(0/false) display imaes only
$caption: (optional)
	(1/true) show caption
	(0/false) do not display caption
$orderby:
	(0/false) use gallery default option set on setting or gallery sort.
	(date) sort by image upload date.
	(title) sort by image title.
	(custom) sort by custom order.
	(caption) sort by caption.
$order:
	(0/false) use gallery default option set on setting or gallery sort.
	(ASC) ascending order 
	(DESC) descending order 
$number:
	(false) show all the images on gallery or 10 if no gallery id is provided.
	(any number) how many images to show. 
	

== Frequently Asked Questions ==

* How do I change the thumbnail side on the fromend for the photo page?
Before installing the plugin set the "Thumbnail size" setting under Wordpress admin > settings > media to the decired size.
After the plugin was installed set the "Thumbnail size" and reset the Image store settings to their defaults Imstore 
under Image Store > Settings > Reset

* How can I make donation to continue the plugin development?
With the plugin installed navigate to Image Store > settings and click on the donate button.


== Changelog ==

= 0.5.3 =
* Added: widget.
* Added: image rss.
* Added: gallery shortcode.
* Fix: permalink confict.
* Fix: js error with new slideshow options.
* Fix: admind displaying wrong expiration date.
* Removed: columns setting, not needed controled by css.


= 0.5.2 =
* CSS compression.
* CSS modifications.
* Added: Slideshow options
* Added: colorbox gallery feature.
* Fix: js errors on IE.
* Fix: watermark text location.
* Fix: expire gallery query/cron
* Fix: CSS AlphaImageLoader image url for (color box)IE.
* Text change: Inside USA to Local.
* Relocated colorbox styles and images.


= 0.5.1 =
* HTML clean up
* CSS modifications.
* Add image cache( htaccess ).
* Fix: permalinks admin/frontend.
* Fix: images displaying on the frontend with trash status.
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
* Upgrade from previous to 0.5.2 slideshow options will be addes or reset setting to update options. 
* Upgrade from 0.5.0 to 0.5.0 may change your permalinks. 


== Screenshots ==

1. Screenshot Menu
2. Screenshot New Gallery
3. Screenshot Pricing
4. Screenshot Sales / Screen Options
5. Screenshot Settings
6. Screenshot Galley Options
7. Screenshot Slideshow
8. Screenshot Pricelist
9. Screenshot Shopping Cart