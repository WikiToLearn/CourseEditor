<?php
if ( !defined( 'MEDIAWIKI' ) ){
  die( );
}

class SpecialCourseEditor extends SpecialPage {

  public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
    parent::__construct( $name );
  }

  /**
  * This function is an entrypoint (Controller, Façade ...) of the CourseEditor
  * extension. It uses the query string or the $par param to generate right view
  * requested by the user.
  * @param String $par Optional param used to generate SpecialPage subpages
  */
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

  /**
  * Generate the view to edit a course
  * @param String $courseName the name of the course (included namespace)
  */
  private function editCourse($courseName){
    $out = $this->getOutput();
    $out->enableOOUI();
    //Get levelTwo elements of the course used to generate the Drag'n'Drop
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

  /**
  * Generate the view to edit a levelTwo element
  * @param String $levelTwoName the name of the levelTwo (included namespace
  * and the name of the course)
  */
  private function editLevelTwo($levelTwoName){
    $out = $this->getOutput();
    $out->enableOOUI();
    //Get levelThree elements of the levelTwo used to generate the Drag'n'Drop
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

  /**
  * Generate the view to publish ready private courses.
  * This view is for admins only.
  */
  private function readyToBePublishedCourses(){
    global $wgCourseEditorNamespaces;
    $readyCourses = CourseEditorUtils::getReadyToBePublishedCourses();
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->setPageTitle(wfMessage('courseeditor-publish-course-pagetitle'));
    $out->addJsConfigVars('wgCourseEditor', $wgCourseEditorNamespaces);
    $template = new PublishCourseTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('readyCourses', $readyCourses);
    $out->addTemplate( $template );
  }

  /**
  * Generate the view to create a new course if the user comes from a
  * department page.
  * @param String $department the name of the department page
  */
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

  /**
  * Generate the view to create a new course if the user comes from a
  * topic page.
  * @param String $topic the name of the topic page
  */
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

  /**
  * Generate the view to manage the metadata of a course.
  * @param String $courseName the name of the course
  */
  private function manageMetadata($courseName){
    global $wgCourseEditorNamespaces;
    $out = $this->getOutput();
    $out->enableOOUI();
    $out->addModules('ext.courseEditor.manageMetadata');
    $out->setPageTitle(wfMessage('courseeditor-managemetata-pagetitle'));
    $out->addJsConfigVars('wgCourseEditor', $wgCourseEditorNamespaces);
    // Remove username from courseName (in present)
    $explodedString = explode('/', $courseName, 2);
    $courseNameNoUser = (sizeof($explodedString) === 1) ? $explodedString[0] : $explodedString[1];
    $username = (sizeof($explodedString) === 1) ? 'null' : $explodedString[0];
    $isPrivate =(sizeof($explodedString) === 1) ? false : true;
    $template = new ManageMetadataTemplate();
    $template->setRef('courseEditor', $this);
    $template->set('context', $this->getContext());
    $template->set('course', $courseNameNoUser);
    $template->set('private', $isPrivate);
    $template->set('username', $username);
    $template->set('userObj', $this->getUser());
    $topics = CourseEditorUtils::getTopics();
    $template->set('topics', $topics);
    $metadataResult = CourseEditorUtils::getMetadata($courseName);
    if($metadataResult !== null){
      $template->set('metadataResult', $metadataResult);
    }
    $out->addTemplate( $template );
  }

  /**
  * Generate the credits and the info of the extension.
  */
  private function renderCreditsAndInfo() {
    $out = $this->getOutput();
    $out->addWikiMsg('courseeditor-credits-info');
  }
}
