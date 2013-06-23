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
(function(e){e("img[data-ims-src]").bind("scrollin",{distance:100},function(){var e=this,t=jQuery(e),n=t.attr("data-ims-src");t.unbind("scrollin").hide().removeAttr("data-ims-src").attr("data-ims-loaded","true");e.src=n;t.fadeIn()})})(jQuery);jQuery(document).ready(function(e){var t=e("#ims-mainbox .ims-toolbar");if(t[0]){var n,r,i;setTimeout(function(){n=t.offset().top;i=e("#ims-mainbox").height()-50},80);e(window).bind("scroll resize",function(){wscroll=e(window).scrollTop();if(wscroll>=n&&wscroll<i+n){t.addClass("ims-float").css({width:e("#ims-mainbox").width()-25});e(".ims-message").addClass("ims-float").css({width:r-25}).hide()}else{e(".ims-message").removeClass("ims-float").hide();t.removeClass("ims-float").css({width:"auto"})}})}e("body").delegate("li.ims-scroll-up","click",function(){e("html,body").animate({scrollTop:e("#ims-mainbox").offset().top-30});return false});if(jQuery().xmslide){e(".ims-filmstrip").xmslide({paging:true});e(".ims-tools-gal").xmslide({paging:true,autostart:true})}e(".ims-img img, #ims-slideshow, .ims-thumb").bind("contextmenu",function(e){e.preventDefault();return false});display_user_message=function(t,n){e(".ims-message").fadeOut().removeClass("ims-error").removeClass("ims-success").addClass(n).html(t).fadeIn();if(e(".ims-message").hasClass("ims-float"))setTimeout(function(){e(".ims-message").fadeOut()},1500)};update_favorites_values=function(t){response=t.split("|");if(typeof response[2]!="undefined"){if(!e(".ims-menu-favorites span")[0])e(".ims-menu-favorites").append("<span>("+response[2]+")</span>");else e(".ims-menu-favorites span").html("("+response[2]+")")}display_user_message(response[0],response[1])};img_ids_to_string=function(){return e(".ims-innerbox input:checked").map(function(){return e(this).val()}).get().join(",")};if(jQuery().imstouch){wpgalleries=imstore.wplightbox?",.gallery .gallery-icon a, a.colorbox, a.xmtouch":"";e("#ims-mainbox .ims-gallery:not(.nolightbox) .ims-img a.url,"+".ims-colorbox .ims-img a.url,"+"td.ims-preview a.url"+wpgalleries).imstouch({onOpen:function(){e(".ims-message").hide()}})}e("body").delegate(".img-metadata .rating","mousedown",function(){if(!imstore.is_logged_in){if(!e(".ims-not-allowed")[0])e("body").append('<div class="ims-not-allowed"></div>');e(".touch-close").trigger("click");e(".ims-not-allowed").hide().html("<em>"+imstore.singin+"</em>").fadeIn();e(".ims-not-allowed").bind("click",function(){e(this).fadeOut()});return}button=e(this).addClass("cliked");ajaxaction=button.hasClass("ims-voted")?"remove-vote":"vote";e.get(imstore.imstoreurl+"/ajax.php",{action:ajaxaction,_wpnonce:imstore.ajaxnonce,imgid:e(this).attr("data-id")},function(e){if(!e)return;button.fadeOut().html('<em class="value">'+e+"</em>"+"+").fadeIn();if(ajaxaction=="vote")button.addClass("ims-voted");else button.removeClass("ims-voted")})});e("#ims-mainbox").delegate(".ims-innerbox label:has('input')","mousedown",function(){if(e(this).hasClass("ims-selected"))e(this).removeClass("ims-selected");else e(this).addClass("ims-selected")});e("body").delegate(".ims-select-all a","click",function(){e(".ims-innerbox label:has('input')").addClass("ims-selected");e(".ims-innerbox [type='checkbox']").attr("checked","checked");return false});e("body").delegate(".ims-unselect-all a","click",function(){e(".ims-innerbox label.ims-selected").removeClass("ims-selected");e(".ims-innerbox [type='checkbox']").removeAttr("checked");return false});e("body").delegate(".add-to-favorite-single a","click",function(){if(e("#ims-thumbs li.selected")[0])$ids=e("#ims-thumbs li.selected").attr("data-id");else $ids=e('.img-metadata input[name="imgs[]"]').val();e.get(imstore.imstoreurl+"/ajax.php",{imgids:$ids,action:"favorites",_wpnonce:imstore.ajaxnonce},function(e){update_favorites_values(e)});e("body").animate({scrollTop:e(".ims-message").offset().top-50},"slow");return false});e("body").delegate(".add-to-favorite a","click",function(){img_ids=img_ids_to_string();e.get(imstore.imstoreurl+"/ajax.php",{imgids:img_ids,action:"favorites",_wpnonce:imstore.ajaxnonce},function(e){update_favorites_values(e)});return false});e("body").delegate(".remove-from-favorite a","click",function(){img_ids=img_ids_to_string();e(".ims-innerbox input:checked").each(function(){e(this).parents("dt,li,figure").remove()});count=e(".ims-innerbox .ims-img").length;e.get(imstore.imstoreurl+"/ajax.php",{count:count,imgids:img_ids,action:"remove-favorites",_wpnonce:imstore.ajaxnonce},function(t){response=t.split("|");if(typeof response[2]!="undefined")e(".ims-menu-favorites span").html("("+response[2]+")");display_user_message(response[0],response[1])});return false});e(".ims-color").click(function(){val=e(this).val();color=e(this).is(":checked")?"&c="+val:"";e(".image-color input").not(".ims-color-"+val).removeAttr("checked");e(".image-wrapper img").animate({opacity:0},400,function(){img=new Image;img.src=s.currentImage.image.src+color;e(".image-wrapper img").replaceWith(img).delay(900/1.5).animate({opacity:1},700)})});if(e("#ims-thumbs")[0]&&imstore.galleriffic&&jQuery().galleriffic){var s=e("#ims-thumbs").galleriffic({preloadAhead:10,enableTopPager:true,enableBottomPager:true,renderSSControls:true,renderNavControls:true,controlsContainerSel:"#ims-player",captionContainerSel:"#ims-caption",imageContainerSel:"#ims-slideshow",numThumbs:parseInt(imstore.numThumbs),maxPagesToShow:parseInt(imstore.maxPagesToShow),playLinkText:imstore.playLinkText,pauseLinkText:imstore.pauseLinkTex,prevLinkText:imstore.prevLinkText,nextLinkText:imstore.nextLinkText,delay:parseInt(imstore.slideshowSpeed),nextPageLinkText:imstore.nextPageLinkText,prevPageLinkText:imstore.prevPageLinkText,autoStart:imstore.autoStart,defaultTransitionDuration:parseInt(imstore.transitionTime),onSlideChange:function(t,n){e(".ims-slideshow-tools [type='checkbox']").removeAttr("checked")},onCreateImage:function(e){e.image.onload="";return e}})}})