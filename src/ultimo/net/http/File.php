<?php

namespace ultimo\net\http;

class File {
  const OPTION_FILENAME = 'filename';
  const OPTION_TYPE = 'type';
  
  public $path;
  public $options = array();
  
  
  public function __construct($path, array $options=array()) {
    $this->path = $path;
    $this->options = $options;
  }
  
  public function __toString() {
    return $this->path;
  }
}