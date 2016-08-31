<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorOperations {

  public static function applyCourseOp($courseName, $operation){
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename':
        $sectionName = $value->elementName;
        $newSectionName = $value->newElementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' .$sectionName);
        $newSectionText = "";
        foreach ($chapters as $value) {
          $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $value ."|". $value ."]]\r\n";
        }
        $pageTitle = $courseName . "/" . $sectionName;
        $newPageTitle = $courseName . '/' . $newSectionName;
        $resultMove = CourseEditorUtils::moveWrapper($pageTitle, $newPageTitle, false, true);
        $resultEdit = CourseEditorUtils::editWrapper($newPageTitle, $newSectionText, null, null);
        $result = array('resultMove' => $resultMove, 'resultEdit' => $resultEdit);
      break;
      case 'delete':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $sectionName = $value->elementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' . $sectionName);
        $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
        if(!$title->userCan('delete', $user, 'secure')){
          $resultChapters = true;
          $pageTitle = $courseName . '/' . $sectionName;
          $prependText = "\r\n{{deleteme}}";
          $resultSection = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          foreach ($chapters as $value) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
            $prependText = "\r\n{{deleteme}}";
            $resultChapters = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          }
        }else {
          $resultChapters = true;
          foreach ($chapters as $value) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
            $resultChapters = CourseEditorUtils::deleteWrapper($pageTitle);
          }
          $pageTitle = $courseName . '/' . $sectionName;
          $resultSection = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        $result = array('resultSection' => $resultSection, 'resultEdit' => $resultChapters);
      break;
      case 'add':
        $sectionName = $value->elementName;
        $pageTitle = $courseName . '/' . $sectionName;
        $text =  "";
        $result = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
      break;
      case 'update':
        $newCourseText = "[{{fullurl:Special:CourseEditor|actiontype=editcourse&pagename={{FULLPAGENAMEE}}}} Modifica]\r\n";
        $newSectionsArray = json_decode($value->elementsList);
        foreach ($newSectionsArray as $value) {
          $newCourseText .= "{{Sezione|" . $value ."}}\r\n";
        }
        $categories = CourseEditorUtils::getCategories($courseName);
        foreach ($categories as $category) {
          $newCourseText .= "\r\n[[" . $category['title'] . "]]";
        }
        $result = CourseEditorUtils::editWrapper($courseName, $newCourseText, null, null);
      break;
    }
    return json_encode($result);
  }

  public static function applySectionOp($sectionName, $operation){
    $context = CourseEditorUtils::getRequestContext();
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename':
        $chapterName = $value->elementName;
        $newChapterName = $value->newElementName;
        $from = $sectionName . '/' . $chapterName;
        $to = $sectionName . '/' . $newChapterName;
        $result = CourseEditorUtils::moveWrapper($from, $to, false, true);
      break;
      case 'delete':
        $user = $context->getUser();
        $chapterName = $value->elementName;
        $title = Title::newFromText($sectionName . '/' . $chapterName, $defaultNamespace=NS_MAIN);
        if(!$title->userCan('delete', $user, 'secure')){
          $pageTitle = $sectionName . '/' . $chapterName;
          $prependText = "\r\n{{deleteme}}";
          $result = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
        }else {
          $pageTitle = $sectionName . '/' . $chapterName;
          $result = CourseEditorUtils::deleteWrapper($pageTitle);
        }
      break;
      case 'add':
        $chapterName = $value->elementName;
        $pageTitle = $sectionName . '/' . $chapterName;
        $text =  "";
        $result = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
      break;
      case 'update':
        $newSectionText = "";
        $newChaptersArray = json_decode($value->elementsList);
        foreach ($newChaptersArray as $value) {
          $newSectionText .= "* [[" . $sectionName . "/" . $value ."|". $value ."]]\r\n";
        }
        $result = CourseEditorUtils::editWrapper($sectionName, $newSectionText);
      break;
      case 'purge':
        list($course, $section) = explode("/", $sectionName);
        $result = CourseEditorUtils::purgeWrapper($course);
      break;
    }
    return json_encode($result);
  }
}
