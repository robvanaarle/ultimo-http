<?php

namespace ultimo\net\http;

/**
 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec2.html
 * 
 * Simple parser to parse RFC specifications. This could be done much more
 * elegant, but it suffies for now.
 */
class RFCParser {
  public $text = "";
  public $next = "";
  const PATTERN_LWS = "(\x0D\x0A)?(\x20|\x09)+";
  
  public function __construct($text) {
    $this->setText($text);
  }
  
  public function setText($text) {
    $this->text = $text;
    $this->next = null;
    if (mb_strlen($text) > 0) {
      $this->next = substr($text, 0, 1);
    }
  }

  protected function consume($text) {
    $this->setText(substr($this->text, mb_strlen($text)));
    return $text;
  }
  
  public function parseLws() {
    if (!preg_match("/^" . static::PATTERN_LWS . "/", $this->text, $matches)) {
      throw new RFCParseException("Linear white space expected", $this->text);
    }
    return $this->consume($matches[0]);
  }
  
  public function consumeLws() {
    if (!preg_match("/^". static::PATTERN_LWS . "/", $this->text, $matches)) {
      return;
    }
    return $this->consume($matches[0]);
  }
  
  public function parseToken() {
    if (!preg_match("/^[^\x-\x1F\x7F()<>@,;:\/\\\"\][?={}\x20\x09]+/", $this->text, $matches)) {
      throw new RFCParseException("Token expected", $this->text);
    }
    $result = $this->consume($matches[0]);
    $this->consumeLws();
    return $result;
  }
  
  public function parseChar($char) {
    if ($this->next == $char) {
      $this->consume($char);
      return $char;
    }
    throw new RFCParseException("'{$char}' expected", $this->text);
  }
  
  public function parseSeparator($char) {
    $result = $this->parseChar($char);
    $this->consumeLws();
    return $result;
  }
  
  public function parseQuotedString() {
    $string = "";
    if ($this->parseChar('"') === null) {
      return null;
    }
    
    $escaping = false;
    while($this->next !== null) {
      $c = $this->consume($this->next);
      
      if (!$escaping) {
        switch ($c) {
          case '\\':
            $escaping = true;
            continue 2;
          
          case '"':
            $this->consumeLws();
            return $string;
            
          case '\r':
            $this->parseLws();
          
          default:
        }
      } else {
        $escaping = false;
      }
      
      $string .= $c;
    }
    
    throw new RFCParseException("Unterminated quoted string", '', $string);
  }
  
  public function parseParameter() {
    $attribute = $this->parseAttribute();
    
    $this->parseChar('=');
    
    $value = $this->parseValue();
    
    return array($attribute => $value);
  }
  
  public function parseAttribute() {
    return $this->parseToken();
  }
  
  public function parseValue() {
    if ($this->next == '"') {
      return $this->parseQuotedString();
    } else {
      return $this->parseToken();
    }
  }
  
  protected function parseCSList($callback) {
    $elements = array(call_user_func($callback));
    while($this->next == ',') {
      $this->parseChar(',');
      $this->consumeLws();
      
      $elements[] = call_user_func($callback);
    }
    
    return $elements;
  }
  
  public function parseAcceptValue() {
    $thiz = $this;
    return $this->parseCSList(function() use ($thiz) {
      $acceptEntry = $thiz->parseMediaRange();
      
      $acceptParams = array();
      if ($thiz->next == ';') {
        $acceptParams = $thiz->parseAcceptParams();
      }
      
      $acceptEntry['accept-params'] = $acceptParams;
      return $acceptEntry;
    });
  }
  
  public function parseMediaRange() {
    $type = $this->parseToken();
    
    $this->parseSeparator('/');

    $subtype = $this->parseToken();
    
    $parameters = array();
    while ($this->next == ';') {
      if (preg_match("/^;(" . static::PATTERN_LWS . ")?q(" . static::PATTERN_LWS . ")?=/", $this->text)) {
        break;
      }
      
      $this->parseSeparator(';');
      $parameters = array_merge($parameters, $this->parseParameter());
    }
    
    return array('type' => $type, 'subtype' => $subtype, 'parameters' => $parameters);
  }
  
  public function parseAcceptParams() {
    $acceptParams = array();
            
    $this->parseSeparator(';');
    $token = $this->parseToken();
    if ($token != 'q') {
      throw new RFCParseException("Expected token 'q' in accept-params", $this->text, $token);
    }
    
    $this->parseSeparator('=');
    $qValue = $this->parseQValue();
    $acceptParams = array('q' => $qValue);
    
    while ($this->next == ';') {
      $acceptParams = array_merge($acceptParams, $this->parseAcceptExtension());
    }
    
    return $acceptParams;
  }
  
  protected function parseAcceptExtension() {
    $this->parseChar(';');
    $attribute = $this->parseToken();
    
    $value = null;
    if ($this->next == '=') {
      $this->parseSeparator('=');
      $value = $this->parseValue();
    }
    
    return array($attribute => $value);
  }
  
  public function parseQValue() {
    if (!preg_match("/^(:?(0(:?\.[0-9]{1,3})?)|(1(:?\.0{1,3})?))+/", $this->text, $matches)) {
      throw new RFCParseException("Q-value expected", $this->text);
    }
    $result = $this->consume($matches[0]);
    $this->consumeLws();
    return $result;
  }
}