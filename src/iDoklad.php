<?php

/**
 * Main iDoklad object. Collects all iDoklad options together and handles all main actions.
 *
 * @author Jan Malcanek
 */

namespace malcanek\iDoklad;

include_once __DIR__.'/auth/iDokladAuth.php';
include_once __DIR__.'/auth/iDokladCredentials.php';
include_once __DIR__.'/iDokladException.php';
include_once __DIR__.'/request/iDokladRequest.php';
include_once __DIR__.'/request/iDokladResponse.php';
include_once __DIR__.'/request/iDokladFilter.php';
include_once __DIR__.'/request/iDokladSort.php';

use malcanek\iDoklad\auth\iDokladAuth;
use malcanek\iDoklad\iDokladException;
use malcanek\iDoklad\request\iDokladRequest;
use malcanek\iDoklad\request\iDokladResponse;
use malcanek\iDoklad\auth\iDokladCredentials;
use malcanek\iDoklad\request\iDokladFilter;
use malcanek\iDoklad\request\iDokladSort;

class iDoklad {

    /**
     * Stores iDokladAuth object
     * @var iDokladAuth
     */
    private $iDokladAuth;

    /**
     * URL for commucation with iDoklad api
     * @var string
     */
    private $url = 'https://app.idoklad.cz/developer/api/v2/';

    /**
     * Api methods allowed by iDoklad
     * @var array
     */
    private $methodsAllowed = array('GET', 'POST', 'PATCH', 'PUT', 'DELETE');

    /**
     * Api langs allowed by iDoklad
     * @var array
     */
    private $langsAllowed = array('en-US', 'de-DE', 'sk-SK', 'cs-CZ');

    /**
     * Helper array to get full lang code.
     * @var array
     */
    private $langsShortToLong = array('cz' => 'cs-CZ', 'de' => 'de-DE', 'sk' => 'sk-SK', 'en' => 'en-US', 'cs' => 'cs-CZ');

    /**
     * Whether or not to throw exception when request get fail http code
     * @var boolean
     */
    private $httpException = false;

    /**
     * Initilizes iDoklad object with necessary parameters
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param iDokladCredentials $credentials
     */
    public function __construct($clientId, $clientSecret, $redirectUri, $credentials = null) {
        $this->iDokladAuth = new iDokladAuth($clientId, $clientSecret, $redirectUri);
        if($credentials != null){
            $this->iDokladAuth->setCredentials($credentials);
        }
    }

    /**
     * Returns url to iDoklad to sign in user and get back code to get credentials
     * @return string
     */
    public function getAuthenticationUrl(){
        return $this->iDokladAuth->getAuthenticationUrl();
    }

    /**
     * Requests authentifications credentials based on code returned as callback from url from getAuthenticationUrl() function
     * @param string $code
     */
    public function requestCredentials($code){
        $this->iDokladAuth->loadCode($code);
        $this->iDokladAuth->auth(iDokladAuth::AUTH_TYPE_OAUTH2);
    }

    /**
     * Returns credential object
     * @return iDokladCredentials
     */
    public function getCredentials(){
        return $this->iDokladAuth->getCredentials();
    }

    /**
     * Sets iDokladCredentials object to authenticate users
     * @param iDokladCredentials $credentials
     */
    public function setCredentials(iDokladCredentials $credentials){
        $this->iDokladAuth->setCredentials($credentials);
    }

    /**
     * Sets callback function, that is called after iDokladCredentials object change and puts new iDokladCredentials object as parameter
     * @param callable $callback
     */
    public function setCredentialsCallback(callable $callback){
        $this->iDokladAuth->setCredentialsCallback($callback);
    }

    /**
     * Authenticates via ccf method
     */
    public function authCCF(){
        $this->iDokladAuth->auth(iDokladAuth::AUTH_TYPE_CCF);
    }

    /**
     * Enable http exceptions
     */
    public function httpExceptionsOn(){
        $this->httpException = true;
    }

    /**
     * Disable http exceptions
     */
    public function httpExceptionsOff(){
        $this->httpException = false;
    }

    /**
     * Check if iDokladCredential object has valid informations
     * @throws iDokladException
     */
    private function checkAuthToken(){
        if(empty($this->iDokladAuth->getCredentials()) || empty($this->iDokladAuth->getCredentials()->getAccessToken()) || (empty($this->iDokladAuth->getCredentials()->getRefreshToken()) && $this->iDokladAuth->getCredentials()->getAuthType() === iDokladAuth::AUTH_TYPE_OAUTH2)){
            throw new iDokladException('Invalid credentials');
        }
        if($this->iDokladAuth->getCredentials()->isExpired()){
            $this->iDokladAuth->reAuth();
        }
    }

    /**
     * Builds and sends request to iDoklad api
     * @param iDokladRequest $request
     * @return iDokladResponse
     * @throws iDokladException
     */
    public function sendRequest(iDokladRequest $request){
        $this->checkAuthToken();
        $curl = curl_init();
        $headers = array('Authorization: Bearer '.$this->iDokladAuth->getCredentials()->getAccessToken());
        if($request->getLang() != null){
            if(in_array($request->getLang(), $this->langsAllowed)){
                $headers[] = 'Accept-Language: '.$request->getLang();
            } elseif(array_key_exists($request->getLang(), $this->langsShortToLong)) {
                $headers[] = 'Accept-Language: '.$this->langsShortToLong[$request->getLang()];
            } else {
                throw new iDokladException('Wrong language selected');
            }
        }

        if($request->isAttachement()) {
            $headers[] = 'Content-Type: multipart/form-data';
        } elseif(in_array($request->getMethodType(), ['POST', 'PUT', 'PATCH', 'DELETE']) || !empty(($request->getPostParams()))){
            $headers[] = 'Content-Type: application/json';
        }
        $curl_opt = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $this->url.'/'.$request->getMethod().'?'.$request->buildGetQuery(),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HEADER => 1
        );

        if($request->isAttachement()) {
            $curl_opt[CURLOPT_POSTFIELDS] = ['FileBytes' => $request->getFile()];
            $curl_opt[CURLOPT_CUSTOMREQUEST] = 'PUT';
        } elseif(in_array($request->getMethodType(), $this->methodsAllowed) && $request->getMethodType() != 'GET'){
            $curl_opt[CURLOPT_POSTFIELDS] = $request->buildPostQuery();
            if($request->getMethodType() != 'POST'){
                $curl_opt[CURLOPT_CUSTOMREQUEST] = $request->getMethodType();
            }
        }
        curl_setopt_array($curl, $curl_opt);
        $data = curl_exec($curl);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        $type = substr($request->getMethod(), 0, strpos($request->getMethod(), '/'));

        $response = new iDokladResponse($data, $header_size, $status, $this->httpException, $type === 'Attachments' || $request->isBinary());
        $response->setType($type);
        return $response;
    }
}
