<?php
namespace Pmp\Sdk;

require_once 'autoload.inc';

use restagent\Request;
use Guzzle\Parser\UriTemplate\UriTemplate;

/**
 * PMP client authentication
 *
 * Oauth on behalf of a user with client-id/secret, to create and revoke
 * tokens for the client
 *
 */
class AuthClient
{
    const URN_ISSUE  = 'urn:collectiondoc:form:issuetoken';
    const URN_REVOKE = 'urn:collectiondoc:form:revoketoken';
    const TIMEOUT_MS = 5000;

    private $_home;
    private $_client_auth;
    private $_token;
    private $_token_last_retrieved;

    /**
     * Constructor
     *
     * @param string $host   URL of the PMP api
     * @param string $id     The client id to connect with
     * @param string $secret The secret for this client
     */
    public function __construct($host, $id, $secret) {
        $this->_home = new \Pmp\Sdk\CollectionDocJson($host, null);
        $this->_client_auth = 'Basic ' . base64_encode($id . ':' . $secret);
        $this->getToken();
    }

    /**
     * Get an auth token for these client credentials
     *
     * @param bool refresh whether to force fetching a new token
     * @return object the auth token object
     */
    public function getToken($refresh = false) {
        if ($refresh || empty($this->_token)) {
            $data = array('grant_type' => 'client_credentials');
            $this->_token = $this->makeRequest(self::URN_ISSUE, $data);

            // check for valid response
            if (empty($this->_token->access_token)) {
                throw new Exception('Got unexpected empty token from the authentication server');
            }
            $this->_token_last_retrieved = time();
        }
        else {
            // update the token-expires-in-seconds
            $countdown = $this->_token->token_expires_in - (time() - $this->_token_last_retrieved);
            $this->_token->token_expires_in = $countdown;
        }
        return $this->_token;
    }

    /**
     * Revoke the auth token for these client credentials
     *
     * @return bool whether the token was deleted or not
     */
    public function revokeToken() {
        $this->makeRequest(self::URN_REVOKE);
        $this->_token = null;
        $this->_token_last_retrieved = null;
        return true;
    }

    /**
     * Make a request as this client
     *
     * @param string $urn the URN of the link to get
     * @param array $data optional data to send with request
     * @return array the json response
     */
    private function makeRequest($urn, $data = null) {
        $link = $this->_home->auth($urn);
        $href = property_exists($link, 'href') ? $link->href : null;
        if (!$href && property_exists($link, 'href-template')) {
            $parser = new UriTemplate();
            $href = $parser->expand($link->{'href-template'}, $data);
        }
        if (!$href) {
            $err = new Exception("Unable to retrieve $urn from the home document");
            throw $err;
        }

        // check hints
        $method = 'GET';
        if ($link->hints && $link->hints->allow && !empty($link->hints->allow)) {
            $method = strtoupper($link->hints->allow[0]);
        }

        // build request
        $request = new Request();
        $request->method($method);
        $request->timeout(self::TIMEOUT_MS);
        $request->header('Authorization', $this->_client_auth);
        $request->header('Accept', 'application/json');

        // optional POST data
        if ($method == 'POST' && $data) {
            $request->header('Content-Type', 'application/x-www-form-urlencoded');
            foreach ($data as $key => $value) {
                if ($value) {
                    $request->data($key, $value);
                }
            }
        }

        // do it!
        $response = $request->send($href);
        if ($response['code'] > 299) {
            $err = "Got unexpected {$response['code']} from $method $href";
            $exception = new Exception($err, $response['code']);
            $exception->setDetails($response);
            throw $exception;
        }
        return json_decode($response['data']);
    }

}
