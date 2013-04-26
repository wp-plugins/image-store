/**
 * Image Store - imstore tinymce inner functions
 *
 * @file imstore.js
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2013
 * @filesource  wp-content/plugins/image-store/_js/tinymce/imstore/imstore.js
 * @since 3.0.0
 */
 
(function(){tinymce.create("tinymce.plugins.imStore",{init:function(e,n){t=this;t.url=n;e.addCommand("imStoreOpen",function(){e.windowManager.open({title:"imStore",inline:1,width:450,height:200,wpDialog:true},{plugin_url:n})});e.addButton("imstore",{cmd:"imStoreOpen",image:n+"/imstore.png",title:e.getLang("imstore.add_gallery")})}});tinymce.PluginManager.add("imstore",tinymce.plugins.imStore)})()