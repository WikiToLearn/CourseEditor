<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorOperations {

  /**
  * Add a category to the course root page to be checked by a bot and a template
  * that display a 'Ready to be published' message.
  * IDEA: should be implemented an Echo notification
  * @param string $operationRequested JSON object with operation type and all
  * params used to public the course like the title
  * @return string $operation JSON object with all the sended params plus
  * a success field
  */
  public static function publishCourseOp($operationRequested){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $operation = json_decode($operationRequested);
    $title = Title::newFromText($operation->courseName);
    $template = "{{". $wgCourseEditorTemplates['ReadyToBePublished'] ."}}";
    $category = "<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['ReadyToBePublished'] ."]]</noinclude>";
    $result = CourseEditorUtils::editWrapper($title, null, $template, $category);
    CourseEditorUtils::setSingleOperationSuccess($operation, $result);
    return json_encode($operation);
  }

  /**
  * Remove the publish category and templte from the course root page
  * @param string $operationRequested JSON object with operation type and all
  * params used to public the course like the title
  * @return string $operation JSON object with all the sended params plus
  * a success field
  */
  public static function undoPublishCourseOp($operationRequested){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $operation = json_decode($operationRequested);
    $title = Title::newFromText($operation->courseName);
    $page = WikiPage::factory($title);
    $pageText = $page->getText();
    $category = "<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['ReadyToBePublished'] ."]]</noinclude>";
    $template = "{{". $wgCourseEditorTemplates['ReadyToBePublished'] ."}}";
    $replacedText = str_replace($category, "", $pageText);
    $newPageText = str_replace($template, "", $replacedText);
    $result = CourseEditorUtils::editWrapper($title, $newPageText, null, null);
    CourseEditorUtils::setSingleOperationSuccess($operation, $result);
    $operation->newPageText = $newPageText;
    return json_encode($operation);
  }

  /**
  * Like a Façade. It's an entrypoint for course create process
  * independently if the course is private/public etc.
  * @param string $operationRequested JSON object with operation type and all
  * params used to create the course (name, topic, ...)
  * @return string $operation JSON object with all the sended params plus
  * a success field and the course complete title(with namespace)
  */
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
    $params = $operation->params;;
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
    $resultPurgeCourse = CourseEditorUtils::purgeWrapper($pageTitle);
    CourseEditorUtils::setComposedOperationSuccess($operation, [$resultCreateMetadataPage, $resultPurgeCourse]);
    return json_encode($operation);
  }

  private function createBasicCourseMetadata($topic, $title, $description){
    //Remove username from title (if present) to be used as topic if $topic is null
    $explodedString = explode('/', $title, 2);
    $titleNoUser = (sizeof($explodedString) === 1) ? $explodedString[0] : $explodedString[1] ;
    $topic = ($topic ===  null ? $titleNoUser : $topic);
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
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $context = CourseEditorUtils::getRequestContext();
    $user = $context->getUser();
    $userPage = $pageTitle . $user->getName();
    $titleWithUser = $user->getName() . '/' . $title;
    $pageTitle = $userPage . "/" . $title;
    $courseText = "{{". $wgCourseEditorTemplates['CourseRoot'] ."|}}\r\n<noinclude>[["
    . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseRoot'] ."]]</noinclude>";
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, $courseText, null, null);
    $resultCreateMetadataPage = self::createBasicCourseMetadata($topic, $titleWithUser, $description);
    $textToPrepend = "{{". $wgCourseEditorTemplates['Course'] ."|" . $title . "|" . $user->getName() . "}}";
    $resultPrependToUserPage = CourseEditorUtils::editWrapper($userPage, null, $textToPrepend, null);
    $resultPurgeCourse = CourseEditorUtils::purgeWrapper($pageTitle);
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultPrependToUserPage, $resultPurgeCourse);

  }

  private function createPublicCourseFromTopic($pageTitle, $topic, $title, $description){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $pageTitle .= $title;
    $courseText = "{{". $wgCourseEditorTemplates['CourseRoot'] ."|}}\r\n<noinclude>[["
    . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseRoot'] ."]]</noinclude>";
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, $courseText, null, null);
    $topicCourses = CourseEditorUtils::getTopicCourses($topic);
    $text = $topicCourses . "{{". $wgCourseEditorTemplates['Course'] ."|" . $title . "}}}}";
    $resultCreateMetadataPage = self::createBasicCourseMetadata($topic, $title, $description);
    $resultAppendToTopic = CourseEditorUtils::editWrapper($topic, $text, null, null);
    $resultPurgeCourse = CourseEditorUtils::purgeWrapper($pageTitle);
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultAppendToTopic, $resultPurgeCourse);
  }

  private function createPublicCourseFromDepartment($pageTitle, $department, $title, $description){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $pageTitle .= $title;
    $courseText = "{{". $wgCourseEditorTemplates['CourseRoot'] ."|}}\r\n<noinclude>[["
    . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseRoot'] ."]]</noinclude>";
    $resultCreateCourse = CourseEditorUtils::editWrapper($pageTitle, $courseText, null, null);
    $text = "{{". $wgCourseEditorTemplates['Topic'] ."|" . "{{". $wgCourseEditorTemplates['Course'] ."|" . $title . "}}}}";
    $listElementText =  "\r\n* [[" . $title . "]]";
    $resultCreateMetadataPage = self::createBasicCourseMetadata(null, $title, $description);
    $resultAppendToTopic = CourseEditorUtils::editWrapper($title, $text, null, null);
    $resultAppendToDepartment = CourseEditorUtils::editSectionWrapper($department, null, null, $listElementText);
    $resultPurgeCourse = CourseEditorUtils::purgeWrapper($pageTitle);
    return array($resultCreateCourse, $resultCreateMetadataPage, $resultAppendToTopic, $resultAppendToDepartment, $resultPurgeCourse);
  }

  public static function applyPublishCourseOp($operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename-move-task':
      $levelTwoName = $value->elementName;
      $newLevelTwoName = $value->newElementName;
      $apiResult = CourseEditorUtils::moveWrapper($levelTwoName, $newLevelTwoName, true, false);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'rename-update-task':
      $levelTwoName = $value->elementName;
      $newLevelTwoName = $value->newElementName;
      $levelsThree = CourseEditorUtils::getLevelsThree($newLevelTwoName);
      $newLevelTwoText = "";
      foreach ($levelsThree as $levelThree) {
        $newLevelTwoText .= "* [[" . $newLevelTwoName . "/" . $levelThree ."|". $levelThree ."]]\r\n";
      }
      $newLevelTwoText .= "\r\n<noinclude>[["
      . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseLevelTwo'] ."]]</noinclude>";
      $apiResult = CourseEditorUtils::editWrapper($newLevelTwoName, $newLevelTwoText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'move-root':
      $courseName = $value->elementName;
      $newCourseName = $value->newElementName;
      $apiResult = CourseEditorUtils::moveWrapper($courseName, $newCourseName, false, false);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'remove-ready-texts':
      $title = Title::newFromText($value->elementName);
      $page = WikiPage::factory($title);
      $pageText = $page->getText();
      $category = "<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['ReadyToBePublished'] ."]]</noinclude>";
      $template = "{{". $wgCourseEditorTemplates['ReadyToBePublished'] ."}}";
      $replacedText = str_replace($category, "", $pageText);
      $newPageText = str_replace($template, "", $replacedText);
      $result = CourseEditorUtils::editWrapper($title, $newPageText, null, null);
      break;
      case 'move-metadata':
      $metadataPage = $value->elementName;
      $newMetadataPage = $value->newElementName;
      $apiResult = CourseEditorUtils::moveWrapper($metadataPage, $newMetadataPage, false, false);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update-collection':
      $apiResult = CourseEditorUtils::updateCollection($value->elementName);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
    }
    return json_encode($value);
  }

  public static function applyCourseOp($courseName, $operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $value = json_decode($operation);
    switch ($value->action) {
      case 'rename-move-task':
        $levelTwoName = $value->elementName;
        $newLevelTwoName = $value->newElementName;
        $pageTitle = $courseName . "/" . $levelTwoName;
        $newPageTitle = $courseName . '/' . $newLevelTwoName;
        $apiResult = CourseEditorUtils::moveWrapper($pageTitle, $newPageTitle);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'rename-update-task':
        $levelTwoName = $value->elementName;
        $newLevelTwoName = $value->newElementName;
        $levelsThree = CourseEditorUtils::getLevelsThree($courseName . '/' .$newLevelTwoName);
        $newLevelTwoText = "";
        foreach ($levelsThree as $levelThree) {
          $newLevelTwoText .= "* [[" . $courseName . "/" . $newLevelTwoName . "/" . $levelThree ."|". $levelThree ."]]\r\n";
        }
        $newLevelTwoText .= "\r\n<noinclude>[["
        . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseLevelTwo'] ."]]</noinclude>";
        $newPageTitle = $courseName . '/' . $newLevelTwoName;
        $apiResult = CourseEditorUtils::editWrapper($newPageTitle, $newLevelTwoText, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete-levelsThree-task':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $levelTwoName = $value->elementName;
        $levelsThree = CourseEditorUtils::getLevelsThree($courseName . '/' . $levelTwoName);
        $title = Title::newFromText( $courseName . '/' . $levelTwoName, $defaultNamespace=NS_MAIN );
        $pageTitle = $courseName . '/' . $levelTwoName;
        if(!$title->userCan('delete', $user, 'secure')){
          $prependText = "\r\n{{". $wgCourseEditorTemplates['DeleteMe'] ."}}";
          foreach ($levelsThree as $levelThree) {
            $pageTitle = $courseName . '/' . $levelTwoName . '/' . $levelThree;
            $prependText = "\r\n{{". $wgCourseEditorTemplates['DeleteMe'] ."}}";
            $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
          }
        }else {
          foreach ($levelsThree as $levelThree) {
            $pageTitle = $courseName . '/' . $levelTwoName . '/' . $levelThree;
            $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
          }
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete-levelTwo-task':
        $user = CourseEditorUtils::getRequestContext()->getUser();
        $levelTwoName = $value->elementName;
        $title = Title::newFromText( $courseName . '/' . $levelTwoName, $defaultNamespace=NS_MAIN );
        $pageTitle = $courseName . '/' . $levelTwoName;
        if(!$title->userCan('delete', $user, 'secure')){
          $prependText = "\r\n{{". $wgCourseEditorTemplates['DeleteMe'] ."}}";
          $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
        }else {
          $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'add':
        $levelTwoName = $value->elementName;
        $pageTitle = $courseName . '/' . $levelTwoName;
        $text =  "\r\n<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseLevelTwo'] ."]]</noinclude>";
        $apiResult = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update':
        $newCourseText = "{{". $wgCourseEditorTemplates['CourseRoot'] ."|\r\n";
        $newLevelsTwoArray = json_decode($value->elementsList);
        foreach ($newLevelsTwoArray as $levelTwo) {
          $newCourseText .= "{{". $wgCourseEditorTemplates['CourseLevelTwo'] ."|" . $levelTwo ."}}\r\n";
        }
        $newCourseText .= "}}";
        /*$categories = CourseEditorUtils::getCategories($courseName);
        if(sizeof($categories) > 0){
          foreach ($categories as $category) {
            //Remode ReadyToBePublished category if user edit the course structure
            $readyToBePublishedCategory = $wgContLang->getNsText( NS_CATEGORY ) . ":" . $wgCourseEditorCategories['ReadyToBePublished'];
            if (strcmp($category['title'], $readyToBePublishedCategory) != 0) {
              $newCourseText .= "\r\n<noinclude>[[" . $category['title'] . "]]</noinclude>";
            }
          }
        }*/
        $newCourseText .= "\r\n<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":"
        . $wgCourseEditorCategories['CourseRoot']. "]]</noinclude>";
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
        list($course, $levelTwo, $levelThree) = explode('/', $linkToReplace);
        $replacement = $course . '/' . $value->replacement . '/' . $levelThree;
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

  public static function applyLevelTwoOp($levelTwoName, $operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $context = CourseEditorUtils::getRequestContext();
    $value = json_decode($operation);
    switch ($value->action) {
      case 'move':
        $chapterName = $value->elementName;
        $newSectionName = $value->newElementName;
        $from = $sectionName . '/' . $chapterName;
        $to = $newSectionName . '/' . $chapterName;
        $apiResult = CourseEditorUtils::moveWrapper($from, $to);
        $explodedString = explode("/", $sectionName);
        $courseName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
        $textToAppend = "* [[" .$courseName . "/" . $newSectionName. "/" . $chapterName ."|". $chapterName ."]]\r\n";
        CourseEditorUtils::editWrapper($courseName . '/' . $newSectionName, null, null, $textToAppend);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      case 'rename':
        $levelThreeName = $value->elementName;
        $newLevelThreeName = $value->newElementName;
        $from = $levelTwoName . '/' . $levelThreeName;
        $to = $levelTwoName . '/' . $newLevelThreeName;
        $apiResult = CourseEditorUtils::moveWrapper($from, $to);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'delete':
        $user = $context->getUser();
        $levelThreeName = $value->elementName;
        $title = Title::newFromText($levelTwoName . '/' . $levelThreeName, $defaultNamespace=NS_MAIN);
        if(!$title->userCan('delete', $user, 'secure')){
          $pageTitle = $levelTwoName . '/' . $levelThreeName;
          $prependText = "\r\n{{". $wgCourseEditorTemplates['DeleteMe'] ."}}";
          $apiResult = CourseEditorUtils::editWrapper($pageTitle, null, $prependText, null);
        }else {
          $pageTitle = $levelTwoName . '/' . $levelThreeName;
          $apiResult = CourseEditorUtils::deleteWrapper($pageTitle);
        }
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'add':
        $levelThreeName = $value->elementName;
        $pageTitle = $levelTwoName . '/' . $levelThreeName;
        $text =  "";
        $apiResult = CourseEditorUtils::editWrapper($pageTitle, $text, null, null);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update':
        $newLevelTwoText = "";
        $newLevelsThreeArray = json_decode($value->elementsList);
        foreach ($newLevelsThreeArray as $levelThree) {
          $newLevelTwoText .= "* [[" . $levelTwoName . "/" . $levelThree ."|". $levelThree ."]]\r\n";
        }
        $newLevelTwoText .= "\r\n<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":" . $wgCourseEditorCategories['CourseLevelTwo'] ."]]</noinclude>";
        $apiResult = CourseEditorUtils::editWrapper($levelTwoName, $newLevelTwoText);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'purge':
        $explodedString = explode("/", $levelTwoName);
        $pageToBePurged = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
        $apiResult = CourseEditorUtils::purgeWrapper($pageToBePurged);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
      case 'update-collection':
      $explodedString = explode("/", $levelTwoName);
      $courseName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
      $apiResult = CourseEditorUtils::updateCollection($courseName);
      CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);
      break;
    }
    return json_encode($value);
  }
}
