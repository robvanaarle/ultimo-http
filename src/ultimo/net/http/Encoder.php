<?php

namespace ultimo\net\http;

use ultimo\net\http\headers\BasicHeader;

interface Encoder {
  public function getName();
  
  public function encode(array $params, BasicHeader $contentTypeHeader=null);
  
  public function decode($data, BasicHeader $contentTypeHeader);
}