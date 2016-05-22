<?php

namespace ultimo\net\http;

abstract class Header {
  /**
   * Returns the name of the header.
   * @return string The name of the header. 
   */
  abstract public function getHeaderName();
  
  /**
   * Returns the value of the header.
   * @return string The value of the header. 
   */
  abstract public function getHeaderValue();
  
  abstract public function setHeaderValue($value);
  
  public function defaultPhpOutput() {
    header((string) $this);
  }
  
  /**
   * Returns the string representation of this instance: header text ready to
   * use in a http request of response.
   * @reutrn string The string representation of this instance. 
   */
  public function __toString() {
    return "{$this->getHeaderName()}: {$this->getHeaderValue()}";
  }
  
  /**
   * Parses a header string to a Header object.
   * @param string $headerData Header string.
   * @return Header Parsed header.
   */
  static public function fromString($headerData) {
    $pos = strpos($headerData, ':');
    if ($pos !== false) {
      //$name = substr($headerData, 0, $pos);
      // name is ignored..
      $value = substr($headerData, $pos+2);
      
      // TODO: check if a class headers\$name exists, and call fromBasicHeader on that class?
      return static::fromHeaderValue($value);
    }
    return null;
  }
  
  /**
   * Constructs an instance by header value.
   * @param string $value Header value.
   * @return Header Constructed header.
   */
  static public function fromHeaderValue($value) {
    // assume name is current classname
    $header = new static();
    $header->setHeaderValue($value);
    return $header;
  }
  
  /**
   * Constructs an instance by header
   * @param headers\Header $header Header object.
   * @return Header Constructed header.
   */
  static public function fromHeader(Header $header) {
    $result = new static();
    $result->setHeaderValue($header->getHeaderValue());
    return $result;
  }

  static protected function escapeValue($value) {
    if (preg_match("/[\x-\x1F\x7F()<>@,;:\/\\\"\][?={}\x20\x09]/", $value)) {
      return '"' . addcslashes($value, '"') . '"';
    } else {
      return $value;
    }
  }
  
  static protected function parametersToString(array $parameters) {
    $params = array();
    foreach ($parameters as $attribute => $value) {
      $params[] = $attribute . '=' . static::escapeValue($value);
    }
    
    return implode('; ', $params);
  }
  
}
