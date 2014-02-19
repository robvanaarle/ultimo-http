<?php

namespace ultimo\net\http\encoders;

use ultimo\net\http\headers\BasicHeader;

class XWwwFormUrlencoded implements \ultimo\net\http\Encoder {
  const NAME = 'x-www-form-urlencoded';
  
  public function getName() {
    return self::NAME;
  }
  
  public function encode(array $params, BasicHeader $contentTypeHeader=null) {
    return http_build_query($params);
  }
  
  public function decode($data, BasicHeader $contentTypeHeader=null) {
    parse_str($data, $params);
    return $params;
  }
}