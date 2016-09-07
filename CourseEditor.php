<?php
/*Entry point */
if (!defined('MEDIAWIKI')){
    die();
}

if(function_exists('wfLoadExtension')) {
    wfLoadExtension('CourseEditor');
    $wgMessagesDirs['CourseEditor'] = __DIR__ . '/i18n';
    $wgExtensionMessagesFiles['CourseEditorAlias'] = __DIR__ . '/CourseEditor.alias.php';
    return;
}
else
{
    die("MediaWiki version 1.25+ is required to use the CourseEditor extension");
}
?>
