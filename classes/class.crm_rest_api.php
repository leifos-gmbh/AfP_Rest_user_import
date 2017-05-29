<?php

/**
 * CRM - REST - API
 * @author Christian Friebel <xray@tecart.de>, Lam Nguyen <lam.nguyen@tecart.de>
 * @copyright TecArt GmbH &copy; 2011
 *
 */

class crm_rest_api {

    protected $curl   = false;
    protected $header = array();

    public function __construct($url = '')
    {
        if(empty($url)) {
            throw new Exception('No URL found.');
        }

        $this->curl = curl_init($url);

        if ($this->curl === false) {
            throw new Exception('Error by initialising cURL.');
        }

        $this->set_options();
    }

    /**
     * Set the options for cURL
     */
    protected function set_options()
    {
        $options = array( CURLOPT_POST           => true,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_HEADER         => false,
                          CURLOPT_SSL_VERIFYPEER => false,
                          CURLOPT_HEADERFUNCTION => array(&$this, 'set_header') );

        if (!curl_setopt_array($this->curl, $options)) {
            throw new Exception('Error by setting curl_setopt_array.');
        }

        return true;
    }

    /**
     * Set response header.
     */
    protected function set_header($handle, $header_line)
    {
        $this->header[] = $header_line;

        return strlen($header_line);
    }

    /**
     * Prepare and create the request.
     */
    public function __call($method, $parameters)
    {
        if (isset($parameters[0])) {
            $params = $parameters[0];
        }
        else {
            throw new Exception('Invalid parameters.');
        }

        $args = array(  'method'        => $method,
        				'response_type' => 'JSON',
        		        'request_type'  => 'JSON',
                        'params'        => json_encode($params) );

        if (!curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($args))) {
            throw new Exception('Error by setting CURLOPT_POSTFIELDS.');
        }

        return $this->sendRequest();
    }

    /**
     * Send the request to CRM Server.
     * @return : Result from json_decode or throw error message.
     */
    protected function sendRequest()
    {
        $response = curl_exec($this->curl);

        if(strpos($this->header[0], '200 OK') === false) {
            throw new Exception($response);
        }

        $this->header = array();

        return json_decode($response);
    }


    function __destruct() 
    {
         curl_close( $this->curl );
    }

}
?>