<?php
namespace Pmp;

require_once 'Sdk/autoload.inc';

/**
 * PMP SDK wrapper
 *
 * Wrapper for the PMP sdk as a whole
 *
 */
class Sdk
{
    const FETCH_DOC     = 'urn:collectiondoc:hreftpl:docs';
    const FETCH_PROFILE = 'urn:collectiondoc:hreftpl:profiles';
    const FETCH_SCHEMA  = 'urn:collectiondoc:hreftpl:schemas';
    const FETCH_TOPIC   = 'urn:collectiondoc:hreftpl:topics';
    const FETCH_USER    = 'urn:collectiondoc:hreftpl:users';

    const QUERY_COLLECTION = 'urn:collectiondoc:query:collection';
    const QUERY_DOCS       = 'urn:collectiondoc:query:docs';
    const QUERY_GROUPS     = 'urn:collectiondoc:query:groups';
    const QUERY_PROFILES   = 'urn:collectiondoc:query:profiles';
    const QUERY_SCHEMAS    = 'urn:collectiondoc:query:schemas';
    const QUERY_TOPICS     = 'urn:collectiondoc:query:topics';
    const QUERY_USERS      = 'urn:collectiondoc:query:users';

    // the home document
    public $home;

    // auth client
    private $_auth;

    /**
     * Constructor
     *
     * This WILL fetch the host's home-doc and attempt to authenticate right
     * off the bat.  So be prepared to catch invalid-host and invalid-auth
     * errors.
     *
     * @param string $host url of the PMP api
     * @param string $id the client id to connect with
     * @param string $secret the secret for this client
     */
    public function __construct($host, $id, $secret) {

        // re-throw 404's as host-not-found (same thing, to the sdk)
        try {
            $this->home = new \Pmp\Sdk\CollectionDocJson($host);
        }
        catch (\Pmp\Sdk\Exception\NotFoundException $e) {
            throw new \Pmp\Sdk\Exception\HostException('Host not found', $e->getCode(), $e);
        }

        // authenticate, then add the auth back into the home document
        $this->_auth = new \Pmp\Sdk\AuthClient($host, $id, $secret, $this->home);
        $this->home->setAuth($this->_auth);
    }

    /**
     * Fetch aliases - all will return CollectionDocJson or null (if not found)
     */
    public function fetchDoc($guid, $options = array()) {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_DOC, $options);
    }
    public function fetchProfile($guid, $options = array()) {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_PROFILE, $options);
    }
    public function fetchSchema($guid, $options = array()) {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_SCHEMA, $options);
    }
    public function fetchTopic($guid, $options = array()) {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_TOPIC, $options);
    }
    public function fetchUser($guid, $options = array()) {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_USER, $options);
    }

    /**
     * Query aliases - all will return CollectionDocJson or null (if 0 results)
     */
    public function queryCollection($collectionGuid, $options = array()) {
        $options['guid'] = $collectionGuid;
        return $this->_request(self::QUERY_COLLECTION, $options);
    }
    public function queryDocs($options = array()) {
        return $this->_request(self::QUERY_DOCS, $options);
    }
    public function queryGroups($options = array()) {
        return $this->_request(self::QUERY_GROUPS, $options);
    }
    public function queryProfiles($options = array()) {
        return $this->_request(self::QUERY_PROFILES, $options);
    }
    public function querySchemas($options = array()) {
        return $this->_request(self::QUERY_SCHEMAS, $options);
    }
    public function queryTopics($options = array()) {
        return $this->_request(self::QUERY_TOPICS, $options);
    }
    public function queryUsers($options = array()) {
        return $this->_request(self::QUERY_USERS, $options);
    }

    /**
     * Shortcut to get a new-doc-of-profile-type
     *
     * @param string $profile the profile alias (or guid)
     * @param array $initDoc optional initial document payload
     * @return CollectionDocJson a new (unsaved) collectiondoc
     */
    public function newDoc($profile, $initDoc) {
        $doc = new \Pmp\Sdk\CollectionDocJson(null, $this->_auth);
        if ($initDoc) {
            $doc->setDocument($initDoc);
        }

        // get the profile link
        $link = $this->home->link(self::FETCH_PROFILE);
        if (empty($link)) {
            $urn = self::FETCH_PROFILE;
            throw new \Pmp\Sdk\Exception\LinkException("Unable to find link $urn in home doc");
        }
        $href = $link->expand(array('guid' => $profile));

        // set the link
        $doc->links->profile = array(new \stdClass());
        $doc->links->profile[0]->href = $href;
        return $doc;
    }

    /**
     * Make a request via the home document
     *
     * @param string $urn the link name
     * @param array $options query options
     * @return CollectionDocJson the fetched document or null
     */
    private function _request($urn, $options = array()) {
        $link = $this->home->link($urn);
        if (empty($link)) {
            throw new \Pmp\Sdk\Exception\LinkException("Unable to find link $urn in home doc");
        }
        try {
            return $link->submit($options);
        }
        catch (\Pmp\Sdk\Exception\NotFoundException $e) {
            return null;
        }
    }

}
