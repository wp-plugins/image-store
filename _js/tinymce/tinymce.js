/**
 * Image Store - imstore tinymce outter functions
 *
 * @file tinymce.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/tinymce/tinymce.js
 * @since 3.0.0
 */
 
var win = window.dialogArguments || opener || parent || top;

jQuery( window ).ready( function( $ ){
	var editor = win.tinyMCE.activeEditor;
	
	//reset popup window
	ims_close_window = function( ){
		wh = 324; fh = 295; 
		editor.windowManager.close( window );
	};
	
	//size content
	$( '#ims-galleries' ).show( ).css( {
		height:( $( window ).height( ) -30 )
	});
		
	//cancel/close window
	$( '#cancel' ).click( function( ){
		ims_close_window( );
	});
	
	//insert shortcode
	$( '#insert' ).click( function( ){
		
		title	= '';
		title 	+= ' id='+$( '#galid' ).val( )+' ';
		title 	+= ( $( '#caption' ).is( ':checked' ) ) ? 'caption=1 ' : '';
		title 	+= 'layout=' + $( "input[name='layout']:checked" ).val( ) + ' ';
		title 	+= ( $( '#order' ).val( ) == 0 ) ? '' : 'sort=' + $( '#order' ).val( ) + ' ';
		title 	+= ( $( '#orderby' ).val( ) == 0 ) ? '' : 'sortby=' + $( '#orderby' ).val( ) + ' ';
		title 	+= ( $( '#number' ).val( ) == '' ) ? '' : 'number=' + $( '#number' ).val( ) + ' ';
		title 	+= 'linkto=' + $( "input[name='linkto']:checked" ).val( ) + ' ';
		img 	= '<img src="'+imslocal.tinyurl+'i.gif" class="imsGallery mceItem" title="ims-gallery'+title+'" />';								
		
		editor.selection.setContent( img );
		ims_close_window( );
		return false;
	});
	
	//show search options
	$( '#internal-toggle' ).toggle( 
		function( ){
			$( '#search-panel' ).show( );
			win.resize_gal_window( 250 );
		},function( ){
			$( '#search-panel' ).hide( );
			win.resize_gal_window( -250 );
	});
	
	//get search results
	search_gals = function(  ){
		$.get( imslocal.imsajax, { 
				action		: 'searchgals',
				_wpnonce	: imslocal.nonceajax,
				q				: $( '#search-field' ).val( )
			},function( d ){ 
				$( "#search-results ul li" ).remove( );
				$( "#search-results ul" ).append( d );
				$( ".link-search-wrapper .waiting" ).hide( );
		});
	}; search_gals( );
	
	//lastCount = 40;
	$( '#search-field' ).keyup( function( ){
		s = $( this ).val( );
		if ( s.length > 2 ) {
			$( ".link-search-wrapper .waiting" ).show( );
			search_gals(  );
		}
	});
	
	//laod search id
	$( "#search-results li" ).live( 'click', function( ){
		$( '#galid' ).val( $( this ).find( 'span.id' ).html( ) );
	});
	
});
