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
      case 'createcourse':
        $this->renderPageContent();
        return;
      default:
        $out = $this->getOutput();
        $out->enableOOUI();
        $out->addHtml('default');
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
        'label' => 'Select a topic',
        'validation-callback' => array('SpecialCourseEditor', 'validateTopic')
      ),
      'name' => array(
        'class' => 'HTMLTextField',
        'label' => 'Select a name'
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
return "Please, insert a topic and a name";
}
}
