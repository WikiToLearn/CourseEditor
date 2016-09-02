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
        $apiResult = array($resultMove, $resultEdit);
        $result = CourseEditorUtils::setComposedOperationSuccess($value, $apiResult);
      break;
      case 'delete':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $sectionName = $value->elementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' . $sectionName);
        $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
        if(!$title->userCan('delete', $user, 'secure')){
          $resultChapters = true;
          $pageTitle = $courseName . '/' . $sectionName;
          $prependText = "\r\n{{DeleteMe}}";
          $resultSection = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          foreach ($chapters as $value) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
            $prependText = "\r\n{{DeleteMe}}";
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
        $apiResult = array($resultSection, $resultChapters);
        $result = CourseEditorUtils::setComposedOperationSuccess($value, $apiResult);
      break;
      case 'add':
        $sectionName = $value->elementName;
        $pageTitle = $courseName . '/' . $sectionName;
        $text =  "";
        $apiResult = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
        $result = CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update':
        $newCourseText = "{{CCourse}}\r\n";
        $newSectionsArray = json_decode($value->elementsList);
        foreach ($newSectionsArray as $value) {
          $newCourseText .= "{{SSection|" . $value ."}}\r\n";
        }
        $categories = CourseEditorUtils::getCategories($courseName);
        foreach ($categories as $category) {
          $newCourseText .= "\r\n[[" . $category['title'] . "]]";
        }
        $apiResult = CourseEditorUtils::editWrapper($courseName, $newCourseText, null, null);
        $result = CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
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
        $apiResult = CourseEditorUtils::moveWrapper($from, $to, false, true);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete':
        $user = $context->getUser();
        $chapterName = $value->elementName;
        $title = Title::newFromText($sectionName . '/' . $chapterName, $defaultNamespace=NS_MAIN);
        if(!$title->userCan('delete', $user, 'secure')){
          $pageTitle = $sectionName . '/' . $chapterName;
          $prependText = "\r\n{{DeleteMe}}";
          $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
        }else {
          $pageTitle = $sectionName . '/' . $chapterName;
          $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'add':
        $chapterName = $value->elementName;
        $pageTitle = $sectionName . '/' . $chapterName;
        $text =  "";
        $apiResult = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update':
        $newSectionText = "";
        $newChaptersArray = json_decode($value->elementsList);
        foreach ($newChaptersArray as $chapter) {
          $newSectionText .= "* [[" . $sectionName . "/" . $chapter ."|". $chapter ."]]\r\n";
        }
        $apiResult = CourseEditorUtils::editWrapper($sectionName, $newSectionText);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'purge':
        list($course, $section) = explode("/", $sectionName);
        $apiResult = CourseEditorUtils::purgeWrapper($course);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
    }
    return json_encode($value);
  }
}
