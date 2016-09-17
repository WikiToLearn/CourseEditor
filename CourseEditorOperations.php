<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorOperations {

  public static function createCourseOp($operationRequested){
    $operation = json_decode($operationRequested);
    switch ($operation->type) {
      case 'fromTopic':
        $result = self::createNewCourseFromTopic($operation);
        CourseEditorUtils::setComposedOperationSuccess($operation, $result);
      break;
      case 'fromDepartment':
        $result = self::createNewCourseFromDepartment($operation);
        CourseEditorUtils::setComposedOperationSuccess($operation, $result);
      break;
    }
    return json_encode($operation);
  }

  public static function manageCourseMetadataOp($operationRequested){
    $operation = json_decode($operationRequested);
    $params = $operation->params;
    $title = $params[0];
    $topic = $params[1];
    $description = $params[2];
    $bibliography = $params[3];
    $exercises = $params[4];
    $books = $params[5];
    $externalReferences = $params[6];
    $isImported = $params[7];
    $originalAuthors = $params[8];
    $isReviewed = $params[9];
    $reviewedOn =  $params[10];

    $pageTitle = MWNamespace::getCanonicalName(NS_COURSEMETADATA) . ':' . $title;
    $metadata = "<section begin=topic />" . $topic . "<section end=topic />\r\n";
    if($description !== '' && $description !== null){
      $metadata .= "<section begin=description />" . $description . "<section end=description />\r\n";
    }
    if($bibliography !== '' && $bibliography !== null){
      $metadata .= "<section begin=bibliography />" . $bibliography . "<section end=bibliography />\r\n";
    }
    if($exercises !== '' && $exercises !== null){
      $metadata .= "<section begin=exercises />" . $exercises . "<section end=exercises />\r\n";
    }
    if($books !== '' && $books !== null){
      $metadata .= "<section begin=books />" . $books . "<section end=books />\r\n";
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
  }

  private function createBasicCourseMetadata($topic, $title, $description){
    $topic = ($topic ===  null ? $title : $topic);
    $pageTitle = MWNamespace::getCanonicalName(NS_COURSEMETADATA) . ':' . $title;
    $metadata = "<section begin=topic />" . $topic . "<section end=topic />\r\n";
    if($description !== '' && $description !== null){
      $metadata .= "<section begin=description />" . $description . "<section end=description />\r\n";
    }
    $apiResult = CourseEditorUtils::editWrapper($pageTitle, $metadata , null, null);
    return $apiResult;
  }

  private function createNewCourseFromDepartment(&$operation){
    $params = $operation->params;
    $department = $params[0];
    $title = $params[1];
    $description = $params[2];
    $namespace = $params[3];

    if($department != null && $title != null && $namespace != null){
      $compareResult = strcmp($namespace, 'NS_COURSE');
      $namespaceCostant = ($compareResult == 0 ? NS_COURSE : NS_USER);
      $pageTitle = MWNamespace::getCanonicalName($namespaceCostant) . ':';
      if($namespaceCostant == NS_USER){
        $result = self::createPrivateCourse($pageTitle, $topic, $title, $description);
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $userPage = $pageTitle . $user->getName();
        $operation->courseTitle = $userPage . '/' . $title;
      }else{
        $result = self::createPublicCourseFromDepartment($pageTitle, $department, $title, $description);
        $operation->courseTitle = $pageTitle . $title;
      }
    }

    return $result;
  }

  private function createNewCourseFromTopic(&$operation){
    $params = $operation->params;
    $topic = $params[0];
    $title = $params[1];
    $description = $params[2];
    $namespace = $params[3];
    if($topic != null && $title != null && $namespace != null){
      $compareResult = strcmp($namespace, 'NS_COURSE');
      $namespaceCostant = ($compareResult === 0 ? NS_COURSE : NS_USER);
      $pageTitle = MWNamespace::getCanonicalName($namespaceCostant) . ':';
      if($namespaceCostant == NS_USER){
        $result = self::createPrivateCourse($pageTitle, $topic, $title, $description);
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $userPage = $pageTitle . $user->getName();
        $operation->courseTitle = $userPage . '/' . $title;
      }else{
        $result = self::createPublicCourseFromTopic($pageTitle, $topic, $title, $description);
        $operation->courseTitle = $pageTitle . $title;
      }
    }
    return $result;
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
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultPrependToUserPage);

  }

  private function createPublicCourseFromTopic($pageTitle, $topic, $title, $description){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse|}}", null, null);
    $topicCourses = CourseEditorUtils::getTopicCourses($topic);
    $text = $topicCourses . "{{Course|" . $title . "}}}}";
    $resultCreateMetadataPage = self::createBasicCourseMetadata($topic, $title, $description);
    $resultAppendToTopic = CourseEditorUtils::editWrapper($topic, $text, null, null);
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultAppendToTopic);
  }

  private function createPublicCourseFromDepartment($pageTitle, $department, $title, $description){
    $pageTitle .= $title;
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, "{{CCourse|}}", null, null);
    $text = "{{Topic|" . "{{Course|" . $title . "}}}}";
    $listElementText =  "\r\n* [[" . $title . "]]";
    $resultCreateMetadataPage = self::createBasicCourseMetadata(null, $title, $description);
    $resultAppendToTopic = CourseEditorUtils::editWrapper($title, $text, null, null);
    $resultAppendToDepartment = CourseEditorUtils::editWrapper($department, null, null, $listElementText);
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultAppendToTopic, $resultAppendToDepartment);
  }


  public static function applyCourseOp($courseName, $operation){
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename-move-task':
        $sectionName = $value->elementName;
        $newSectionName = $value->newElementName;
        $pageTitle = $courseName . "/" . $sectionName;
        $newPageTitle = $courseName . '/' . $newSectionName;
        $apiResult = CourseEditorUtils::moveWrapper($pageTitle, $newPageTitle);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'rename-update-task':
        $sectionName = $value->elementName;
        $newSectionName = $value->newElementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' .$newSectionName);
        $newSectionText = "";
        foreach ($chapters as $chapter) {
          $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $chapter ."|". $chapter ."]]\r\n";
        }
        $newPageTitle = $courseName . '/' . $newSectionName;
        $apiResult = CourseEditorUtils::editWrapper($newPageTitle, $newSectionText, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete-chapters-task':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $sectionName = $value->elementName;
        $chapters = CourseEditorUtils::getChapters($courseName . '/' . $sectionName);
        $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
        $pageTitle = $courseName . '/' . $sectionName;
        if(!$title->userCan('delete', $user, 'secure')){
          $prependText = "\r\n{{DeleteMe}}";
          foreach ($chapters as $chapter) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $chapter;
            $prependText = "\r\n{{DeleteMe}}";
            $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          }
        }else {
          foreach ($chapters as $chapter) {
            $pageTitle = $courseName . '/' . $sectionName . '/' . $chapter;
            $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
          }
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete-section-task':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $sectionName = $value->elementName;
        $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
        $pageTitle = $courseName . '/' . $sectionName;
        if(!$title->userCan('delete', $user, 'secure')){
          $prependText = "\r\n{{DeleteMe}}";
          $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
        }else {
          $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
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
      /*case 'fix-link':
        $targetPage = $value->elementName;
        $linkToReplace = $value->linkToReplace;
        list($course, $section, $chapter) = explode('/', $linkToReplace);
        $replacement = $course . '/' . $value->replacement . '/' . $chapter;
        $title = Title::newFromText($targetPage);
        $page = WikiPage::factory( $title );
        $content = $page->getContent( Revision::RAW );
        $text = ContentHandler::getContentText( $content );
        $newText = str_replace(str_replace(' ', '_', $linkToReplace), $replacement, $text);
        $apiResult = CourseEditorUtils::editWrapper($targetPage, $newText, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
        $value->text = $newText;
      break;*/
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
