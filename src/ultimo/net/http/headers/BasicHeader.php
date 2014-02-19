<?php

namespace ultimo\net\http\headers;

class BasicHeader extends \ultimo\net\http\Header {
  /**
   * Name of the header.
   * @var string 
   */
  public $name;
  
  /**
   * Value of the header.
   * @var string
   */
  public $value;
  
  /**
   * Constructor.
   * @param string $name Name of the header.
   * @param string $value Value of the header.
   */
  public function __construct($name, $value) {
    $this->name = $name;
    $this->value = $value;
  }
  
  /**
   * Returns the name of the header.
   * @return string The name of the header. 
   */
  public function getHeaderName() {
    return $this->name;
  }
  
  /**
   * Returns the value of the header.
   * @return string The value of the header. 
   */
  public function getHeaderValue() {
    return $this->value;
  }
  
  public function setHeaderValue($value) {
    $this->value = $value;
  }
  
  static public function fromString($headerData) {
    $pos = strpos($headerData, ':');
    if ($pos !== false) {
      $name = substr($headerData, 0, $pos);
      $value = substr($headerData, $pos+2);
      
      return new static($name, $value);
    }
    return null;
  }
  
  public function getItem($index, $delimiter=';') {
    $items = explode($delimiter, $this->getHeaderValue());
    if (!isset($items[$index])) {
      return null;
    }
    return $items[$index];
  }
}
