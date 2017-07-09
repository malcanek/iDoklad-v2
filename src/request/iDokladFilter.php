<?php
/**
 * Class entity that represents one request filter. Every request can have multiple filters.
 *
 * @author Jan Malcánek
 */

namespace malcanek\iDoklad\request;

use malcanek\iDoklad\iDokladException;

class iDokladFilter {
    
    /**
     * Consists of allowed operators.
     * @var array
     */
    private $queryOperators = array('lt' => '<', 'lte' => '<=', 'gt' => '>', 'gte' => '>=', 'eq' => '==', '!eq' => '!=', 'ct' => 'contains', '!ct' => '!contains', 'between' => '<>');
    
    /**
     *
     * @var string
     */
    private $querySepartor = '~';
    
    /**
     * Name of property to filter
     * @var string
     */
    private $propertyName;
    
    /**
     * Filter operator
     * @var string
     */
    private $operator;
    
    /**
     * Filter value
     * @var string
     */
    private $propertyValue;

    /**
     * Optionally inicialize whole filter.
     * @param string $propertyName
     * @param string $operator
     * @param string $propertyValue
     * @throws iDokladException
     */
    public function __construct($propertyName = null, $operator = null, $propertyValue = null) {
        if(($operator == '<>' || $operator == 'between') && !is_array($propertyValue)){
            throw new iDokladException('propertyValue has to be array when using between operator');
        }
        $this->propertyName = $propertyName;
        if(!in_array($operator, $this->queryOperators) && !in_array($operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } elseif(in_array($operator, $this->queryOperators)) {
            $this->operator = array_search($operator, $this->queryOperators);
        } else {
            $this->operator = $operator;
        }
        $this->propertyValue = $propertyValue;
    }
    
    /**
     * Adds property name of filter
     * @param string $propertyName
     * @return \malcanek\iDoklad\request\iDokladFilter
     */
    public function addPropertyName(string $propertyName){
        $this->propertyName = $propertyName;
        return $this;
    }
    
    /**
     * Adds filter operator
     * @param string $operator
     * @return \malcanek\iDoklad\request\iDokladFilter
     * @throws iDokladException
     */
    public function addOperator(string $operator){
        if(!in_array($operator, $this->queryOperators) && !in_array($operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } elseif(in_array($operator, $this->queryOperators)) {
            $this->operator = array_search($operator, $this->queryOperators);
        } else {
            $this->operator = $operator;
        }
        return $this;
    }
    
    /**
     * Adds filter value, mostly string, in case of operator between array
     * @param mixed $propertyValue
     * @return \malcanek\iDoklad\request\iDokladFilter
     */
    public function addPropertyValue(mixed $propertyValue){
        $this->propertyValue = $propertyValue;
        return $this;
    }
    
    /**
     * Builds filter string by its specification
     * @return string
     * @throws iDokladException
     */
    public function buildQuery(){
        if(empty($this->propertyValue)){
            throw new iDokladException("Property value in filter cannot be empty");
        }
        if(empty($this->propertyName)){
            throw new iDokladException("Property name in filter cannot be empty");
        }
        if(empty($this->operator)){
            throw new iDokladException("Operator in filter cannot be empty");
        }
        
        if(($this->operator == '<>' || $this->operator == 'between') && !is_array($this->propertyValue)){
            throw new iDokladException('propertyValue has to be array when using between operator');
        } elseif($this->operator == '<>' || $this->operator == 'between') {
            return $this->propertyName.$this->querySepartor.'>'.array_shift($this->propertyValue).'|'.$this->propertyName.$this->querySepartor.'<'.array_shift($this->propertyValue);
        }
        
        if(!in_array($this->operator, array_keys($this->queryOperators))){
            throw new iDokladException('Invalid operator');
        } else {
            return $this->propertyName.$this->querySepartor.$this->operator.$this->querySepartor.$this->propertyValue;
        }
    }
}
