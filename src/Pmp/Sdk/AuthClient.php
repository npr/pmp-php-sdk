<?php
namespace Pmp\Sdk;

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

    private $_host;
    private $_home;
    private $_clientAuth;
    private $_token;

    /**
     * Constructor
     *
     * @param string $host url of the PMP api
     * @param string $id the client id to connect with
     * @param string $secret the secret for this client
     * @param CollectionDocJson $home an optional pre-loaded home doc
     */
    public function __construct($host, $id, $secret, CollectionDocJson $home = null) {
        $this->_host = $host;
        $this->_home = $home;
        $this->_clientAuth = 'Basic ' . base64_encode($id . ':' . $secret);
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
            $this->_token = $this->_request(self::URN_ISSUE, $data);

            // check for valid response
            if (empty($this->_token->access_token)) {
                throw new Exception\AuthException('Unexpected empty token from the authentication server');
            }
        }
        return $this->_token;
    }

    /**
     * Revoke the auth token for these client credentials
     *
     * @return bool whether the token was deleted or not
     */
    public function revokeToken() {
        $this->_request(self::URN_REVOKE);
        $this->_token = null;
        return true;
    }

    /**
     * Make a request as this user
     *
     * @param string $urn the URN of the link to get
     * @param array $data optional data to send with request
     * @return array the json response
     */
    private function _request($urn, $data = null) {
        list($method, $url) = $this->_authLink($urn, $data);
        list($code, $json) = Http::basicRequest($method, $url, $this->_clientAuth, $data);
        return $json;
    }

    /**
     * Fetch an auth link from the home document
     *
     * @param string $urn the URN of the link to get
     * @param array $data optional href-template params
     * @return array($method, $url) the method and url for that link
     */
    private function _authLink($urn, $data = null) {
        if (empty($this->_home)) {
            $this->_home = new CollectionDocJson($this->_host, null);
        }

        // fetch the link
        $link = $this->_home->auth($urn);
        if (!$link) {
            throw new Exception\LinkException("Unable to retrieve $urn from the home document");
        }

        // expand the link (data will be ignored unless it's an href-template)
        $url = $link->expand($data);

        // check hints
        $method = 'GET';
        if (!empty($link->hints) && !empty($link->hints->allow)) {
            $method = strtoupper($link->hints->allow[0]);
        }

        return array($method, $url);
    }

}
