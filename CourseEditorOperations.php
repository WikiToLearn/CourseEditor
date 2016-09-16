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
    //FIXME Must be a serius return object and error handling
    return "ok";
  }

  public static function manageCourseMetadataOp($operationRequested){
    $operation = json_decode($operationRequested);
    $params = $operation->params;
    $title = $params[0];
    $topic = $params[1];
    $description = $params[2];
    $externalReferences = $params[3];
    $isImported = $params[4];
    $originalAuthors =  $params[5];
    $isReviewed = $params[6];
    $reviewedOn =  $params[7];

    $pageTitle = MWNamespace::getCanonicalName(NS_COURSEMETADATA) . ':' . $title;
    $metadata = "<section begin=topic />" . $topic . "<section end=topic />\r\n";
    if($description !== '' && $description !== null){
      $metadata .= "<section begin=description />" . $description . "<section end=description />\r\n";
    }
    if($externalReferences !== '' && $externalReferences !== null){
      $metadata .= "<section begin=externalreferences />" . $externalReferences . "<section end=externalreferences />\r\n";
    }
    if($isImported !== false || $isReviewed !== false){
      $metadata .= "<section begin=hasbadge />" . true . "<section end=hasbadge />\r\n";
      if($isImported !== false){
        $metadata .= "<section begin=isimported />" . $isImported . "<section end=isimported />\r\n";
        $metadata .= "<section begin=originalauthors />" . $originalAuthors . "<section end=originalauthors />\r\n";
      }
      if($isReviewed !== false){
        $metadata .= "<section begin=isreviewed />" . $isReviewed . "<section end=isreviewed />\r\n";
        $metadata .= "<section begin=reviewedon />" . $reviewedOn . "<section end=reviewedon />\r\n";
      }
    }
    $resultCreateMetadataPage = CourseEditorUtils::editWrapper($pageTitle, $metadata , null, null);
    CourseEditorUtils::setSingleOperationSuccess($operation, $resultCreateMetadataPage);
    return json_encode($operation);
    //FIXME Return an object with results in order to display error to the user
  }

  private function createBasicCourseMetadata($topic, $title, $description){
    $topic = ($topic ===  null ? $title : $topic);
    $pageTitle = MWNamespace::getCanonicalName(NS_COURSEMETADATA) . ':' . $title;
    $metadata = "<section begin=topic />" . $topic . "<section end=topic />\r\n";
    if($description !== '' && $description !== null){
      $metadata .= "<section begin=description />" . $description . "<section end=description />\r\n";
    }
    $resultCreateMetadataPage = CourseEditorUtils::editWrapper($pageTitle, $metadata , null, null);
    //FIXME Return an object with results in order to display error to the user
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
        self::createPrivateCourse($pageTitle, null, $title, $description);
      }else{
        self::createPublicCourseFromDepartment($pageTitle, $department, $title, $description);
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
      $namespaceCostant = ($compareResult === 0 ? NS_COURSE : NS_USER);
      $pageTitle = MWNamespace::getCanonicalName($namespaceCostant) . ':';
      if($namespaceCostant == NS_USER){
        self::createPrivateCourse($pageTitle, $topic, $title, $description);
      }else{
        self::createPublicCourseFromTopic($pageTitle, $topic, $title, $description);
      }
    }
  }

  private function createPrivateCourse($pageTitle, $topic, $title, $description){
    $context = CourseEditorUtils::getRequestContext();
    $user = $context->getUser();
    $userPage = $pageTitle . $user->getName();
    $titleWithUser = $user->getName() . '/' . $title;
    $pageTitle = $userPage . "/" . $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse|}}", null, null);
    $resultCreateMetadataPage = self::createBasicCourseMetadata($topic, $titleWithUser, $description);
    $textToPrepend = "{{Course|" . $title . "|" . $user->getName() . "}}";
    $resultPrependToUserPage = CourseEditorUtils::editWrapper($userPage, null, $textToPrepend, null);
    //FIXME Return an object with results in order to display error to the user
  }

  private function createPublicCourseFromTopic($pageTitle, $topic, $title, $description){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse|}}", null, null);
    $topicCourses = CourseEditorUtils::getTopicCourses($topic);
    $text = $topicCourses . "{{Course|" . $title . "}}}}";
    $resultCreateMetadataPage = self::createBasicCourseMetadata($topic, $title, $description);
    $resultAppendToTopic = CourseEditorUtils::editWrapper($topic, $text, null, null);
    //FIXME Return an object with results in order to display error to the user

  }

  private function createPublicCourseFromDepartment($pageTitle, $department, $title, $description){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse|}}", null, null);
    $text = "{{Topic|" . "{{Course|" . $title . "}}}}";
    $listElementText =  "\r\n* [[" . $title . "]]";
    $resultCreateMetadataPage = self::createBasicCourseMetadata(null, $title, $description);
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
        $newCourseText = "{{CCourse|\r\n";
        $newSectionsArray = json_decode($value->elementsList);
        foreach ($newSectionsArray as $section) {
          $newCourseText .= "{{SSection|" . $section ."}}\r\n";
        }
        $newCourseText .= "}}";
        $categories = CourseEditorUtils::getCategories($courseName);
        if(sizeof($categories) > 0){
          foreach ($categories as $category) {
            $newCourseText .= "\r\n[[" . $category['title'] . "]]";
          }
        }
        $apiResult = CourseEditorUtils::editWrapper($courseName, $newCourseText, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update-collection':
      $apiResult = CourseEditorUtils::updateCollection($courseName);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'fix-link':
        $targetPage = $value->elementName;
        $linkToReplace = $value->linkToReplace;
        list($course, $section, $chapter) = explode('/', $linkToReplace);
        $replacement = $course . '/' . $value->replacement . '/' . $chapter;
        $title = Title::newFromText($targetPage);
        $page = WikiPage::factory( $title );
        $content = $page->getContent( Revision::RAW );
        $text = ContentHandler::getContentText( $content );
        str_replace($linkToReplace, $replacement, $text);
        $apiResult = CourseEditorUtils::editWrapper($targetPage, $text, null, null);
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
        $explodedString = explode("/", $sectionName);
        $pageToBePurged = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
        $apiResult = CourseEditorUtils::purgeWrapper($pageToBePurged);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update-collection':
      $explodedString = explode("/", $sectionName);
      $courseName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
      $apiResult = CourseEditorUtils::updateCollection($courseName);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
    }
    return json_encode($value);
  }
}
