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
 
var win=window.dialogArguments||opener||parent||top;jQuery(window).ready(function(e){var t=win.tinyMCE.activeEditor;ims_close_window=function(){t.windowManager.close(window)};e("#ims-galleries").show().css({height:e(window).height()-30});e("#cancel").click(function(){ims_close_window()});e("#insert").click(function(){var n="";n+=" id="+e("#galid").val()+" ";n+=e("#caption").is(":checked")?"caption=1 ":"";n+="layout="+e("input[name='layout']:checked").val()+" ";n+=e("#order").val()==0?"":"sort="+e("#order").val()+" ";n+=e("#orderby").val()==0?"":"sortby="+e("#orderby").val()+" ";n+=e("#number").val()==""?"":"number="+e("#number").val()+" ";n+="linkto="+e("input[name='linkto']:checked").val()+" ";img='<img src="'+imslocal.tinyurl+'i.gif" class="imsGallery mceItem" title="ims-gallery'+n+'" />';t.focus();t.selection.setContent(img);t.selection.select(e(t.selection.getNode()).find("img")[0]);ims_close_window();return false});e("#internal-toggle").click(function(){if(e("#search-panel:hidden")[0]){e("#search-panel").show();win.resize_gal_window(165)}else{e("#search-panel").hide();win.resize_gal_window(-165)}});search_gals=function(){e.get(imslocal.imsajax,{action:"searchgals",_wpnonce:imslocal.nonceajax,q:e("#search-field").val()},function(t){e("#search-results ul li").remove();e("#search-results ul").append(t);e(".link-search-wrapper .waiting").hide()})};search_gals();e("#search-field").keyup(function(){s=e(this).val();if(s.length>2){e(".link-search-wrapper .waiting").show();search_gals()}});e("#search-results").delegate("li","click",function(){e("#galid").val(e(this).find("span.id").html())})})