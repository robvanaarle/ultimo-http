<?php

namespace ultimo\net\http\php\sapi;

use ultimo\net\http\Response;
use ultimo\net\http\XRequest;

interface SapiAdapter {
  public function getRequest(XRequest $request);
  
  public function flush(Response $response);
  
  public function getOutputStream();
}