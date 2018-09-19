<?php

namespace Pmp;

use Pmp\Sdk\AuthClient;
use Pmp\Sdk\CollectionDocJson;
use Pmp\Sdk\Exception\HostException;
use Pmp\Sdk\Exception\LinkException;
use Pmp\Sdk\Exception\NotFoundException;
use Pmp\Sdk\Http;

/**
 * PMP SDK client
 */
class Sdk implements \Serializable
{
    const VERSION = '1.0.4'; // UPDATE ME!!!

    // Known URNs
    const FETCH_DOC = 'urn:collectiondoc:hreftpl:docs';
    const FETCH_PROFILE = 'urn:collectiondoc:hreftpl:profiles';
    const FETCH_SCHEMA = 'urn:collectiondoc:hreftpl:schemas';
    const FETCH_TOPIC = 'urn:collectiondoc:hreftpl:topics';
    const FETCH_USER = 'urn:collectiondoc:hreftpl:users';
    const QUERY_COLLECTION = 'urn:collectiondoc:query:collection';
    const QUERY_DOCS = 'urn:collectiondoc:query:docs';
    const QUERY_GROUPS = 'urn:collectiondoc:query:groups';
    const QUERY_PROFILES = 'urn:collectiondoc:query:profiles';
    const QUERY_SCHEMAS = 'urn:collectiondoc:query:schemas';
    const QUERY_TOPICS = 'urn:collectiondoc:query:topics';
    const QUERY_USERS = 'urn:collectiondoc:query:users';

    // Serialization prefixes
    const GZIP_SERIAL_PREFIX = 'gz=';
    const BASE64_SERIAL_PREFIX = '64=';

    /**
     * The home doc
     *
     * @var CollectionDocJson
     */
    public $home;

    /**
     * Advanced config options
     *
     * @var array
     */
    private $_options;

    /**
     * auth client
     *
     * @var AuthClient
     */
    private $_auth;

    /**
     * Constructor
     *
     * This attempts to both retrieve an auth token and a doc so be prepared to catch
     * appropriate exceptions
     *
     * @param string $host URL of a doc in the PMP API
     * @param string $id the client ID to connect with
     * @param string $secret the secret for this client
     * @param array $options optional advanced options for the sdk
     */
    public function __construct($host, $id, $secret, $options = [])
    {
        Http::setOptions($options);
        $this->_options = $options;

        // fetch the doc then add as the home doc
        try {
            $this->home = new CollectionDocJson($host);
        } catch (NotFoundException $e) {
            // re-throw 404's as host-not-found (same thing, to the sdk)
            throw new HostException('Host not found', $e->getCode(), $e);
        }

        // authenticate, then add the auth back into the home doc
        $this->_auth = new AuthClient($host, $id, $secret, $this->home);
        $this->home->setAuth($this->_auth);
    }

    /**
     * Save this SDK to string, including any fetched home-doc / tokens
     *
     * @return string
     */
    public function serialize()
    {
        $serialized = serialize([$this->_options, $this->_auth]);

        // encode data (optionally attempt to zip)
        if (function_exists('gzencode') && isset($this->_options['serialzip']) && $this->_options['serialzip']) {
            $encoded = self::GZIP_SERIAL_PREFIX . gzencode($serialized);
        } else {
            $encoded = self::BASE64_SERIAL_PREFIX . base64_encode($serialized);
        }
        return $encoded;
    }

    /**
     * Attempt to recreate an SDK from string
     */
    public function unserialize($raw)
    {
        $prefix = substr($raw, 0, 3);
        $encoded = substr($raw, 3);
        if ($prefix == self::GZIP_SERIAL_PREFIX) {
            if (function_exists('gzdecode')) {
                $serialized = gzdecode($encoded);
            } else if (function_exists('gzinflate')) {
                $serialized = gzinflate(substr($encoded, 10, -8)); // alternate method
            } else {
                throw new \RuntimeException('Unable to unzip serialized data!');
            }
        } else if ($prefix == self::BASE64_SERIAL_PREFIX) {
            $serialized = base64_decode($encoded);
        } else {
            $serialized = $encoded;
        }

        // unserialize and sanity check
        $data = unserialize($serialized);
        if ($data && is_array($data) && count($data) == 2) {
            Http::setOptions($data[0]);
            $this->_options = $data[0];
            $this->_auth = $data[1];
            $this->home = $data[1]->home;
        } else {
            throw new \UnexpectedValueException('Invalid serialized data for PmpSdk');
        }
    }

    /**
     * Get the URL of a doc by guid or alias
     *
     * @param string $guid
     * @return string
     */
    public function hrefDoc($guid)
    {
        return $this->_expandGuid(self::FETCH_DOC, $guid);
    }

