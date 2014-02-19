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
  
  public function __construct(Adapter $adapter) {
    $this->adapter = $adapter;
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
    
    $response = $this->adapter->request($request, $this->getOptions(), $fileHandle);
    
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
  
  protected function getOptions() {
    return array(
      'certificates' => $this->certificates,
      'followLocationHeader' => $this->followLocationHeader,
      'maxRedirects' => $this->maxRedirects
    );
  }
}