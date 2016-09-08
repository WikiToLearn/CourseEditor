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
      case 'managemetadata':
        $courseName = $request->getVal('pagename');
        $this->manageMetadata($courseName);
      break;
      default:
        $this->renderCreditsAndInfo();
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
    $resultStatus = preg_match($regex, $courseName, $matches);
    if ($resultStatus === 0 ||  $resultStatus === false) {
      $courseNameWithoutNamespace = $courseName;
    }else {
      $courseNameWithoutNamespace = $matches[1];
    }
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

  private function createNewCourseFromDepartment($department){
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.create');
    $out->setPageTitle("Create course");
    $template = new CourseCreatorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('department', $department);
    $out->addTemplate( $template );
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
  }

  private function manageMetadata($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    //$out->addModules('ext.courseEditor.create');
    $out->setPageTitle("Manage metadata");
    $template = new ManageMetadataTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseName);
    $metadataResult = CourseEditorUtils::getMetadata($courseName);
    if($metadataResult !== null){
      $template->set('metadataResult', $metadataResult);
    }
    $out->addTemplate( $template );
  }

  private function renderCreditsAndInfo() {
    $out = $this->getOutput();
    $out->addWikiMsg('courseeditor-credits-info');
  }
}
