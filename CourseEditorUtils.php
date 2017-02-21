<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorUtils {
  private static $requestContext = null;

  public static function getTopics(){
    try {
      $api = new ApiMain(
        new DerivativeRequest(
          self::getRequestContext()->getRequest(),
          array(
            'action' => 'query',
            'list' => 'embeddedin',
            'eititle' => 'Template:Topic',
            'eilimit' => 500
          )
        ),
        true
      );
      $api->execute();
      $results = $api->getResult()->getResultData(null, array('Strip' => 'all'));
      $pages = $results['query']['embeddedin'];
      $topics = array();
      foreach ($pages as $page) {
        array_push($topics, $page['title']);
      }
      sort($topics);
      return $topics;
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  public static function addElement(&$operationObj, $text = ""){
    $elementName = $operationObj->elementName;
    $apiResult = CourseEditorUtils::editWrapper($elementName, $text, null, null);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  public static function moveElement(&$operationObj){
    $levelTwoName = $operationObj->elementName;
    $newLevelTwoName = $operationObj->newElementName;
    $apiResult = CourseEditorUtils::moveWrapper($levelTwoName, $newLevelTwoName, true, false);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  public static function deleteElement(&$operationObj){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $context = CourseEditorUtils::getRequestContext();
    $user = $context->getUser();
    $elementToRemove = $operationObj->elementName;
    $title = Title::newFromText($elementToRemove, $defaultNamespace=NS_MAIN);
    if(!$title->userCan('delete', $user, 'secure')){
      $prependText = "\r\n{{". $wgCourseEditorTemplates['DeleteMe'] ."}}";
      $apiResult = CourseEditorUtils::editWrapper($elementToRemove, null, $prependText, null);
    }else {
      $apiResult = CourseEditorUtils::deleteWrapper($elementToRemove);
    }
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  /**
  * Update a levelTwoName page.
  * Case 1: the method was triggered by a levelTwo rename, so either elementName
  * and newElementName params are set.
  * Case 2: the the method was triggered by a levelTwo change, for example
  * a levelThree was added. In this case newElementName is not set.
  * @param Object $operationObj it could cointain elementName, newElementName and
  * elementsList
  */
  public static function updateLevelTwo(&$operationObj){
    global $wgCourseEditorCategories, $wgContLang;

    $levelTwoName = $operationObj->elementName;
    $newLevelTwoName = (isset($operationObj->newElementName)) ? $operationObj->newElementName : $levelTwoName;
    $levelsThree = (isset($operationObj->elementsList)) ? json_decode($operationObj->elementsList) : CourseEditorUtils::getLevelsThree($newLevelTwoName);
    $newLevelTwoText = "";
    foreach ($levelsThree as $levelThree) {
      $newLevelTwoText .= "* [[" . $newLevelTwoName . "/" . $levelThree ."|". $levelThree ."]]\r\n";
    }
    $newLevelTwoText .= "\r\n<noinclude>[["
    . $wgContLang->getNsText( NS_CATEGORY ) . ":". $wgCourseEditorCategories['CourseLevelTwo'] ."]]</noinclude>";
    $apiResult = CourseEditorUtils::editWrapper($newLevelTwoName, $newLevelTwoText, null, null);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  public static function updateRoot(&$operationObj){
    global $wgCourseEditorTemplates, $wgCourseEditorCategories, $wgContLang;
    $courseName = $operationObj->elementName;
    $newCourseText = "{{". $wgCourseEditorTemplates['CourseRoot'] ."|\r\n";
    $newLevelsTwoArray = json_decode($operationObj->elementsList);
    foreach ($newLevelsTwoArray as $levelTwo) {
      $newCourseText .= "{{". $wgCourseEditorTemplates['CourseLevelTwo'] ."|" . $levelTwo ."}}\r\n";
    }
    $newCourseText .= "}}";
    $newCourseText .= "\r\n<noinclude>[[" . $wgContLang->getNsText( NS_CATEGORY ) . ":"
    . $wgCourseEditorCategories['CourseRoot']. "]]</noinclude>";
    $apiResult = CourseEditorUtils::editWrapper($courseName, $newCourseText, null, null);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  public static function purgeCache(&$operationObj){
    $pageToBePurged = $operationObj->elementName;
    $apiResult = CourseEditorUtils::purgeWrapper($pageToBePurged);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  /**
  * Create/update the collection page of a public course
  * @param Object $operationObj
  */
  public static function updateCollection(&$operationObj) {
    $courseName = $operationObj->elementName;
    list($namespace, $name) = explode(':', $courseName, 2);
    $title = Title::newFromText($courseName, $defaultNamespace=NS_COURSE );
    $namespaceIndex = $title->getNamespace();

    if(MWNamespace::equals($namespaceIndex, NS_USER)){
      self::updateUserCollection($operationObj);
    }else {
      $pageTitle = "Project:" . wfMessage('courseeditor-collection-book-category') ."/" . $name;
      $collectionText = "{{" . wfMessage('courseeditor-collection-savedbook-template') . "
        \n| setting-papersize = a4
        \n| setting-toc = auto
        \n| setting-columns = 1
        \n| setting-footer = yes\n}}\n";
      $collectionText .= "== " . str_replace('_', ' ', $name) . " ==\r\n";
      $levelsTwo = self::getLevelsTwo($courseName);
      foreach ($levelsTwo as $levelTwo) {
        $levelsThree = self::getLevelsThree($courseName . '/' .$levelTwo);
        $collectionText .= ";" . $levelTwo . "\r\n";
        foreach ($levelsThree as $levelThree) {
          $collectionText .= ":[[" . $courseName . "/" . $levelTwo . "/" . $levelThree . "]]\r\n";
        }
      }
      $categoryName = wfMessage('courseeditor-collection-book-category');

  		if ( !$categoryName->isDisabled() ) {
  			$catTitle = Title::makeTitle( NS_CATEGORY, $categoryName );
  			if ( !is_null( $catTitle ) ) {
          $collectionText .= "\n[[" . $catTitle->getPrefixedText() ."|" . $name . "]]";
        }
      }

      $apiResult = self::editWrapper($pageTitle, $collectionText, null, null);
      CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
    }
  }

  /**
  * Create/update the collection page of a private course
  * @param $operationObj
  */
  private static function updateUserCollection(&$operationObj){
    $courseName = $operationObj->elementName;
    list($namespaceAndUser, $title) = explode('/', $courseName, 2);
    $pageTitle = $namespaceAndUser . "/" . wfMessage('courseeditor-collection-book-category') . "/" . $title;
    $collectionText = "{{" . wfMessage('courseeditor-collection-savedbook-template') . "
      \n| setting-papersize = a4
      \n| setting-toc = auto
      \n| setting-columns = 1
      \n| setting-footer = yes\n}}\n";
    $collectionText .= "== " . str_replace('_', ' ', $title). " ==\r\n";
    $levelsTwo = self::getLevelsTwo($courseName);
    foreach ($levelsTwo as $levelTwo) {
      $levelsThree = self::getLevelsThree($courseName . '/' .$levelTwo);
      $collectionText .= ";" . $levelTwo . "\r\n";
      foreach ($levelsThree as $levelThree) {
        $collectionText .= ":[[" . $courseName . "/" . $levelTwo . "/" . $levelThree . "]]\r\n";
      }
    }
    $categoryName = wfMessage('courseeditor-collection-book-category');

		if ( !$categoryName->isDisabled() ) {
			$catTitle = Title::makeTitle( NS_CATEGORY, $categoryName );
			if ( !is_null( $catTitle ) ) {
        $collectionText .= "\n[[" . $catTitle->getPrefixedText() ."|" . $title. "]]";
      }
    }
    $apiResult = self::editWrapper($pageTitle, $collectionText, null, null);
    CourseEditorUtils::setSingleOperationSuccess($operationObj, $apiResult);
  }

  /**
  * Get the metadata of a course
  * @param String $courseName the name of a course
  * @return Array $metadataResult the associative array with the metadata keys
  *  and values
  */
  public static function getMetadata($courseName){
    $title = Title::newFromText($courseName, $defaultNamespace=NS_COURSEMETADATA );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    if($text === ''){
      return null;
    }
    /*
    This regex is used on strings formed like this:
    '<section begin=metadataKey>metadataValue<section end=metadataKey>'
    */
    $regex = "/<section begin=(.*?)\s*\/>(.*?)<section end=.*?\/>/s";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    $metadataResult =  array();
    $metadataKeys =  $matches[1];
    $metadataValues = $matches[2];
    for ($i=0; $i < sizeof($metadataKeys); $i++) {
      $metadataResult[$metadataKeys[$i]] =  $metadataValues[$i];
    }
    return $metadataResult;
  }

  /**
  * Get the courses in a topic page
  * @param String $topic the name of a topic page
  * @return Array $matches the courses within a topic page
  */
  public static function getTopicCourses($topic){
    global $wgCourseEditorTemplates;
    $title = Title::newFromText($topic, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $textNoNewLines = trim(preg_replace('/\n+/', '', $text));
    /*
    This regex is used on strings formed like this (space are trimmed):
    '{{Topic|
       {{CourseTemplateName|Course}}
       {{CourseTemplateName|Course}}
      }}'
    */
    $regex = "/({{" . $wgCourseEditorTemplates['Topic'] . "|.+)}}.*$/";
    preg_match_all($regex, $textNoNewLines, $matches, PREG_PATTERN_ORDER);
    return $matches[1][0];
  }

  /**
  * This method is a workaround (read it HACK) to check the API results.
  * MediaWiki ApiResult object is not "standard" but if an error/exception
  * occurs the result variable is a string.
  */
  public static function setSingleOperationSuccess(&$operation, $result){
    $isSuccess = true;
    if (is_string($result)) {
      $isSuccess = false;
      $operation->error = $result;
    }
    $operation->success = $isSuccess;
  }

  /**
  * This method is a workaround (read it HACK) to check the API results.
  * MediaWiki ApiResult object is not "standard" but if an error/exception
  * occurs the result variable is a string.
  */
  public static function setComposedOperationSuccess(&$operation, $resultsArray){
    $isSuccess = true;
    foreach ($resultsArray as $result) {
      if (is_string($result)) {
        $isSuccess = false;
        $operation->error = $result;
        break;
      }
    }
    $operation->success = $isSuccess;
  }

  /**
  * Utility function that implement the Singleton pattern
  * to get the instance of the requestContext object
  */
  public static function getRequestContext(){
    if(self::$requestContext == null)
    {
      $context = new RequestContext();
      self::$requestContext = $context;
    }
    return self::$requestContext;
  }

  /**
  * Utility wrapper to get the category of a page
  * @param Title $courseName the name of a course
  * @return Array the array with the category titles
  */
  public static function getCategories($courseName){
    try {
      $api = new ApiMain(
        new DerivativeRequest(
          self::getRequestContext()->getRequest(),
          array(
            'action' => 'query',
            'titles' => $courseName,
            'prop' => 'categories'
          )
        ),
        true
      );
      $api->execute();
      $results = $api->getResult()->getResultData(null, array('Strip' => 'all'));
      $page = reset($results['query']['pages']);
      return $page['categories'];
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Utility to get the levelsThree
  * @param Title $courseName the name of a course
  * @return Array $matches the levelThree pages
  */
  public static function getLevelsThree($levelTwoName){
    $title = Title::newFromText($levelTwoName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    /*
    This regex is used on string formed like this:
    '*[[LinkToLevelThree|LevelThreeName]]'
    */
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[2];
  }

  /**
  * Utility to get the levelsTwo
  * @param Title $courseName the name of a course
  * @return Array $matches the levelTwo pages
  */
  public static function getLevelsTwo($courseName){
    global $wgCourseEditorTemplates;
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    /*
    This regex is used on string formed like this:
    '{{LevelTwoTemplateName|LevelTwoName}}'
    */
    $regex = "/\{{2}". $wgCourseEditorTemplates['CourseLevelTwo'] ."\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
  }

  /**
  * Utility to get the levelsThree in JSON
  * @param Title $courseName the name of a course
  * @return Array with the levelsTwo
  */
  public static function getLevelsThreeJson($courseName){
    return json_encode(self::getLevelsThree($courseName));
  }

  /**
  * Utility to get the levelsTwo in JSON
  * @param Title $courseName the name of a course
  * @return Array with the levelsTwo
  */
  public static function getLevelsTwoJson($courseName){
    return json_encode(self::getLevelsTwo($courseName));
  }

  public static function getCourseTree($courseName){
    $tree = array(
      'root' => $courseName,
      'levelsTwo' => array(),
      'levelsThree' => array()
    );

    $levelsTwo = self::getLevelsTwo($courseName);
    //array_walk($levelsTwo, array('self', 'buildFullPageName'), $courseName);
    $tree['levelsTwo'] = $levelsTwo;

    foreach ($levelsTwo as $level) {
      $levelsThree = self::getLevelsThree($courseName . '/' . $level);
      //array_walk($levelsThree, array('self', 'buildFullPageName'), $level);
      array_push($tree['levelsThree'], $levelsThree);
    }

    return json_encode($tree);
  }

  /*public static function getCourseTree($courseName){
    $tree = array(
      'root' => $courseName,
      'levelsTwo' => array(),
      'levelsThree' => array()
    );

    $levelsTwo = self::getLevelsTwo($courseName);
    array_walk($levelsTwo, array('self', 'buildFullPageName'), $courseName);
    $tree['levelsTwo'] = $levelsTwo;

    foreach ($tree['levelsTwo'] as $level) {
      $levelsThree = self::getLevelsThree($level);
      array_walk($levelsThree, array('self', 'buildFullPageName'), $level);
      array_push($tree['levelsThree'], $levelsThree);
    }

    return json_encode($tree);
  }

  private function buildFullPageName(&$item, $key, $toBePrepended){
    $item = $toBePrepended . '/' . $item;
  }*/

  /**
  * Get the previous and the next pages of a given page
  * @param Title $pageTitle the name of page
  * @return Array with the error element or the next and previous pages
  */
  public static function getPreviousAndNext($pageTitle){
    $subElements = self::getSubCourseElements($pageTitle);
    if(isset($subElements['error'])){
      return $subElements;
    }else {
      return self::buildPreviousAndNext($pageTitle, $subElements);
    }
  }

  /**
  * Utility function to get the array with the levelTwo or levelThree pages
  * @param Title $pageTitle the name of page
  * @return Array $subElements the levelTwo or levelThree pages
  */
  private static function getSubCourseElements($pageTitle) {
    $namespace = $pageTitle->getNamespace();
    //Count subpage levels to define what type of subElements should be gotten
    $levels = substr_count($pageTitle->getText(), "/");
    $basePage = MWNamespace::getCanonicalName($namespace) . ":" . $pageTitle->getBaseText();
    if($namespace === NS_COURSE){
      if($levels === 1){
        $subElements = self::getLevelsTwo($basePage);
      }elseif ($levels === 2) {
        $subElements = self::getLevelsThree($basePage);
      }else {
        return array('error' => "Page levels not valid." );
      }
    }elseif ($namespace === NS_USER) {
      if($levels === 2){
        $subElements = self::getLevelsTwo($basePage);
      }elseif ($levels === 3) {
        $subElements = self::getLevelsThree($basePage);
      }else {
        return array('error' => "Page levels not valid." );
      }
    }else {
      return array('error' => "Namespace not valid." );
    }

    return $subElements;
  }

  /**
  * Utility function to build the array with the next and the previous
  * of a given levelTwo or levelThree
  * @param Title $pageTitle the name of page
  * @param Array $subElements the levelTwo or levelThree elements
  * @return Array $previousAndNext the associative array with the next and the
  * previous page
  */
  private static function buildPreviousAndNext($pageTitle, $subElements){
    $namespace = $pageTitle->getNamespace();
    $basePage = MWNamespace::getCanonicalName($namespace) . ":" . $pageTitle->getBaseText();
    $lastPage = $pageTitle->getSubpageText();
    $previous = null;
    $next = null;
    $previousAndNext = array('previous' => $previous, 'next' => $next);

    //If there are less than 2 subElements it means there is no next/previous
    if(sizeof($subElements) < 2){
      return $previousAndNext;
    }else {
      //$key = array_search($lastPage, $subElements);
      for ($i=0; $i < sizeof($subElements); $i++) {
        $safeTitle = Title::newFromText($subElements[$i]);
        $result = strcmp($lastPage, $safeTitle->getText());
        if($result === 0){
          $key = $i;
          break;
        }
      }
      if($key === sizeof($subElements) - 1){
        $previousAndNext['previous'] = $basePage . "/" . $subElements[$key - 1];
      }else if($key === 0){
        $previousAndNext['next'] = $basePage . "/" . $subElements[$key + 1];
      }else{
        $previousAndNext['previous'] = $basePage . "/" . $subElements[$key - 1];
        $previousAndNext['next'] = $basePage . "/" . $subElements[$key + 1];
      }
      return $previousAndNext;
    }
  }

  /**
  * Utility delete wrapper to delete a page
  * @param String $title the name of page
  * @return Array the result of the API call
  */
  public static function deleteWrapper($title){
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'delete',
            'title'      => $title,
            'token'      => $token
          ),
          true
        ),
        true
      );
      $api->execute();
      return $api->getResult()->getResultData(null, array('Strip' => 'all'));
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Utility purge wrapper to purge the cache of a page/s
  * @param String $titles the title of the page/s
  * @return Array the result of the API call
  */
  public static function purgeWrapper($titles){
    $context = self::getRequestContext();
    try {
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'purge',
            'titles'      => $titles,
            'forcerecursivelinkupdate' => true
          ),
          true
        ),
        true
      );
      $api->execute();
      return $api->getResult()->getResultData(null, array('Strip' => 'all'));
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Utility edit API wrapper to edit or create a page
  * @param String $title the name of page
  * @param String $text the text of the section
  * @param String $textToPrepend the text to prepend to the section
  * @param String $textToAppend the text to append to the section
  * @return Array the result of the API call
  */
  public static function editWrapper($title, $text, $textToPrepend, $textToAppend){
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      //$token = $this->getCsrfToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'edit',
            'title'      => $title,
            'text' => $text,
            // automatically override text
            'prependtext' => $textToPrepend,
            // automatically override text
            'appendtext' => $textToAppend,
            'notminor'   => true,
            'token'      => $token
          ),
          true
        ),
        true
      );
      $api->execute();
      return $api->getResult()->getResultData(null, array('Strip' => 'all'));
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Utility edit section wrapper to edit or create a specific section in a page
  * @param String $title the name of page
  * @param String $text the text of the section
  * @param String $textToPrepend the text to prepend to the section
  * @param String $textToAppend the text to append to the section
  * @return Array the result of the API call
  */
  public static function editSectionWrapper($title, $text, $textToPrepend, $textToAppend){
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $levelTwoExist = self::checkNewTopicsSectionExist($title);
      //Create the section if not exists, otherwise append to it
      if(!$levelTwoExist){
        $api = new ApiMain(
          new DerivativeRequest(
            $context->getRequest(),
            array(
              'action'     => 'edit',
              'title'      => $title,
              'text' => $text,
              'section' => 'new',
              'sectiontitle' => wfMessage('courseeditor-newtopics-section-title'),
              // automatically override text
              'prependtext' => $textToPrepend,
              // automatically override text
              'appendtext' => $textToAppend,
              'notminor'   => true,
              'token'      => $token
            ),
            true
          ),
          true
        );
      }else {
        $api = new ApiMain(
          new DerivativeRequest(
            $context->getRequest(),
            array(
              'action'     => 'edit',
              'title'      => $title,
              'text' => $text,
              'sectiontitle' => wfMessage('courseeditor-newtopics-section-title'),
              // automatically override text
              'prependtext' => $textToPrepend,
              // automatically override text
              'appendtext' => $textToAppend,
              'notminor'   => true,
              'token'      => $token
            ),
            true
          ),
          true
        );
      }
      $api->execute();
      return $api->getResult()->getResultData(null, array('Strip' => 'all'));
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Utility wrapper of the move API.
  * @param String $from the name of page to be moved
  * @param String $to the new name of the page
  * @param boolean $withSubpages (optional) move the subpages also
  * @param boolean $noRedirect (optional) suppress the redirects
  * @return Array the result of the API call
  */
  public static function moveWrapper($from, $to, $withSubpages=true, $noRedirect=false){
    $context = self::getRequestContext();
    $params = array(
      'action' => 'move',
      'from'      => $from,
      'to' => $to,
      'movetalk' => true
    );
    if($withSubpages){
      $params['movesubpages'] = true;
    }
    if ($noRedirect) {
      $params['noredirect'] = true;
    }
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $params['token'] = $token;
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          $params,
          true
        ),
        true
      );
      $api->execute();
      return $api->getResult()->getResultData(null, array('Strip' => 'all'));
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Get the private courses ready to be published using the categorymembers
  * API.
  * @return Array $readyCourses the private courses ready to be published
  */
  public static function getReadyToBePublishedCourses(){
    global $wgCourseEditorCategories;
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'query',
            'list'      => 'categorymembers',
            'cmtitle' => 'Category:' . $wgCourseEditorCategories['ReadyToBePublished']
          )
        )
      );
      $api->execute();
      $apiResult = $api->getResult()->getResultData(null, array('Strip' => 'all'));
      $readyCoursesDirtyArray = $apiResult['query']['categorymembers'];
      $readyCourses = [];
      //Clean the result array and get only the titles
      foreach ($readyCoursesDirtyArray as $course) {
        array_push($readyCourses, $course['title']);
      }
      return $readyCourses;
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  /**
  * Check if the section where new topics are added is in the department page.
  * @param String $department the name of the department page
  * @return boolean $ret true if the there is the section
  */
  private function checkNewTopicsSectionExist($department) {
    $title = Title::newFromText( $department);
    $page = WikiPage::factory( $title);
    $context = self::getRequestContext();
    $parserOptions = ParserOptions::newFromContext($context);
    $levelsTwo = $page->getParserOutput($parserOptions)->getSections();
    $newCoursesSection = wfMessage('courseeditor-newtopics-section-title')->text();
    if(!is_array($levelsTwo)){
      return false;
    }else {
      foreach($levelsTwo as $element) {
        $ret = in_array($newCoursesSection, $element);
      }
    }
    return $ret;
  }

  /**
  * Generate the url to edit the root level of a course
  * @param Title $title the title of the course page
  * @return Array $result an array with the 'href' to CourseEditor SpecialPage and
  *         the localised title for the actiontype
  */
  public static function makeEditCourseUrl($title){
    $titleText = $title->getNsText() . ":" . $title->getText();
    $url = "/Special:CourseEditor?actiontype=editcourse&pagename=" . $titleText;
    $result = array('href' => $url, 'text' => wfMessage('courseeditor-editcourse-pagetitle')->text());
    return $result;
  }

  /**
  * Generate the url to edit the second level of a course
  * @param Title $title the title of the course page
  * @return Array $result an array with the 'href' to CourseEditor SpecialPage and
  *         the localised title for the actiontype
  */
  public static function makeEditLevelTwoUrl($title){
    $titleText = $title->getNsText() . ":" . $title->getText();
    $url = "/Special:CourseEditor?actiontype=editleveltwo&pagename=" . $titleText;
    $result = array('href' => $url, 'text' => wfMessage('courseeditor-editlevelTwo-pagetitle')->text());
    return $result;
  }

  /**
  * Generate the url to download a whole course through Collection extension
  * @param Title $title the title of the course page
  * @return string $url the url to Collection SpecialPage
  */
  public static function makeDownloadCourseUrl($title){
    if($title->getNamespace() === NS_USER){
      $user = $title->getRootText();
      $collection = $title->getNstext() . ":" . $user . "/" . wfMessage('courseeditor-collection-book-category')->text() . "/" . $title->getSubpageText();
    }else {
      $collection = "Project:" . wfMessage('courseeditor-collection-book-category')->text() . "/" . $title->getRootText();
    }
    //Return only the url instead of an array with 'href' and 'title'.
    //The title is created within the skin using the titles of the Collection tools
    return $url = "/Special:Collection?bookcmd=render_collection&writer=rdf2latex&colltitle=" . $collection;
  }
}
