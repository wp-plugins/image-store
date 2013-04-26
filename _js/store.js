/**
 * Image Store - store functions
 *
 * @file store.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/store.js
 * @since 3.2.1
 */
 
jQuery(document).ready(function(e){var t=[];try{e("#ims-pricelist").hide();if(typeof imstore=="undefined")imstore={};e(".ims-cart-form input[type=hidden]").each(function(){t[e(this).attr("name")]=e(this).attr("data-value-ims");e(this).removeAttr("data-value-ims")});e(".ims-cart-form input[type=submit]:not([name=ims-apply-changes])").live("click",function(){fname=e(this).attr("name");if(fname!="fname")e(".ims-cart-form").attr({method:"post"});for(var n in t)e("input[name='"+n+"']").val(t[n]);if(t["_xmvdata"]>0)e(".ims-cart-form").attr({action:decodeURIComponent(e(this).attr("data-submit-url"))});else e(this).attr({name:"ims-enotification"})});e(".ims-cart-form input[name=ims-apply-changes]").live("click",function(){e("input[name=_wpnonce]").val(t["_wpnonce"]);e(".ims-cart-form").attr({method:"post"})})}catch(n){return false}e(".add-images-to-cart a").imstouch({href:"#ims-pricelist",onOpen:function(){e(".ims-message").hide();count=e(".ims-innerbox input:checked").length;e(".ims-image-count em").html(count);e("#ims-to-cart-ids").val(img_ids_to_string());if(count==0)e(".ims-add-error").show()},onClose:function(){e(".ims-add-error").hide()}});e(".add-images-to-cart-single a").imstouch({href:"#ims-pricelist",onOpen:function(){if(e("#ims-thumbs li.selected")[0])$ids=e("#ims-thumbs li.selected").attr("data-id");else $ids=e('.img-metadata input[name="imgs[]"]').val();e(".ims-message").hide();e("#ims-to-cart-ids").val($ids)}});e("#touch-overlay label:has('input')").live("mousedown",function(){imgid=e(this).find('input[type="checkbox"]').val();$checkbox=e('.ims-gallery input[value="'+imgid+'"]');if(e(this).hasClass("ims-selected")){$checkbox.removeAttr("checked");e(this).removeClass("ims-selected");$checkbox.parent().removeClass("ims-selected")}else{e(this).addClass("ims-selected");$checkbox.attr({checked:"checked"});$checkbox.parent().addClass("ims-selected")}})})