    /**
     * Get the URL of a profile by guid or alias
     *
     * @param string $guid
     * @return string
     */
    public function hrefProfile($guid)
    {
        return $this->_expandGuid(self::FETCH_PROFILE, $guid);
    }

    /**
     * Get the URL of a schema by guid or alias
     *
     * @param string $guid
     * @return string
     */
    public function hrefSchema($guid)
    {
        return $this->_expandGuid(self::FETCH_SCHEMA, $guid);
    }

    /**
     * Get the URL of a topic by guid or alias
     *
     * @param string $guid
     * @return string
     */
    public function hrefTopic($guid)
    {
        return $this->_expandGuid(self::FETCH_TOPIC, $guid);
    }

    /**
     * Get the URL of a user by guid or alias
     *
     * @param string $guid
     * @return string
     */
    public function hrefUser($guid)
    {
        return $this->_expandGuid(self::FETCH_USER, $guid);
    }

    /**
     * Fetch a doc
     *
     * @param string $guid
     * @param array $options
     * @return CollectionDocJson
     */
    public function fetchDoc($guid, $options = [])
    {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_DOC, $options);
    }

    /**
     * Fetch a profile
     *
     * @param string $guid
     * @param array $options
     * @return CollectionDocJson
     */
    public function fetchProfile($guid, $options = [])
    {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_PROFILE, $options);
    }

    /**
     * Fetch a schema
     *
     * @param string $guid
     * @param array $options
     * @return CollectionDocJson
     */
    public function fetchSchema($guid, $options = [])
    {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_SCHEMA, $options);
    }

    /**
     * Fetch a topic
     *
     * @param string $guid
     * @param array $options
     * @return CollectionDocJson
     */
    public function fetchTopic($guid, $options = [])
    {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_TOPIC, $options);
    }

    /**
     * Fetch a user
     *
     * @param string $guid
     * @param array $options
     * @return CollectionDocJson
     */
    public function fetchUser($guid, $options = [])
    {
        $options['guid'] = $guid;
        return $this->_request(self::FETCH_USER, $options);
    }

    /**
     * Query the collections
     *
     * @param string $collectionGuid
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryCollection($collectionGuid, $options = [])
    {
        $options['guid'] = $collectionGuid;
        return $this->_request(self::QUERY_COLLECTION, $options);
    }

    /**
     * Query the docs
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryDocs($options = [])
    {
        return $this->_request(self::QUERY_DOCS, $options);
    }

    /**
     * Query the groups
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryGroups($options = [])
    {
        return $this->_request(self::QUERY_GROUPS, $options);
    }

    /**
     * Query the profiles
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryProfiles($options = [])
    {
        return $this->_request(self::QUERY_PROFILES, $options);
    }

    /**
     * Query the schemas
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function querySchemas($options = [])
    {
        return $this->_request(self::QUERY_SCHEMAS, $options);
    }

    /**
     * Query the topics
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryTopics($options = [])
    {
        return $this->_request(self::QUERY_TOPICS, $options);
    }

    /**
     * Query the users
     *
     * @param array $options
     * @return CollectionDocJson
     */
    public function queryUsers($options = [])
    {
        return $this->_request(self::QUERY_USERS, $options);
    }

    /**
     * Create a new doc
     *
     * @param string $profile the profile alias (or guid)
     * @param array $initDoc optional initial doc payload
     * @return CollectionDocJson a new (unsaved) doc
     */
    public function newDoc($profile, $initDoc = null)
    {
        $doc = new CollectionDocJson(null, $this->_auth);
        if ($initDoc) {
            $doc->setDocument($initDoc);
        }

        // get the profile link
        $urn = self::FETCH_PROFILE;
        $link = $this->home->link($urn);
        if (empty($link)) {
            throw new LinkException("Unable to find link $urn in home doc");
        }
        $href = $link->expand(['guid' => $profile]);

        // set the link
        $doc->links->profile = [new \StdClass()];
        $doc->links->profile[0]->href = $href;
        return $doc;
    }

    /**
     * Make a request via the home doc
     *
     * @param string $urn the link name
     * @param array $options query options
     * @return CollectionDocJson the fetched doc
     */
    private function _request($urn, $options = [])
    {
        $link = $this->home->link($urn);
        if (empty($link)) {
            throw new LinkException("Unable to find link $urn in home doc");
        }
        try {
            return $link->submit($options);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Get the fetch path for a guid/alias
     *
     * @param string $urn the link name
     * @param string $guid the guid or alias
     * @return string the full url to the resource
     */
    private function _expandGuid($urn, $guid)
    {
        $link = $this->home->link($urn);
        if (empty($link)) {
            throw new LinkException("Unable to find link $urn in home doc");
        }
        return $link->expand(['guid' => $guid]);
    }
}
