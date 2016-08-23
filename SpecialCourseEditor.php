<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {
  public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
    parent::__construct( $name );
  }

  public $chaptersList = array();
  public $sectionsList = array();

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
    $this->sectionsList = $this->getSections($courseName);
    $this->setHeaders();
    $out->setPageTitle("Course Editor");
    $out->addInlineScript(" var sections = " . json_encode($this->sectionsList) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.course' );
    $template = new CourseEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseName);
    $out->addTemplate( $template );
  }

  private function saveCourse($courseName, $originalSections, $editStack, $newSections){
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
          $sectionName = $value->elementName;
          $newSectionName = $value->newElementName;
          $chapters = $this->getChapters($courseName . '/' .$sectionName);
          $newSectionText = "";
          foreach ($chapters as $value) {
            $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $value ."|". $value ."]]\r\n";
          }
          $pageTitle = $courseName . "/" . $sectionName;
          $newPageTitle = $courseName . '/' . $newSectionName;
          $this->moveWrapper($pageTitle, $newPageTitle, false, true);
          $this->editWrapper($newPageTitle, $newSectionText, null, null);
        break;
        case 'delete':
          $user = $this->getContext()->getUser();
          $sectionName = $value->elementName;
          $chapters = $this->getChapters($courseName . '/' . $sectionName);
          $title = Title::newFromText( $courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN );
          if(!$title->userCan('delete', $user, 'secure')){
            $pageTitle = $courseName . '/' . $sectionName;
            $prependText = '{{deleteme}}';
            $this->editWrapper($pageTitle, null, $prependText, null);
            foreach ($chapters as $value) {
              $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
              $prependText = '{{deleteme}}';
              $this->editWrapper($pageTitle, null, $prependText, null);
            }
          }else {
            foreach ($chapters as $value) {
              $pageTitle = $courseName . '/' . $sectionName . '/' . $value;
              $this->deleteWrapper($pageTitle);
            }
            $pageTitle = $courseName . '/' . $sectionName;
            $this->deleteWrapper($pageTitle);
          }
        break;
        case 'add':
          $sectionName = $value->elementName;
          $pageTitle = $courseName . '/' . $sectionName;
          $text =  "";
          $this->editWrapper($pageTitle, $text, null, null);
        break;
      }
    }
    //FIXME: Category with topic's course must me added again
    $newCourseText = "[{{fullurl:Special:CourseEditor|actiontype=editcourse&pagename={{FULLPAGENAMEE}}}} Modifica]\r\n";
    $newSectionsArray = json_decode($newSections);
    foreach ($newSectionsArray as $value) {
      $newCourseText .= "{{Sezione|" . $value ."}}\r\n";
    }
    $this->editWrapper($courseName, $newCourseText, null, null);
  }

  private function saveSection($sectionName, $originalChapters, $editStack, $newChapters){
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
          $chapterName = $value->elementName;
          $newChapterName = $value->newElementName;
          $from = $sectionName . '/' . $chapterName;
          $to = $sectionName . '/' . $newChapterName;
          $this->moveWrapper($from, $to, false, true);
        break;
        case 'delete':
          $user = $this->getContext()->getUser();
          $chapterName = $value->elementName;
          $title = Title::newFromText($sectionName . '/' . $chapterName, $defaultNamespace=NS_MAIN);
          if(!$title->userCan('delete', $user, 'secure')){
            $pageTitle = $sectionName . '/' . $chapterName;
            $prependText = '{{deleteme}}';
            $this->editWrapper($pageTitle, null, $prependText, null);
          }else {
            $pageTitle = $sectionName . '/' . $chapterName;
            $this->deleteWrapper($pageTitle);
          }
        break;
        case 'add':
          $chapterName = $value->elementName;
          $pageTitle = $sectionName . '/' . $chapterName;
          $text =  "";
          $this->editWrapper($pageTitle, $text, null, null);
        break;
      }
    }
    $newSectionText = "";
    $newChaptersArray = json_decode($newChapters);
    foreach ($newChaptersArray as $value) {
      $newSectionText .= "* [[" . $sectionName . "/" . $value ."|". $value ."]]\r\n";
    }
    $this->editWrapper($sectionName, $newSectionText);
    list($course, $section) = explode("/", $sectionName);
    $this->purgeWrapper($course);
  }

  private function editSection($sectionName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $this->chaptersList = $this->getChapters($sectionName);
    $this->setHeaders();
    $out->setPageTitle("Section Editor");
    $out->addInlineScript(" var chapters = " . json_encode($this->chaptersList) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.section' );
    $template = new SectionEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('section', $sectionName);
    $template->set('chapters', $this->chaptersList);
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
      $context = new RequestContext();
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
            'token'      => $token,
            'notminor'   => true
          ),
          true // treat this as a POST
          ),
          true // Enable write.
        );
        $api->execute();
      } catch(UsageException $e){
        return $e;
      }
      return true;
    }
    return wfMessage( 'courseeditor-validate-form' );
  }

/** HELPERS AND UTILITIES METHODS **/

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
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'delete',
            'title'      => $title,
            'token'      => $token
          ),
          true // treat this as a POST
        ),
        true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      return $e;
    }
  }
  private function purgeWrapper($titles){
    try {
      $user = $this->getContext()->getUser();
      $api = new ApiMain(
        new DerivativeRequest(
          $this->getContext()->getRequest(),
            array(
              'action'     => 'purge',
              'titles'      => $titles,
              'forcerecursivelinkupdate' => true
            ),
            true // treat this as a POST
        ),
        true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      return $e;
    }
  }
  private function editWrapper($title, $text, $textToPrepend, $textToAppend){
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'edit',
            'title'      => $title,
            'text' => $text,
            'prependtext' => $textToPrepend, //automatically override text
            'appendtext' => $textToAppend, //automatically override text
            'token'      => $token,
            'notminor'   => true
          ),
          true // treat this as a POST
        ),
        true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      return $e;
    }
  }

  private function moveWrapper($from, $to, $redirect, $subpages){
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
        new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'move',
            'from'      => $from,
            'to' => $to,
            'token'      => $token,
            'noredirect' => $redirect,
            'movetalk' => true,
            'movesubpages'=> $subpages
          ),
          true // treat this as a POST
        ),
        true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      return $e;
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
