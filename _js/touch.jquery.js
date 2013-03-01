/**
 * Image Store - jQuery touch plugin
 *
 * @file xmslider.jquery.js
 * @package Image Store
 * @author  Martin Angelov / Hafid Trujillo
 * @copyright 20010-2013
 * @url http://tutorialzine.com/2012/04/mobile-touch-gallery/
 * @filesource  wp-content/plugins/image-store/_js/xmslider.jquery.js
 * @since 3.2.1
 */
 
(function(e){var t=e('<div id="touch-overlay">'),n=e('<div id="touch-slider">'),r=e('<a id="touch-prev"></a>'),i=e('<a id="touch-next"></a>'),s=0,o=false;e.fn.imstouch=function(u){function c(e){setTimeout(function(){y(e)},1e3)}function h(t,n){e.event.trigger(t);if(n){n.call(e("#ims-slide-"+f))}}function p(t){return parseInt(e("#touch-slider .ims-slide").index(e("#ims-slide-"+t)))}function d(t){return parseInt(e(t).attr("class").replace(/^(.+)?(ims-touch-)([0-9]{1,})(.+)?$/g,"$3"))}function v(e){n.css("left",-e*100+"%")}function m(e){if(o){return false}t.show();setTimeout(function(){t.addClass("visible")},100);v(e);o=true;h("touch_open",l.onOpen)}function g(t,n){var r=e('<img class"touch-shadow">').bind("load",function(){n.call(r)});if(!("ontouchstart"in window))r.bind("click",w);r.attr("src",t)}function y(t){if(!e("#ims-slide-"+t)[0]){return false}if(l.href){if(!e(l.href).hasClass("touch-shadow"))e(l.href).addClass("touch-shadow");e("#ims-slide-"+t).html(e(l.href).css({display:"inline-block"}));return true}g(e(".ims-touch-"+t).attr("href"),function(){closelink=e('<a class="touch-close">x</a>').click(function(e){e.preventDefault();b()});e("#ims-slide-"+t).html(this).append(e(".ims-touch-"+t).next(".img-metadata").clone().append(closelink))});if(!l.href){r.show();i.show()}}function b(){if(!o){return false}t.hide().removeClass("visible");o=false;if(l.href){e(l.href).hide().appendTo("body")}else a.empty();r.hide();i.hide();tems=allitems;h("touch_close",l.onClose)}function w(){if(f+1<a.length){f++;v(p(f));c(f+1)}else{n.addClass("rightSpring");setTimeout(function(){n.removeClass("rightSpring")},500)}}function E(){if(f>0){f--;v(p(f));c(f-1)}else{n.addClass("leftSpring");setTimeout(function(){n.removeClass("leftSpring")},500)}}var a=e([]),f=0;allitems=this,items=allitems;var l=e.extend({href:false,onOpen:false,onClose:false},u);if(l.href){e(l.href).find(".touch-close").bind("click",b)}if(!e("#touch-overlay")[0]){t.hide().appendTo("body");n.appendTo(t)}closeOverlay=function(t){if(e(t.target).attr("class")!="ims-slide")return;b()};items.each(function(){e(this).addClass("ims-touch-"+s);a=a.add(e('<div id="ims-slide-'+s+'" class="ims-slide">').bind("click",closeOverlay));s++});n.append(a);e("#touch-overlay .ims-slide img").live("touchstart",function(e){var t=e.originalEvent,r=t.changedTouches[0].pageX;n.bind("touchmove",function(e){e.preventDefault();t=e.originalEvent.touches[0]||e.originalEvent.changedTouches[0];if(t.pageX-r>10){n.unbind("touchmove");E()}else if(t.pageX-r<-10){n.unbind("touchmove");w()}});return false}).bind("touchend",function(){n.unbind("touchmove")});items.live("click",function(e){e.preventDefault();f=d(this);offset=p(f);m(offset);y(f);if(!l.href){c(f+1);c(f-1)}});if(!l.href){t.append(r).append(i);r.click(function(e){e.preventDefault();E()});i.click(function(e){e.preventDefault();w()});r.hide();i.hide()}}})(jQuery)