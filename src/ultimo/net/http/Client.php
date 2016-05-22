<?php

namespace ultimo\net\http;

/**
 * [ ] Proxy
 * [ ] Files
 * [ ] Cookiejar
 */
class Client {
  
  /**
   * Http adapter.
   * @var Adapter 
   */
  protected $adapter;
  
  protected $followLocationHeader = false;
  
  protected $maxRedirects = 5;
  
  protected $certificates = null;
  
  protected $headers;
  
  protected $proxy = null;
  
  /**
   * Cookie jar
   * @var CookieJar
   */
  protected $cookieJar = null;
  
  public function __construct(Adapter $adapter) {
    $this->adapter = $adapter;
    $this->clearHeaders();
  }
  
  /**
   * Executes the http request and returns the response.
   * @param Request $request Http request.
   * @param string|handle $file Filepath or filehandle to store the body of the
   * response to. Null to return the body in the response.
   * @return Response $response Http response. 
   */
  public function request(Request $request, $file=null) {
    if (is_string($file)) {
      $fileHandle = fopen($file, 'wb');
    } else {
      $fileHandle = $file;
    }
    
    $request->addHeaders($this->headers);
    if ($this->cookieJar !== null) {
      $this->cookieJar->appendCookies($request);
    }
    
    $response = $this->adapter->request($request, $this->getOptions(), $fileHandle);
    
    if ($this->cookieJar !== null) {
      $this->cookieJar->extractCookies($request, $response);
    }
    
    if (is_string($file)) {
      fclose($fileHandle);
    }
    
    return $response;
  }
  
  /**
   * Shortcut for executing a Http GET request.
   * @param string $url URL to request to.
   * @param array $getParams A hashtable with GET parameters.
   * @return Response Http response.
   */
  public function get($url, array $getParams = array()) {
    $request = new XRequest($url, Request::METHOD_GET);
    $request->setGetParams($getParams);
    
    return $this->request($request);
  }
  
  public function post($url, array $postParams = array(), array $getParams = array()) {
    $request = new XRequest($url, Request::METHOD_POST);
    $request->setPostParams($postParams);
    $request->setGetParams($getParams);
    return $this->request($request);
  }
  
  public function setFollowLocationHeader($followLocationHeaders) {
    $this->followLocationHeader = $followLocationHeaders;
    return $this;
  }
  
  public function setMaxRedirects($maxRedirects) {
    $this->maxRedirects = $maxRedirects;
    return $this;
  }
  
  public function setCertificates($certificates) {
    $this->certificates = $certificates;
    return $this;
  }
  
  public function setProxy($proxy) {
    $this->proxy = $proxy;
    return $this;
  }
  
  public function getProxy($proxy) {
    return $this->proxy;
  }
  
  protected function getOptions() {
    return array(
      'certificates' => $this->certificates,
      'followLocationHeader' => $this->followLocationHeader,
      'maxRedirects' => $this->maxRedirects,
      'proxy' => $this->proxy
    );
  }
  
  public function setCookieJar(CookieJar $cookieJar) {
    $this->cookieJar = $cookieJar;
    return $this;
  }
  
  public function getCookieJar() {
    return $this->cookieJar;
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
}