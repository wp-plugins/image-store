/**
 * Image Store - imstore tinymce plugin
 *
 * @file imstore.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/tinymce/imstore.js
 * @since 3.0.0
 */
 
(function(){tinymce.create("tinymce.plugins.imStore",{init:function(e,n){t=this;t.url=n;t._createButtons();e.addCommand("imStoreOpen",function(){e.windowManager.open({inline:1,width:505,height:300,file:n+"/tinymce.php?nonce=347234"},{plugin_url:n})});e.addCommand("imsEditImage",function(){var t=e.selection.getNode(),r,i,s,o=e.dom.getAttrib(t,"class");if(o.indexOf("imsGallery")!=0||t.nodeName!="IMG")return;q=e.dom.getAttrib(t,"title").replace(/\s/g,"&");e.windowManager.open({inline:1,width:500,height:295,file:n+"/tinymce.php?nonce=347234&"+q},{plugin_url:n})});e.addButton("imstore",{cmd:"imStoreOpen",image:n+"/imstore.png"});e.onBeforeSetContent.add(function(e,n){n.content=t._do_gallery(n.content,t.url)});e.onPostProcess.add(function(e,n){if(n.get)n.content=t._get_gallery(n.content)});e.onMouseDown.add(function(e,n){var r=n.target;if(e.dom.getAttrib(r,"class").indexOf("imsGallery")==0){t._showButtons(r,"ims_editbtns")}else{t._hideButtons()}});e.onInit.add(function(e){tinymce.dom.Event.add(e.getWin(),"scroll",function(e){t._hideButtons()});tinymce.dom.Event.add(e.getBody(),"dragstart",function(e){t._hideButtons()})})},_do_gallery:function(e,t){return e.replace(/\[ims-gallery([^\]]*)\]/g,function(e,n){return'<img src="'+t+'/i.gif" class="imsGallery mceItem" title="ims-gallery'+tinymce.DOM.encode(n)+'" />'})},_getAttr:function(e,t){t=(new RegExp(t+'="([^"]+)"',"g")).exec(e);return t?tinymce.DOM.decode(t[1]):""},_get_gallery:function(e){t._hideButtons();return e.replace(/(?:<p[^>]*>)*(<img[^>]+>)(?:<\/p>)*/g,function(e,n){var r=t._getAttr(n,"class");if(r.indexOf("imsGallery")!=-1)return"<p>["+tinymce.trim(t._getAttr(n,"title"))+"]</p>";return e})},_createButtons:function(){var e=this,t=tinyMCE.activeEditor,n=tinymce.DOM,r,i;n.remove("ims_editbtns");n.add(document.body,"div",{id:"ims_editbtns",style:"display:none;"});r=n.add("ims_editbtns","img",{src:e.url+"/image.png",id:"ims_editibtn",width:"24",height:"24"});tinymce.dom.Event.add(r,"mousedown",function(e){var t=tinyMCE.activeEditor;t.windowManager.bookmark=t.selection.getBookmark("simple");t.execCommand("imsEditImage")});i=n.add("ims_editbtns","img",{src:e.url+"/delete.png",id:"ims_deletebtn",width:"24",height:"24"});tinymce.dom.Event.add(i,"mousedown",function(t){var n=tinyMCE.activeEditor,r=n.selection.getNode(),i;if(r.nodeName=="IMG"&&n.dom.getAttrib(r,"class").indexOf("imsGallery")==0){if((i=n.dom.getParent(r,"div"))&&n.dom.hasClass(i,"mceTemp"))n.dom.remove(i);else if((i=n.dom.getParent(r,"A"))&&i.childNodes.length==1)n.dom.remove(i);else n.dom.remove(r);e._hideButtons();n.execCommand("mceRepaint");return false}})},_hideButtons:function(){if(document.getElementById("ims_editbtns"))tinymce.DOM.hide("ims_editbtns")},_showButtons:function(e,t){var n=tinyMCE.activeEditor,r,i,s,o=tinymce.DOM,u,a;s=n.dom.getViewPort(n.getWin());r=o.getPos(n.getContentAreaContainer());i=n.dom.getPos(e);u=Math.max(i.x-s.x,0)+r.x;a=Math.max(i.y-s.y,0)+r.y;o.setStyles(t,{top:a+5+"px",left:u+5+"px",display:"block"})}});tinymce.PluginManager.add("imstore",tinymce.plugins.imStore)})();jQuery(window).ready(function(e){wh=324;fh=295;resize_gal_window=function(t){wt=e(".clearlooks2").position().top-t/2;e(".clearlooks2").css({height:wh+t+"px",top:wt+"px"});e(".clearlooks2 iframe").css({height:fh+t+"px"});wh=wh+t;fh=fh+t}})