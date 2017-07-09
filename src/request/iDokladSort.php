<?php
/**
 * Class represents entity for one sort. Every request can have multiple sorts.
 *
 * @author Jan Malcánek
 */

namespace malcanek\iDoklad\request;

use malcanek\iDoklad\iDokladException;

class iDokladSort {
    
    /**
     *
     * @var string
     */
    private $querySepartor = '~';
    
    /**
     * Name of property to sort
     * @var string
     */
    private $propertyName;
    
    /**
     * Sort order, can be asc or desc
     * @var string
     */
    private $sortOrder;
    
    /**
     * Orders allowed by iDoklad api
     * @var array
     */
    private $allowedOrder = array('asc', 'desc');
    
    /**
     * Optionally initializes sort with all necessary datas
     * @param string $propertyName
     * @param string $sortOrder
     */
    public function __construct($propertyName = null, $sortOrder = null) {
        $this->propertyName = $propertyName;
        $sortOrder = strtolower($sortOrder);
        if(!in_array($sortOrder, $this->allowedOrder)){
            throw new iDokladException('Unknown sort order');
        } else {
            $this->sortOrder = $sortOrder;
        }
    }
    
    /**
     * Adds property name to sort
     * @param string $propertyName
     * @return \malcanek\iDoklad\request\iDokladSort
     */
    public function addPropertyName(string $propertyName){
        $this->propertyName = $propertyName;
        return $this;
    }
    
    /**
     * Adds sort order. Can be asc or desc
     * @param string $sortOrder
     * @return \malcanek\iDoklad\request\iDokladSort
     */
    public function addSortOrder(string $sortOrder){
        $this->sortOrder = $sortOrder;
        return $this;
    }
    
    /**
     * Returns sort query built by its specifications
     * @return string
     * @throws iDokladException
     */
    public function buildQuery(){
        if(empty($this->propertyName)){
            throw new iDokladException("Property name in sort cannot be empty");
        }
        if(empty($this->sortOrder) || (strtolower($this->sortOrder) !== 'asc' && strtolower($this->sortOrder) !== 'desc')){
            throw new iDokladException("Sort order must be 'asc' or 'desc'");
        }
        
        return $this->propertyName.$this->querySepartor.$this->sortOrder;
    }
}
