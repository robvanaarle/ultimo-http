<?php

namespace ultimo\net\http\responsebodies;

class Callback implements \ultimo\net\http\ResponseBody {
  protected $printCallback;
  protected $printCallbackArgs;
  
  protected $stringCallback;
  protected $stringCallbackArgs;
  
  public function __construct($printCallback, array $printCallbackArgs=array(), $stringCallback=null, $stringCallbackArgs=array()) {
    $this->printCallback = $printCallback;
    $this->printCallbackArgs = $printCallbackArgs;
    $this->stringCallback = $stringCallback;
    $this->stringCallbackArgs = $stringCallbackArgs;
  }
  
  public function printBody() {
    call_user_func_array($this->printCallback, $this->printCallbackArgs);
  }
  
  public function __toString() {
    if ($this->stringCallback !== null) {
      return call_user_func_array($this->stringCallback, $this->stringCallbackArgs);
    } else {
      ob_start();
      $this->printBody();
      return ob_get_clean();
    }
  }
}