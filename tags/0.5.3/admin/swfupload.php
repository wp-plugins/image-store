<?php

/**
 * support for jqery swf upload
 * 
 * @package Image Store
 * @author Hafid Trujillo
 * @copyright 20010-2011
 * @since 0.5.0
*/

//dont cache file
header( 'Expires: 0');
header( 'Pragma: no-cache' );
header( 'Cache-control: private');
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
header( 'Cache-control: no-cache, no-store, must-revalidate, max-age=0');;

//use to process big images
ini_set('memory_limit', '216M');
ini_set('set_time_limit', '900');
		
if ( !empty( $_FILES ) ) {
	
	$abspath = str_replace( "\\","/", __FILE__ );
	$relpath = getenv( "SCRIPT_NAME");
	$docroot = str_replace( $relpath, "", $abspath ) . '/';
	
	$tempfile 		= $_FILES['Filedata']['tmp_name'];
	$filename 		= str_replace(' ', '-', $_FILES['Filedata']['name'] );
	$targetpath 	= str_replace( '//','/', $docroot . $_REQUEST['folder'] );
	$targetfile 	= $targetpath . '/' . $filename;
	
	if( !file_exists ( $targetpath ) ){
		@mkdir( $targetpath, 0775, true );
	}
	
	if( preg_match( '/(png|jpg|jpeg|gif)$/i', $filename ) ){
		if( !file_exists( $targetfile ) ){
			move_uploaded_file( $tempfile, $targetfile );
			@chmod( $targetfile, 0755 );
			@unlink( tempfile )
			echo $targetfile; return;
		}else{
			@unlink( tempfile )
			echo "x";
			return;
		}
	}
	@unlink( tempfile )
	return;
}
die( );
?>