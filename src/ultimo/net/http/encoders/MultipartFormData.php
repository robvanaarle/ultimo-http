<?php

namespace ultimo\net\http\encoders;

use ultimo\net\http\headers\BasicHeader;

class MultipartFormData implements \ultimo\net\http\Encoder {
  const NAME = 'multipart/form-data';
  
  public function getName() {
    return self::NAME;
  }
  
  public function encode(array $params, BasicHeader $contentTypeHeader=null) {
    return "todo: encode multipart/form-data";
  }
  
  public function decode($data, BasicHeader $contentTypeHeader=null) {
    return "todo: decode multipart/form-data";
  }
}
