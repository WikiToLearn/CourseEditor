<?php
class CourseEditorApi extends ApiBase {
	public function execute() {
    global $wgContLang;
		$courseTitle = $this->getMain()->getVal( 'coursetitle' );

    if(is_null($courseTitle) || empty($courseTitle)){
      if (is_callable([$this, 'dieWithError'])){
        $this->dieWithError(
          [ 'apierror-paramempty', $this->encodeParamName( 'coursetitle' ) ], 'nocoursetitle'
        );
      } else {
        $this->dieUsage( 'No coursetitle selected', '_nocoursetitle' );
      }
    }

		$result = $this->getResult();

    $courseTree = CourseEditorUtils::getCourseTree($courseTitle);
    $result->addValue(null, $this->getModuleName(),
      array (
        'success' => 'true',
        'response' => json_decode($courseTree)
      )
    );
		return true;
	}

	// coursetitle parameter.
	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'coursetitle' => array (
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => 'true',
        ApiBase::PARAM_HELP_MSG => 'api-help-param-coursetitle'
			)
		) );
	}

  protected function getExamplesMessages() {
		return [
			'action=coursetree&coursetitle=Course:Modern%20Physics&format=json' =>
				'api-help-course-tree-example'
		];
	}
}
