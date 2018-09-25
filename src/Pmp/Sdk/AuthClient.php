<?php

namespace Pmp\Sdk;

use Pmp\Sdk\Exception\AuthException;
use Pmp\Sdk\Exception\LinkException;

/**
 * Authentication client
 *
 * Authenticate with a client ID/secret to manage access tokens
 * for a client of a user
 */
class AuthClient
{
    // Known URNs
    const URN_ISSUE = 'urn:collectiondoc:form:issuetoken';
    const URN_REVOKE = 'urn:collectiondoc:form:revoketoken';

    /**
     * The home doc
     *
     * @var CollectionDocJson
     */
    public $home;

    /**
     * The URL of the API
     *
     * @var string
     */
    private $_host;

    /**
     * Basic authentication header value
     *
     * @var string
     */
    private $_clientAuth;

    /**
     * The access token object
     *
     * @var \StdClass
     */
    private $_token;

    /**
     * Constructor
     *
     * @param string $host URL of the API
     * @param string $id the client id to connect with
     * @param string $secret the secret for this client
     * @param CollectionDocJson $home a pre-loaded home doc
     */
    public function __construct($host, $id, $secret, CollectionDocJson $home = null)
    {
        $this->_host = $host;
        if (empty($id) || empty($secret)) {
            throw new AuthException("Missing client credentials");
        }
        $this->_clientAuth = 'Basic ' . base64_encode($id . ':' . $secret);
        $this->home = $home;
        $this->getToken();
    }

    /**
     * Get an auth token for these client credentials
     *
     * @param bool $refresh whether to force fetching a new token
     * @return \StdClass the auth token object
     */
    public function getToken($refresh = false)
    {
        if ($refresh || empty($this->_token)) {
            $data = ['grant_type' => 'client_credentials'];
            $this->_token = $this->_request(self::URN_ISSUE, $data);

            // check for valid response
            if (empty($this->_token->access_token)) {
                throw new AuthException('Unexpected empty token from the authentication server');
            }
        }
        return $this->_token;
    }

    /**
     * Revoke the auth token for these client credentials
     *
     * @return bool whether the token was deleted or not
     */
    public function revokeToken()
    {
        $this->_request(self::URN_REVOKE);
        $this->_token = null;
        return true;
    }

    /**
     * Make a request as this user client
     *
     * @param string $urn the URN of the link to request
     * @param array $data data to send with request
     * @return \StdClass the json response
     */
    private function _request($urn, array $data = [])
    {
        list($method, $url) = $this->_authLink($urn, $data);
        list($code, $json) = Http::basicRequest($method, $url, $this->_clientAuth, $data);
        return $json;
    }

    /**
     * Fetch an auth link from the home doc
     *
     * @param string $urn the URN of the link to get
     * @param array $data href-template params
     * @return array the method and URL for the requested link as array(string $method, string $url)
     */
    private function _authLink($urn, array $data = [])
    {
        if (empty($this->home)) {
            $this->home = new CollectionDocJson($this->_host);
        }

        // fetch the link
        $link = $this->home->auth($urn);
        if (!$link) {
            throw new LinkException("Unable to retrieve $urn from the home document");
        }

        // expand the link (data will be ignored unless it's an href-template)
        $url = $link->expand($data);

        // check hints
        $method = 'GET';
        if (!empty($link->hints) && !empty($link->hints->allow)) {
            $method = strtoupper($link->hints->allow[0]);
        }

        return [$method, $url];
    }
}
