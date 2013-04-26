=== Image Store ===
Contributors: Hax
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YM9GXCFBND89E
Tags: e-commerce,photo store,galleries,imstore,image-store,secure,watermark,slideshow,rate,wepay,shopping,cart,paypal,widget,prints,pagseguro,nextgen, alternative
Requires at least: 3.0.0
Tested up to: 3.5.1
Stable tag: 3.2.7
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Image Store (ImStore) is a photo gallery and store plugin for WordPress with Paypal and Google Checkout integration.

== Description ==
This plugin was created because there was a need in the WordPress community to have an images store that did not required the installation of multiple plugins. Enjoy!! and please support the plugin. :@) 

The plugin fully integrated with the WordPress database it only creates a post_expire column on the posts table. so you will not find extra tables on your database(Cool!.. at least for me, I hate extra tables). NextGEN Gallery alternative.

* Example http://xparkmedia.com/image-store/demos/
* Support http://xparkmedia.com/support/plugin/image-store
* Languages http://xparkmedia.com/plugins/image-store/#languages
* Shortcode guide http://xparkmedia.com/image-store/usage/

= Features =

* Payment notification.
* Paypal Cart integration.
* Google checkout integration
* WP edit image integration.
* Image RSS.
* Promotions.
* Gallery widget.
* Galley shortcode.
* Gallery expiration.
* Sort images feature.
* CSV sales download.
* CSV customer download.
* Sales menu: To keep track of you sales.
* Image upload: Via a zip-file(no zip-mod required).
* Dynamic generation of sepia and black & white images.
* Taxonomy (albums/tags): Group Galleries using custom taxonomy.
* Price lists: Create only list and apply across galleries.
* Gallery Comments: allow user to add comments to galleries.
* Watermark function: You can add a watermark image or text. 
* Image download: Allow user to download image after purchase.
* Disable Store: Use it just like a gallery manager and not a store.
* Folder import: add galleries by just uploading image through FTP.
* Public Galleries: display your photos so that anybody can buy them.
* Secure Galleries: Secure clients photos so that only they can see them.
* User Permissions: Give access to users to specific sections of the plugin.
* Customer menu: Keep track of your galleries and customers.
* Plugin uninstall: Remove all entries added by the plugin.

== Installation ==

* Download the latest version of the plugin to your computer.
* With an FTP program,access your site's server.
* Upload the plugin folder to the /wp-content/plugins folder.
* In the WordPress administration panels,click on plugins from the menu on the left side.
* You should see the "Image Store" plugin listed.
* To turn the plugin on,click "activate" on the bottom of the plugin name.
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

= Recommendations =

* Change your upload folder "Gallery folder path" for security purpose Image Store > settings > gallery settings.
* Before installing the plugin set "Thumbnail size" setting to the desired size Wordpress admin > settings > media.
* DON'T provide download option for print size images use this option only for pixel sizes. 

== Frequently Asked Questions ==

* http://xparkmedia.com/image-store/faq/
* http://checkout.google.com/support/sell/bin/answer.py?hl=en&answer=70647

== Changelog ==

= 3.2.7 =
* Fixed: PHP error on php 5.3.
* Fixed: jquery issue with some themes.
* Fixed: email notification issues.
* Fixed: TinyMCE issue on IE.

= 3.2.6 =
* Fixed: cart error
* Fixed: admin ajax columns.
* Fixed: saving some settings.
* Fixed: expired galleries not showing on admin area.
* Improved: custom mysql queries.
* Improved: Fixed gallery menu.
* Added: Initial support for WP 3.6

= 3.2.5 =
* Fixed: Email checkout issues.
* Fixed: Price list display issues.
* Fixed: Bug on password-protected images.
* Fixed: GoogleCheckout image download.
* Added: ligthbox keyboard navigation.
* Added: Pagseguro payment gateway (beta).

= 3.2.4 =
* Fixed: Email checkout issues.
* Fixed: Price list display issues.
* Fixed: Digital downloads and shipping issue.
* Fixed: Navigation display issue.
* Fixed: PHP notices.

= 3.2.3 =
* Fixed: Checkout issues.
* Fixed: Lightbox showing wrong image.
* Fixed: Deactivate voting issue.
* Fixed: Shipping issue.
* Fixed: Add price list issue.
* Fixed: Add image ajax issue.
* Fixed: PHP notices.

