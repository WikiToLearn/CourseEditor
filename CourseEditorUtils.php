<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorUtils {
  private static $requestContext = null;

  public static function getMetadata($courseName){
    $title = Title::newFromText($courseName, $defaultNamespace=NS_COURSEMETADATA );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    if($text === ''){
      return null;
    }
    $regex = "/<section begin=(.*?)\s*\/>(.*?)<section end=.*?\/>/";
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
  public static function setComposedOperationSuccess(&$operation, $result){
    $isSuccess = true;
    if (is_string($result[0]) || is_string($result[1])) {
      $isSuccess = false;
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

  public static function getChapters($sectionName){
    $title = Title::newFromText($sectionName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[2];
  }

  public static function getSections($courseName){
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\{{2}SSection\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
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
            'noredirect' => true,
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
}
