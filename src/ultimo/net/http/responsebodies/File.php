<?php

namespace ultimo\net\http\responsebodies;

class File implements \ultimo\net\http\ResponseBody {
  protected $filePath;
  
  public function __construct($filePath) {
    $this->filePath = $filePath;
  }
  
  public function printBody() {
    readfile($this->filePath);
  }
  
  public function __toString() {
    return file_get_contents($this->filePath);
  }
}