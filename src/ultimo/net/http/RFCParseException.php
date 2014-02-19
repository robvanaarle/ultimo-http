<?php

namespace ultimo\net\http;

class RFCParseException extends Exception {
  public $lastText;
  public $remainingText;
  
  public function __construct($message, $remainingText='', $lastText='') {
    $this->lastText = $lastText;
    $this->remainingText = $remainingText;
    parent::__construct($message . "\nlastText: {$lastText}\nremainingText: {$remainingText}");
  }
}