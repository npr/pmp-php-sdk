<?php
namespace Pmp\Sdk;

require_once 'autoload.inc';

use restagent\Request;
use Guzzle\Parser\UriTemplate\UriTemplate;

/**
 * PMP user authentication
 *
 * Authenticate as a username/password, and manage the oauth clients
 * for that user.
 *
 */
class AuthUser
{
    const URN_LIST   = 'urn:collectiondoc:form:listcredentials';
    const URN_CREATE = 'urn:collectiondoc:form:createcredentials';
    const URN_REMOVE = 'urn:collectiondoc:form:removecredentials';
    const TIMEOUT_MS = 5000;

    private $_home;
    private $_user_auth;

    /**
     * Constructor
     *
     * @param string $host     URL of the PMP api
     * @param string $username The user to connect as
     * @param string $password The user's password
     */
    public function __construct($host, $username, $password) {
        $this->_home = new \Pmp\Sdk\CollectionDocJson($host, null);
        $this->_user_auth = 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * List credentials
     *
     * @return array the current client credentials for the user
     */
    public function listCredentials() {
        return $this->makeRequest(self::URN_LIST);
    }

    /**
     * Create a credential
     *
     * @param array $options scope/expires/label options
     * @return array the newly created credential
     */
    public function createCredential($scope, $expires, $label) {
        $data = array(
            'scope' => $scope,
            'label' => $label,
            'token_expires_in' => $expires,
        );
        return $this->makeRequest(self::URN_CREATE, $data);
    }

    /**
     * Remove a credential
     *
     * @param string $id the id of the credential to remove
     * @return boolean whether a credential was deleted or not
     */
    public function removeCredential($id) {
        $this->makeRequest(self::URN_REMOVE, array('client_id' => $id));
        return true; // just assume it worked
    }

    /**
     * Make a request as this user
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
        $request->header('Authorization', $this->_user_auth);
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
