/**
 * Image Store - Admin gallery script
 *
 * @file imstore.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/imstore.js
 * @since 0.5.0
 */
 
 // Start image loading

( function( $ ) {
	$( 'img[data-ims-src]' )
	.bind( 'scrollin', { distance: 20 }, function( ) {
		var img = this,
		$img = jQuery( img ),
		src = $img.attr( 'data-ims-src' );
		$img.unbind( 'scrollin' ) 
			.hide( )
			.removeAttr( 'data-ims-src' )
			.attr( 'data-ims-loaded', 'true' );
		img.src = src;
		$img.fadeIn( );
	} );
} )( jQuery );



// Wait for page to load
jQuery(function($) {
	
	var $mainbox = $('#ims-mainbox');
	var $message = $( '.ims-message' );
	var $toolbar = $( '#ims-mainbox .ims-toolbar' );
	
	// Fixed toolbar
	
	if( $toolbar[0] ){
		
		var float = 'ims-float';
		var elements = [ $toolbar, $message ];
		var toolstop, bodywidth, bodyheight;
		
		// Set height width
		
		setTimeout( function( ){ 
			toolstop = $toolbar.offset( ).top 
			bodyheight = $mainbox.height( );
		}, 80 );
		
		// Scroll window linstener
		
		$( window ).bind( 'scroll resize', function( ){
			wscroll = $( window ).scrollTop( );
			
			if( wscroll >= toolstop && (wscroll < (bodyheight + toolstop)) ){
				$message.hide();
				$.each(elements, function(){
					this.addClass( float ).css({ width: $('.imstore-nav').innerWidth( )-20});
				});
			}else {
				$.each(elements, function(){
					this.removeClass( float ).css({ width: 'auto' });
				});
			}
		});
	}
	
		
	// Scroll up

	$( 'body' ).delegate( 'li.ims-scroll-up', 'click', function( e ){
		
		e.preventDefault();
		
		$( "html,body" ).animate(
			{ scrollTop: ( $mainbox.offset( ).top -30 ) }
		);
	});
	
	
	// Prevent right click on ims images
	
	$(".ims-img img, #ims-slideshow, .ims-thumb")
	.bind( "contextmenu", function(e) {
		e.preventDefault();
	});
	
	
	// Display frontend messages
	
	display_user_message = function ( message, css ){
		$message
		.fadeOut( )
		.removeClass( 'ims-error' )
		.removeClass( 'ims-success' )
		.addClass( css )
		.html( message )
		.fadeIn( );
		
		if( $message.hasClass( 'ims-float' ) ) {
			setTimeout( function(){ 
				$message.fadeOut( ) 
			}, 1500 )
		}
	};
	
	
	// Image ids to string
	
	img_ids_to_string = function (  ){
		return  $( ".ims-innerbox input:checked" )
		.map( function( ){ 
			return $( this ).val( ); 
		} ).get( ).join( ',' );
	};
	
	
	
	// Widget sliders
	
	if( jQuery( ).xmslide ){ 
	
		$('.ims-filmstrip').xmslide({ paging: true });
		$('.ims-tools-gal').xmslide({ paging: true });
		
		setTimeout( function(){
			$('#ims-simple-slideshow').xmslide({ 
				boxsize:1, 
				paging: true, 
				autostart:true, 
			});
		}, 200 );
	}
	
	
	// Update favorite values
	
	update_favorites_values = function( data ){
		
		response = data.split( '|' );
	
		if( typeof response[2] != 'undefined' ){
			if( ! $( '.ims-menu-favorites span' )[0] )
				$( '.ims-menu-favorites' ).append( '<span>(' + response[2] + ')</span>' );
			 else $( '.ims-menu-favorites span' ).html( '(' + response[2]+ ')' );
		} 
		display_user_message( response[0], response[1] );
	};
	
	
	// Gallery lightbox
	
	if( jQuery( ).imstouch ){
		
		// WordPress galleries
		wpgalleries = ( imstore.wplightbox ) ? ",.gallery .gallery-icon a, a.colorbox, a.xmtouch" : '';
		
		$( '#ims-mainbox .ims-gallery:not(.nolightbox) .ims-img a.url,'  +
			'.ims-colorbox .ims-img a.url,' + '.ims-preview a.url' + wpgalleries 
		).imstouch( {
			onOpen:function( ){
				$message.hide( );
			}
		} );
	}
	
	
	// Single select
	
	$( '#ims-mainbox' ).delegate( ".ims-innerbox label:has('input')", 'mousedown', function( ){
		
		$parent = $(this).parents( '.hmedia' );
		
		if( $parent.hasClass( 'ims-selected' ) )
			$parent.removeClass( 'ims-selected' );
			
		else  $parent.addClass( 'ims-selected' );
	} );	
	
	
	// Check all
	
	$( 'body' ).delegate( '.ims-select-all a', 'click', function( ){
		$(".ims-innerbox label:has('input')")
		.parents('.hmedia').addClass( 'ims-selected' )
		
		$(".ims-innerbox [type='checkbox']")
		.attr( 'checked','checked' );
		return false;
	} );


	// Uncheck all
	
	$( 'body' ).delegate( '.ims-unselect-all a', 'click', function( ){
		$(".ims-innerbox .hmedia.ims-selected")
		.removeClass( 'ims-selected' )
		
		$( ".ims-innerbox [type='checkbox']" )
		.removeAttr( 'checked' );
		return false;
	} );	
	
	
	// Add to favorites
	
	$( 'body' ).delegate( '.add-to-favorite a', 'click', function( ){
		
		img_ids = img_ids_to_string( );
		
		$.get( imstore.imstoreurl+'/ajax.php', {
			
				imgids:img_ids,
				action:"favorites",
				_wpnonce:imstore.ajaxnonce
				
			},  function( data ){ 
				update_favorites_values( data );
			} );
		
		return false;
	} );
	
	
	// Add to favorites single
	
	$( 'body' ).delegate( '.add-to-favorite-single a', 'click', function( ){ 
		
		if(  $( '#ims-thumbs li.selected' )[0] )
			$ids =  $( '#ims-thumbs li.selected' ).attr( 'data-id' );
		else $ids = $( '.img-metadata input[name="imgs[]"]' ).val( );
		
		$.get( imstore.imstoreurl+'/ajax.php', {
			
				imgids: $ids,
				action:"favorites",
				_wpnonce:imstore.ajaxnonce
				
			}, function( data ){ 
				update_favorites_values( data );
			} );
		
		$( 'body' ).animate( { 
		 	scrollTop: $( '.ims-message' ).offset( ).top-50
		}, 'slow' );
		 
		return false;
	} );
	
	
	// Remove from favorites
	
	$( 'body' ).delegate('.remove-from-favorite a', 'click', function( ){
		
		img_ids = img_ids_to_string( );
		
		$(".ims-innerbox input:checked")
		.each( function( ){ 
			$( this ).parents( 'dt,li,figure' )
			.remove( ); 
		} ); 
		
		count = $( '.ims-innerbox .ims-img' ).length;		
		$.get( imstore.imstoreurl+'/ajax.php', {
				count:count,
				imgids:img_ids,
				action:"remove-favorites",
				_wpnonce:imstore.ajaxnonce
			}, function( data ){
							
			response = data.split('|');
			
			if( typeof( response[2] ) != 'undefined' )
				$('.ims-menu-favorites span').html( '(' + response[2] + ')' );
			display_user_message( response[0], response[1] );
		} );
		
		return false;
	} );
	
	
	// Image like
	
	$( 'body' ).delegate( ".img-metadata .rating", 'click', function( e ){
		
		if( ! imstore.is_logged_in && ! imstore.gallery_user ){
			
			selector = '.ims-not-allowed';
		
			if( ! $( selector )[0] )
				 $( 'body' ).append( '<div class="'+ selector.replace('.','') +'"></div>' );
	
			$( '.touch-close' ).trigger( 'click' );
				
			$( selector ).hide( ).bind('click', function(){ 
				$( this ).fadeOut( )
			}).html( '<aside>'+ 'The email has been sent' +'</aside>' )
			.fadeIn( ).delay(1500).fadeOut( );
		
			return;
		}
		
		button = $( this ).addClass( 'cliked' );
		ajaxaction = ( button.hasClass( 'ims-voted' ) ) ? 'remove-vote' :  'vote';
		
		$.get( imstore.imstoreurl + '/ajax.php', {
			
			action: ajaxaction,
			_wpnonce:imstore.ajaxnonce,
			gallery_user: imstore.gallery_user,
			imgid: $( this ).attr( 'data-id' )
		
		},  function( res ){ 
				
				if( ! res )
					return;
					
				button.fadeOut( )
				.html( '<em class="value">' + res + '</em>' )
				.fadeIn( );
				
				if( ajaxaction == 'vote' )
					button.addClass( 'ims-voted' )
				else button.removeClass( 'ims-voted' );
					
		} );
		
		return false;
		
	} );
	
	//Color preview
	
	$( '.ims-color' ).click( function( ){ 
		val		= $( this ).val( );
		color =  $( this ).is( ':checked') ? "&c=" + val : ''; 
		
		$( '.image-color input')
		.not( '.ims-color-' + val )
		.removeAttr( 'checked' );
		
		$( '.image-wrapper img' )
		.animate( { opacity:0 }, 400, function( ){
			img = new Image( );
			img.src = gallery.currentImage.image.src + color;
			$('.image-wrapper img')
			.replaceWith( img )
			.delay( 900/1.5 )
			.animate( { opacity:1 }, 700 );
		});
	});
	
	
	// Gallerific slideshow
	
	if( $('#ims-thumbs')[0] && imstore.galleriffic && jQuery( ).galleriffic ){
		
		var gallery = $( '#ims-thumbs' ).galleriffic( {
			preloadAhead:  			10,
			enableTopPager:  		true,
			enableBottomPager:	true,
			renderSSControls:		true,
			renderNavControls:	true,
			controlsContainerSel:	'#ims-player',
			captionContainerSel:	'#ims-caption',
			imageContainerSel:	'#ims-slideshow',
			
			numThumbs:  					parseInt( imstore.numThumbs ),
			maxPagesToShow:  	parseInt( imstore.maxPagesToShow ),
			playLinkText:						imstore.playLinkText,
  			pauseLinkText:					imstore.pauseLinkTex,
  			prevLinkText:						imstore.prevLinkText,
  			nextLinkText:						imstore.nextLinkText,
			delay:											parseInt( imstore.slideshowSpeed ),
  			nextPageLinkText:		imstore.nextPageLinkText,
  			prevPageLinkText:		imstore.prevPageLinkText,
			autoStart:    						imstore.autoStart,
			defaultTransitionDuration: parseInt( imstore.transitionTime ),
			
			onSlideChange: function( prevIndex, nextIndex ){
				$(".ims-slideshow-tools [type='checkbox']")
				.removeAttr( 'checked' );
			},
			
			onCreateImage: function( imageData ){
				imageData.image.onload = '';
				return imageData;
			}
			
		} );
		
	};
});