jQuery(document).ready(function($){
	
	//admin url
	nonce = $('#_wpnonce').val();
	galid = $('#gallery-id').val();
	wplbx = $('#wplightbox').val();
	imsurl = $('#imstore-url').val();
	
	//slected label
	slcttxt = $('.ims-image-count').html();
	
	//message animation
	$('.ims-message').fadeOut().fadeIn();
	
	//disable right click
	$('.ims-innerbox, #cboxWrapper, .ims-table').bind( "contextmenu", function(e){ return false; });

	//hide boxes
	$('.ims-add-error').hide( ); 
	$('#ims-pricelist').hide( ); 
	
	//check all
	$('.ims-select-all a').click(function(){
		$(".ims-innerbox [type='checkbox']").attr('checked','checked');
		return false;
	});

	//uncheck all
	$('.ims-unselect-all a').click(function(){
		$(".ims-innerbox [type='checkbox']").removeAttr('checked');
		return false;
	});

	// add a single image to favorites
	$('.add-to-favorite-single a').click(function(){
		url = $('.image-wrapper img').attr('src').replace('&c=s','').replace('&c=g','').split('&img=');
		$.get( imsurl+'/ajax.php', { _wpnonce:nonce, galid:galid, action:"favorites", imgids:url[1] }, 
		function(data){
			response = data.split('|');
			$('.ims-message').fadeOut().removeClass('error').removeClass('success').addClass(response[1]).html(response[0]).fadeIn();
		});
		return false;
	});
	
	
	// add to favorites
	$('.add-to-favorite a').click(function(){
		imgids = $(".ims-innerbox input:checked").map(function(){ return $(this).val(); }).get().join(',');
		$.get( imsurl+'/ajax.php', { _wpnonce:nonce, galid:galid, action:"favorites", imgids:imgids }, 
		function(data){
			response = data.split('|');
			$('.ims-message').fadeOut().removeClass('error').removeClass('success').addClass(response[1]).html(response[0]).fadeIn();
		});
		return false;
	});
	
	
	// remove from favorites
	$('.remove-from-favorite a').click(function(){
		imgids = $(".ims-innerbox input:checked").map(function(){ return $(this).val(); }).get().join(',');
		$(".ims-innerbox input:checked").each(function(){ $(this).parents('dt').remove(); }); 
		$.get( imsurl+'/ajax.php', { _wpnonce:nonce, galid:galid, action:"remove-favorites", imgids:imgids }, 
		function(data){
			response = data.split('|');
			$('.ims-message').fadeOut().removeClass('error').removeClass('success').addClass(response[1]).html(response[0]).fadeIn();
		});
		return false;
	});
	
	
	//black and white preview
	$('#ims-color-bw').click(function(){
		color = ($(this).is(':checked'))? '&c=g': '';
		$('#ims-color-sepia').attr({checked:''});
		$('.image-wrapper img').animate({opacity:0},400,function(){
			$(this).attr({ src: $('.image-wrapper img').attr('src').replace('&c=g','').replace('&c=s','') + color})
			.delay(900/1.5).animate({opacity:1},700);
		});
	});
	
	
	//sepia preview
	$('#ims-color-sepia').click(function(){
		$('#ims-color-bw').attr({checked:''});
		color = ($(this).is(':checked'))? '&c=s': '';
		$('.image-wrapper img').animate({opacity:0},400,function(){
			$(this).attr({ src: $('.image-wrapper img').attr('src').replace('&c=g','').replace('&c=s','') + color})
			.delay(900/1.5).animate({opacity:1},700);
		});
	});

	
	// image colorbox
	$(".ims-gallery .ims-colorbox").colorbox({
		current:'',
		photo:true, 
		maxWidth:"95%", 
		maxHeight:'90%', 
		speed: imstore.slideshowSpeed,
		next: imstore.nextLinkText,
		close: imstore.closeLinkText,
		previous: imstore.prevLinkText
	});
	
	//slideshow
	if( $('#ims-thumbs').length > 0 ){
		var gallery = $('#ims-thumbs').galleriffic({
			preloadAhead:   		10,
			enableTopPager:   	true,
			enableBottomPager:		true,
			renderSSControls:		true,
			renderNavControls:		true,
			controlsContainerSel:	'#ims-player',
			captionContainerSel:	'#ims-caption',
			imageContainerSel:		'#ims-slideshow',
			numThumbs:    			parseInt(imstore.numThumbs),
			maxPagesToShow:   		parseInt(imstore.maxPagesToShow),
			playLinkText:			imstore.playLinkText,
    		pauseLinkText:			imstore.pauseLinkTex,
    		prevLinkText:			imstore.prevLinkText,
    		nextLinkText:			imstore.nextLinkText,
			delay:					parseInt(imstore.slideshowSpeed),
    		nextPageLinkText:		imstore.nextPageLinkText,
    		prevPageLinkText:		imstore.prevPageLinkText,
			autoStart:       imstore.autoStart,
			defaultTransitionDuration: parseInt(imstore.transitionTime),
			onSlideChange: function( prevIndex, nextIndex ) {
				$(".ims-slideshow-tools [type='checkbox']").removeAttr('checked');
			},
			onCreateImage: function( imageData ){
				imageData.image.onload = '';
				imageData.image.src = imageData.image.src.replace('&c=g','').replace('&c=s','');
				return imageData;
			}
		});
	};

	// add to image to cart
	$(".add-images-to-cart a").colorbox({
		width:"75%", height: '280px', inline:true, href:"#ims-pricelist",
		onClosed: function(){ 
			$('.ims-add-error').hide();
			$('#ims-pricelist').hide(); 
		},
		onOpen:	function(){ 
			$('#ims-pricelist').show( ); 
			count = $(".ims-innerbox input:checked").length;
			imgids = $(".ims-innerbox input:checked").map(function(){ return $(this).val(); }).get().join(',');
			$('#ims-to-cart-ids').val( imgids );
			$('.ims-image-count').html( count + ' ' + slcttxt ); 
			if( count == 0 ) $('.ims-add-error').show();
		}
	});
	
	// add to cart box single slideshow
	$(".add-images-to-cart-single a").colorbox({
		width:"75%", height: '280px', inline:true, href:"#ims-pricelist",
		onClosed: function(){ 
			$('.ims-add-error').hide();
			$('#ims-pricelist').hide(); 
		},
		onOpen:	function(){ 
			$('#ims-pricelist').show( ); 
			url = $('.image-wrapper img').attr('src').replace('&c=s','').replace('&c=g','').split('&img=');
			$('#ims-to-cart-ids').val( url[1] );
		}
	});
	
	// hide download links
	if( $('.ims-download').length > 0 ){
		var links = new Array();
		$('.ims-download').each(function(index) {
			 links[index] = $(this).attr('href');
			 $(this).attr('href', index );
		});
		
		$('.ims-download').click( function(){
			index = $('.ims-download').index($(this));
			window.location.href = links[index];	
			return false;
		});
		
	};
	
	if(imstore.lightbox == '1') $(".gallery-icon a").colorbox({photo:true, maxWidth:"95%",maxHeight:'90%'});

/*--------------------------------------------------------------------------*
	ZOOM GALLERY FUNCTION
*--------------------------------------------------------------------------*/
	
	
	/*$(".ims-slideshow-tools-box .zoom").css({width:'195px', height:'180px', margin:'0 0 20px', border:'solid 1px #eee',});
	
	function Lens() {
		lens = document.createElement("div");
		$( lens )
			.css({ 
				display	:'block',
				width	: '110px',
				height	: '100px',
				backgroundColor: 'white',
				opacity	: 0.4,
				position: 'absolute',
				border	: '1px dashed #bbbbbb',
				zIndex	: 5000,
				cursor	: 'crosshair'
			}).addClass('zoomlensactive');
		
		function hidezoom(){
			$('.zoom').html( '' );
			$('.zoomlensactive').remove();
			$('.zoom').removeClass('loader');
		};
		
		$(lens).bind('mouseout', hidezoom);
		$('.content').bind('mouseover', hidezoom);
		
	 return lens;
	};
	
	$('.advance-link img').live('mouseover', function(){
		zoom = 1.8;
		img = $(this);
		lens = new Lens();
		imgPost = $(img).offset();
		imgTop = imgPost.top;
		imgLeft = imgPost.left;
		
		imglg = new Image();
		imglg.src = $(img).attr('src');
		imglg.width = $(img).width()*zoom;
		imglg.height = $(img).height()*zoom;
		
		$('.zoom').addClass('loader');
		img.css({cursor: 'crosshair'});
		$(imglg).css({position:'absolute', width:imglg.width+'px', height:imglg.height+'px'});
		$('.zoom').css({overflow: 'hidden', position:'relative'});
		
		
		$(img).bind('mousemove', function(e){
			lens.style.top = e.pageY - $(lens).height() / 2 + 'px';	
			lens.style.left = e.pageX - $(lens).width() / 2 + 'px';	
		});
		
		$(lens).bind('mousemove', function(e){
			lenspos		= $(this).offset();
			lenstop 	= e.pageY - $(this).height()/ 2;
			lensleft 	= e.pageX - $(this).width()/ 2;
			imgBttm 	= $(img).height() + imgPost.top - $(this).height() ;
			imgRight 	= $(img).width() + imgPost.left - $(this).width();
			
			if(lenstop < imgTop )
				this.style.top = imgTop + 'px';
				
			if(lenstop > imgBttm)
				this.style.top = imgBttm + 'px';
				
			if(lensleft < imgLeft)
				this.style.left = imgLeft + 'px';
				
			if(lensleft >= ( imgRight - $(this).width()) ) 
				this.style.left = imgRight + 'px';
				
			if((lenstop >= imgTop) && (lenstop <= imgBttm))
				this.style.top = lenstop + 'px';
				
			if((lensleft >= imgLeft) && (lensleft <= imgRight))
				this.style.left = lensleft + 'px';
				
			$(imglg).css({
				top:((imgTop - lenspos.top)*zoom),
				left:((imgLeft - lenspos.left )*zoom)
			});
		});
		
		if( $('.zoomlensactive').length < 2 ){ 
			$('body').append(lens); 
		};
		
		$('.zoom').html(imglg);
	});*/
	
});