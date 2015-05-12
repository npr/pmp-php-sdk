<?php
namespace Pmp\Sdk;

/**
 * PMP CollectionDoc+JSON
 *
 * Object representation of a remote CollectionDoc.
 *
 */
class CollectionDocJson
{
    const URN_SAVE   = 'urn:collectiondoc:form:documentsave';
    const URN_DELETE = 'urn:collectiondoc:form:documentdelete';

    const URN_AUDIO_ITEM = 'urn:collectiondoc:audio';
    const URN_IMAGE_ITEM = 'urn:collectiondoc:image';
    const URN_VIDEO_ITEM = 'urn:collectiondoc:video';

    const URN_CONTRIBUTOR = 'urn:collectiondoc:collection:contributor';
    const URN_PROPERTY    = 'urn:collectiondoc:collection:property';
    const URN_SERIES      = 'urn:collectiondoc:collection:series';
    const URN_TOPIC       = 'urn:collectiondoc:collection:topic';

    // TODO: un-hardcode these and pull directly from aliases doc
    private static $_profileAliases = array(
        'ef7f170b-4900-4a20-8b77-3142d4ac07ce' => 'audio',
        '8bf6f5ae-84b1-4e52-a744-8e1ac63f283e' => 'contributor',
        '42448532-7a6f-47fb-a547-f124d5d9053e' => 'episode',
        '5f4fe868-5065-4aa2-86e6-2387d2c7f1b6' => 'image',
        '88506918-b124-43a8-9f00-064e732cbe00' => 'property',
        'c07bd70c-8644-4c5d-933a-40d5d7032036' => 'series',
        'b9ce545e-01a2-44d0-9a15-a73da4ed304b' => 'story',
        '3ffa207f-cfbe-4bcd-987c-0bd8e29fdcb6' => 'topic',
        '85115aa1-df35-4324-9acd-2bb261f8a541' => 'video',
    );

    // TODO: get rid of this someday
    const AUTH_RETRY_WAIT_S = 1;

    // global static links (cached after first request)
    private static $_staticLinkNames = array('query', 'edit', 'auth');
    private static $_staticLinks;

    // auth client
    private $_auth;

    // collection-doc accessors
    public $version;
    public $href;
    public $attributes;
    public $links;
    public $items;
    public $errors;

    // the raw response data (for debug/test purposes)
    public $_raw;

    /**
     * Constructor
     *
     * @param string $uri location of a Collection.doc+json object
     * @param AuthClient $auth the authentication client
     */
    public function __construct($uri = null, AuthClient $auth = null) {
        $this->clearDocument();

        // init
        $this->href  = is_string($uri) ? trim($uri, '/') : null;
        $this->_auth = $auth;
        if (empty(self::$_staticLinks)) {
            self::$_staticLinks = new \stdClass;
        }

        // fetch the document, if a uri was passed
        if (!empty($this->href)) {
            $this->load();
        }
    }

    /**
     * Set the auth client associated with this document
     *
     * @param AuthClient $auth the authentication client
     */
    public function setAuth(AuthClient $auth = null) {
        $this->_auth = $auth;
    }

    /**
     * Set this document back to the default state
     */
    public function clearDocument() {
        $this->version    = '1.0';
        $this->href       = null;
        $this->attributes = new \stdClass();
        $this->links      = new \stdClass();
        $this->items      = array();
        $this->errors     = null;
        return $this;
    }

    /**
     * Set this documents payload
     *
     * @param stdClass|array $doc the document object
     */
    public function setDocument($doc) {
        $this->clearDocument();
        $doc = json_decode(json_encode($doc)); // clone and convert arrays

        // set known properties
        if (!empty($doc->version)) {
            $this->version = $doc->version;
        }
        if (!empty($doc->href)) {
            $this->href = $doc->href;
        }
        if (!empty($doc->attributes)) {
            $this->attributes = $doc->attributes;
        }
        if (!empty($doc->links)) {
            $this->links = $doc->links;
        }
        if (!empty($doc->items)) {
            $this->items = $doc->items;
        }
        if (!empty($doc->errors)) {
            $this->errors = $doc->errors;
        }

        // get/set static links (preserving them between sets)
        foreach (self::$_staticLinkNames as $name) {
            if (empty($this->links->$name)) {
                $this->links->$name = self::$_staticLinks->$name;
            }
            else {
                self::$_staticLinks->$name = $this->links->$name;
            }
        }
        return $this;
    }

