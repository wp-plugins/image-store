jQuery(document).ready(function(a){a(".ims-box").hide();a(".ims-box").eq(0).show();a(".ims-tabs li").eq(0).addClass("current");if(hash=window.location.hash){a(".ims-box").hide();a(".ims-tabs li").removeClass("current");index=a(".ims-tabs li a").index(a("a[href|="+hash+"]"));a(".ims-tabs li").eq(index).addClass("current");a(hash).show();a(window).delay(20).scrollTop(0)}a(".ims-tabs li").click(function(){a("#message").remove();a(".ims-box").hide();a(".ims-tabs li").removeClass("current");a(".ims-box").eq(a(".ims-tabs li").index(a(this))).show();a(this).addClass("current");return false});a("select[name=userid]").change(function(){if(a(this).val()>0){window.location.hash="permissions";window.location.search="post_type=ims_gallery&page=ims-settings&userid="+a(this).val();window.location.href=window.location}});if(jQuery().datepicker){a("#starts").datepicker({altField:"#start_date",altFormat:"yy-m-d",dateFormat:imslocal.dateformat});a("#expires").datepicker({altField:"#expiration_date",altFormat:"yy-m-d",dateFormat:imslocal.dateformat})}a(".ims-box .content").hide();a("#print-finishes-box .inside").hide();a("#color_options .inside").hide();a(".ims-box.pricing .postbox .handlediv").click(function(){a(this).siblings(".inside").toggle()});a(".itemtop a").toggle(function(){a(this).html("[-]");table=a(this).parents(".ims-table");table.find(".content").show()},function(){a(this).html("[+]");table=a(this).parents(".ims-table");table.find(".content").hide()});a("td.x").live("click",function(){parent=a(this).parents("tbody");if(parent.find("tr.row").length<=1)parent.find("tr.filler").show();a(this).parent().remove()});a(".ims-table tbody.content").each(function(){if(a(this).find(".row").length<=0){a(this).find("tr.filler").show()}});a("#price-list-box input.downloadable").live("click",function(){if(a(this).is(":checked"))a(this).parents("tr.size").find("td").eq(1).append("<em>"+imslocal.download+"</em>");else a(this).parents("tr.size").find("td").eq(1).find("em").remove()});a("#price-list .trash").click(function(){del=confirm(imslocal.deletelist);if(del){id=a(this).parent().find(".listid").val();a.get(imslocal.imsajax,{action:"deletelist",postid:id,_wpnonce:imslocal.nonceajax},function(){a("#ims-list-"+id).remove()})}return false});a("#packages .trash").click(function(){del=confirm(imslocal.deletepackage);if(del){id=a(this).parent().find(".packageid").val();a.get(imslocal.imsajax,{postid:id,action:"deletepackage",_wpnonce:imslocal.nonceajax},function(){a("#package-list-"+id).remove()})}return false});a("#promo_type").change(function(){if(a(this).val()==3)a('input[name="discount"]').attr({disabled:"disabled"});else a('input[name="discount"]').removeAttr("disabled")});a(".addimagesize,.addfinish,.addcoloropt,.addshipping,.addcolorfilter").click(function(){parent=a(this).parents(".postbox");i=parent.find(".row").length;row=parent.find(".copyrow");type=row.attr("title");clone=row.clone().removeAttr("title").removeClass("copyrow").addClass("row");clone.find("input.name").attr("name",type+"["+i+"][name]");clone.find("input.price").attr("name",type+"["+i+"][price]");clone.find("input.width").attr("name",type+"["+i+"][w]");clone.find("input.height").attr("name",type+"["+i+"][h]");clone.find("input.unit").attr("name",type+"["+i+"][unit]");clone.find("input.code").attr("name",type+"["+i+"][code]");clone.find("select.type").attr("name",type+"["+i+"][type]");clone.find("input.colorize").attr("name",type+"["+i+"][colorize]");clone.find("input.contrast").attr("name",type+"["+i+"][contrast]");clone.find("input.grayscale").attr("name",type+"["+i+"][grayscale]");clone.find("input.brightness").attr("name",type+"["+i+"][brightness]");row.before(clone);return false});if(jQuery().sortable){a(".price-list tbody.sizes,"+".price-list tbody.colors,"+".package-list tbody.packages,"+".price-list tbody.finishes").each(function(){a(this).sortable({axis:"y",cursor:"move",helper:"clone",containment:this,items:"tr:not(.header)",placeholder:"widget-placeholder",stop:function(b,c){i=0;a(this).find(".filler").hide();type=a(this).attr("class").split(" ")[0];a(this).find(".row").each(function(){a(this).find(".unit").attr("name",type+"["+i+"][unit]");a(this).find("input.id").attr("name",type+"["+i+"][ID]");a(this).find("input.code").attr("name",type+"["+i+"][code]");a(this).find("input.name").attr("name",type+"["+i+"][name]");a(this).find("input.price").attr("name",type+"["+i+"][price]");a(this).find("select.type").attr("name",type+"["+i+"][type]");a(this).find("input.count").attr("name",type+"["+i+"][count]");a(this).find("input.downloadable").attr("name",type+"["+i+"][download]");i++})}})})}if(jQuery().draggable){a("#price-list .finish").draggable({helper:"clone",revert:"invalid",connectToSortable:"#price-list-box .finishes"});a("#color_options .color").draggable({helper:"clone",revert:"invalid",connectToSortable:"#price-list-box .colors"});a("#price-list .sizes-list .size, #price-list-package .packages").draggable({helper:"clone",revert:"invalid",connectToSortable:".price-list .sizes"});a("#packages .sizes-list .size").draggable({helper:"clone",revert:"invalid",connectToSortable:".package-list .packages"})}})