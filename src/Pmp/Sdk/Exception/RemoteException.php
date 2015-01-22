<?php
namespace Pmp\Sdk\Exception;

/**
 * PMP errors related to the remote API server
 *
 * Usually HTTP 400/500-ish, or an unexpected response body with a 200-ish
 */
class RemoteException extends PmpException {

    public $httpMethod;
    public $httpUrl;
    public $httpStatus;
    public $httpResponse;

    /**
     * Constructor to allow an array of data instead of the usual code.
     *
     * @param string $message the message to display
     * @param int|array $code the http code or an array of data
     * @param Exception $prev optional nested exception
     */
    public function __construct($message, $code = 0, \Exception $prev = null) {

        // scrape data from nested remote exceptions
        if ($prev && is_a($prev, '\Pmp\Sdk\Exception\RemoteException')) {
            $code = array(
                'method' => $prev->httpMethod,
                'url'    => $prev->httpUrl,
                'code'   => $prev->httpStatus,
                'body'   => $prev->httpResponse,
            );
        }

        // save http data
        if (is_array($code)) {
            if (isset($code['method'])) {
                $this->httpMethod = $code['method'];
            }
            if (isset($code['url'])) {
                $this->httpUrl = $code['url'];
            }
            if (isset($code['code'])) {
                $this->httpStatus = $code['code'];
            }
            if (isset($code['body'])) {
                $this->httpResponse = $code['body'];
            }
            $code = $this->httpStatus ? $this->httpStatus : 0;
        }
        parent::__construct($message, $code, $prev);
    }

    /**
     * Custom string representation of these errors
     */
    public function __toString() {
        $str = get_class($this) . ": [{$this->code}]: {$this->message}";
        if ($this->httpMethod || $this->httpUrl) {
            $str .= ' =>';
            if ($this->httpMethod) {
                $str .= ' ' . $this->httpMethod;
            }
            if ($this->httpUrl) {
                $str .= ' ' . $this->httpUrl;
            }
        }
        $str .= "\n";
        return $str;
    }

    /**
     * Determine if the http response looks like a json object
     *
     * @return stdClass the decoded response, or null if wasn't a json object
     */
    public function getJsonResponse() {
        $json = json_decode($this->httpResponse);
        if (is_null($json) && json_last_error() != JSON_ERROR_NONE) {
            return null; // decode error
        }
        else {
            return is_object($json) ? $json : null;
        }
    }

}
