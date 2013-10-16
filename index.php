<?php
/**
 * JSONP Api
 * --------
 */
class jsonApi {

/**
 * PRIVATE DATA
 */
    private $apiData;
    private $apiResponses = array(
        // errors messages
        'data/missing'     => array( 'status' => "error", 'msg' => "Missing ALL data." ),
        'action/missing'   => array( 'status' => "error", 'msg' => "Missing `action` data." ),
        'action/invalid'   => array( 'status' => "error", 'msg' => "Action requested isn't allowed." ),
        'method/missing'   => array( 'status' => "error", 'msg' => "Missing `method` data." ),
        'method/invalid'   => array( 'status' => "error", 'msg' => "Method requested doesn't exist." ),
        'response/invalid' => array( 'status' => "error", 'msg' => "Invalid data passed to response method." ),
        // success messages
        'consultant/lead/success' => array( 'status' => "success", 'msg' => "Consultant lead created." )
    );
    private $apiValidActions = array( 'get', 'post', 'put', 'delete' );

/**
 * PUBLIC METHODS
 */
    
    /**
     * Class constructor
     * @param array $data Data that the api will be using internally
     * @public
     */
    public function __construct( $data ) {
        // save data for later use
        $this->apiData = $data;
        
        // check for ANY data
        if( count( $this->apiData ) == 0 ) {
            $this->response( $this->apiResponses['data/missing'] );
        }

        // check for ACTION data
        if( !array_key_exists( 'action', $this->apiData ) ) {
            $this->response( $this->apiResponses['action/missing'] );
        }

        // check for METHOD data
        if( !array_key_exists( 'method', $this->apiData ) ) {
            $this->response( $this->apiResponses['method/missing'] );
        }

        // check action
        if( !in_array( $this->apiData['action'], $this->apiValidActions ) ) {
            $this->response( $this->apiResponses['action/invalid'] );
        }

        // send to router
        $this->router( $this->apiData['action'], $this->apiData['method'] );
    }

/**
 * PRIVATE METHODS
 */

    /**
     * Response method for the api. Returns everything in json format.
     * @param  array  $responseData Response data in array format to be converted to JSON
     * @return output               Kills process with FINAL output
     */
    private function response( $responseData ) {
        // check integrity of data
        if( !is_array( $responseData ) ) {
            // override `responseData` with error message
            $responseData = $this->apiResponses['response/invalid'];
        }

        // send data in JSON format
        die( json_encode( $responseData ) );
    }

    /**
     * Router method that routes request to correct destination
     * @param  string $action Name of action type requested
     * @param  string $method Name of method requested
     * @return void
     * @private
     */
    private function router( $action, $method ) {
        $action = strtolower( $action );
        $method = '_'.$action.'_'.$method;

        // validate method requested
        if( !method_exists( 'jsonApi', $method ) ) {
            $this->response( $this->apiResponses['method/invalid'] );
        }

        // call requested method
        call_user_func( array( $this, $method ) );
    }

    private function _put_product_lead() {
        $this->response( "IN HERE" );
    }

    private function _put_consultant_lead() {
        $this->response( $this->apiResponses['consultant/lead/success'] );
    }

}

// initialie api and process request
$api = new jsonApi( count( $_GET ) ? $_GET : array() );