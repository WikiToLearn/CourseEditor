<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {
  public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
    parent::__construct( $name );
  }

  public $chaptersList = array();

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
      default:
        $this->renderPageContent();
    }
  }

  private function savesection($sectionName, $originalChapters, $editStack, $newChapters){
    $out = $this->getOutput();
    $stack = json_decode($editStack);
    foreach ($stack as $value) {
      switch ($value->action) {
        case 'rename':
        try {
          $user = $this->getContext()->getUser();
          $token = $user->getEditToken();
          $api = new ApiMain(
          new DerivativeRequest(
          $this->getContext()->getRequest(),
          array(
            'action'     => 'move',
            'from'      => $sectionName . '/' . $value->chapterName,
            'to' => $sectionName . '/' . $value->newChapterName,
            'token'      => $token,
            'noredirect' => true,
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
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
      new DerivativeRequest(
      $this->getContext()->getRequest(),
      array(
        'action'     => 'delete',
        'title'      => $sectionName . '/' . $value->chapterName,
        'token'      => $token
      ),
      true // treat this as a POST
      ),
      true // Enable write.
      );
      $api->execute();
    } catch(UsageException $e){
      print_r($api);
      return $e;
    }
    break;
    case 'add':
    try {
      $user = $this->getContext()->getUser();
      $token = $user->getEditToken();
      $api = new ApiMain(
      new DerivativeRequest(
      $this->getContext()->getRequest(),
      array(
        'action'     => 'edit',
        'title'      => MWNamespace::getCanonicalName( NS_MAIN ) . $sectionName . '/' . $value->chapterName,
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
    $out->addModules( 'ext.courseEditor' );
    $template = new SectionEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('section', $sectionName);
    $template->set('chapters', $this->chaptersList);
    $out->addTemplate( $template );
  }

  private function renderPageContent() {
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
        $pageTitle = MWNamespace::getCanonicalName( NS_USER ) . ':' . $user->getName() . '/' . $formData['name'];

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
