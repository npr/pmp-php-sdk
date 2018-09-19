<?php

namespace Pmp\Sdk;

use Pmp\Sdk\Exception\LinkException;
use Pmp\Sdk\Exception\NotFoundException;

/**
 * Authenticated user
 *
 * Authenticate with a username/password to manage client credentials
 * for a user
 */
class AuthUser
{
    // Known URNs
    const URN_LIST = 'urn:collectiondoc:form:listcredentials';
    const URN_CREATE = 'urn:collectiondoc:form:createcredentials';
    const URN_REMOVE = 'urn:collectiondoc:form:removecredentials';

    /**
     * The URL of the API
     * @var string
     */
    private $_host;

    /**
     * The home doc
     *
     * @var CollectionDocJson
     */
    private $_home;

    /**
     * Basic authentication header value
     *
     * @var string
     */
    private $_userAuth;

    /**
     * Constructor
     *
     * @param string $host URL of the API
     * @param string $username the user to connect as
     * @param string $password the user's password
     * @param CollectionDocJson $home a pre-loaded home doc
     */
    public function __construct($host, $username, $password, CollectionDocJson $home = null)
    {
        $this->_host = $host;
        $this->_home = $home;
        $this->_userAuth = 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * List credentials
     *
     * @return \StdClass the current client credentials for the user
     */
    public function listCredentials()
    {
        return $this->_request(self::URN_LIST);
    }

    /**
     * Create a credential
     *
     * @param string $scope the scope for the credential
     * @param string $expires number of seconds after which generated tokens should expire
     * @param string $label human-readable name for the credential
     * @return \StdClass the newly created credential
     */
    public function createCredential($scope, $expires, $label)
    {
        $data = [
            'scope' => $scope,
            'label' => $label,
            'token_expires_in' => $expires,
        ];
        return $this->_request(self::URN_CREATE, $data);
    }

    /**
     * Remove a credential
     *
     * @param string $id the client ID of the credential to remove
     * @return bool whether a credential was deleted or not
     */
    public function removeCredential($id)
    {
        try {
            $this->_request(self::URN_REMOVE, ['client_id' => $id]);
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Make a request as this user
     *
     * @param string $urn the URN of the link to request
     * @param array $data ata to send with request
     * @return \StdClass the json response
     */
    private function _request($urn, $data = [])
    {
        list($method, $url) = $this->_authLink($urn, $data);
        list($code, $json) = Http::basicRequest($method, $url, $this->_userAuth, $data);
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
        if (empty($this->_home)) {
            $this->_home = new CollectionDocJson($this->_host);
        }

        // fetch the link
        $link = $this->_home->auth($urn);
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
