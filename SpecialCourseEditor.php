<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {

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
    $actionType = $request->getVal('actiontype');
    switch ($actionType){
      case 'editsection':
        $sectionName = $request->getVal('pagename');
        $this->editSection($sectionName);
        return;
      case 'editcourse':
        $courseName = $request->getVal('pagename');
        $this->editCourse($courseName);
        return;
      case 'movecourse':
        $courseName = $request->getVal('pagename');
        $this->moveCourse($courseName);
        return;
      case 'createcourse':
        if($request->getVal('department')){
          $department = $request->getVal('department');
          $this->createNewCourseFromDepartment($department);
        }else if($request->getVal('topic')){
          $topic = $request->getVal('topic');
          $this->createNewCourseFromTopic($topic);
        }
        return;
      default:
        //$this->createNewCourse();
      return;
    }
  }

  private function editCourse($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $sections = CourseEditorUtils::getSections($courseName);
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

  private function editSection($sectionName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $chapters = CourseEditorUtils::getChapters($sectionName);
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
    CourseEditorUtils::moveWrapper($courseName, $to);
    $sections = CourseEditorUtils::getSections($to);
    foreach ($sections as $sectionName) {
      $chapters = CourseEditorUtils::getChapters($to . '/' . $sectionName);
      $newSectionText = "";
      foreach ($chapters as $chapterName) {
        $newSectionText .= "* [[" . $to . "/" . $sectionName . "/" . $chapterName ."|". $chapterName ."]]\r\n";
      }
      $pageTitle = $to . "/" . $sectionName;
      CourseEditorUtils::editWrapper($pageTitle, $newSectionText, null, null);
    }
    CourseEditorUtils::purgeWrapper($to);
  }

  private function createNewCourseFromTopic($topic) {
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.create');
    $out->setPageTitle("Create course");
    $template = new CourseCreatorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('topic', $topic);
    $out->addTemplate( $template );
    /*$formDescriptor = array(
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
    //$form->setSubmitCallback( array( 'SpecialCourseEditor', 'validateForm' ) );
    $form->show();*/

  }

  /*public static function validateForm($formData){
    if($formData['topic'] != null || $formData['name'] != null){
      $context = CourseEditorUtils::getRequestContext();
      try {
        $user = $context->getUser();
        $token = $user->getEditToken();
        $selectedNamespace = $formData['namespace'];
        if($formData['keyword'] == null){
          $randomCourseId = CourseEditorUtils::generateRandomCourseId();
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
            'appendtext' => "{{CCourse}}\r\n[[Category:".$formData['topic']."]]",
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
  }*/
}
