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

  /**
  * Update the metadata page with new data submitted by the user.
  * Moreover the course root page cache is purged.
  * @param string $operationRequested JSON object with new new data
  * @return string $operation JSON object with the operation requested plus
  * a success field
  */
  public static function manageCourseMetadataOp($operationRequested){
    $operation = json_decode($operationRequested);
    // Get all the params
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

    // Create the page title prepending the namespace
    $pageTitle = MWNamespace::getCanonicalName(NS_COURSEMETADATA) . ':' . $title;
    // Add the new metadata submitted
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

  /**
  * Create the basic metadata page when a new course is created.
  * @param string $title the title of the course
  * @param string $topic the topic of the course
  * @param string $description the description of the course
  * @return $apiResult the result of the edit
  */
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

  /**
  * All the possible small tasks to publish a course from a userpage.
  * This function is used also for course renaming because "techinally" the
  * tasks are the same.
  * @param string $operation JSON object with the action requested and the
  * params needed
  * @return string $operationObj JSON object with the request and the a success
  * field
  */
  public static function applyPublishCourseOp($operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $operationObj = json_decode($operation);
    switch ($operationObj->action) {
      case 'rename-move-task':
      CourseEditorUtils::moveElement($operationObj);
      break;
      case 'rename-update-task':
      CourseEditorUtils::updateLevelTwo($operationObj);
      break;
      case 'move-root':
      CourseEditorUtils::moveElement($operationObj);
      break;
      case 'remove-ready-texts':
      $title = Title::newFromText($operationObj->elementName);
      $page = WikiPage::factory($title);
      $pageText = $page->getText();
      $category = "<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['ReadyToBePublished'] ."]]</noinclude>";
      $template = "{{". $wgCourseEditorTemplates['ReadyToBePublished'] ."}}";
      $replacedText = str_replace($category, "", $pageText);
      $newPageText = str_replace($template, "", $replacedText);
      $apiResult = CourseEditorUtils::editWrapper($title, $newPageText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
      break;
      case 'move-metadata':
      CourseEditorUtils::moveElement($operationObj);
      case 'purge':
      CourseEditorUtils::purgeCache($operationObj);
      break;
      case 'update-collection':
      CourseEditorUtils::updateCollection($operationObj);
      break;
      case 'remove-from-topic-page':
      $title = Title::newFromText($operationObj->elementName);
      $page = WikiPage::factory($title);
      $pageText = $page->getText();
      $replacedText = str_replace("{{". $wgCourseEditorTemplates['Course'] ."|" . $operationObj->courseName . "}}", '', $pageText);
      $apiResult = CourseEditorUtils::editWrapper($title, $replacedText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
      break;
      case 'append-to-topic-page':
      $title = Title::newFromText($operationObj->newElementName);
      $topicCourses = CourseEditorUtils::getTopicCourses($operationObj->newElementName);
      $pageText = $topicCourses . "{{". $wgCourseEditorTemplates['Course'] ."|" . $operationObj->courseName . "}}}}";
      $apiResult = CourseEditorUtils::editWrapper($title, $pageText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
      break;
      case 'update-topic-page':
      $title = Title::newFromText($operationObj->topicName);
      $page = WikiPage::factory($title);
      $pageText = $page->getText();
      $replacedText = str_replace($operationObj->elementName, $operationObj->newElementName, $pageText);
      $apiResult = CourseEditorUtils::editWrapper($title, $replacedText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
      break;
      case 'update-user-page':
      $title = Title::newFromText($wgContLang->getNsText( NS_USER ) . ":" . $operationObj->username);
      $page = WikiPage::factory($title);
      $pageText = $page->getText();
      $replacedText = str_replace(
        "{{". $wgCourseEditorTemplates['Course'] ."|" . $operationObj->elementName . "|" . $operationObj->username . "}}",
        "{{". $wgCourseEditorTemplates['Course'] ."|" . $operationObj->newElementName . "|". $operationObj->username . "}}",
        $pageText
      );
      $apiResult = CourseEditorUtils::editWrapper($title, $replacedText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
      break;
    }
    return json_encode($operationObj);
  }

  /**
  * All the possible small tasks that can be performed on a levelTwo.
  * @param string $operation JSON object with the action requested and the
  * params needed
  * @return string $operationObj JSON object with the request and the a success
  * field
  */
  public static function applyCourseOp($operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $operationObj = json_decode($operation);
    switch ($operationObj->action) {
      case 'rename-move-task':
      CourseEditorUtils::moveElement($operationObj);
      break;
      case 'rename-update-task':
      CourseEditorUtils::updateLevelTwo($operationObj);
      break;
      case 'delete-levelsThree-task':
        $levelTwoName = $operationObj->elementName;
        $levelsThree = CourseEditorUtils::getLevelsThree($levelTwoName);
        if(empty($levelsThree)){
          $operationObj->success = true;
        }else {
          foreach ($levelsThree as $levelThree) {
            $operationObj->elementName = $levelTwoName . '/' . $levelThree;
            CourseEditorUtils::deleteElement($operationObj);
            if ($operationObj->success !== true) {
              break;
            }
          }
        }
      break;
      case 'delete-levelTwo-task':
        CourseEditorUtils::deleteElement($operationObj);
      break;
      case 'add':
      $text =  "\r\n<noinclude>[["
      . $wgContLang->getNsText( NS_CATEGORY )
      . ":". $wgCourseEditorCategories['CourseLevelTwo']
      ."]]</noinclude>";
      CourseEditorUtils::addElement($operationObj, $text);
      break;
      case 'update':
        CourseEditorUtils::updateRoot($operationObj);
      break;
      case 'update-collection':
      CourseEditorUtils::updateCollection($operationObj);
      break;
    }
    return json_encode($operationObj);
  }

  /**
  * All the possible small tasks that can be performed on a course.
  * @param string $operation JSON object with the action requested and the
  * params needed
  * @return string $operationObj JSON object with the request and the a success
  * field
  */
  public static function applyLevelTwoOp($operation){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $context = CourseEditorUtils::getRequestContext();
    $operationObj = json_decode($operation);
    switch ($operationObj->action) {
      // Not yet implemented
      /*case 'move':
        $chapterName = $value->elementName;
        $newSectionName = $value->newElementName;
        $from = $sectionName . '/' . $chapterName;
        $to = $newSectionName . '/' . $chapterName;
        $apiResult = CourseEditorUtils::moveWrapper($from, $to);
        $explodedString = explode("/", $sectionName);
        $courseName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
        $textToAppend = "* [[" .$courseName . "/" . $newSectionName. "/" . $chapterName ."|". $chapterName ."]]\r\n";
        CourseEditorUtils::editWrapper($courseName . '/' . $newSectionName, null, null, $textToAppend);
        CourseEditorUtils::setSingleOperationSuccess($value, $apiResult);*/
      case 'rename':
      CourseEditorUtils::moveElement($operationObj);
      break;
      case 'delete':
      CourseEditorUtils::deleteElement($operationObj);
      break;
      case 'add':
      CourseEditorUtils::addElement($operationObj);
      break;
      case 'update':
      CourseEditorUtils::updateLevelTwo($operationObj);
      break;
      case 'purge':
      $explodedString = explode("/", $operationObj->elementName);
      $operationObj->elementName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
      CourseEditorUtils::purgeCache($operationObj);
      break;
      case 'update-collection':
      $explodedString = explode("/", $operationObj->elementName);
      $operationObj->elementName = (sizeof($explodedString) > 2 ? $explodedString[0] . "/" . $explodedString[1] : $explodedString[0]);
      CourseEditorUtils::updateCollection($operationObj);
      break;
    }
    return json_encode($operationObj);
  }
}
