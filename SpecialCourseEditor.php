<?php
class SpecialCourseEditor extends SpecialPage {
    public function __construct( $name = 'CourseEditor', $restriction = 'move' ) {
        parent::__construct( $name );
    }
    
    public function execute() {
    
        if ( ! ( $this->getUser()->isAllowed( 'move' ) ) ) {
            // The effect of loading this page is comparable to purge a page.
			// If desired a dedicated right e.g. "viewmathstatus" could be used instead.
			throw new PermissionsError( 'move' );
        }
        $this->renderPageContent();
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
        
    public static function validateTopic($topic, $allData) {
    
        return true;
    }
    
    public static function validateForm($formData){
        if($formData['topic'] != null || $formData['name'] != null){
            $context = new RequestContext();
            try{
                $user = $context->getUser();
                $token = $user->getEditToken();
                $pageTitle = MWNamespace::getCanonicalName( NS_USER ) . ':' . $user->getName() . '/' . $formData['name']; 

                $api = new ApiMain(
                        new DerivativeRequest(
                            $context->getRequest(),
                            array(
                                'action'     => 'edit',
                                'title'      => $pageTitle,
                                'appendtext' => "Prova testo",
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
