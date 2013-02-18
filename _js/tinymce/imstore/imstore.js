/**
 * Image Store - imstore tinymce inner functions
 *
 * @file imstore.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/tinymce/imstore/imstore.js
 * @since 3.0.0
 */
 
(function( ) {
	tinymce.create( 'tinymce.plugins.imStore', {
		
		init : function(ed, url) {
			t = this;
			t.url = url;
			ed.addCommand('imStoreOpen', function( ) {
				ed.windowManager.open({
						//id : 'ims-gals',
						title: 'imStore',
						inline : 1,
						width : 450,
						height : 200,
						wpDialog : true
				}, {
						plugin_url : url // Plugin absolute URL
				});
			});	
			
			// Register example button
			ed.addButton( 'imstore', {
				cmd : 'imStoreOpen',
				image : url + '/imstore.png',
				title :  ed.getLang( 'imstore.add_gallery' )
			});
		}
		
	});

	// Register plugin
	tinymce.PluginManager.add( 'imstore', tinymce.plugins.imStore );
})( );