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
        $doc = json_decode(json_encode($doc)); // convert arrays

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
            throw new Exception('No href set for document!');
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
        $isNew = false;
        if (empty($this->attributes->guid)) {
            $this->attributes->guid = $this->createGuid();
            $isNew = true;
        }

        // expand link template
        $link = $this->edit(self::URN_SAVE);
        if (!$link) {
            $urn = self::URN_SAVE;
            var_dump($this->links->edit);
            throw new Exception("Unable to find link $urn - have you loaded the document yet?");
        }
        $url = $link->expand(array('guid' => $this->attributes->guid));

        // create a saveable version of this doc
        $json = new \stdClass();
        $json->version    = $this->version;
        $json->attributes = $this->attributes;
        $json->links      = $this->links;

        // remote save
        $resp = $this->_request('put', $url, $json);
        if (empty($resp->url)) {
            $e = new Exception("Invalid PUT response missing url!");
            $e->setDetails($resp);
            throw $e;
        }

        // re-load new docs
        if ($isNew) {
            $this->href = $resp->url;
            $this->load();
        }
        return $this;
    }

    /**
     * Delete the current document on the remote server
     */
    public function delete() {
        if (empty($this->attributes->guid)) {
            throw new Exception('Document has no guid!');
        }

        // expand link template
        $link = $this->edit(self::URN_DELETE);
        if (!$link) {
            $urn = self::URN_DELETE;
            throw new Exception("Unable to find link $urn - have you loaded the document yet?");
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
     * @return CollectionDocJsonLinks the links object
     */
    public function links($relType) {
        $links = array();
        if (!empty($this->links->$relType)) {
            $links = $this->links->$relType;
        }
        return new CollectionDocJsonLinks($links, $this->_auth);
    }

    /**
     * Shortcut for the profile link
     *
     * @return CollectionDocJsonLink the profile link object
     */
    public function getProfile() {
        $links = $this->links('profile');
        return $links[0];
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
     * @return CollectionDocJsonItems
     */
    public function items() {
        $items = array();
        if (!empty($this->items)) {
            $items = $this->items;
        }
        return new CollectionDocJsonItems($items, $this);
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
     * Make a remote request
     *
     * @param string $method the http method to use
     * @param string $url the location of the resource
     * @param array $data optional data to send with request
     * @param bool $is_retry whether this request is a 401-retry
     * @return stdClass the json-decoded response
     */
    private function _request($method, $url, $data = null) {
        $token = $this->getAccessToken();
        list($code, $json) = Http::bearerRequest($method, $url, $token, $data);

        // retry 401's with refreshed token
        if ($code == 401) {
            $token = $this->getAccessToken(true);
            list($code, $json) = Http::bearerRequest($method, $url, $token, $data);
        }

        // barf on non-200
        if ($code < 200 || $code > 299) {
            $e = new Exception("Got unexpected HTTP-$code while retrieving $url", $code);
            $e->setDetails($json);
            throw $e;
        }

        return $json;
    }

}
