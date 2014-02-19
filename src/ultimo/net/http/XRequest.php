<?php

namespace ultimo\net\http;

class XRequest extends Request {
  
  protected $getParams;
  
  protected $getEncoder;
  
  protected $postParams;
  
  protected $postEncoder;
  
  public function __construct($url=null, $method=self::METHOD_GET) {
    $this->clearGetParams();
    $this->clearPostParams();
    $this->setGetEncoder(new encoders\XWwwFormUrlencoded());
    parent::__construct($url, $method);
  }
  
  public function setUri($uri) {
    $pos = strpos($uri, '?');
    if ($pos !== false) {
      $queryString = substr($uri, $pos+1);
      
      $this->setGetParams($this->getGetEncoder()->decode($queryString));
      
      $uri = substr($uri, 0, $pos);
    }
    
    $this->uri = $uri;
    return $this;
  }
  
  public function getQueryString($prependQuestionMark=true) {
    $queryString = $this->getGetEncoder()->encode($this->getGetParams());
    if ($queryString != '') {
      $queryString = '?' . $queryString;
    }
    return $queryString;
  }
  
  public function getUri($appendQueryString=true) {
    $queryString = '';
    if ($appendQueryString) {
      $queryString = $this->getQueryString();
    }
    return $this->uri . $queryString;
  }
  
  public function getUrl($appendQueryString=true) {
    $url = parent::getUrl();
    
    if ($appendQueryString) {
      $url .= $this->getQueryString();
    }
    
    return $url;
  }
  
  public function setGetParam($name, $value) {
    $this->getParams[$name] = $value;
    return $this;
  }
  
  public function setGetParams($getParams) {
    $this->getParams = array_merge($this->getParams, $getParams);
    return $this;
  }
  
  public function getGetParam($name) {
    if (!array_key_exists($name, $this->getParams)) {
      return null;
    }
    return $this->getParams[$name];
  }
  
  public function getGetParams() {
    return $this->getParams;
  }
  
  public function removeGetParams($name) {
    unset($this->getParams[$name]);
    return $this;
  }
  
  public function clearGetParams() {
    $this->getParams = array();
    return $this;
  }
  
  public function clearGetParam($name) {
    unset($this->getParams[$name]);
    return $this;
  }
  
  public function clearPostParam($name) {
    unset($this->postParams[$name]);
    return $this;
  }
  
  public function setGetEncoder(Encoder $getEncoder) {
    $this->getEncoder = $getEncoder;
    return $this;
  }
  
  public function getGetEncoder() {
    return $this->getEncoder;
  }
  
  public function addCookie($name, $value) {
    $cookie = $this->getHeader('Cookie');
    if ($cookie === null) {
      $cookie = new headers\Cookie();
      $this->addHeader($cookie);
    }
    $cookie->values[$name] = $value;
    return $this;
  }
  
  public function getCookieValue($name) {
    foreach ($this->getHeaders('Cookie') as $cookie) {
      if (array_key_exists($name, $cookie->values)) {
        return $cookie->values[$name];
      }
    }
    return null;
  }
  
  public function getCookieValues() {
    $values = array();
    foreach ($this->getHeaders('Cookie') as $cookie) {
      $values = array_merge($values, $cookie->values);
    }
    return $values;
  }
  
  public function removeCookie($cookieOrName) {
    if ($cookieOrName instanceof headers\Cookie) {
      $this->removeHeader($cookieOrName);
    } else {
      $headerCount = count($this->headers);
      for ($i=$headerCount-1; $i>=0; $i--) {
        if ($this->headers[$i] instanceof headers\Cookie) {
          unset($this->headers[$i]->values[$cookieOrName]);
          if (empty($this->headers[$i]->values)) {
            $this->removeHeader($this->headers[$i]);
          }
        }
      }
    }
    return $this;
  }
  
  public function clearCookies() {
    $this->removeHeader('Cookie');
  }
  
  /**
   * Sets the body.
   * @param string $body The body.
   * @return Request This instance for fluid design.
   */
  public function setBody($body) {
    $postParams = $this->getPostEncoder()->decode($body, $this->getHeader('Content-Type'));
    if ($postParams !== null) {
      $this->setPostParams($postParams);
    }
    
    return $this;
  }
  
  public function getBody() {
    return $this->getPostEncoder()->encode($this->postParams, $this->getHeader('Content-Type'));
  }
  
  public function setPostParam($name, $value) {
    $this->postParams[$name] = $value;
    return $this;
  }
  
  public function setPostParams($postParams) {
    $this->postParams = array_merge($this->postParams, $postParams);
    return $this;
  }
  
  public function getPostParam($name) {
    if (!array_key_exists($name, $this->postParams)) {
      return null;
    }
    return $this->postParams[$name];
  }
  
  public function getPostParams() {
    return $this->postParams;
  }
  
  public function removePostParams($name) {
    unset($this->postParams[$name]);
    return $this;
  }
  
  public function clearPostParams() {
    $this->postParams = array();
    return $this;
  }
  
  public function getContentType() {
    $header = $this->getHeader('Content-Type');
    if ($header === null) {
      return null;
    }
    return $header->getItem(0);
  }
  
  public function setPostEncoder(Encoder $postEncoder, $setContentType=true) {
    if ($setContentType) {
      $this->setHeader("Content-Type", $postEncoder->getName());
    }
    $this->postEncoder = $postEncoder;
    return $this;
  }
  
  public function getPostEncoder() {
    if ($this->postEncoder === null) {
      $contentType = $this->getContentType();
      if ($contentType === null) {
        $this->addHeader('Content-Type', 'x-www-form-urlencoded');
        $contentType = 'x-www-form-urlencoded';
      }

      $className = $contentType;
      $className[0] = strtoupper($className[0]);
      $className = preg_replace_callback('/[-\/]{1}([a-z])/', function($c) {
        return strtoupper($c[1]);
      }, $className);
      
      $class = __NAMESPACE__ . '\\encoders\\' . $className;
      if (!class_exists($class)) {
        return new encoders\Raw($contentType);
      } else {
        return new $class();
      }
    } else {
      return $this->postEncoder;
    }
  }
  
  /**
   * Returns the string representation of this instance: a raw http request.
   * @return string The string representation of this instance.
   */
  public function __toString() {
    $lines = array();
    $lines[] = "{$this->getMethod()} {$this->getUri()} {$this->getHttpVersion()}";
    
    $body = $this->getBody();
    
    foreach ($this->getHeaders() as $header) {
      if ($body == '' && $header->getHeaderName() == 'Content-Type') {
        continue;
      }
      $lines[] = (string) $header;
    }
    
    if ($body !== '') {
      $lines[] = (string) new headers\BasicHeader("Content-Lenght", strlen($body));
      $lines[] = '';
      $lines[] = $body;
    }
    
    return implode("\r\n", $lines);
  }
}