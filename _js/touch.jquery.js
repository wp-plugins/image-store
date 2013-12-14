/**
 * Image Store - jQuery touch plugin
 *
 * @file touch.jquery.js
 * @package Image Store
 * @author  Martin Angelov / Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/touch.jquery.jquery.js
 * @since 3.2.1
 */
(function(e){var t=e('<div id="touch-overlay">'),n=e('<div id="touch-slider">'),r=e('<a id="touch-prev"></a>'),i=e('<a id="touch-next"></a>'),s=e('<a class="touch-close">x</a>'),o=0,u=false;e.fn.imstouch=function(a){function v(e){setTimeout(function(){S(e)},1e3)}function m(t,n){e.event.trigger(t,l);if(n){n.call(e("#ims-slide-"+l))}}function g(t){return parseInt(e("#touch-slider .ims-slide").index(e("#ims-slide-"+t)))}function y(t){return parseInt(e(t).attr("class").replace(/^(.+)?(ims-touch-)([0-9]{1,})(.+)?$/g,"$3"))}function b(e){n.css("left",-e*100+"%")}function w(n,s){url=e(s.target).parent().attr("href");if(!d.href&&url!=""&&url.search(/\.(png|jpg|jpeg|gif|php)/i)==-1){window.location=url;return}if(u){return false}if(d.href){e(d.href).find(".touch-close").bind("click",T)}t.show();setTimeout(function(){t.addClass("visible")},100);e(document).bind("keydown",function(e){if(e.keyCode===37){e.preventDefault();r.click()}else if(e.keyCode===39){e.preventDefault();i.click()}else if(e.keyCode==27)T()});b(n);u=true;m("touch_open",d.onOpen)}function E(t,n){var r=e('<img class="touch-shadow">').bind("load",function(){n.call(r);m("touch_image_loaded")});if(!("ontouchstart"in window))r.bind("click",N);r.attr("src",t)}function S(t){$slide=e(".ims-touch-"+t);$selected=$slide.parent().hasClass("ims-selected");E($slide.attr("href"),function(){$meta=e('<span class="img-metadata" ></span>');if($slide.next(".img-metadata")[0])$meta=$slide.next(".img-metadata").clone();if(title=$slide.parents(".ims-img").find(".img-name").html())$meta.prepend('<span class="image-title">'+title+"</span>");e("#ims-slide-"+t).removeClass("ims-selected").html(this).append($meta);if($selected)e("#ims-slide-"+t).addClass("ims-selected")})}function x(t){if(!e("#ims-slide-"+t)[0]){return false}if(d.href){if(!e(d.href).hasClass("touch-shadow"))e(d.href).addClass("touch-shadow");if(d.iframe&&e(".ims-touch-"+t).attr("href")){s.show();$iframe=e('<iframe class="touch-iframe" src="'+e(".ims-touch-"+t).attr("href")+'"></iframe>');e("#ims-slide-"+t).html($iframe);$iframe.bind("load",function(){e(this).unbind("load");e(this).animate({width:"90%",height:"85%"})})}else{e("#ims-slide-"+t).html(e(d.href).css({display:"inline-block"}))}return true}S(t);if(!d.href){if(t>0)r.show();if(t<p.length-1)i.show();s.show()}}function T(){if(!u){return false}m("touch_before_close");t.hide().removeClass("visible");u=false;if(d.href)e(d.href).hide().appendTo("body");else p.empty();r.hide();i.hide();s.hide();e(document).unbind("keydown");tems=c;e(window).scrollTop(e(".ims-touch-"+l).offset().top-300);m("touch_close",d.onClose)}function N(){if(r.is(":hidden"))r.show();if(l+1<p.length){l++;if(l>=p.length-1)i.hide();b(g(l));v(l+1);m("touch_next")}else{n.addClass("rightSpring");setTimeout(function(){n.removeClass("rightSpring")},500)}}function C(){if(i.is(":hidden"))i.show();if(l>0){l--;if(l<=0)r.hide();b(g(l));v(l-1);m("touch_previous")}else{n.addClass("leftSpring");setTimeout(function(){n.removeClass("leftSpring")},500)}}var f="",l=0,c=this,h=c,p=e([]);var d=e.extend({href:false,iframe:false,onOpen:false,onClose:false},a);if(!e("#touch-overlay")[0]){t.hide().appendTo("body");n.appendTo(t)}closeOverlay=function(t){if(e(t.target).attr("class")!="ims-slide")return;T()};h.each(function(){e(this).addClass("ims-touch-"+o);f+='<div id="ims-slide-'+o+'" class="ims-slide"></div>';o++});n.append(p=e(f).bind("click",closeOverlay));e("#touch-overlay").delegate(" .ims-slide img","touchstart",function(e){var t=e.originalEvent,r=t.changedTouches[0].pageX;n.bind("touchmove",function(e){e.preventDefault();t=e.originalEvent.touches[0]||e.originalEvent.changedTouches[0];if(t.pageX-r>10)C();else if(t.pageX-r<-10)N();n.unbind("touchmove")});return false}).bind("touchend",function(){n.unbind("touchmove")});h.bind("click",function(e){e.preventDefault();l=y(this);offset=g(l);w(offset,e);x(l);if(!d.href){v(l+1);v(l-1)}});if(!d.href){t.append(r).append(i);r.bind("click",function(e){e.preventDefault();C()});i.bind("click",function(e){e.preventDefault();N()});r.hide();i.hide()}if(!t.find(".touch-close")[0]){s.bind("click",function(e){e.preventDefault();T()});t.prepend(s);s.hide()}}})(jQuery)