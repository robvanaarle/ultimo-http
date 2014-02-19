<?php

namespace ultimo\net\http;

interface ResponseBody {
  function printBody();
  
  function __toString();
}