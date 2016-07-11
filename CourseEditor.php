<?php
/*Entry point */
if (!defined('MEDIAWIKI')){
    die();
}
if(function_exists('wfLoadExtension')) {
    wfLoadExtension('CourseEditor');
    
    wfWarn( "Deprecated entry point to CourseEditor. Please use wfLoadExtension('CourseEditor').");
    
}
else
{
    die("MediaWiki version 1.25+ is required to use the CourseEditor extension");
}
?>
