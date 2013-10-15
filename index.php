<?php
/**
 * JSONP Api
 * --------
 */
class jsonApi {
    private $apiForData;

    public function __construct( $data ) {
        // save data for later use
        $this->$apiForData = $data;

        // check for ANY data
        if( count( $this->$apiForData ) > 0 ) {
            die( "Missing ALL data." );
        }

        // check for ACTION data
        if( !array_key_exists( 'action', $this->apiForData ) ) {
            die( "Missing ACTION data" );
        }

        // check for METHOD data
        if( !array_key_exists( 'method', $this->apiForData ) ) {
            die( "Missing METHOD data" );
        }

        // send to router
        $this->router( $this->apiForData['action'], $this->apiForData['method'] );
    }

    private function router( $action, $method ) {
        switch( strtolower( $action ) ) {
            case 'get':
                # code...
                break;
            
            case 'post':
                # code...
                break;

            case 'put':
                if( method_exists( 'jsonApi', 'putProductLead' ) ) {

                }
                break;

            case 'delete':
                # code...
                break;
        }
    }
}

// initialie api
$api = new jsonApi( $_GET );