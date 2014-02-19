<?php

namespace ultimo\net\http;

class Request {
  /**
   * Request methods. 
   */
  const METHOD_GET = 'GET';
  const METHOD_POST = 'POST';
  const METHOD_DELETE = 'DELETE';
  const METHOD_PUT = 'PUT';
  
  protected $method;
  
  protected $uri;
  
  /**
   * Http version, e.g. HTTP/1.1
   * @var string 
   */
  protected $httpVersion;
  
  protected $headers;
  
  protected $body;
  
  public function __construct($url=null, $method=self::METHOD_GET) {
    $this->clearHeaders();
    $this->clearBody();
    
    $this->setMethod($method);
    $this->setHttpVersion('HTTP/1.1');
    
    if ($url !== null) {
      $this->setUrl($url);
    }
  }
  
  public function setMethod($method) {
    $this->method = $method;
    return $this;
  }
  
  public function getMethod() {
    return $this->method;
  }
  
  public function setUri($uri) {
    $this->uri = $uri;
    return $this;
  }
  
  public function getUri() {
    return $this->uri;
  }
  
  public function setUrl($url) {
    $info = @parse_url($url);
    if ($info !== false) {
      
      if (isset($info['host'])) {
        $host = $info['host'];
        if (isset($info['port'])) {
          $host .= ':' . $info['port'];
        }
        $this->setHeader('Host', $host);
      }
      
      if (!isset($info['path'])) {
        $info['path'] = '/';
      }
      
      if (isset($info['query'])) {
        $info['path'] .= '?' . $info['query'];
      }
      
      if (isset($info['scheme'])) {
        $this->setHttpVersion(strtoupper($info['scheme']) . '/1.1');
      }
      
      $this->setUri($info['path']);
    }
    
    return $this;
  }
  
  public function getUrl() {
    $hostHeader = $this->getHeader("Host");
    if ($hostHeader === null) {
      return null;
    }
    return $this->getScheme() . '://' . $hostHeader->getHeaderValue() . $this->uri;
  }
  
  public function getScheme() {
    $elems = explode('/', $this->getHttpVersion());
    return strtolower($elems[0]);
  }
  
  /**
   * Sets the http version, e.g. HTTP/1.1
   * @param string $httpVersion The http version.
   * @return Request This instance for fluid design.
   */
  public function setHttpVersion($httpVersion) {
    $this->httpVersion = $httpVersion;
    return $this;
  }
  
  /**
   * Returns the http version, e.g. HTTP/1.1
   * @return string The http version. 
   */
  public function getHttpVersion() {
    return $this->httpVersion;
  }
  
  public function addHeaders(array $headers) {
    foreach ($headers as $indexOrName => $headerOrValue) {
      if (is_int($indexOrName)) {
        $this->addHeader($headerOrValue);
      } else {
        $this->addHeader($indexOrName, $headerOrValue);
      }
    }
    return $this;
  }
  
   /**
   * Adds a header.
   * @param Header|string $headerOrName Header object or header name.
   * @param string $value Header value, used only if first parameter is a header
   * name.
   * @return Request This instance for fluid design.
   */
  public function addHeader($headerOrName, $value = null) {
    if (!($headerOrName instanceof Header)) {
      if ($value !== null) {
        $headerOrName = new headers\BasicHeader($headerOrName, $value);
      } else {
        $headerOrName = headers\BasicHeader::fromString($headerOrName);
      }
    }
    
    $this->headers[] = $headerOrName;
    return $this;
  }
  
  public function setHeader($headerOrName, $value=null) {
    if (!($headerOrName instanceof Header)) {
      $this->removeHeader($headerOrName);
    } else {
      $this->removeHeader($headerOrName->getHeaderName());
    }
    
    $this->addHeader($headerOrName, $value);
    
    return $this;
  }
  
  /**
   * Returns the first header with the specified name.
   * @param string $name Name of the header.
   * @return Header The header with the specified name, or null if no header
   * with that name exists. 
   */
  public function getHeader($name) {
    foreach ($this->headers as $header) {
      if ($header->getHeaderName() == $name) {
        return $header;
      }
    }
    return null;
  }
  
  /**
   * Returns the headers.
   * @param string $name Name of the headers to get, or null to return all
   * headers.
   * @return array The headers.
   */
  public function getHeaders($name=null) {
    if ($name === null) {
      return $this->headers;
    } else {
      $headers = array();
      foreach ($this->headers as $header) {
        if ($header->getHeaderName() == $name) {
          $headers[] = $header;
        }
      }
      return $headers;
    }
  }
  
  /**
   * Removes a header of all headers with the specified name.
   * @param Header|string $headerOrName Header object or header name.
   * @return Request This instance for fluid design.
   */
  public function removeHeader($headerOrName) {
    if ($headerOrName instanceof Header) {
      $index = array_search($headerOrName, $this->headers);
      if ($index !== false) {
        array_splice($this->headers, $index, 1);
      }
    } else {
      $headerCount = count($this->headers);
      for ($i=$headerCount-1; $i>=0; $i--) {
        if ($this->headers[$i]->getHeaderName() == $headerOrName) {
          array_splice($this->headers, $i, 1);
        }
      }
    }
    return $this;
  }
  
  /**
   * Clears the headers.
   * @return Request This instance for fluid design.
   */
  public function clearHeaders() {
    $this->headers = array();
    return $this;
  }

  /**
   * Sets the body.
   * @param string $body The body.
   * @return Request This instance for fluid design.
   */
  public function setBody($body) {
    $this->clearBody();
    $this->appendBody($body);
    return $this;
  }
  
  /**
   * Appends a string to the body.
   * @param string $body The string to append to the body.
   * @return Request This instance for fluid design.
   */
  public function appendBody($body) {
    $this->body[] = $body;
    return $this;
  }
  
  /**
   * Prepends a string to the body..
   * @param string $body The string to prepend to the body.
   * @return Request This instance for fluid design.
   */
  public function prependBody($body) {
    $this->body = array_unshift($this->body, $body);
    return $this;
  }
  
  /**
   * Returns the body.
   * @return string The body.
   */
  public function getBody() {
    return implode('', $this->body);
  }
  
  /**
   * Clears the body.
   * @return Request This instance for fluid design.
   */
  public function clearBody() {
    $this->body = array();
    return $this;
  }
  
  /**
   * Returns the string representation of this instance: a raw http request.
   * @return string The string representation of this instance.
   */
  public function __toString() {
    $lines = array();
    $lines[] = "{$this->getMethod()} {$this->getUri()} {$this->getHttpVersion()}";
    
    foreach ($this->getHeaders() as $header) {
      $lines[] = (string) $header;
    }
    
    $body = $this->getBody();
    if ($body !== '') {
      $lines[] = '';
      $lines[] = $body;
    }
    
    return implode("\r\n", $lines);
  }
}