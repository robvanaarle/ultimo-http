<?php

namespace ultimo\net\http\headers;

class SetCookie extends \ultimo\net\http\Header {
  /**
   * The name of the cookie.
   * @var string 
   */
  public $name;
  
  /**
   * The value of the cookie.
   * @var string 
   */
  public $value;
  
  /**
   * The time the cookie expires. This is a Unix timestamp so is in number of
   * seconds since the epoch. 
   * @var integer 
   */
  public $expire;
  
  /**
   * The path on the server in which the cookie will be available on. Use null
   * to have no path.
   * @var string 
   */
  public $path;
  
  /**
   * The domain that the cookie is available to. Use null to have no path.
   * @var string 
   */
  public $domain;
  
  /**
   * Whether the cookie should only be set on secure (https) connections.
   * @var boolean 
   */
  public $secure;
  
  /**
   * When true the cookie will be made accessible only through the http
   * protocol.
   * @var boolean 
   */
  public $httpOnly;
  
  /**
   * Constructor
   * @param string $name The name of the cookie.
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
   */
  public function __construct($name, $value, $expire=0, $path='', $domain='', $secure='', $httpOnly='') {
    $this->name = $name;
    $this->value = $value;
    $this->expire = $expire;
    $this->path = $path;
    $this->domain = $domain;
    $this->secure = $secure;
    $this->httpOnly = $httpOnly;
  }
  
  /**
   * Sets the cookie using the php function setcookie. 
   */
  public function defaultPhpOutput() {
    setcookie($this->name, $this->value, $this->expire, $this->path, $this->domain, $this->secure, $this->httpOnly);
  }
  
  /**
   * Encodes a string for use in a cookie as value.
   * @param string $value Value to encode.
   * @return Encoded value.
   */
  static public function encodeValue($value) {
    return strtr($value,
      array_combine(
        str_split($tmp=",; \t\r\n\013\014"),
        array_map('rawurlencode', str_split($tmp)))
      );
  }
  
  /**
   * Decodes a encoded cookie value.
   * @param string $encodedValue Encoded cookie value.
   * @return string Decoded value.
   */
  static public function decodeValue($encodedValue) {
    return urldecode($encodedValue);
  }
  
  /**
   * Returns the name of the header.
   * @return string The name of the header. 
   */
  public function getHeaderName() {
    return 'Set-Cookie';
  }
  
  /**
   * Returns the string representation of this instance.
   * @return string String representation of this instance.
   */
  public function getHeaderValue() {
    $data = array();
    $value = static::encodeValue($this->value);
    $data[] = "{$this->name}={$value}";
    
    if ($this->expire != 0) {
      $date = date('D, d-M-Y H:i:s', $this->expire) . ' GMT';
      $data[] = "Expires={$date}";
    }
    
    if ($this->path !== null) {
      $data[] = "Path={$this->path}";
    }
    
    if ($this->domain !== null) {
      $data[] = "Domain={$this->domain}";
    }
    
    if ($this->secure) {
      $data[] = "Secure";
    }
    
    if ($this->httpOnly) {
      $data[] = "HttpOnly";
    }
    
    return implode('; ', $data);
  }
  
  public function setHeaderValue($value) {
    throw new Exception("Not implemented");
  }
}