= 3.2.2 =
* Fixed: foder path issue
* Fixed: Permalink issue
* Fixed: Visits counter
* Fixed: A few php notices.
* Fixed: Sort issue.
* Added: Floating tools menu.

= 3.2.1 =
* Fixed: A few php notices / warnings.
* Fixed: Issues with JetPack plugin.
* Fixed: Missing finish information on sale details.
* Fixed: Watermark breaks image display on some servers
* Fixed: Metabox location breaks image upload.
* Change: Lightbox, colorbox is no longer supported.
* Change: Removed message subpath.
* Change: Improved customer experience.
* Improved: More mobile friendly.
* Added: Column display option.
* Added: Multi-language plugins support.
* Added: Groups (albums,tags) settings.
* Added: Voting feature.

= 3.2.0 =
* Fixed: Image URL encoding issue
* Fixed: Slash issue on setting.
* Fixed: Translation issue on checkout
* Fixed: Email checkout data issues.
* Fixed: Email checkout data issues.
* Fixed: Add to favorites issue.
* Fixed: Image download issue.
* Fixed: Sales missing information.
* Change: scan folder process to keep original image id.
* Added: multisite upgrade feature.

= 3.1.9 =
* Fixed: Fix blank cart buttons using shortcode
* Fixed: Send email to user using shortcode cart
* Fixed: Select field display under images
* Fixed: A few php notices.

= 3.1.8 =
* Fixed: Potential image display error.
* Added: additional WP 3.5 support.
* Added: Tag shortcode.
* Added: Tag column id.

= 3.1.7 =
* Code clean up
* Fixed: taxonomy template.
* Fixed: potential php errors.
* Fixed: sales customer csv files.
* Fixed: taxonomy template.
* Fixed: backward compatibility for WordPress 3.0.0
* Added: WePay Support.
* Added: Promotion limits
* Added: additional WP 3.5 support.
* Changed: Taxonomy template.
* Changed: Taxonomy content display.

= 3.1.6 =
* Fixed: Slideshow paging.
* Fixed: Some translation issues.
* Fixed: Some price formatting.
* Fixed: Issue uninstalling plugin.
* Fixed: Issue images w/not titles and PayPal.
* Added: WP 3.5 support.
* Added: Disable shipping option.
* Added: Additional plugin hooks.
* Added: Additional template hooks.
* Added: Additional currencies for eNotifications.
* Added: Polish and Vietnamese translations.

= 3.1.5 =
* Fixed: IE issues.
* Fixed: Empty gallery error.
* Fixed: Sales reports.
* Fixed: Image Edit.
* Fixed: Cart tax PayPal.
* Fixed: Receipt issues.
* Added: WP 3.4.2 support.

= 3.1.4 =
* Fixed: PHP notices.
* Fixed: Issues with image download.
* Fixed: Issues with PayPal IPN.
* Fixed: Issues with notifications.
* Fixed: Issues saving image sizes.
* Removed: do_action parameters by reference.

= 3.1.3 =
* Fixed: customer email.
* Fixed: Paypal IPN issues.
* Fixed: page rewrites.
* Fixed: cart page issues.
* Fixed: receipt display

= Full change log =
* http://xparkmedia.com/image-store/changelog/

== CREDITS ==

= Galleriffic =
Trent Foley(http://www.twospy.com/galleriffic/)

= Colorbox =
Jack Moore,Alex Gregory(http://colorpowered.com/colorbox/)

== Upgrade Notice ==
* Upgrade to 3.1.0 update image and taxonomy templates image-store/theme, deactivate/activate plugin.
* Upgrade to 3.0.3 please update permalink.
* Upgrade from 2.0.0 will change your permalinks. 
* Upgrade from 1.0.2 and previous price lists need to bee updated to use the image unit. 
* Upgrade from 0.5.2 and previous slideshow options will be added or reset setting to update options. 
* Upgrade from 0.5.0 to 0.5.0 may change your permalinks. 
* Upgrade to 3.2.1 Colorbox is no longer supported.

== Screenshots ==

1. Screenshot Menu
2. Screenshot New Gallery
3. Screenshot Pricing
4. Screenshot Sales / Screen Options
5. Screenshot Settings
7. Screenshot Photos / voting
7. Screenshot Slideshow
8. Screenshot Price list
9. Screenshot Shopping Cart
10. Screenshot Gallery embed
11. Users