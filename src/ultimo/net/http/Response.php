<?php

namespace ultimo\net\http;

class Response {
  
  /**
   * Http version, e.g. HTTP/1.1
   * @var string 
   */
  protected $httpVersion;
  
  /**
   * The http status code.
   * @var integer
   */
  protected $statusCode;
  
  /**
   * The http reason.
   * @var string
   */
  protected $reason;
  
  /**
   * The headers, an array of Header objects.
   * @var array
   */
  protected $headers;
  
  /**
   * The body.
   * @var string
   */
  protected $body;
  
  /**
   * Http reasons. A hashtable with statusCodes as key and reason messages as
   * value.
   * @var array
   */
  static protected $reasons = array(
    // [Informational 1xx]
    100=>'Continue',
    101=>'Switching Protocols',

    // [Successful 2xx]
    200=>'OK',
    201=>'Created',
    202=>'Accepted',
    203=>'Non-Authoritative Information',
    204=>'No Content',
    205=>'Reset Content',
    206=>'Partial Content',

    // [Redirection 3xx]
    300=>'Multiple Choices',
    301=>'Moved Permanently',
    302=>'Found',
    303=>'See Other',
    304=>'Not Modified',
    305=>'Use Proxy',
    307=>'Temporary Redirect',

    // [Client Error 4xx]
    400=>'Bad Request',
    401=>'Unauthorized',
    402=>'Payment Required',
    403=>'Forbidden',
    404=>'Not Found',
    405=>'Method Not Allowed',
    406=>'Not Acceptable',
    407=>'Proxy Authentication Required',
    408=>'Request Timeout',
    409=>'Conflict',
    410=>'Gone',
    411=>'Length Required',
    412=>'Precondition Failed',
    413=>'Request Entity Too Large',
    414=>'Request-URI Too Long',
    415=>'Unsupported Media Type',
    416=>'Requested Range Not Satisfiable',
    417=>'Expectation Failed',

    // [Server Error 5xx]
    500=>'Internal Server Error',
    501=>'Not Implemented',
    502=>'Bad Gateway',
    503=>'Service Unavailable',
    504=>'Gateway Timeout',
    505=>'HTTP Version Not Supported'
  );
  
  /**
   * Constructor.
   */
  public function __construct() {
    $this->clearHeaders();
    $this->clearBody();
    
    $this->setHttpVersion('HTTP/1.1');
    $this->setStatusCode(200);
  }
  
  /**
   * Parses a response string to a Response object.
   * @param string $rawReponse The response string.
   * @return Response Parsed response.
   */
  static public function parse($rawReponse) {
    // construct response
    $response = new static();
    
    // split lines
    $lines = explode("\r\n", $rawReponse);
   
    // empty response?
    if (empty($lines)) {
      return $response;
    }
    
    // parse first line (HTTP/1.1 200 OK)
    $line = array_shift($lines);
    list($httpVersion, $statusCode, $reason) = explode(' ', $line);
    $response->setHttpVersion($httpVersion);
    $response->setStatusCode($statusCode, false);
    $response->setReason($reason);
    
    // parse headers
    while(!empty($lines)) {
      $line = array_shift($lines);
      if (empty($line)) {
        break;
      }
      
      $header = headers\BasicHeader::fromString($line);
      if ($header !== null) {
        $response->addHeader($header);
      }
    }
    
    // is there more data?
    if (empty($lines)) {
      return $response;
    }
    
    // remaining lines is body
    $response->setBody(implode("\r\n", $lines));

    return $response;
  }
  
  /**
   * Sets the http version, e.g. HTTP/1.1
   * @param string $httpVersion The http version.
   * @return Response This instance for fluid design.
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
  
  /**
   * Sets the http status code.
   * @param integer $statusCode The http status code.
   * @param boolean $detectReason Whether to set the reason based on the status
   * code.
   * @return Response This instance for fluid design.
   */
  public function setStatusCode($statusCode, $detectReason=true) {
    $this->statusCode = $statusCode;
    if ($detectReason) {
      $this->detectReason();
    }
    return $this;
  }
  
  /**
   * Returns the http response code.
   * @return integer The http status code.
   */
  public function getStatusCode() {
    return $this->statusCode;
  }
  
  /**
   * Sets the http reason.
   * @param string $reason Http reason.
   * @return Response This instance for fluid design.
   */
  public function setReason($reason) {
    $this->reason = $reason;
    return $this;
  }
  
  /**
   * Returns the http reason.
   * @return string Http reason.
   */
  public function getReason() {
    return $this->reason;
  }
  
  /**
   * Sets the reason based on the status code.
   * @return Response This instance for fluid design.
   */
  public function detectReason() {
    if (isset(self::$reasons[$this->statusCode])) {
      $this->setReason(self::$reasons[$this->statusCode]);
    }
    return $this;
  }
  