    /**
     * Load this document from the remote server
     */
    public function load() {
        if (empty($this->href)) {
            throw new Exception\PmpException('No href set for document!');
        }
        else {
            $doc = $this->_request('get', $this->href);
            $this->setDocument($doc);
        }
        return $this;
    }

    /**
     * Persist this document to the remote server
     */
    public function save() {
        if (empty($this->attributes->guid)) {
            $this->attributes->guid = $this->createGuid();
        }

        // expand link template
        $link = $this->edit(self::URN_SAVE);
        if (!$link) {
            $urn = self::URN_SAVE;
            throw new Exception\LinkException("Unable to find link $urn - have you loaded the document yet?");
        }
        $url = $link->expand(array('guid' => $this->attributes->guid));

        // create a saveable version of this doc
        $json = new \stdClass();
        $json->version    = $this->version;
        $json->attributes = $this->attributes;
        $json->links = new \stdClass();
        foreach ($this->links as $relType => $links) {
            if (!in_array($relType, self::$_staticLinkNames)) {
                $json->links->$relType = $links;
            }
        }

        // remote save
        $resp = $this->_request('put', $url, $json);
        if (empty($resp->url)) {
            $data = array('url' => $url, 'body' => json_encode($json));
            throw new Exception\RemoteException('Invalid PUT response missing url!', $data);
        }

        // re-load doc
        $this->href = $resp->url;
        $this->load();
        return $this;
    }

    /**
     * Delete the current document on the remote server
     */
    public function delete() {
        if (empty($this->attributes->guid)) {
            throw new Exception\PmpException('Document has no guid!');
        }

        // expand link template
        $link = $this->edit(self::URN_DELETE);
        if (!$link) {
            $urn = self::URN_DELETE;
            throw new Exception\LinkException("Unable to find link $urn - have you loaded the document yet?");
        }
        $url = $link->expand(array('guid' => $this->attributes->guid));

        // delete and clear document
        $this->_request('delete', $url);
        $this->clearDocument();
        return $this;
    }

    /**
     * Gets an access token from the authentication client
     *
     * @param bool $refresh whether to refresh the token
     * @return string the auth token
     */
    public function getAccessToken($refresh = false) {
        if ($this->_auth) {
            return $this->_auth->getToken($refresh)->access_token;
        }
        else {
            return null;
        }
    }

    /**
     * Creates a guid using UUID v4 based on RFC 4122
     *
     * @see http://tools.ietf.org/html/rfc4122#section-4.4
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     * @return string a uuid-v4
     */
    public function createGuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Get a single link by urn, or relType + urn
     *
     * @param string $urn the uniform resource name to look for
     * @return CollectionDocJsonLink the link object or null
     */
    public function link($urnOrRelType, $urn = null) {
        $relTypeKeys = array_keys(get_object_vars($this->links));
        if ($urn) {
            $relTypeKeys = array($urnOrRelType);
        }
        else {
            $urn = $urnOrRelType;
        }

        // look for a matching urn within the links
        foreach ($relTypeKeys as $relType) {
            $links = $this->links($relType);
            $matching = $links->rel($urn);
            if ($matching) {
                return $matching;
            }
        }
        return null;
    }

    /**
     * Get array of links by relation type
     *
     * @param string $relType type of relation
     * @param string $urn the uniform resource name to look for
     * @return CollectionDocJsonLinks the links object
     */
    public function links($relType, $urn = null) {
        $rawLinks = array();
        if (!empty($this->links->$relType)) {
            $rawLinks = $this->links->$relType;
        }
        $links = new CollectionDocJsonLinks($rawLinks, $this->_auth);

        // optionally filter by urn
        if ($urn) {
            return $links->rels(array($urn));
        }
        else {
            return $links;
        }
    }

    /**
     * Shortcut for the profile link
     *
     * @return CollectionDocJsonLink the profile link object
     */
    public function getProfile() {
        $links = $this->links('profile');
        return isset($links[0]) ? $links[0] : null;
    }

