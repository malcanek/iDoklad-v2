<?php
/**
 * Class represents entity for one api request. Collects data and then returns data to provide request.
 *
 * @author Jan Malcánek
 */

namespace malcanek\iDoklad\request;

include_once __DIR__.'/iDokladFilter.php';
include_once __DIR__.'/iDokladSort.php';

use malcanek\iDoklad\iDokladException;
use malcanek\iDoklad\request\iDokladFilter;
use malcanek\iDoklad\request\iDokladSort;

class iDokladRequest {
    
    /**
     * Holds method (IssuedInvoices)
     * @var string
     */
    private $method;
    
    /**
     * Holds post parameters
     * @var array
     */
    private $postParams = array();
    
    /**
     * Holds get parameters
     * @var array
     */
    private $getParams = array();
    
    /**
     * Holds methodType (e.g. GET, POST)
     * @var string
     */
    private $methodType = 'GET';
    
    /**
     * Holds filters
     * @var array
     */
    private $filters = array();
    
    /**
     * Holds sorts
     * @var array
     */
    private $sorts = array();
    
    /**
     * Holds request lang if set
     * @var string
     */
    private $lang = null;
    
    /**
     * Holds file for cases when we want to send attachement
     * @var CURLFile
     */
    private $file = null;
    
    /**
     * Detects if attachement is being send
     * @var bool
     */
    private $sendAttachement = false;
    
    /**
     * Indicates that response will be binary Base64 encoded file
     * @var boolean
     */
    private $isBinary = false;

    /**
     * Optionally initializes request parameters
     * @param string $method
     * @param string $methodType
     * @param array $getParameters
     * @param array $postParameters
     */
    public function __construct($method = null, $methodType = 'GET', $getParameters = array(), $postParameters = array()) {
        $this->method = $method;
        $this->methodType = $methodType;
        $this->getParams = $getParameters;
        $this->postParams = $postParameters;
    }
    
    /**
     * Sets api method (e.g. IssuedInvoices)
     * @param string $method
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function setMethod($method){
        $this->method = $method;
        return $this;
    }
    
    /**
     * Sets api response language
     * @param string $lang
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function setLang($lang){
        $this->lang = $lang;
        return $this;
    }

    /**
     * Adds request post parameters from array
     * @param array $params
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addPostParameters(array $params){
        $this->postParams = $params;
        return $this;
    }
    
    /**
     * Adds request post parameter by key and value
     * @param string $key
     * @param string $value
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addPostParameter($key, $value){
        $this->postParams[$key] = $value;
        return $this;
    }
    
    /**
     * Adds request get parameters
     * @param array $params
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addGetParameters(array $params){
        $this->getParams = $params;
        return $this;
    }
    
    /**
     * Adds reuqest get parameters by key and value
     * @param string $key
     * @param string $value
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addGetParameter($key, $value){
        $this->getParams[$key] = $value;
        return $this;
    }
    
    /**
     * Sets method type (e.g. GET, POST)
     * @param string $methodType
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addMethodType($methodType){
        $this->methodType = $methodType;
        return $this;
    }
    
    /**
     * Adds data filter
     * @param \malcanek\iDoklad\request\iDokladFilter $filter
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addFilter(iDokladFilter $filter){
        $this->filters[] = $filter;
        return $this;
    }
    
    /**
     * Adds data sort
     * @param \malcanek\iDoklad\request\iDokladSort $sort
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addSort(iDokladSort $sort){
        $this->sorts[] = $sort;
        return $this;
    }
    
    /**
     * 
     * @param CURLFile $file
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function addFile(\CURLFile $file) {
        $this->file = $file;
        $this->sendAttachement = true;
        return $this;
    }
    
    /**
     * Declares page from pagination
     * @param int $page
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function setPage($page){
        $this->getParams['page'] = $page;
        return $this;
    }
    
    /**
     * Declares number of returned items by request
     * @param int $pageSize
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function setPageSize($pageSize){
        $this->getParams['pagesize'] = $pageSize;
        return $this;
    }
    
    /**
     * Sets filter type allowed and and or
     * @param string $type
     * @return \malcanek\iDoklad\request\iDokladRequest
     * @throws iDokladException
     */
    public function setFilterType($type){
        if($type != 'and' && $type != 'or'){
            throw new iDokladException('Filter type must be \'and\' or \'or\'');
        }
        $this->getParams['filtertype'] = $type;
        return $this;
    }

    /**
     * Builds http query from get parameters. Auto adds filters and sorts to get query
     * @return string
     * @throws iDokladException
     */
    public function buildGetQuery(){
        $filterString = array();
        foreach($this->filters as $filter){
            $filterString[] = $filter->buildQuery();
        }
        if(!empty($filterString)){
            $this->addGetParameter("filter", implode('|', $filterString));
        }
        $sortString = array();
        foreach($this->sorts as $sort){
            $sortString[] = $sort->buildQuery();
        }
        if(!empty($sortString)){
            $this->addGetParameter("sort", implode('|', $sortString));
        }
        if(is_array($this->getParams)){
            return http_build_query($this->getParams);
        } else {
            throw new iDokladException('Get parameters have to be array');
        }
    }
    
    /**
     * Builds http query from post parameters.
     * @return string
     * @throws iDokladException
     */
    public function buildPostQuery(){
        if(is_array($this->postParams)){
            return json_encode($this->postParams);
        } else {
            throw new iDokladException('Post parameters have to be array');
        }
    }
    
    /**
     * Returns setted method to request.
     * @return string
     */
    public function getMethod(){
        return trim($this->method, '/');
    }
    
    /**
     * Returns setted method type.
     * @return string
     */
    public function getMethodType(){
        return $this->methodType;
    }
    
    /**
     * Returns setted lang.
     * @return string
     */
    public function getLang(){
        return $this->lang;
    }
    
    /**
     * Sets request method type as get
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function get(){
        $this->methodType = 'GET';
        return $this;
    }
    
    /**
     * Sets request method type as post
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function post(){
        $this->methodType = 'POST';
        return $this;
    }
    
    /**
     * Sets request method type as put
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function put(){
        $this->methodType = 'PUT';
        return $this;
    }
    
    /**
     * Sets request method type as delete
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function delete(){
        $this->methodType = 'DELETE';
        return $this;
    }
    
    /**
     * Sets request method type as patch
     * @return \malcanek\iDoklad\request\iDokladRequest
     */
    public function patch(){
        $this->methodType = 'PATCH';
        return $this;
    }
    
    /**
     * Returns post params
     * @return array
     */
    public function getPostParams(){
        return $this->postParams;
    }
    
    /**
     * Return file
     * @return CURLFile
     */
    public function getFile() {
        return $this->file;
    }
    
    /**
     * Returns if we are sending attachement
     * @return bool
     */
    public function isAttachement() {
        return $this->sendAttachement;
    }
    
    /**
     * Indicates that response will be binary Base64 encoded file
     */
    public function binary() {
        $this->isBinary = true;
        return $this;
    }
    
    /**
     * Returns if response is going to be binary
     * @return boolean
     */
    public function isBinary() {
        return $this->isBinary;
    }
}
