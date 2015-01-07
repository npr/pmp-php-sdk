<?php

namespace Pmp\Sdk;

class Exception extends \Exception
{
    private $httpCode = null;
    private $details = array();

    public function getHttpCode() {
      return $this->httpCode;
    }

    public function setHttpCode($code) {
      $this->httpCode = $code;
    }

    public function getDetails() {
        return $this->details;
    }

    public function setDetails(array $details) {
        $this->details = $details;
    }
}
