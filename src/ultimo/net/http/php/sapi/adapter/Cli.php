<?php

namespace ultimo\net\http\php\sapi\adapter;

use ultimo\net\http\XRequest;
use ultimo\net\http\Response;

class Cli implements \ultimo\net\http\php\sapi\SapiAdapter {
  public function getRequest(XRequest $request) {
    $request->setMethod($_SERVER['REQUEST_METHOD']);
    $request->setHttpVersion($_SERVER['SERVER_PROTOCOL']);
    
    $headers = Apache::getCurrentRequestHeaders();
    
    // Set Cookie using the Cookie header object.
    if (isset($headers['Cookie'])) {
      $cookieHeader = new \ultimo\net\http\headers\BasicHeader('Cookie', $headers['Cookie']);
      $request->addHeader(\ultimo\net\http\headers\Cookie::constructByHeader($cookieHeader));
      unset($headers['Cookie']);
    }
    
    // Add headers
    foreach ($headers as $name => $value) {
      $request->addHeader($name, $value);
    }
    
    // Set url
    $request->setUrl(Apache::getCurrentRequestUrl());
    
    // Add body
    $request->setBody(self::getCurrentRequestBody());
    return $request;
  }
  
  static public function getCurrentRequestBody() {
    return stream_get_contents(STDIN);
  }
  
  public function flush(Response $response) {
    // don't output the first line
    $lines = array();
    
    foreach ($response->getHeaders() as $header) {
      $lines[] = (string) $header;
    }
    
    $lines[] = '';
    
    echo implode("\r\n", $lines);
    
    echo "\r\n\r\n";
    
    $response->printBody();
  }
  
  public function getOutputStream() {
    return STDOUT;
  }
}