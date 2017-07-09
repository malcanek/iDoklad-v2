<?php
/**
 * Class represents entity for one iDoklad response. Each response is parsed into this object.
 *
 * @author honza
 */

namespace malcanek\iDoklad\request;
use malcanek\iDoklad\iDokladException;

class iDokladResponse {
    
    /**
     * Response code from iDoklad
     * @var int
     */
    private $code;
    
    /**
     * Data returned from iDoklad
     * @var array
     */
    private $data;
    
    /**
     * Headers returned from request
     * @var string
     */
    private $headers;
    
    /**
     * Number of items returned. These items are in data var.
     * @var int
     */
    private $totalItems;
    
    /**
     * Number of pages available
     * @var int
     */
    private $totalPages;
    
    /**
     *
     * @var string
     */
    private $links;
    
    /**
     * Stores return messages for codes
     * @var array
     */
    private $codesCZ = array(
        200 => 'operace proběhla úspěšně a vrátila data',
        201 => 'změna existujících dat',
        204 => 'změna byla provedená úspěšně ale nebyla vrácena žádná data',
        400 => 'špatný formát/validační chyba (více informace bude uvedeno v odpovědi)',
        401 => 'nepřihlášený uživatel/špatné přihlašovací údaje',
        402 => 'operace vyžaduje zakoupení předplatného',
        403 => 'nutnost přihlášení do webového prostředí iDokladu a vyřešení přechodu na nižší předplatné',
        404 => 'resource nenalezen/špatné Id resourcu',
        500 => 'interní chyba serveru - v tomto případě kontaktujte naší technickou podporu',
        'no_text' => 'kód nemá přiřazen žádný text'
    );
    
    /**
     * Initializes iDoklad response
     * @param string $rawOutput
     * @param int $headerSize
     * @param int $code
     */
    public function __construct($rawOutput, $headerSize, $code) {
        $this->code = $code;
        $this->headers = substr($rawOutput, 0, $headerSize);
        
        $parsed = $this->parseJSON(trim(substr($rawOutput, $headerSize)));
        $this->data = $parsed['Data'];
        $this->links = $parsed['Links'];
        $this->totalItems = $parsed['TotalItems'];
        $this->totalPages = $parsed['TotalPages'];
    }
    
    /**
     * Returns response code
     * @return int
     */
    public function getCode(){
        return $this->code;
    }
    
    /**
     * Returns response code text in czech
     * @return string
     */
    public function getCodeText(){
        return (isset($this->codesCZ[$this->code]) ? $this->codesCZ[$this->code] : $this->codesCZ['no_text']);
    }
    
    /**
     * Returns parsed response data
     * @return array
     */
    public function getData(){
        return $this->data;
    }
    
    /**
     * Returns total items returned in request
     * @return int
     */
    public function getTotalItems(){
        return $this->totalItems;
    }
    
    /**
     * Returns total pages that are possible to return in requests
     * @return int
     */
    public function getTotalPages(){
        return $this->totalPages;
    }
    
    /**
     * 
     * @return 
     */
    public function getLink(){
        return $this->links;
    }
    
    /**
     * Parses json string
     * @param string $json
     * @return array
     * @throws iDokladException
     */
    private function parseJSON($json){
        if($this->getCode() == 400){
            $this->data = $json;
            throw new iDokladException('Request error: '.$json);
        }
        $parsed = json_decode($json, true);
        $le = json_last_error();
        if($le != JSON_ERROR_NONE){
            $errors = array(
                JSON_ERROR_NONE => 'No error',
                JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
                JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
                JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
                JSON_ERROR_SYNTAX => 'Syntax error',
                JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
            );
            throw new iDokladException('JSON error: '.(isset($errors[$le]) ? $errors[$le] : 'Unknown error'), $le);
        }
        return $parsed;
    }
    
}
