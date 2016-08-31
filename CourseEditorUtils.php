<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class CourseEditorUtils {
  private static $requestContext = null;

  public static function setElementSuccess($result, &$value, &$isSuccess){
    if($result){
      $value->success = true;
    }else {
      $value->success = false;
      $isSuccess = false;
    }
  }

  public static function setOperationSuccess($result, $action, &$isSuccess, &$stack){
    if(!$result){
      $isSuccess = false;
      $obj = new stdClass();
      $obj->action = $action;
      $obj->success = false;
      array_push($stack, $obj);
    }
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

  public function getChapters($sectionName){
    $title = Title::newFromText($sectionName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[2];
  }

  public function getSections($courseName){
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\{{2}\w+\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
  }

  public function deleteWrapper($title){
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

  public static function moveWrapper($from, $to, $redirect, $subpages){
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

  public static function generateRandomCourseId(){
    $randomCourseId = '';
    $switchToChar = true;
    $random = 0;
    for ($i=0; $i < 6; $i++) {
      $random = mt_rand(48, 57);
      if($switchToChar){
        $random += 49;
        $randomCourseId .= chr($random);
        $switchToChar = false;
      }else {
        $randomCourseId .=  chr($random);
        $switchToChar = true;
      }
    }
    return $randomCourseId;
  }
}
