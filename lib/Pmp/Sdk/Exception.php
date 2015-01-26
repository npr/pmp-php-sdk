<?php
namespace Pmp\Sdk;

class Exception extends \Exception
{
    private $details = array();

    public function getDetails() {
        return $this->details;
    }

    public function setDetails(array $details) {
        $this->details = $details;
    }
}
