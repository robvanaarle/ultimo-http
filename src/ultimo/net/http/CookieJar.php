<?php

namespace ultimo\net\http;

class CookieJar {
  protected $cookies = array();
  
  public function __construct() {
    
  }
  
  public function setCookie($domain, headers\SetCookie $setCookie) {
    $domain = strtolower($domain);
    if (!array_key_exists($domain, $this->cookies)) {
      $this->cookies[$domain] = array();
    }
    if ($setCookie->value == '') {
      unset($this->cookies[$domain][$setCookie->name]);
    } else {
      $this->cookies[$domain][$setCookie->name] = $setCookie;
    }
  }
  
  public function extractCookies(Request $request, Response $response) {
    $defaultDomain = $request->getHeader("Host")->getHeaderValue();
    
    foreach ($response->getHeaders("Set-Cookie") as $cookieHeader) {
      $setCookie = headers\SetCookie::fromHeader($cookieHeader);
      $domain = $setCookie->domain;
      if (empty($domain)) {
        $domain = $defaultDomain;
      }
      
      $this->setCookie($domain, $setCookie);
    }
  }
  
  public function appendCookies(Request $request) {
    $cookie = $this->getCookie($request->getHeader("Host")->getHeaderValue());
    
    if ($cookie !== null) {
      $request->addHeader($cookie);
    }
  }
  
  public function getCookie($domain) {
    // TODO: respect 'Expire' and 'Secure'
    $values = array();
    
    $domain = strtolower($domain);
    $domainElems = explode('.', $domain);
    $domainElems = array_reverse($domainElems);
    
    $domain = '';
    foreach ($domainElems as $domainElem) {
      if (!empty($domain)) {
        $domain = '.' . $domain;
      }
      $domain = $domainElem . $domain;
      
      if (array_key_exists($domain, $this->cookies)) {
        $domainCookies = $this->cookies[$domain];
        foreach ($domainCookies as $cookie) {
          $values[$cookie->name] = $cookie->value;
        }
      }
    }
    
    if (empty($values)) {
      return null;
    } else {
      return new headers\Cookie($values);
    }
  }
  
  public function clear() {
    $this->cookies = array();
  }
  
}