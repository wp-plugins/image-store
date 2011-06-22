<?php

/**
 *support for jqery swf upload
 *
 *@package Image Store
 *@author Hafid Trujillo
 *@copyright 20010-2011
 *@since 0.5.0
*/

//dont cache file
header('Expires:0');
header('Pragma:no-cache');
header('Cache-control:private');
header('Last-Modified:'.gmdate('D,d M Y H:i:s').' GMT');
header('Cache-control:no-cache,no-store,must-revalidate,max-age=0');;

//use to process big images
ini_set('memory_limit','256M');
ini_set('set_time_limit','1000');
		
if(!empty($_FILES)){
	
	$relpath = getenv("SCRIPT_NAME");
	$abspath = str_replace("\\","/",__FILE__);
	$docroot = str_replace($relpath,"",$abspath).'/';
	$special_chars = array("?","[","]","/","\\","=","<",">",":",";",",","'","\"","&","$","#","*","(",")","|","~","`","!","{","}",chr(0));

	$tempfile 		= $_FILES['Filedata']['tmp_name'];
	$filename 		= str_replace($special_chars,'',$_FILES['Filedata']['name']);
	$filename 		= preg_replace('/[\s-]+/','-',$filename);
	$targetpath 	= str_replace(array('//','/wp-admin/'),'/',$docroot.$_REQUEST['folder']);
	$targetfile 	= $targetpath.'/'.$filename;
	
	if(!file_exists($targetpath)){
		@mkdir($targetpath,0775,true);
	}
	
	if(preg_match('/(png|jpg|jpeg|gif)$/i',$filename)){
		if(!file_exists($targetfile)){
			move_uploaded_file($tempfile,$targetfile);
			@chmod($targetfile,0775);
			@unlink(tempfile);
			echo $targetfile; return;
		}else{
			@unlink(tempfile);
			echo "x";
			return;
		}
	}
	@unlink(tempfile);
	return;
}
die();
?>