<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {
  private static $requestContext = null;

  public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
    parent::__construct( $name );
  }

  public function execute() {
    $request = $this->getRequest();
    $user = $this->getUser();
    if ( ! ( $user->isAllowed( 'move' ) ) ) {
      // The effect of loading this page is comparable to purge a page.
      // If desired a dedicated right e.g. "viewmathstatus" could be used instead.
      throw new PermissionsError( 'move' );
    }
    switch ($request->getVal('actiontype')){
      case 'editsection':
        $sectionName = $request->getVal('pagename');
        $this->editSection($sectionName);
        return;
      case 'savesection':
        $sectionName = $request->getVal('sectionName');
        $editStack = $request->getVal('editStack');
        $newChapters = $request->getVal('newChapters');
        $this->saveSection($sectionName, $editStack, $newChapters);
        return;
      case 'editcourse':
        $courseName = $request->getVal('pagename');
        $this->editCourse($courseName);
        return;
      case 'savecourse':
        $courseName = $request->getVal('courseName');
        $editStack = $request->getVal('editStack');
        $newSections = $request->getVal('newSections');
        $this->saveCourse($courseName, $editStack, $newSections);
        return;
      case 'movecourse':
        $courseName = $request->getVal('pagename');
        $this->moveCourse($courseName);
        return;
      default:
        $this->createNewCourse();
        return;
    }
  }

  private function editCourse($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $sections = $this->getSections($courseName);
    $this->setHeaders();
    $out->setPageTitle("Course Editor");
    $out->addInlineScript(" var sections = " . json_encode($sections) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.course' );
    $template = new CourseEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseName);
    $out->addTemplate( $template );
  }

  public static function saveCourse($courseName, $editStack, $newSections){
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
          $sectionName = $value->elementName;
          $newSectionName = $value->newElementName;
          $chapters = self::getChapters($courseName . '/' .$sectionName);
          $newSectionText = "";
          foreach ($chapters as $value) {
            $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $value ."|". $value ."]]\r\n";
          }
          $pageTitle = $courseName . "/" . $sectionName;
          $newPageTitle = $courseName . '/' . $newSectionName;
          self::moveWrapper($pageTitle, $newPageTitle, false, true);
          self::editWrapper($newPageTitle, $newSectionText, null, null);
        break;
        case 'delete':
          $user = self::getRequestContext()->getUser();
          $sectionName = $value->elementName;
          $chapters = self::getChapters($courseName . '/' . $sectionName);
          $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
          if(!$title->userCan('delete', $user, 'secure')){
            $pageTitle = $courseName . '/' . $sectionName;
            $prependText = "\r\n{{deleteme}}";
            self::editWrapper($pageTitle, null, $prependText, null);
            foreach ($chapters as $value) {
              $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
              $prependText = "\r\n{{deleteme}}";
              self::editWrapper($pageTitle, null, $prependText, null);
            }
          }else {
            foreach ($chapters as $value) {
              $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
              self::deleteWrapper($pageTitle);
            }
            $pageTitle = $courseName . '/' . $sectionName;
            self::deleteWrapper($pageTitle);
          }
        break;
        case 'add':
          $sectionName = $value->elementName;
          $pageTitle = $courseName . '/' . $sectionName;
          $text =  "";
          self::editWrapper($pageTitle, $text, null, null);
        break;
      }
    }
    $newCourseText = "[{{fullurl:Special:CourseEditor|actiontype=editcourse&pagename={{FULLPAGENAMEE}}}} Modifica]\r\n";
    $newSectionsArray = json_decode($newSections);
    foreach ($newSectionsArray as $value) {
      $newCourseText .= "{{Sezione|" . $value ."}}\r\n";
    }
    $categories = self::getCategories($courseName);
    foreach ($categories as $category) {
      $newCourseText .= "\r\n[[" . $category['title'] . "]]";
    }
    self::editWrapper($courseName, $newCourseText, null, null);
    return "success";
  }

  public static function saveSection($sectionName, $editStack, $newChapters){
    $context = self::getRequestContext();
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
          $chapterName = $value->elementName;
          $newChapterName = $value->newElementName;
          $from = $sectionName . '/' . $chapterName;
          $to = $sectionName . '/' . $newChapterName;
          self::moveWrapper($from, $to, false, true);
        break;
        case 'delete':
          $user = $context->getUser();
          $chapterName = $value->elementName;
          $title = Title::newFromText($sectionName . '/' . $chapterName, $defaultNamespace=NS_MAIN);
          if(!$title->userCan('delete', $user, 'secure')){
            $pageTitle = $sectionName . '/' . $chapterName;
            $prependText = "\r\n{{deleteme}}";
            self::editWrapper($pageTitle, null, $prependText, null);
          }else {
            $pageTitle = $sectionName . '/' . $chapterName;
            self::deleteWrapper($pageTitle);
          }
        break;
        case 'add':
          $chapterName = $value->elementName;
          $pageTitle = $sectionName . '/' . $chapterName;
          $text =  "";
          self::editWrapper($pageTitle, $text, null, null);
        break;
      }
    }
    $newSectionText = "";
    $newChaptersArray = json_decode($newChapters);
    foreach ($newChaptersArray as $value) {
      $newSectionText .= "* [[" . $sectionName . "/" . $value ."|". $value ."]]\r\n";
    }
    self::editWrapper($sectionName, $newSectionText);
    list($course, $section) = explode("/", $sectionName);
    self::purgeWrapper($course);
    return "success";
  }

  private function editSection($sectionName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $chapters = $this->getChapters($sectionName);
    $this->setHeaders();
    $out->setPageTitle("Section Editor");
    $out->addInlineScript(" var chapters = " . json_encode($chapters) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.section' );
    $template = new SectionEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('section', $sectionName);
    $out->addTemplate( $template );
  }

  private function moveCourse($courseName){
    $regex = "/\/(.*)/";
    preg_match($regex, $courseName, $matches);
    $courseNameWithoutNamespace = $matches[1];
    $to = MWNamespace::getCanonicalName(NS_COURSE) . ':' . $courseNameWithoutNamespace;
    $this->moveWrapper($courseName, $to, true, true);
    $sections = $this->getSections($to);
    foreach ($sections as $sectionName) {
      $chapters = $this->getChapters($to . '/' . $sectionName);
      $newSectionText = "";
      foreach ($chapters as $chapterName) {
        $newSectionText .= "* [[" . $to . "/" . $sectionName . "/" . $chapterName ."|". $chapterName ."]]\r\n";
      }
      $pageTitle = $to . "/" . $sectionName;
      $this->editWrapper($pageTitle, $newSectionText, null, null);
    }
    $this->purgeWrapper($to);
  }

  private function createNewCourse() {
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.create');
    $formDescriptor = array(
      'topic' => array(
        'class' => 'HTMLTextField',
        'label' => wfMessage( 'courseeditor-set-topic' )
      ),
      'name' => array(
        'class' => 'HTMLTextField',
        'label' => wfMessage( 'courseeditor-set-course' )
      ),
      'keyword' => array(
        'class' => 'HTMLTextField',
        'label' => 'Keyword'
      ),
      'namespace' => array(
				'class' => 'HTMLRadioField',
				'label' => wfMessage('courseeditor-radiobutton-namespace'),
				'options' => array(
					wfMessage('courseeditor-radiobutton-namespace-private')->text() => NS_USER,
					wfMessage('courseeditor-radiobutton-namespace-public')->text() => NS_COURSE
				),
				'default' => NS_USER,
			)
    );
    $form = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
    $form->setSubmitCallback( array( 'SpecialCourseEditor', 'validateForm' ) );
    $form->show();
  }

  public static function validateForm($formData){
    if($formData['topic'] != null || $formData['name'] != null){
      $context = self::getRequestContext();
      try {
        $user = $context->getUser();
        $token = $user->getEditToken();
        $selectedNamespace = $formData['namespace'];
        if($formData['keyword'] == null){
          $randomCourseId = SpecialCourseEditor::generateRandomCourseId();
        }else {
          $randomCourseId = $formData['keyword'];
        }
        $pageTitle = MWNamespace::getCanonicalName($selectedNamespace) . ':';
        if($selectedNamespace == NS_USER){
          $pageTitle .=  $user->getName() . '/' . $formData['name'] . '_' . $randomCourseId;
        }else{
          $pageTitle .= $formData['name'] . '_' . $randomCourseId;
        }
        $api = new ApiMain(
          new DerivativeRequest(
          $context->getRequest(),
          array(
            'action'     => 'edit',
            'title'      => $pageTitle,
            'appendtext' => "[{{fullurl:Special:CourseEditor|actiontype=editcourse&pagename={{FULLPAGENAMEE}}}} Modifica]\n\n[[Category:".$formData['topic']."]]",
            'notminor'   => true,
            'token'      => $token
          ),
          true
          ),
          true
        );
        $api->execute();
      } catch(UsageException $e){
        return $e->getMessage();
      }
      return true;
    }
    return wfMessage( 'courseeditor-validate-form' );
  }

/** HELPERS AND UTILITIES METHODS **/

  public static function getRequestContext()
   {
      if(self::$requestContext == null)
      {
         $context = new RequestContext();
         self::$requestContext = $context;
      }

      return self::$requestContext;
   }

  private function getCsrfToken(){
    try {
      $api = new ApiMain(
        new DerivativeRequest(
          self::getRequestContext()->getRequest(),
          array(
            'action' => 'query',
            'meta' => 'tokens'
          )
        ),
        true
      );
      $api->execute();
      $results = $api->getResult()->getResultData(null, array('Strip' => 'all'));
      print_r($results['query']['tokens']['csrftoken']);
      return $results['query']['tokens']['csrftoken'];
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  private function getCategories($courseName){
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

  private function getChapters($sectionName){
    $title = Title::newFromText($sectionName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[2];
  }

  private function getSections($courseName){
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\{{2}\w+\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    return $matches[1];
  }

  private function deleteWrapper($title){
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
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  private function purgeWrapper($titles){
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
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  private function editWrapper($title, $text, $textToPrepend, $textToAppend){
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
    } catch(UsageException $e){
      return $e->getMessage();
    }
  }

  private function moveWrapper($from, $to, $redirect, $subpages){
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
            'noredirect' => $redirect,
            'movetalk' => true,
            'movesubpages'=> $subpages,
            'token'      => $token
          ),
          true
        ),
        true
      );
      $api->execute();
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
