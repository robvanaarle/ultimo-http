<?php

namespace ultimo\net\http\headers;

class Cookie extends \ultimo\net\http\Header {
  public $values;
  
  public function __construct(array $values = array()) {
    $this->values = $values;
  }
  
  /**
   * Returns the name of the header.
   * @return string The name of the header. 
   */
  public function getHeaderName() {
    return 'Cookie';
  }
  
   /**
   * Returns the string representation of this instance.
   * @return string String representation of this instance.
   */
  public function getHeaderValue() {
    $data = array();
    foreach ($this->values as $name => $value) {
      $value = SetCookie::encodeValue($value);
      $data[] = "{$name}={$value}";
    }
    return implode('; ', $data);
  }
  
  public function setHeaderValue($value) {
    $values = array();
    $cookies = explode('; ', $value);
    foreach ($cookies as $cookieData) {
      $pos = strpos($cookieData, '=');
      
      if ($pos !== false) {
        $name = substr($cookieData, 0, $pos);
        $value = SetCookie::decodeValue(substr($cookieData, $pos+1));
        $values[$name] = $value;
      }
    }
    
    return new static($values);
  }
}