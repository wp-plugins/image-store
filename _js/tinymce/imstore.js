/**
 * Image Store - imstore tinymce plugin
 *
 * @file imstore.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/tinymce/imstore.js
 * @since 3.0.0
 */
 
( function( ) {
	tinymce.create( 'tinymce.plugins.imStore', {
		init : function( ed, url ) {
			
			t = this;
			t.url = url;
			t._createButtons( );
			
			ed.addCommand( 'imStoreOpen', function( ) {
				ed.windowManager.open( {
						inline : 1,
						width : 500,
						height : 295,
						file : 	url + '/tinymce.php?nonce=347234'
				}, {
						plugin_url : url
				});
			});	
			
			ed.addCommand( 'imsEditImage', function( ) {
				var el = ed.selection.getNode( ), vp, H, W, cls = ed.dom.getAttrib( el, 'class' );
	
				if ( cls.indexOf( 'imsGallery' ) != 0 || el.nodeName != 'IMG' )
					return;

				q = ed.dom.getAttrib( el, 'title' ).replace( /\s/g, '&' ) 
				
				ed.windowManager.open( {
					inline : 1,
					width : 500,
					height : 295,
					file : 	url + '/tinymce.php?nonce=347234&'+q
				}, {
					plugin_url : url
				});
				
			});
			
			// Register example button
			ed.addButton( 'imstore', {
				cmd : 'imStoreOpen',
				image : url + '/imstore.png'
			});
			
			ed.onBeforeSetContent.add( function( ed, o ) {
				o.content = t._do_gallery( o.content,t.url );
			});
			
			ed.onPostProcess.add( function( ed, o ) {
				if ( o.get )
					o.content = t._get_gallery( o.content );
			});
			
			// show editimage buttons
			ed.onMouseDown.add( function( ed, e ) {
				var target = e.target;
				if (  ed.dom.getAttrib( target, 'class' ).indexOf( 'imsGallery' ) == 0 ){
					 t._showButtons( target, 'ims_editbtns' );
				}else{
					t._hideButtons( );
				}
			}); 
			
			ed.onInit.add( function( ed ) {
				tinymce.dom.Event.add( ed.getWin( ), 'scroll', function( e ) {
					t._hideButtons( );
				});
				tinymce.dom.Event.add( ed.getBody( ), 'dragstart', function( e ) {
					t._hideButtons( );
				});
			});
			
		},
		
		_do_gallery : function( co,url ) {
			return co.replace( /\[ims-gallery([^\]]*)\]/g, function( a,b ){
				return '<img src="'+url+'/i.gif" class="imsGallery mceItem" title="ims-gallery'+tinymce.DOM.encode( b )+ '" />';
			});
		},
		
		_getAttr: function ( s, n ) {
			n = new RegExp( n + '=\"([^\"]+)\"', 'g' ).exec( s );
			return n ? tinymce.DOM.decode( n[1] ) : '';
		},
		
		_get_gallery : function( co ) {
			t._hideButtons( );
			return co.replace( /(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g, function( a,im ) {
				var cls = t._getAttr( im, 'class' );
				
				if ( cls.indexOf( 'imsGallery' ) != -1 )
					return '<p>['+tinymce.trim( t._getAttr( im, 'title' ) )+']</p>';
				return a;
			});
		},
		
		_createButtons : function( ) {
			var t = this, ed = tinyMCE.activeEditor, DOM = tinymce.DOM, editButton, dellButton;
			
			DOM.remove( 'ims_editbtns' );
			
			DOM.add( document.body, 'div', {
				id : 'ims_editbtns',
				style : 'display:none;'
			});
			
			editButton = DOM.add( 'ims_editbtns', 'img', {
				src : t.url+'/image.png',
				id : 'ims_editibtn',
				width : '24',
				height : '24',
			});
			
			tinymce.dom.Event.add( editButton, 'mousedown', function( e ) {
				var ed = tinyMCE.activeEditor;
				ed.windowManager.bookmark = ed.selection.getBookmark( 'simple' );
				ed.execCommand( "imsEditImage" );
			});
			
			dellButton = DOM.add( 'ims_editbtns', 'img', {
				src : t.url+'/delete.png',
				id : 'ims_deletebtn',
				width : '24',
				height : '24',
			});
			
			tinymce.dom.Event.add( dellButton, 'mousedown', function( e ) {
				var ed = tinyMCE.activeEditor, el = ed.selection.getNode( ), p;
				if ( el.nodeName == 'IMG' && ed.dom.getAttrib( el, 'class' ).indexOf( 'imsGallery' ) == 0 ) {
					if ( ( p = ed.dom.getParent( el, 'div' ) ) && ed.dom.hasClass( p, 'mceTemp' ) )
						ed.dom.remove( p );
					else if ( ( p = ed.dom.getParent( el, 'A' ) ) && p.childNodes.length == 1 )
						ed.dom.remove( p );
					else
						ed.dom.remove( el );
						
					t._hideButtons();
					ed.execCommand( 'mceRepaint' );
					return false;
				}
			});
			
		},
		
		_hideButtons: function( ){
			if ( document.getElementById( 'ims_editbtns' ) )
				tinymce.DOM.hide( 'ims_editbtns' );
		},
		
		_showButtons : function( n, id ) {
			var ed = tinyMCE.activeEditor, p1, p2, vp, DOM = tinymce.DOM, X, Y;

			vp = ed.dom.getViewPort( ed.getWin( ) );
			p1 = DOM.getPos( ed.getContentAreaContainer( ) );
			p2 = ed.dom.getPos( n );

			X = Math.max( p2.x - vp.x, 0 ) + p1.x;
			Y = Math.max( p2.y - vp.y, 0 ) + p1.y;
			
			DOM.setStyles( id, {
				'top' : Y+5+'px',
				'left' : X+5+'px',
				'display' : 'block'
			});

		}
		
	});

	// Register plugin
	tinymce.PluginManager.add( 'imstore', tinymce.plugins.imStore );
})( );

jQuery( window ).ready( function( $ ){
	wh = 324; fh = 295; 
	resize_gal_window = function( h ){
		wt = $( '.clearlooks2' ).position( ).top - ( h/2 );
		$( '.clearlooks2' ).css( { height: ( wh+h )+'px', top: wt + 'px' });
		$( '.clearlooks2 iframe' ).css( { height: ( fh+h )+'px' });
		wh = ( wh+h ); 
		fh = ( fh+h );
	}
});