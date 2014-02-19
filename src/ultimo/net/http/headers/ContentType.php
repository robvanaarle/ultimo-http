<?php

namespace ultimo\net\http\headers;

class ContentType extends \ultimo\net\http\Header {
  public $mediaType = null;
  public $mediaSubtype = null;
  public $parameters = array();
  
  public function __construct($mediaType=null, $mediaSubtype=null, array $parameters = array()) {
    $this->mediaType = $mediaType;
    $this->mediaSubtype = $mediaSubtype;
    $this->parameters = $parameters;
  }
  
  public function getType() {
    return "{$this->mediaType}/{$this->mediaSubtype}";
  }
  
  public function getHeaderName() {
    return 'Content-Type';
  }
  
  public function getHeaderValue() {
    $value = $this->getType();
    
    $params = static::parametersToString($this->parameters);
    if ($params != '') {
      $value .= '; ' . $params;
    }
    
    return $value;
  }
  
  public function setHeaderValue($value) {
    $parser = new \ultimo\net\http\RFCParser($value);
    $mediaRange = $parser->parseMediaRange();
    $this->mediaType = $mediaRange['type'];
    $this->mediaSubtype = $mediaRange['subtype'];
    $this->parameters = $mediaRange['parameters'];
  }

  
  
}