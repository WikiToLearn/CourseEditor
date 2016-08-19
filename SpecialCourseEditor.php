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
        $originalChapters = $request->getVal('originalChapters');
        $editStack = $request->getVal('editStack');
        $newChapters = $request->getVal('newChapters');
        $this->saveSection($sectionName, $originalChapters, $editStack, $newChapters);
        return;
      case 'editcourse':
        $courseName = $request->getVal('pagename');
        $this->editCourse($courseName);
        return;
      case 'savecourse':
        $courseName = $request->getVal('courseName');
        $originalSections = $request->getVal('originalSections');
        $editStack = $request->getVal('editStack');
        $newSections = $request->getVal('newSections');
        $this->saveCourse($courseName, $originalSections, $editStack, $newSections);
        return;
      default:
        $this->createNewCourse();
        return;
    }
  }

  private function editCourse($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $title = Title::newFromText( $courseName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\{{2}\w+\|(.*)\}{2}/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    $this->sectionsList = $matches[1];
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
        $title = Title::newFromText( $courseName . "/" . $sectionName, $defaultNamespace=NS_MAIN );
        $page = WikiPage::factory( $title );
        $content = $page->getContent( Revision::RAW );
        $text = ContentHandler::getContentText( $content );
        $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
        preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
        $chapters = $matches[2];
        $newSectionText = "";
        foreach ($chapters as $value) {
          $newSectionText .= "* [[" . $courseName . "/" . $newSectionName . "/" . $value ."|". $value ."]]\r\n";
        }
        try {
          $user = $this->getContext()->getUser();
          $token = $user->getEditToken();
          $api = new ApiMain(
          new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'edit',
            'title'      => $courseName . "/" . $sectionName,
            'text' => $newSectionText,
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
       try {
          $user = $this->getContext()->getUser();
          $token = $user->getEditToken();
          $api = new ApiMain(
          new DerivativeRequest(
            $this->getContext()->getRequest(),
            array(
              'action'     => 'move',
              'from'      => $courseName . '/' . $sectionName,
              'to' => $courseName . '/' . $newSectionName,
              'token'      => $token,
              'noredirect' => false,
              'movetalk' => true,
              'movesubpages'=> true
            ),
            true // treat this as a POST
          ),
          true // Enable write.
          );
        $api->execute();
        } catch(UsageException $e){
          return $e;
        }
        break;
        case 'delete':
        $user = $this->getContext()->getUser();
        $sectionName = $value->elementName;
        $title = Title::newFromText($courseName . '/' . $sectionName, $defaultNamespace=NS_MAIN);
        $page = WikiPage::factory( $title );
        $content = $page->getContent( Revision::RAW );
        $text = ContentHandler::getContentText( $content );
        $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
        preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
        $chapters = $matches[2];
        if(!$title->userCan('delete', $user, 'secure')){
          try {
            $token = $user->getEditToken();
            $api = new ApiMain(
            new DerivativeRequest(
            $this->getContext()->getRequest(),
            array(
              'action'     => 'edit',
              'title'      => $courseName . '/' . $sectionName,
              'prependtext' => '{{deleteme}}',
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
        foreach ($chapters as $value) {
          try {
            $token = $user->getEditToken();
            $api = new ApiMain(
            new DerivativeRequest(
            $this->getContext()->getRequest(),
            array(
              'action'     => 'edit',
              'title'      => $courseName . '/' . $sectionName . '/' . $value,
              'prependtext' => '{{deleteme}}',
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
        }else {
          foreach ($chapters as $value) {
            try {
              $token = $user->getEditToken();
              $api = new ApiMain(
              new DerivativeRequest(
              $this->getContext()->getRequest(),
              array(
                'action'     => 'delete',
                'title'      => $courseName . '/' . $sectionName . '/' . $value,
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
          try {
            $user = $this->getContext()->getUser();
            $token = $user->getEditToken();
            $api = new ApiMain(
            new DerivativeRequest(
            $this->getContext()->getRequest(),
            array(
              'action'     => 'delete',
              'title'      => $courseName . '/' . $sectionName,
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
        break;
        case 'add':
        $sectionName = $value->elementName;
        try {
          $user = $this->getContext()->getUser();
          $token = $user->getEditToken();
          $api = new ApiMain(
          new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'edit',
            'title'      => $courseName . '/' . $sectionName,
            'text' => "",
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
      break;
      }
    }
    $newCourseText = "[{{fullurl:Special:CourseEditor|actiontype=editcourse&pagename={{FULLPAGENAME}}}} Modifica]\r\n";
    $newSectionsArray = json_decode($newSections);
    foreach ($newSectionsArray as $value) {
      $newCourseText .= "{{Sezione|" . $value ."}}\r\n";
    }
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
      new DerivativeRequest(
      $this->getContext()->getRequest(),
      array(
        'action'     => 'edit',
        'title'      => $courseName,
        'text' => $newCourseText,
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

  private function saveSection($sectionName, $originalChapters, $editStack, $newChapters){
    $out = $this->getOutput();
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
        $chapterName = $value->elementName;
        $newChapterName = $value->newElementName;
        try {
          $user = $this->getContext()->getUser();
          $token = $user->getEditToken();
          $api = new ApiMain(
          new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'move',
            'from'      => $sectionName . '/' . $chapterName,
            'to' => $sectionName . '/' . $newChapterName,
            'token'      => $token,
            'noredirect' => false,
            'movetalk' => true,
            'movesubpages'=> true
          ),
          true // treat this as a POST
        ),
        true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      return $e;
    }
    break;
    case 'delete':
    $user = $this->getContext()->getUser();
    $chapterName = $value->elementName;
    $title = Title::newFromText($sectionName . '/' . $chapterName, $defaultNamespace=NS_MAIN);
    if(!$title->userCan('delete', $user, 'secure')){
      try {
        $token = $user->getEditToken();
        $api = new ApiMain(
        new DerivativeRequest(
        $this->getContext()->getRequest(),
        array(
          'action'     => 'edit',
          'title'      => $sectionName . '/' . $chapterName,
          'prependtext' => '{{deleteme}}',
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
  }else {
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
      new DerivativeRequest(
      $this->getContext()->getRequest(),
      array(
        'action'     => 'delete',
        'title'      => $sectionName . '/' . $chapterName,
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

    break;
    case 'add':
    $chapterName = $value->elementName;
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
      new DerivativeRequest(
      $this->getContext()->getRequest(),
      array(
        'action'     => 'edit',
        'title'      => $sectionName . '/' . $chapterName,
        'text' => "",
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
  break;
  }
}
  $newSectionText = "";
  $newChaptersArray = json_decode($newChapters);
  foreach ($newChaptersArray as $value) {
    $newSectionText .= "* [[" . $sectionName . "/" . $value ."|". $value ."]]\r\n";
  }
  try {
    $user = $this->getContext()->getUser();
    $token = $user->getEditToken();
    $api = new ApiMain(
    new DerivativeRequest(
    $this->getContext()->getRequest(),
    array(
      'action'     => 'edit',
      'title'      => $sectionName,
      'text' => $newSectionText,
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

try {
  $user = $this->getContext()->getUser();
  $token = $user->getEditToken();
  list($course, $section) = explode("/", $sectionName);
  $api = new ApiMain(
  new DerivativeRequest(
  $this->getContext()->getRequest(),
  array(
    'action'     => 'purge',
    'titles'      => $course,
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

  private function editSection($sectionName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $title = Title::newFromText( $sectionName, $defaultNamespace=NS_MAIN );
    $page = WikiPage::factory( $title );
    $content = $page->getContent( Revision::RAW );
    $text = ContentHandler::getContentText( $content );
    $regex = "/\*\s*\[{2}([^|]*)\|?([^\]]*)\]{2}\s*/";
    preg_match_all($regex, $text, $matches, PREG_PATTERN_ORDER);
    $this->chaptersList = $matches[2];
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

  private function createNewCourse() {
    $out = $this->getOutput();
    $out->enableOOUI();
    $formDescriptor = array(
      'topic' => array(
        'class' => 'HTMLTextField',
        'label' => wfMessage( 'courseeditor-set-topic' )
      ),
      'name' => array(
        'class' => 'HTMLTextField',
        'label' => wfMessage( 'courseeditor-set-course' )
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
        $pageTitle = MWNamespace::getCanonicalName($selectedNamespace) . ':';
        if($selectedNamespace == NS_USER){
          $pageTitle .=  $user->getName() . '/' . $formData['name'];
        }else{
          $pageTitle .= $formData['name'];
        }

        $api = new ApiMain(
        new DerivativeRequest(
        $context->getRequest(),
        array(
          'action'     => 'edit',
          'title'      => $pageTitle,
          'appendtext' => "{{Course|}}\n\n[[Category:".$formData['topic']."]]",
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
}
