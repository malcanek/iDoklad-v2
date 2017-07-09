<?php

/**
 * Class that handles authentication and access tokens
 *
 * @author Jan Malcanek
 */

namespace malcanek\iDoklad\auth;

use malcanek\iDoklad\iDokladException;

class iDokladAuth {
    
    /**
     * Holds authorize URL
     * @var string
     */
    private $authorizeUrl = 'https://app.idoklad.cz/identity/server/connect/authorize';
    
    /**
     * Holds token URL
     * @var string
     */
    private $tokenUrl = 'https://app.idoklad.cz/identity/server/connect/token';
    
    /**
     * Holds client id set by developer
     * @var string
     */
    private $clientId;
    
    /**
     * Holds client secret set by developer
     * @var string
     */
    private $clientSecret;
    
    /**
     * Holds redirect URI to get back from authentication url
     * @var string
     */
    private $redirectUri;
    
    /**
     * Holds code when returned from authentication url
     * @var string
     */
    private $code;
    
    /**
     * Holds iDokladCredentials object
     * @var iDokladCredentials
     */
    private $credentials;
    
    /**
     * Holds credentials callback object
     * @var callable
     */
    private $credentialsCallback;
    
    /**
     * Initializes iDokladAuth object
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     */
    public function __construct($clientId, $clientSecret, $redirectUri) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
    }
    
    /**
     * Chooses authenticate method and gets credentials
     * @param string $authType
     * @param \malcanek\iDoklad\auth\iDokladCredentials $credentials
     * @throws iDokladException
     */
    public function auth($authType = 'oauth2', iDokladCredentials $credentials = null) {
        $this->credentials = $credentials;
        switch ($authType){
            case 'oauth2':
                $this->oauth2();
                break;
            case 'ccf':
                $this->ccf();
                break;
            default :
                throw new iDokladException('Unknown access type '.$type.'. (Allowed oauth2, ccf).', 1);
        }
    }
    
    /**
     * Provides oauth2 authentication and in case of success returns iDokladCredentials object
     * @return iDokladCredentials
     * @throws iDokladException
     */
    private function oauth2(){
        if(empty($this->credentials) && empty($this->code)){
            throw new iDokladException('Load authentication screen first.', 4);
        }
        if(empty($this->credentials) && isset($this->code)){
            $params = array('grant_type' => 'authorization_code', 'client_id' => $this->clientId, 'client_secret' => $this->clientSecret, 'scope' => 'idoklad_api%20offline_access', 'code' => $this->code, 'redirect_uri' => $this->redirectUri);
            $json = $this->curl($params);
            $this->credentials = new iDokladCredentials($json, true);
            $this->credentials->addLastValidation(date('Y-m-d H:i:s'));
            $this->callCredentialsCallback();
            return $this->credentials;
        }
    }
    
    /**
     * Refreshes access token after expiration
     * @return iDokladCredentials
     * @throws iDokladException
     */
    public function oauth2Refresh(){
        if(!empty($this->credentials)){
            $params = array('grant_type' => 'refresh_token', 'client_id' => $this->clientId, 'client_secret' => $this->clientSecret, 'scope' => 'idoklad_api%20offline_access', 'refresh_token' => $this->credentials->getRefreshToken(), 'redirect_uri' => $this->redirectUri);
            $json = $this->curl($params);
            if(!empty($json)){
                $ret = json_encode($json);
                if(!isset($ret['error'])){
                    $this->credentials = new iDokladCredentials($json, true);
                    $this->credentials->addLastValidation(date('Y-m-d H:i:s'));
                    $this->credentials->setAuthType('oauth2');
                    $this->callCredentialsCallback();
                    return $this->credentials;
                } else {
                    throw new iDokladException('Error occured while refreshing token. Message: '.$ret['error']);
                }
            }
        }
    }
    
    /**
     * Authenticates user by ccf method
     * @return iDokladCredentials
     */
    private function ccf(){
        $params = array('grant_type' => 'client_credentials', 'client_id' => $this->clientId, 'client_secret' => $this->clientSecret, 'scope' => 'idoklad_api');
        $json = $this->curl($params);
        $this->credentials = new iDokladCredentials($json, true);
        $this->credentials->addLastValidation(date('Y-m-d H:i:s'));
        $this->credentials->setAuthType('ccf');
        $this->callCredentialsCallback();
        return $this->credentials;
    }
    
    /**
     * Provides curl to get authentication json
     * @param array $params
     * @return string
     */
    private function curl(array $params){
        $curl = curl_init();
        $curl_opt = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->tokenUrl,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => http_build_query($params)
        );
        curl_setopt_array($curl, $curl_opt);
        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }
    
    /**
     * Returns authenticaion URL
     * @return string
     */
    public function getAuthenticationUrl(){
        return $this->authorizeUrl.'?scope=idoklad_api%20offline_access&response_type=code&client_id='.$this->clientId.'&redirect_uri='.$this->redirectUri;
    }
    
    /**
     * Sets code returned by callback from authentication URL
     * @param string $code
     */
    public function loadCode($code){
        $this->code = $code;
    }
    
    /**
     * Returns iDokladCredentials
     * @return iDokladCredentials
     */
    public function getCredentials(){
        return $this->credentials;
    }
    
    /**
     * Calls callback function after credetials change
     */
    private function callCredentialsCallback(){
        if(!empty($this->credentialsCallback) && is_callable($this->credentialsCallback)){
            call_user_func($this->credentialsCallback, $this->credentials);
        }
    }
    
    /**
     * Sets iDokladCredentials to provide authentication
     * @param \malcanek\iDoklad\auth\iDokladCredentials $credentials
     */
    public function setCredentials(iDokladCredentials $credentials){
        $this->credentials = $credentials;
    }
    
    /**
     * Sets callback function that is called after credentials change
     * @param callable $callback
     */
    public function setCredentialsCallback(callable $callback){
        $this->credentialsCallback = $callback;
    }
}