    /**
     * Get the alias of this doc's profile (if it's an aliased profile)
     *
     * @return string the profile alias, or guid if alias cannot be inferred
     */
    public function getProfileAlias() {
        $link = $this->getProfile();
        if ($link && $link->href) {
            $guidOrAlias = explode('/', $link->href);
            $guidOrAlias = end($guidOrAlias);
            return isset(self::$_profileAliases[$guidOrAlias]) ? self::$_profileAliases[$guidOrAlias] : $guidOrAlias;
        }
        else {
            return null;
        }
    }

    /**
     * Shortcut for the creator link
     *
     * @return CollectionDocJsonLink the creator link object
     */
    public function getCreator() {
        $links = $this->links('creator');
        return isset($links[0]) ? $links[0] : null;
    }

    /**
     * Shortcut for the owner link
     *
     * @return CollectionDocJsonLink the owner link object
     */
    public function getOwner() {
        $links = $this->links('owner');
        return isset($links[0]) ? $links[0] : null;
    }

    /**
     * Shortcut for collection links
     *
     * @param $collectionType optional urn (or urn suffix) to filter by
     * @return CollectionDocJsonLink the collection links array
     */
    public function getCollections($urnOrSuffix = null) {
        $knownUrns = array(
            'contributor' => self::URN_CONTRIBUTOR,
            'property'    => self::URN_PROPERTY,
            'series'      => self::URN_SERIES,
            'topic'       => self::URN_TOPIC,
        );
        $urnOrSuffix = isset($knownUrns[$urnOrSuffix]) ? $knownUrns[$urnOrSuffix] : $urnOrSuffix;
        return $this->links('collection', $urnOrSuffix);
    }

    /**
     * Link shortcuts (could also just use the "link" method)
     */
    public function query($urn) {
        return $this->link('query', $urn);
    }
    public function edit($urn) {
        return $this->link('edit', $urn);
    }
    public function auth($urn) {
        return $this->link('auth', $urn);
    }
    public function navigation($urn) {
        return $this->link('navigation', $urn);
    }

    /**
     * Return the set of document items
     *
     * @param $profileAlias optional profile to limit returned items to
     * @return CollectionDocJsonItems
     */
    public function items($profileAlias = null) {
        $rawItems = empty($this->items) ? array() : $this->items;
        $items = new CollectionDocJsonItems($rawItems, $this);

        // optionally filter based on profile alias
        if ($profileAlias) {
            $filteredRawItems = array();
            foreach ($items as $idx => $item) {
                if ($item->getProfileAlias() == $profileAlias) {
                    $filteredRawItems[] = $rawItems[$idx];
                }
            }
            $items = new CollectionDocJsonItems($filteredRawItems, $this);
        }
        return $items;
    }

    /**
     * Get an iterator for all the document items
     *
     * @param $pageLimit the maximum number of pages to fetch
     * @return PageIterator the iterator
     */
    public function itemsIterator($pageLimit = null) {
        return new PageIterator($this, $pageLimit);
    }

    /**
     *
     *
     */

    /**
     * Make a remote request
     *
     * @param string $method the http method to use
     * @param string $url the location of the resource
     * @param array $data optional data to send with request
     * @param bool $is_retry whether this request is a 401-retry
     * @return stdClass the json-decoded response
     */
    private function _request($method, $url, $data = null, $is_retry = false) {
        $token = $this->getAccessToken($is_retry);

        // make request, retrying auth failures ONCE with a new token
        try {
            list($code, $json, $rawData) = Http::bearerRequest($method, $url, $token, $data);
        }
        catch (Exception\AuthException $e) {
            sleep(self::AUTH_RETRY_WAIT_S); // TODO: i hate this
            return $this->_request($method, $url, $data, true);
        }
        catch (Exception\RemoteException $e) {
            if (Exception\ValidationException::looksValidationy($e)) {
                throw new Exception\ValidationException('Validation error', $e->getCode(), $e);
            }
            else {
                throw $e; // re-throw
            }
        }

        // record the raw response data, in case we want to check it later on
        $this->_raw = $rawData;

        return $json;
    }

}
