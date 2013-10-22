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
        'data/missing'            => array( 'status' => "error", 'msg' => "Missing ALL data." ),
        'action/missing'          => array( 'status' => "error", 'msg' => "Missing `action` data." ),
        'action/invalid'          => array( 'status' => "error", 'msg' => "Action requested isn't allowed." ),
        'method/missing'          => array( 'status' => "error", 'msg' => "Missing `method` data." ),
        'method/invalid'          => array( 'status' => "error", 'msg' => "Method requested doesn't exist." ),
        'response/invalid'        => array( 'status' => "error", 'msg' => "Invalid data passed to response method." ),
        'database/config/missing' => array( 'status' => "error", 'msg' => "Database configuration file is missing." ),
        'database/insert/error'   => array( 'status' => "error", 'msg' => "Problem occured on insertion into database." ),
        'lead/data/missing'       => array( 'status' => "error", 'msg' => "Missing required data." ),
        // success messages
        'lead/insert/success'     => array( 'status' => "success", 'msg' => "One of our consultants will contact you as soon as possible. Thank you!" )
    );
    private $apiValidActions = array( 'get', 'post', 'put', 'delete' );
    private $databaseConfig = array();
    private $environment;
    private $db; 

/**
 * PUBLIC METHODS
 */
    
    /**
     * Class constructor
     * @param array $data Data that the api will be using internally
     * @public
     */
    public function __construct( $data ) {
        // set environment, validating and uppercasing as insurance
        $this->setEnvironment();

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

        // connect database
        $this->databaseInit();

        // send to router
        $this->router( $this->apiData['action'], $this->apiData['method'] );
    }

