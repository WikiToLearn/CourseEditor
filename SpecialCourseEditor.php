<?php
class SpecialEasyLink extends SpecialPage {
    public function __construct( $name = 'EasyLink' ) {
        parent::__construct( $name );
    }

    public function execute() {
        $method = $_SERVER['REQUEST_METHOD'];
        $request = $this->getRequest();
        if($method == 'POST'){
            $wikitext = $request->getVal( 'wikitext' );
            $scoredCandidates = $request->getVal('scoredCandidates');
            $threshold = $request->getVal(threshold);
            $this->forwardPost($wikitext, $scoredCandidates, $threshold);
        }else if($method == 'GET' &&  $request->getVal( 'id' ) != null){
            $requestId = $request->getVal( 'id' );
            $this->forwardGet($requestId);
        }else if($method == 'DELETE') {
            $requestId = $request->getVal('id');
            $this->forwardDelete($requestId);
        }else if($method == 'GET' &&  $request->getVal( 'annotations' ) != null){
            $this->forwardGetAnnotations();
        }
    }

    public function forwardPost($wikitext, $scoredCandidates, $threshold){
        $params = ['wikitext' => $wikitext, 'scoredCandidates' => $scoredCandidates, 'threshold' => $threshold];

        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a userAgent too here
        curl_setopt_array($curl, array(
            CURLOPT_FRESH_CONNECT => 1, 
            CURLOPT_RETURNTRANSFER => 1, 
            CURLOPT_FORBID_REUSE => 1, 
            CURLOPT_URL => 'http://easylink:8080/EasyLinkAPI/webapi/analyze',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query($params),
            //CURLOPT_TIMEOUT => 60
        ));
        // Send the request & save response to $response
        $response = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        echo $response;
        die();
    }

    public function forwardGetAnnotations(){
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a userAgent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'http://easylink:8080/EasyLinkAPI/webapi/annotations/'
        ));
        // Send the request & save response to $response
        $response = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        echo $response;
        die();
    }

    public function forwardGet($requestId){
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a userAgent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'http://easylink:8080/EasyLinkAPI/webapi/status/' . $requestId
        ));
        // Send the request & save response to $response
        $response = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        echo $response;
        die();
    }

    public function forwardDelete($requestId){
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a userAgent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_URL => 'http://easylink:8080/EasyLinkAPI/webapi/status/' . $requestId
        ));
        // Send the request & save response to $response
        $response = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);
        echo $response;
        die();
    }


}
