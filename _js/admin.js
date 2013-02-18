/**
 * Image Store - Admin
 *
 * @file admin.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/admin.js
 * @since 3.0.0
 */
jQuery(document).ready(function(e){e(".ims-box").hide();e(".ims-box").eq(0).show();e(".ims-tabs li").eq(0).addClass("current");if(hash=window.location.hash){e(".ims-box:visible").hide();e(".ims-tabs li.current").removeClass("current");index=e(".ims-tabs li a").index(e("a[href|="+hash+"]"));e(".ims-tabs li").eq(index).addClass("current");e(hash).show();e(window).delay(20).scrollTop(0)}e(".ims-tabs li").click(function(){e("#message").remove();e(".ims-box:visible").hide();e(".ims-tabs li.current ").removeClass("current");e(".ims-box").eq(e(".ims-tabs li").index(e(this))).show();e(this).addClass("current");return false});e("select[name=userid]").change(function(){if(e(this).val()>0){window.location.hash="permissions";window.location.search="post_type=ims_gallery&page=ims-settings&userid="+e(this).val();window.location.href=window.location}})})