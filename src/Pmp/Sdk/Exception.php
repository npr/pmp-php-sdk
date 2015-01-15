<?php
namespace Pmp\Sdk;

/**
 * PMP Exception
 *
 * A custom exception class for PMP errors - optionally bundled with
 * details and an http status code.
 *
 */
class Exception extends \Exception
{
    private $_details = array();

    /**
     * Get the details object
     *
     * @return array the details or at least an empty array
     */
    public function getDetails() {
        return $this->_details;
    }

    /**
     * Set details
     *
     * @param mixed $details some sort of error details
     */
    public function setDetails($details) {
        if (is_array($details)) {
            $this->_details = $details;
        }
        else if (is_object($details)) {
            $this->_details = json_decode(json_encode($details), true);
        }
        else if (is_string($details)) {
            $this->_details = array($details);
        }
        else {
            $this->_details = array();
        }
    }

}
