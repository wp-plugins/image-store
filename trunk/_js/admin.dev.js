jQuery(document).ready(function($){
	
	postboxes.add_postbox_toggles(pagenow);
	
	/******** TABS **********/

	//tabs default state
	$('.imstore .ims-box').hide();
	$('.imstore .ims-box').eq(0).show();
	$('.ims-tabs li').eq(0).addClass('current');
			
	//tabs actions
	$('.ims-tabs li').click(function(){
		$('#message').remove();
		$('.imstore .ims-box').hide();
		$('.ims-tabs li').removeClass('current');
		$('.imstore .ims-box').eq($('.ims-tabs li').index( $(this) )).show();
		$(this).addClass('current');
		return false;
	});
	
	//tabs select
	if( hash = window.location.hash ){
		$('.imstore .ims-box').hide();
		$('.ims-tabs li').removeClass('current');
		index = $('.ims-tabs li a').index($('a[href|=' + hash + ']'));
		$('.ims-tabs li').eq(index).addClass('current');
		$( hash ).show();
		$('html, body').animate({scrollTop: '0px'});
	};
	
	//permissions: user dropdown
	$('#ims_user').change(function(){
		if( $(this).val() > 0 ){
			window.location.hash = 'caps_settings';
			window.location.search = 'page=ims-settings&userid='+ $(this).val() ;
			window.location.href = window.location ;
		}
	});
	
	//open thickbox
	$('#addwatermarkurl').live('click', function(){
		win = window.dialogArguments || opener || parent || top;
		tb_show('Attach File', 'media-upload.php?imstore=1&TB_iframe=true'); 				 
	});
	
	//add watermark url
	window.add_watermark_url = function( image ) {
		jQuery('#watermarkurl').val( image );
		tb_remove();
	};
	
	//add image size
	$('#addimagesize').click( function( ){
		var count = $('.image-size').length;
		var clas = ( count % 2 ) ? '' : ' alternate';
		var row = '<tr class="t image-size'+clas+'"><td scope="row">';
		row += '<input type="checkbox" name="imgid_'+count+'" class="inputmd" /></td>';
  row += '<td><input type="text" name="imagesize_'+count+'[name]" class="inputmd" /></td>';
		row += '<td><label><input type="text" name="imagesize_'+count+'[w]" class="inputsm" /></label></td>';
		row += '<td><label><input type="text" name="imagesize_'+count+'[h]" class="inputsm" /></label></td>';
		row += '<td><label><input type="text" name="imagesize_'+count+'[q]" class="inputsm" />(%)</label></td>';
		row += '<td>'+ imslocal.pixels +'</td>';
		row += '<td>&nbsp;</td></tr>';
		$('.ims-image-sizes').before( row );	
		return false;
	});
	
	/******** DRAG/DROP/SORT **********/
	
	$(".price-list tbody").sortable({
		//revert: true,
		cursor: 'move',
		handle: '.move',
		placeholder: 'widget-placeholder',
		stop: function(event, ui){
			$("tr.filler").hide();
			ui.item.attr( 'class', 'alternate size' ).removeAttr('style'); 
		}
	});
	
	$("#price-list .sizes-list .size, #price-list .package-list .size").draggable({
		helper: 'clone',
		revert: 'invalid',
		handle: '.move',
		connectToSortable: '.price-list tbody'
	});
	
	$("#packages .package-list tbody").sortable({
		cursor: 'move',
		handle: '.move',
		placeholder: 'widget-placeholder',
		stop: function(event, ui){
			$("tr.filler").hide();
			ui.item.find('.price').remove();
			ui.item.attr('class', 'alternate size').removeAttr('style'); 
		}
	});
	
	$("table.sort-images tbody").sortable({
		axis: 'y',
		cursor: 'move',
		helper: 'clone',
		placeholder: 'widget-placeholder',
		update : function () { 
			$(this).find('tr').each(function(i) {
				$(this).find('.column-order input').val(i+1);
				if( (i%2) != 0 ) $(this).removeClass('alternate').addClass('alternate');
				else $(this).removeClass('alternate');
			});
		} 
	});
	
	$("#packages .sizes-list .size").draggable({
		helper: 'clone',
		revert: 'invalid',
		handle: '.move',
		connectToSortable: '.package-list tbody'
	});

	$("tr.size").disableSelection();
	$('td.x').live( 'click', function(){
			if( $(this).parent().parent().find('tr.size').length <= 1 ) 
				$('tr.filler').show();
			$(this).parent().remove();
		}
	);
	
	/******** WIDGETS **********/
	
	//default state
	$('.show-free').hide();
	$('tbody.content').hide();
	$('tbody.content').hide();
	$('tfoot.content').hide();
	$('.show-download').hide();
	
	//show/hide widget list content
	$('.itemtop a').toggle( 
		function( ){ 
			$(this).html('[-]'); 
			index = $('.itemtop a').index($(this));
			$('tbody.content').eq(index).show( );
			$('tfoot.content').eq(index).show( );
			if( $('tbody.content').eq(index).find('tr.size').length <= 0 )
				$('tr.filler').eq(index).show( );
			else $('tr.filler').eq(index).hide( );
			$(".price-list tbody").sortable( "refresh" );
			$(".package-list tbody").sortable( "refresh" );
		},	
		function( ){ 
			$(this).html('[+]') ;
			index = $('.itemtop a').index($(this));
			$('tbody.content').eq(index).hide();
			$('tfoot.content').eq(index).hide();
			$('tr.filler').eq(index).hide( );
			$(".price-list tbody").sortable( "refresh" );
			$(".package-list tbody").sortable( "refresh" );
		}
	);
	
	//trash pricelist
	$("#price-list .trash").click(function(){
		del = confirm( imslocal.deletelist );
		if( del ){
			id = $(this).parent().find('.listid').val();
			$.get( imslocal.imsajax, { 	
				action: 'deletelist',
				listid: id,
				_wpnonce: imslocal.nonceajax
			}, function(){$('#ims-list-'+id).remove() });
			
		}
		return false;
	});
	
	
	//trash pricelist
	$("#packages .trash").click(function(){
		del = confirm( imslocal.deletepackage );
		if( del ){
			id = $(this).parent().find('.packageid').val();
			$.get( imslocal.imsajax, { 	
				action: 'deletepackage',
				packageid: id,
				_wpnonce: imslocal.nonceajax
			}, function(){$('#package-list-'+id).remove()});
			
		}
		return false;
	});
	
	//trash item
	$("span.delete a").click(function(){
		del = confirm( imslocal.deleteentry );
		if( del ){ 
			return true;
		}
		return false;
	});
	
	//promotions 
/*	$("#promo_type").change(function(){
		if($(this).val() == 3 ){
			$('.show-free').show();
			$('.hide-free').hide();
		}else{
			$('.show-free').hide();
			$('.hide-free').show();
			$(".show-download").hide();
		}
	});
	*/
	
	//promotions 
	$("#promo_type").change(function(){
		if( $(this).val() == 3 ){
			$('input[name="discount"]').attr({disabled:"disabled"});
		}else{
			$('input[name="discount"]').removeAttr('disabled');
		}
	});
	
	//add add size
	$(".add-image-size").click(function(){
		counter = $(this).parents('.postbox').find(".copyrow .name").val();
		row = $(this).parents('.postbox').find(".copyrow").clone().removeClass('copyrow');
		row.find('.name').attr( 'name','sizes['+counter+'][name]').removeAttr('value');
		row.find('.price').attr( 'name','sizes['+counter+'][price]');
		row.find('.unit').attr( 'name','sizes['+counter+'][unit]');
		$(this).parents('.postbox').find(".addrow").before(row);
		$(this).parents('.postbox').find(".copyrow .name").val(counter++);
		return false;
	});

	
	/******** IMAGE UPLOAD **********/
	
	var exists = 0;
	var uploaded = 0;
	
	//remove message
	function ims_file_selected( ){
		$('#message').remove( );
	};
	
	if( $('input[name=disableflash]').length > 0 ){
		
		//change folder path when event is selected
		$('#upload-images input[type=radio]').click(function( ){
			$.get( imslocal.imsajax, { 
				action		: 'swuploadfolder',
				galleryid	: $(this).val( ),
				_wpnonce	: imslocal.nonceajax
				}, function( data ){
				 $( '#imagefiles' ).uploadifySettings( 'folder', data );
			});
		});

		$("#imagefiles").uploadify({
			'buttonText'	 : imslocal.flastxt,
			'multi' 		 : true,
			'uploader' 		 : imslocal.imsurl + '_swf/uploadify.swf',
			'script' 		 : imslocal.imsurl + 'admin/swfupload.php',
			'scriptData' 	 : {'action' : 'swupload'},
			'cancelImg' 	 : imslocal.imsurl + '_img/xit.gif',
			//'wmode'		 : 'transparent',
			'height'		 : '26',
			'width'			 : '118',
			'fileExt'		 : '*.jpg;*.jpeg;*.gif;*.png',
			'fileDesc'		 : 'Image files',
			'onSelect'		 : ims_file_selected,
			'onComplete'	 : ims_file_uploaded,
			'onAllComplete'	 : ims_upload_complete,
			'buttonTextColor' : '#333333'
		});
		
	}
		
	//add image: event upload files
	$('.upload-images').click(function(){
		exists = 0;
		uploaded = 0;
		 if( $('#upload-images input[type=radio]:checked').length <= 0 ){
			 alert( imslocal.selectgal );
			 return false; 
		 }else{ $('#imagefiles').uploadifyUpload( ); }
		 return false; 
	 });

	// run every time a file is uploaded
	function ims_file_uploaded( event, ID, fileObj, response, data ){
		if(response == 'x'){
			exists++;
		}else{
			$.get( imslocal.imsajax, { 
				action		: 'flashimagedata',
				galleryid	: $('#upload-images input[type=radio]:checked').val( ),
				imagename 	: fileObj.name,
				filepath	: response,
				_wpnonce	: imslocal.nonceajax
			}/*, function( data ){ alert( data )}*/);
			uploaded ++; 
		}
	};
	
	//redirect after files uploaded
	function ims_upload_complete( event, data ){
		setTimeout(function( ){
			message = '<div class="updated fade" id="message"><p>' + uploaded + imslocal.uploaded;
			if( exists > 0 ) message += exists + imslocal.exists;
			message += '</p></div>';
			$(message).insertBefore('.add-menu-item-tabs');
		}, 2000 );
	};
	
	//show uploading icon
	$('#zipupload, #importfolder, #rebuildimgs').click(function(){
		$(this).parents('td').find('div.loading').show( );	
	});
	
	/******** DATE PICKERS **********/
	$("#date").datepicker({ altField: '#post_date', altFormat: 'yy-m-d', dateFormat: imslocal.dateformat });
	$("#expire").datepicker({ altField: '#ims_expire', altFormat: 'yy-m-d', dateFormat: imslocal.dateformat });
	
	$("#starts").datepicker({ altField: '#start_date', altFormat: 'yy-m-d', dateFormat: imslocal.dateformat });
	$("#expires").datepicker({ altField: '#expiration_date', altFormat: 'yy-m-d', dateFormat: imslocal.dateformat });
	
});