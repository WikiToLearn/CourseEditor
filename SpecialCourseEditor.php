<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {

  public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
    parent::__construct( $name );
  }

  public function execute($par) {
    $request = $this->getRequest();
    $user = $this->getUser();
    //Redirect user if he is not logged
    if ( ! ( $user->isAllowed( 'move' ) ) ) {
      global $wgOut;
      $title = Title::newFromText('Special:UserLogin');
      $pageName = "Special:" . $this->mName;
      $params = strstr($request->getRequestURL(), '?');
      $returnTo = "returnto=" . $pageName;
      if($params != ""){
        $returnTo .= "&returntoquery=" . urlencode($params);
      }
      $wgOut->redirect($title->getFullURL($returnTo));
    }
    $actionType = $request->getVal('actiontype');
    if($par === 'ReadyCourses'){
      if (!$user->isAllowed( 'undelete' )){
        throw new PermissionsError( 'undelete' );
      }
        $this->readyToBePublishedCourses();
        return;
    }
    switch ($actionType){
      case 'editleveltwo':
        $levelTwoName = $request->getVal('pagename');
        $this->editLevelTwo($levelTwoName);
        return;
      case 'editcourse':
        $courseName = $request->getVal('pagename');
        $this->editCourse($courseName);
        return;
      case 'readycourses':
        if (!$user->isAllowed( 'undelete' )){
          throw new PermissionsError( 'undelete' );
        }
          $this->readyToBePublishedCourses();
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
        return;
      default:
        $this->renderCreditsAndInfo();
      return;
    }
  }

  private function editCourse($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $levelsTwo = CourseEditorUtils::getLevelsTwo($courseName);
    $this->setHeaders();
    $out->setPageTitle(wfMessage('courseeditor-editcourse-pagetitle'));
    $out->addInlineScript(" var levelsTwo = " . json_encode($levelsTwo) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.course' );
    $template = new CourseEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseName);
    $out->addTemplate( $template );
  }

  private function editLevelTwo($levelTwoName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $levelsThree = CourseEditorUtils::getLevelsThree($levelTwoName);
    $this->setHeaders();
    $out->setPageTitle(wfMessage('courseeditor-editlevelTwo-pagetitle'));
    $out->addInlineScript(" var levelsThree = " . json_encode($levelsThree) . ", editStack = [];");
    $out->addModules( 'ext.courseEditor.levelTwo' );
    $template = new LevelTwoEditorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('levelTwo', $levelTwoName);
    $out->addTemplate( $template );
  }

  private function readyToBePublishedCourses(){
    global $wgCourseEditorNamespaces;
    /*$regex = "/\/(.*)/";
    $resultStatus = preg_match($regex, $courseName, $matches);
    if ($resultStatus === 0 ||  $resultStatus === false) {
      $courseNameWithoutNamespace = $courseName;
    }else {
      $courseNameWithoutNamespace = $matches[1];
    }
    $to = MWNamespace::getCanonicalName(NS_COURSE) . ':' . $courseNameWithoutNamespace;
    CourseEditorUtils::moveWrapper($courseName, $to);
    $levelsTwo = CourseEditorUtils::getLevelsTwo($to);
    foreach ($levelsTwo as $levelTwoName) {
      $levelsThree = CourseEditorUtils::getLevelsThree($to . '/' . $levelTwoName);
      $newLevelTwoText = "";
      foreach ($levelsThree as $levelThreeName) {
        $newLevelTwoText .= "* [[" . $to . "/" . $levelTwoName . "/" . $levelThreeName ."|". $levelThreeName ."]]\r\n";
      }
      $pageTitle = $to . "/" . $levelTwoName;
      CourseEditorUtils::editWrapper($pageTitle, $newLevelTwoText, null, null);
    }
    CourseEditorUtils::purgeWrapper($to);*/
    $readyCourses = CourseEditorUtils::getReadyToBePublishedCourses();
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->setPageTitle('Pubblicazione corsi');
    $out->addJsConfigVars('wgCourseEditor', $wgCourseEditorNamespaces);
    $template = new PublishCourseTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('readyCourses', $readyCourses);
    $out->addTemplate( $template );
  }

  private function createNewCourseFromDepartment($department){
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.create');
    $out->setPageTitle(wfMessage('courseeditor-createcourse-pagetitle'));
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
    $out->setPageTitle(wfMessage('courseeditor-createcourse-pagetitle'));
    $template = new CourseCreatorTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('topic', $topic);
    $out->addTemplate( $template );
  }

  private function manageMetadata($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.manageMetadata');
    $out->setPageTitle(wfMessage('courseeditor-managemetata-pagetitle'));
    $template = new ManageMetadataTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseName);
    $template->set('user', $this->getUser());
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