/**
 * PRIVATE METHODS
 */
    /**
     * Set correct environment for class instance
     * @return void
     * @private
     */
    private function setEnvironment() {
        // capitalize and set correct environment
        $this->environment = getenv( 'ENVIRONMENT' );
        if( empty( $this->environment ) ) {
            $this->environment = 'DEVELOPMENT';
        }
        $this->environment = strtoupper( $this->environment );

        // hide warnings on productions
        if( $this->environment === 'PRODUCTION') {
            error_reporting( E_ERROR | E_PARSE );
        }
    }

    /**
     * Response method for the api. Returns everything in json (or jsonp) format.
     * @param  array  $responseData Response data in array format to be converted to JSON
     * @return output               Kills process with FINAL output
     * @private
     */
    private function response( $responseData ) {
        // check integrity of data
        if( !is_array( $responseData ) ) {
            // override `responseData` with error message
            $responseData = $this->apiResponses['response/invalid'];
        }

        // send data in JSONP format
        if( array_key_exists( 'callback', $this->apiData ) && !empty( $this->apiData['callback'] ) ) {
            die( $this->apiData['callback'].'('.json_encode( $responseData ).')' );
        }
        // send data in JSON format
        else {
            die( json_encode( $responseData ) );
        }
    }

    /**
     * Configure database and connect to it
     * @return void
     * @private
     */
    private function databaseInit() {
        // include database paths
        require_once "paths.php";

        // select correct db config
        if( isset( $dbEnvironments ) ) {
            $this->databaseConfig = $dbEnvironments[$this->environment];
        } else {
            // no db paths, error out
            $this->response( $this->apiResponses['database/config/missing'] );
        }

        // start database connection
        $this->db = new mysqli(
            $this->databaseConfig['hostname'],
            $this->databaseConfig['username'],
            $this->databaseConfig['password'],
            $this->databaseConfig['database']
        );

        // verify connection
        if( $this->db->connect_error ) {
            die( 'Connect Error ('.$this->db->connect_errno.') '.$this->db->connect_error );
        }

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

    /**
     * Validate required fields against pool of possible data fields
     * @param  array  $requiredFields  Array of required fields
     * @param  array  $availableFields Array of possible available fields
     * @param  string $errorToUse      Error code to use on possible requirement infracture
     * @return void
     * @private
     */
    private function validateFields( $requiredFields, $availableFields, $errorToUse = 'lead/data/missing' ) {
        // check for missing required fields and log them
        $isValid   = TRUE;
        $isMissing = array();
        foreach( $requiredFields as $field ) {
            if( !array_key_exists( $field, $availableFields ) || empty( $availableFields[$field] ) ) {
                array_push( $isMissing, "`$field` is missing." );
                $isValid = FALSE;
            }
        }
        
        // if anything is missing, through output here and halt script execution
        if( !$isValid ) {
            $errorMsg     = $this->apiResponses[$errorToUse];
            $errorMsg['debug'] = $isMissing;
            $this->response( $errorMsg );
        }
    }

    /**
     * Determine if a single field is available and has data
     * @param  array  $dbData Array of data going in to the database
     * @param  string $field  Name of field that will be checked
     * @return array          New array of data going into the database (possible with an extra field)
     * @private
     */
    private function filterForDbData( $dbData, $field ) {
        if( array_key_exists( $field, $this->apiData ) && !empty( $this->apiData[$field] ) ) {
            $dbData[strtoupper( $field )] = trim( $this->apiData[$field] );
        }
        return $dbData;
    }

    /**
     * Build all data for database (required and extra fields)
     * @param  array $possibleFields Array of possible fields to add into database data
     * @return array                 Complete database data array
     */
    private function buildDbData( $possibleFields ) {
        $dbData = array();
        foreach( $possibleFields as $field ) {
            $dbData = $this->filterForDbData( $dbData, $field );
        }
        return $dbData;
    }

    /**
     * Put NEW product lead in database
     * @return string Result of request in JSON format
     */
    private function _put_product_lead() {
        // validate required fields
        $this->validateFields( array( 'domain', 'name', 'telephone', 'product' ), $this->apiData );

        // prepare data for db
        $dbData = $this->buildDbData( array( 'domain', 'name', 'telephone', 'product', 'email', 'message' ) );
        if( count( $dbData ) ) {
            // set lead type
            $dbData['LEAD_TYPE'] = 'product';

            // insert into db
            $sql = "INSERT INTO `leads` ({FIELDS}) VALUES ({VALUES})";
            $sql = str_replace( array( '{FIELDS}', '{VALUES}' ), array( '`'.implode( '`, `', array_keys( $dbData ) ).'`', "'".implode( "','", array_values( $dbData ) )."'" ), $sql );

            // output request result
            if( $this->db->query( $sql ) ) {
                $successMsg       = $this->apiResponses['lead/insert/success'];
                $successMsg['id'] = $this->db->insert_id;
                $this->response( $successMsg );
            }
            // error occured, notify client
            else {
                $this->response( $this->apiResponses['database/insert/error'] );
            }
        }

        // missing db data
        $errorMsg     = $this->apiResponses['lead/data/missing'];
        $errorMsg['debug'] = array( 'No Database data present.' );
        $this->response( $errorMsg );
    }

    /**
     * Put NEW contact lead in database
     * @return string Result of request in JSON format
     */
    private function _put_contact_lead() {
        // validate required fields
        $this->validateFields( array( 'domain', 'name', 'telephone' ), $this->apiData );

        // prepare data for db
        $dbData = $this->buildDbData( array( 'domain', 'name', 'telephone', 'email', 'message' ) );
        if( count( $dbData ) ) {
            // set lead type
            $dbData['LEAD_TYPE'] = 'contact';

            // insert into db
            $sql = "INSERT INTO `leads` ({FIELDS}) VALUES ({VALUES})";
            $sql = str_replace( array( '{FIELDS}', '{VALUES}' ), array( '`'.implode( '`, `', array_keys( $dbData ) ).'`', "'".implode( "','", array_values( $dbData ) )."'" ), $sql );

            // output request result
            if( $this->db->query( $sql ) ) {
                $successMsg       = $this->apiResponses['lead/insert/success'];
                $successMsg['id'] = $this->db->insert_id;
                $this->response( $successMsg );
            }
            // error occured, notify client
            else {
                $this->response( $this->apiResponses['database/insert/error'] );
            }
        }

        // missing db data
        $errorMsg     = $this->apiResponses['lead/data/missing'];
        $errorMsg['debug'] = array( 'No Database data present.' );
        $this->response( $errorMsg );
    }

}

// initialie api and process request
$api = new jsonApi( count( $_GET ) ? $_GET : array() );