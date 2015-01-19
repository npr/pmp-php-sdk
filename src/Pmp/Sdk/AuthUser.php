<?php
namespace Pmp\Sdk;

/**
 * PMP user authentication
 *
 * Authenticate as a username/password, and manage the oauth clients
 * for that user.
 *
 */
class AuthUser
{
    const URN_LIST     = 'urn:collectiondoc:form:listcredentials';
    const URN_CREATE   = 'urn:collectiondoc:form:createcredentials';
    const URN_REMOVE   = 'urn:collectiondoc:form:removecredentials';

    private $_host;
    private $_home;
    private $_userAuth;

    /**
     * Constructor
     *
     * @param string $host url of the PMP api
     * @param string $username the user to connect as
     * @param string $password the user's password
     * @param CollectionDocJson $home an optional pre-loaded home doc
     */
    public function __construct($host, $username, $password, CollectionDocJson $home = null) {
        $this->_host = $host;
        $this->_home = $home;
        $this->_userAuth = 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * List credentials
     *
     * @return array the current client credentials for the user
     */
    public function listCredentials() {
        return $this->_request(self::URN_LIST);
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
        return $this->_request(self::URN_CREATE, $data);
    }

    /**
     * Remove a credential
     *
     * @param string $id the id of the credential to remove
     * @return boolean whether a credential was deleted or not
     */
    public function removeCredential($id) {
        $this->_request(self::URN_REMOVE, array('client_id' => $id));
        return true; // just assume it worked
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
        list($code, $json) = Http::basicRequest($method, $url, $this->_userAuth, $data);
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