  /**
   * Adds a header.
   * @param Header|string $headerOrName Header object or header name.
   * @param string $value Header value, used only if first parameter is a header
   * name.
   * @return Response This instance for fluid design.
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
   * @return BasicHeader The header with the specified name, or null if no header
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
   * @param BasicHeader|string $headerOrName Header object or header name.
   * @return Response This instance for fluid design.
   */
  public function removeHeader($headerOrName) {
    if ($headerOrName instanceof BasicHeader) {
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
   * @return Response This instance for fluid design.
   */
  public function clearHeaders() {
    $this->headers = array();
    return $this;
  }
  
  /**
   * Redirect to a url with a http response code indicating a redirect.
   * @param string $url The url to redirect to.
   * @param integer $responseCode The http response code to indicate the
   * redirect.
   * @return Response This instance for fluid design.
   */
  public function redirect($url, $responseCode=302) {
    $this->setHeader('Location', $url, true)
         ->setStatusCode($responseCode);
    return $this;
  }
  
  /**
   * Returns whether the response represents a redirect.
   * @return boolean Whether the response indicates a redirect.
   */
  public function isRedirect() {
    return $this->statusCode >= 300 && $this->statusCode <= 399;
  }
  
  /**
   * Adds a cookie.
   * @param headers\SetCookie|string $cookieOrName Cookie header or the name of
   * the cookie.
   * @param string $value The value of the cookie.
   * @param integer $expire The time the cookie expires. This is a Unix
   * timestamp so is in number of seconds since the epoch. 
   * @param string $path The path on the server in which the cookie will be
   * available on. Use null to have no path.
   * @param string $domain The domain that the cookie is available to. Use null
   * to have no path.
   * @param boolean $secure Whether the cookie should only be set on secure
   * (https) connections.
   * @param boolean $httpOnly When true the cookie will be made accessible only
   * through the http protocol.
   * @return Response This instance for fluid design.
   */
  public function addCookie($cookieOrName, $value='', $expire=0, $path=null, $domain=null, $secure=false, $httpOnly=false) {
    if (!($cookieOrName instanceof headers\SetCookie)) {
      $cookieOrName = new headers\SetCookie($cookieOrName, $value, $expire, $path, $domain, $secure, $httpOnly);
    }
    $this->addHeader($cookieOrName);
  }
  
  /**
   * Returns a cookie by name.
   * @param string $name Name of the cookie.
   * @return headers\SetCookie The cookie with the specified name, or null if
   * the cookie does not exists.
   */
  public function getCookie($name) {
    foreach ($this->headers as $header) {
      if ($header instanceof headers\SetCookie &&
            $header->name == $name) {
        return $header;
      }
    }
    return null;
  }
  
  /**
   * Returns the cookies.
   * @param string $name Name of the cookies to get, or null to return all
   * cookies..
   * @return array The cookies.
   */
  public function getCookies($name=null) {
    $cookies = array();
    foreach ($this->headers as $header) {
      if ($header instanceof headers\SetCookie) {
        if (!$name || $header->name == $name) {
          $cookies[] = $header;
        }
      }
    }
    return $cookies;
  }
  
  /**
   * Removes a cookie or all cookies with the specified name.
   * @param headers\SetCookie|string $cookieOrName Cookie header or the name of
   * the cookie.
   * @return Response This instance for fluid design.
   */
  public function removeCookie($cookieOrName) {
    if ($cookieOrName instanceof headers\SetCookie) {
      $this->removeHeader($cookieOrName);
    } else {
      $headerCount = count($this->headers);
      for ($i=$headerCount-1; $i>=0; $i--) {
        if ($this->headers[$i] instanceof headers\SetCookie &&
             $this->headers[$i]->name == $cookieOrName) {
          array_splice($this->headers, $i, 1);
        }
      }
    }
    return $this;
  }
  
  /**
   * Clears the cookies.
   * @return Response This instance for fluid design.
   */
  public function clearCookies() {
    return $this->removeHeader('Set-Cookie');
  }
  
  /**
   * Sets the body.
   * @param string $body The body.
   * @return Response This instance for fluid design.
   */
  public function setBody($body) {
    $this->clearBody();
    $this->appendBody($body);
    return $this;
  }
  
  /**
   * Appends a string to the body.
   * @param string $body The string to append to the body.
   * @return Response This instance for fluid design.
   */
  public function appendBody($body) {
    $this->body[] = $body;
    return $this;
  }
  
  /**
   * Prepends a string to the body..
   * @param string $body The string to prepend to the body.
   * @return Response This instance for fluid design.
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
   * Prints the body. 
   */
  public function printBody() {
    foreach ($this->body as $bodyElem) {
      if ($bodyElem instanceof ResponseBody) {
        $bodyElem->printBody(); 
      } else {
        echo $bodyElem;
      }
    }
  }
  
  /**
   * Clears the body.
   * @return Response This instance for fluid design.
   */
  public function clearBody() {
    $this->body = array();
    return $this;
  }
  
  /**
   * Returns the string representation of this instance: a raw http response.
   * @return string The string representation of this instance.
   */
  public function __toString() {
    $lines = array();
    $lines[] = "{$this->getHttpVersion()} {$this->getStatusCode()} {$this->getReason()}";
    foreach ($this->getHeaders() as $header) {
      $lines[] = (string) $header;
    }
    
    $lines[] = '';
    $lines[] = $this->getBody();
    
    return implode("\r\n", $lines);
  }
  
}