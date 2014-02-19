<?php

namespace ultimo\net\http\headers;

/**
 * TOD: A class 'MediaRange' and/or 'AcceptValue' should be created. The compare
 * and toString functions should be moved there.
 */
class Accept extends \ultimo\net\http\Header {
  
  protected $acceptValues = array();
  
  /**
   * Returns the name of the header.
   * @return string The name of the header. 
   */
  public function getHeaderName() {
    return 'Accept';
  }
  
  /**
   * Returns the value of the header.
   * @return string The value of the header. 
   */
  public function getHeaderValue() {
    $headerValue = array();
    
    foreach ($this->acceptValues as $mediaRange) {
      $mediaRangeValue = array();
      
      $mediaRangeValue[] = static::mediaRangeToString($mediaRange);
      
      foreach ($mediaRange['accept-params'] as $attribute => $value) {
        if ($value !== null) {
          $mediaRangeValue[] = $attribute . '=' . static::escapeValue($value);
        } else {
          $mediaRangeValue[] = $attribute;
        }
      }
      
      $headerValue[] = implode('; ', $mediaRangeValue);
    }
    
    return implode(', ', $headerValue);
  }
  
  public function getBestMediaRangeMatch(array $mediaRanges) {
    
    $parser = new \ultimo\net\http\RFCParser('');
    $parsedMediaRanges = array();
    
    // parse supported media-ranges
    foreach ($mediaRanges as $mediaRange) {
      $parser->setText($mediaRange);
      $parsedMediaRanges[$mediaRange] = $parser->parseMediaRange();
    }
    
    // compare each (sorted) accept value to each (sorted) media range, until a
    // a match has been found
    foreach ($this->getAcceptValues(true) as $acceptValue) {
      foreach ($parsedMediaRanges as $mediaRange => $parsedMediaRange) {
        if (static::matchesMediaRange($parsedMediaRange, $acceptValue)) {
          return $mediaRange;
        }
      }
    }
    
    // no match found
    return null;
  }
  
  static protected function matchesMediaRange($in, $to) {
    if ($to['type'] != '*' && $in['type'] != $to['type']) {
      return false;
    }
    
    if ($to['subtype'] != '*' && $in['subtype'] != $to['subtype']) {
      return false;
    }
    
    // All 'to' parameters must be present with the same value in 'in'
    $paramIntersection = array_intersect_assoc($in['parameters'], $to['parameters']);
    if (count($paramIntersection) != count($to['parameters'])) {
      return false;
    }
    
    return true;
  }
  
  static public function mediaRangeToString(array $mediaRange) {
    $params = static::parametersToString($mediaRange['parameters']);
    if ($params != '') {
      $params = ';' . $params;
    }
    
    return $mediaRange['type'] . '/' . $mediaRange['subtype'] . $params;
  }
  
  public function getAcceptValues($sort=false) {
    if (!$sort) {
      return $this->acceptValues;
    }
    
    $acceptValue = $this->acceptValues;
    usort($acceptValue, array($this, 'compareAcceptValues'));
    
    return $acceptValue;
  }
  
  public function compareMediaRanges($a, $b) {
    if ($a['type'] == '*') {
      return 1;
    } elseif ($b['type'] == '*') {
      return -1;
    } elseif ($a['type'] == $b['type']) {
      if ($a['subtype'] == '*') {
        return 1;
      } elseif ($b['subtype'] == '*') {
        return -1;
      } elseif ($a['subtype'] == $b['subtype']) {
        if (count($a['parameters']) == 0) {
          return 1;
        } elseif (count($b['parameters']) == 0) {
          return -1;
        }
      }
    }
    return 0;
  }
  
  public function compareAcceptValues($a, $b) {
    $aQValue = 1.0;
    $bQValue = 1.0;

    if (isset($a['accept-params']['q'])) {
      $aQvalue = $a['accept-params']['q'];
    }
    
    if (isset($b['accept-params']['q'])) {
      $aQvalue = $b['accept-params']['q'];
    }

    if ($aQValue == $bQValue) {
      return $this->compareMediaRanges($a, $b);
    }

    return ($aQValue > $bQValue) ? -1 : 1;
  }
  
  public function setHeaderValue($value) {
    $parser = new \ultimo\net\http\RFCParser($value);
    $this->acceptValues = $parser->parseAcceptValue();
  }
  
}