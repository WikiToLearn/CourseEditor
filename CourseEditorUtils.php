<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorUtils {
  private static $requestContext = null;

  public static function updateCollection($courseName) {
    list($namespace, $name) = explode(':', $courseName, 2);
    $title = Title::newFromText($courseName, $defaultNamespace=NS_COURSE );
    $namespaceIndex = $title->getNamespace();

    if(MWNamespace::equals($namespaceIndex, NS_USER)){
      self::updateUserCollection($courseName);
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

      $editResult = self::editWrapper($pageTitle, $collectionText, null, null);
      return $editResult;
    }
  }

  private function updateUserCollection($courseName){
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
    $editResult = self::editWrapper($pageTitle, $collectionText, null, null);
    return $editResult;
  }

  public static function getMetadata($courseName){
    $title = Title::newFromText($courseName, $defaultNamespace=NS_COURSEMETADATA );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    if($text === ''){
      return null;
    }
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

  public static function getTopicCourses($topic){
    $title = Title::newFromText($topic, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $textNoNewLines = trim(preg_replace('/\n+/', '', $text));
    $regex = "/({{Topic|.+)}}.*$/";
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
        break;
      }
    }
    $operation->success = $isSuccess;
  }

  public static function getRequestContext(){
      if(self::$requestContext == null)
      {
         $context = new RequestContext();
         self::$requestContext = $context;
      }
      return self::$requestContext;
  }

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

  public static function getLevelsThree($levelTwoName){
    $title = Title::newFromText($levelTwoName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[2];
  }

  public static function getLevelsTwo($courseName){
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\{{2}SSection\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
  }

  public static function getPreviousAndNext($pageTitle){
    $subElements = self::getSubCourseElements($pageTitle);
    if($subElements['error']){
      return $subElements;
    }else {
      return self::buildPreviousAndNext($pageTitle, $subElements);
    }
  }

  private function getSubCourseElements($pageTitle) {
    $namespace = $pageTitle->getNamespace();
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

  private function buildPreviousAndNext($pageTitle, $subElements){
    $namespace = $pageTitle->getNamespace();
    $basePage = MWNamespace::getCanonicalName($namespace) . ":" . $pageTitle->getBaseText();
    $lastPage = $pageTitle->getSubpageText();
    $previous = null;
    $next = null;
    $previousAndNext = array('previous' => $previous, 'next' => $next);

    if(sizeof($subElements) < 2){
      return $previousAndNext;
    }else {
      $key = array_search($lastPage, $subElements);
      if($key === sizeof($subElements) - 1){
        $previousAndNext['previous'] = $basePage . "/" . $subElements[$key - 1];
      }else if($key === 0){
        $previousAndNext['next'] = $basePage . "/" . $subElements[$key + 1];
      }else {
        $previousAndNext['previous'] = $basePage . "/" . $subElements[$key - 1];
        $previousAndNext['next'] = $basePage . "/" . $subElements[$key + 1];
      }
      return $previousAndNext;
    }
  }

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

  public static function editSectionWrapper($title, $text, $textToPrepend, $textToAppend){
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $levelTwoExist = self::checkNewCoursesSectionExist($title);
      if(!$levelTwoExist){
        $api = new ApiMain(
          new DerivativeRequest(
            $context->getRequest(),
            array(
              'action'     => 'edit',
              'title'      => $title,
              'text' => $text,
              'section' => 'new',
              'sectiontitle' => wfMessage('courseeditor-newcourses-section-title'),
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
              'sectiontitle' => wfMessage('courseeditor-newcourses-section-title'),
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

  public static function moveWrapper($from, $to){
    $context = self::getRequestContext();
    try {
      $user = $context->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'move',
            'from'      => $from,
            'to' => $to,
            //'noredirect' => true,
            'movetalk' => true,
            'movesubpages' => true,
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

  private function checkNewCoursesSectionExist($department) {
    $title = Title::newFromText( $department);
    $page = WikiPage::factory( $title);
    $context = self::getRequestContext();
    $parserOptions = ParserOptions::newFromContext($context);
    $levelsTwo = $page->getParserOutput($parserOptions)->getSections();
    $newCoursesSection = wfMessage('courseeditor-newcourses-section-title')->text();
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
