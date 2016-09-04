<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorOperations {

  public static function createCourseOp($operationRequested){
    $operation = json_decode($operationRequested);
    switch ($operation->type) {
      case 'fromTopic':
        self::createNewCourseFromTopic($operation->params);
      break;
      case 'fromDepartment':
        self::createNewCourseFromDepartment($operation->params);
      break;
    }
    return "ok";
  }

  private function createNewCourseFromDepartment($params){
    $department = $params[0];
    $title = $params[1];
    $description = $params[2];
    $namespace = $params[3];

    if($department != null && $title != null && $namespace != null){
      $compareResult = strcmp($namespace, 'NS_COURSE');
      $namespaceCostant = ($compareResult == 0 ? NS_COURSE : NS_USER);
      $pageTitle = MWNamespace::getCanonicalName($namespaceCostant) . ':';
      if($namespaceCostant == NS_USER){
        self::createPrivateCourse($pageTitle, $title);
      }else{
        self::createPublicCourseFromDepartment($pageTitle, $department, $title);
      }
    }
  }

  private function createNewCourseFromTopic($params){
    $topic = $params[0];
    $title = $params[1];
    $description = $params[2];
    $namespace = $params[3];
    if($topic != null && $title != null && $namespace != null){
      $compareResult = strcmp($namespace, 'NS_COURSE');
      $namespaceCostant = ($compareResult == 0 ? NS_COURSE : NS_USER);
      $pageTitle = MWNamespace::getCanonicalName($namespaceCostant) . ':';
      if($namespaceCostant == NS_USER){
        self::createPrivateCourse($pageTitle, $title);
      }else{
        self::createPublicCourse($pageTitle, $topic, $title);
      }
    }
  }

  private function createPrivateCourse($pageTitle, $title){
    $context = CourseEditorUtils::getRequestContext();
    $user = $context->getUser();
    $pageTitle .=  $user->getName() . '/' . $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse}}", null, null);
    //FIXME Return an object with results in order to display error to the user
  }

  private function createPublicCourseFromTopic($pageTitle, $topic, $title){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse}}", null, null);
    $topicCourses = CourseEditorUtils::getTopicCourses($topic);
    $text = $topicCourses . "{{Course|" . $title;
    /*if(sizeof($topicCourse) > 0){
      $text = $topicCourses[1][0] . "{{Course|" . $title;
    }else {
      $text = "{{Topic|" . "{{Course|" . $title;
    }*/
    if($description !== ""){
      $text .= "|" . $description . "}}}}";
    }else {
      $text .= "}}}}";
    }
    $resultAppendToTopic = CourseEditorUtils::editWrapper($topic, $text, null, null);
    //FIXME Return an object with results in order to display error to the user

  }

  private function createPublicCourseFromDepartment($pageTitle, $department, $title){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse}}", null, null);
    $text = "{{Topic|" . "{{Course|" . $title;
    if($description !== ""){
      $text .= "|" . $description . "}}}}";
    }else {
      $text .= "}}}}";
    }
    $listElementText =  "\r\n* [[" . $title . "]]";
    $resultAppendToTopic = CourseEditorUtils::editWrapper($title, $text, null, null);
    $resultAppendToDepartment = CourseEditorUtils::editWrapper($department, null, null, $listElementText);
    //FIXME Return an object with results in order to display error to the user

  }


  public static function applyCourseOp($courseName, $operation){
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename':
        $sectionName = $value->elementName;
        $newSectionName = $value->newElementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' .$sectionName);
        $newSectionText = "";
        foreach ($chapters as $chapter) {
          $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $chapter ."|". $chapter ."]]\r\n";
        }
        $pageTitle = $courseName . "/" . $sectionName;
        $newPageTitle = $courseName . '/' . $newSectionName;
        $resultMove = CourseEditorUtils::moveWrapper($pageTitle, $newPageTitle);
        $resultEdit = CourseEditorUtils::editWrapper($newPageTitle, $newSectionText, null, null);
        $apiResult = array($resultMove, $resultEdit);
        CourseEditorUtils::setComposedOperationSuccess($value, $apiResult);
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
          foreach ($chapters as $chapter) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $chapter;
            $prependText = "\r\n{{DeleteMe}}";
            $resultChapters = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          }
        }else {
          $resultChapters = true;
          foreach ($chapters as $chapter) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $chapter;
            $resultChapters = CourseEditorUtils::deleteWrapper($pageTitle);
          }
          $pageTitle = $courseName . '/' . $sectionName;
          $resultSection = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        $apiResult = array($resultSection, $resultChapters);
        CourseEditorUtils::setComposedOperationSuccess($value, $apiResult);
      break;
      case 'add':
        $sectionName = $value->elementName;
        $pageTitle = $courseName . '/' . $sectionName;
        $text =  "";
        $apiResult = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update':
        $newCourseText = "{{CCourse}}\r\n";
        $newSectionsArray = json_decode($value->elementsList);
        foreach ($newSectionsArray as $section) {
          $newCourseText .= "{{SSection|" . $section ."}}\r\n";
        }
        $categories = CourseEditorUtils::getCategories($courseName);
        if(sizeof($categories) > 0){
          foreach ($categories as $category) {
            $newCourseText .= "\r\n[[" . $category['title'] . "]]";
          }
        }
        $apiResult = CourseEditorUtils::editWrapper($courseName, $newCourseText, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
    }
    return json_encode($value);
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
        $apiResult = CourseEditorUtils::moveWrapper($from, $to);
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
