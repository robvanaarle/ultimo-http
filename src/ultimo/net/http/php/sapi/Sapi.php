<?php

namespace ultimo\net\http\php\sapi;

use ultimo\net\http\Response;
use ultimo\net\http\Request;

class Sapi {
  /**
   * Whether the headers are sent.
   * @var boolean 
   */
  protected $headersSent = false;

  /**
   * SapiAdapter
   * @var SapiAdapter
   */
  protected $adapter;
  
  public function __construct(SapiAdapter $adapter=null) {
    if ($adapter === null) {
      $adapter = $this->detectSapiAdapter();
    }
    $this->setAdapter($adapter);
  }
  
  public function setAdapter(SapiAdapter $adapter) {
    $this->adapter = $adapter;
    return $this;
  }
  
  protected function detectSapiAdapter() {
    switch (php_sapi_name()) {
      case 'cli':
        return new adapter\Cli();
        
      default:
        return new adapter\Apache();  
    }
  }
  
  public function hseadersSent() {
    return $this->headersSent;
  }
  
  public function getRequest(Request $request=null) {
    if ($request === null) {
      $request = new \ultimo\net\http\Request();
    }
    return $this->adapter->getRequest($request);
  }
  
  public function flush(Response $response) {
    if ($this->headersSent && count($response->getHeaders()) > 0) {
      throw new Exception('Headers already sent.');
    }
    $this->adapter->flush($response);
    return $this;
  }
  
  public function getOutputStream() {
    return $this->adapter->getOutputStream();
  }
}
