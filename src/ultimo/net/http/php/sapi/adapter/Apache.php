<?php

namespace ultimo\net\http\php\sapi\adapter;

use ultimo\net\http\XRequest;
use ultimo\net\http\Response;

class Apache implements \ultimo\net\http\php\sapi\SapiAdapter {
  public function getRequest(XRequest $request) {
    $request->setMethod($_SERVER['REQUEST_METHOD']);
    $request->setHttpVersion($_SERVER['SERVER_PROTOCOL']);
    
    $headers = self::getCurrentRequestHeaders();
    // Cookies are set using $_COOKIE
    unset($headers['Cookie']);
    // Add all headers
    foreach ($headers as $name => $value) {
      $request->addHeader($name, $value);
    }
    
    // Add url
    $request->setUrl(self::getCurrentRequestUrl());
    
    // Add post params
    $request->setPostParams($_POST);
    
    // Add file params, these are strictly speaking a post params
    $request->setPostParams(\ultimo\net\http\php\sapi\UploadedFile::getPostedFiles());
    
    // Add cookies
    foreach ($_COOKIE as $name => $value) {
      $request->addCookie($name, $value);
    }
    
    return $request;
  }
  
  /**
   * Returns the http headers of the current request.
   * @return array The http headers of the current request.
   */
  static public function getCurrentRequestHeaders() {
    $headers = array();
    foreach($_SERVER as $name => $value) {
      if(substr($name, 0, 5) == 'HTTP_') {
        $name = strtolower(substr($name, 5));
        
        $name[0] = strtoupper($name[0]);
        $name = preg_replace_callback('/_([a-z])/', function($c) {
          return '-' . strtoupper($c[1]);
        }, $name);
        
        $headers[$name] = $value;
      }
    }
    return $headers;
  }
  
  /**
   * Returns the url of the current http request.
   * @return string Rhe url of the current http request.
   */
  static public function getCurrentRequestUrl() {
    $scheme = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) {
      $scheme = 'https';
    }
    
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  }
  
  public function flush(Response $response) {
    // output first http response line
    header("{$response->getHttpVersion()} {$response->getStatusCode()} {$response->getReason()}");

    // output each header by default php fashion
    foreach($response->getHeaders() as $header) {
      $header->defaultPhpOutput();
    }

    // echo the body
    $response->printBody();
  }
  
  public function getOutputStream() {
    return fopen("php://output", "w");
  }
}
