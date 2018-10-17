<?php

/**
 * Entity that stores authentication data
 *
 * @author Jan Malcanek
 */

namespace malcanek\iDoklad\auth;

use malcanek\iDoklad\iDokladException;

class iDokladCredentials {
    
    /**
     * Stores refresh token
     * @var string
     */
    private $refresh_token;
    
    /**
     * Stores access token
     * @var string
     */
    private $access_token;
    
    /**
     * Time after which access token expires
     * @var int
     */
    private $expires_in;
    
    /**
     * Timestamp of last validation to calculate whether access token is expired and should be refreshed
     * @var datetime
     */
    private $lastValidation;
    
    /**
     * Stores authentication type
     * @var string
     */
    private $authType = 'oauth2';
    
    /**
     * Initializes iDokladCredentials object and loads data
     * @param json|array $client
     * @param boolean $json
     * @throws iDokladException
     */
    public function __construct($client, $json = false) {
        if($json){
            $this->loadFromJson($client);
        } else {
            if(is_array($client)){
                $this->refresh_token = $client['refresh_token'];
                $this->access_token = $client['access_token'];
                $this->expires_in = $client['expires_in'];
                $this->lastValidation = $client['lastValidation'];
                $this->authType = $client['authType'];
            } else {
                throw new iDokladException('Invalid credential array.', 2);
            }
        }
    }
    
    /**
     * Returns refresh token
     * @return string
     */
    public function getRefreshToken(){
        return $this->refresh_token;
    }
    
    /**
     * Returns access token
     * @return string
     */
    public function getAccessToken(){
        return $this->access_token;
    }
    
    /**
     * Returns whether access token is expired or not
     * @return boolean
     */
    public function isExpired(){
        $time = strtotime($this->lastValidation) + $this->expires_in - 10;
        return $time < time();
    }
    
    /**
     * Returns authentication type
     * @return string
     */
    public function getAuthType(){
        return $this->authType;
    }
    
    /**
     * Sets authentication type
     * @param string $type
     */
    public function setAuthType($type){
        $this->authType = $type;
    }
    
    /**
     * Proccesses json to object
     * @param json $json
     */
    public function loadFromJson($json){
        $arr = json_decode($json, true);
        foreach($arr as $key => $val){
            $this->$key = $val;
        }
    }
    
    /**
     * Returns json from object
     * @return json
     */
    public function toJson(){
        return json_encode(get_object_vars($this));
    }
    
    /**
     * Adds last validation date
     * @param datetime $lastValidation
     */
    public function addLastValidation($lastValidation){
        $this->lastValidation = $lastValidation;
    }
}
