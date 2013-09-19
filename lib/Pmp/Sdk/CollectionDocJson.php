<?php
namespace Pmp\Sdk;

require_once('CollectionDocJsonLinks.php');
require_once(dirname(__FILE__) . '/../../restagent/restagent.lib.php');
use restagent\Request as Request;

class CollectionDocJson
{
    private $accessToken;
    private $readOnlyLinks;

    /**
     * @param string $uri
     *    URI for a Collection.doc+json document
     * @param string $accessToken
     *    access token retrieved from the authentication client
     * @throws \Exception
     */
    public function __construct($uri, $accessToken) {
        $this->accessToken = $accessToken;

        // Retrieve the document from the given URL. Document is never empty. It will throw exception if it is empty.
        $document = $this->getDocument($uri, $accessToken);

        // Extract read-only links needed by the client
        $this->extractReadOnlyLinks($document);

        // Map the document properties to this object's properties
        $this->setDocument($document);
    }

    /**
     * Gets the set of links from the document that are associated with the given link relation
     * @param string $relType
     *     link relation of the set of links to get from the document
     * @return CollectionDocJsonLinks
     */
    public function links($relType) {
        $links = array();
        if (!empty($this->readOnlyLinks->$relType)) {
            $links = $this->readOnlyLinks->$relType;
        } else if (!empty($this->links->$relType)) {
            $links = $this->links->$relType;
        }
        return new CollectionDocJsonLinks($links, $this->getAccessToken());
    }

    /**
     * Saves the current document
     * @return CollectionDocJson
     * @throws \Exception
     */
    public function save() {

        // Determine where to save the document
        $saveUri = $this->getSaveUri();

        // Save the document
        $this->putDocument($saveUri, $this->getAccessToken());

        return $this;
    }

    /**
     * Gets the set of items from the document
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
     * Gets a default "search" relation link that has the given URN
     * @param string $urn
     *    the URN associated with the desired "search" link
     * @return CollectionDocJsonLink
     */
    public function search($urn) {
        $urnSearchLink = null;
        $searchLinks = $this->links('search');

        // Lookup rels by given URN if search links found in document
        if (!empty($searchLinks)) {
            $urnSearchLinks = $searchLinks->rels(array($urn));

            // Use the first link found for the given URN if found
            if (!empty($urnSearchLinks[0])) {
                $urnSearchLink = $urnSearchLinks[0];
            }
        }
        return ($urnSearchLink) ? $urnSearchLink : new CollectionDocJsonLink(null, $searchLinks);
    }

    /**
     * Does a GET operation on the given URI and returns a JSON object
     * @param $uri
     *    the URI to use in the request
     * @param $accessToken
     *    the access token to use in the request
     * @return stdClass
     * @throws \Exception
     */
    private function getDocument($uri, $accessToken) {
        $request = new Request();

        // GET request needs an authorization header with given access token
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->get($uri);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got unexpected non-HTTP-200 response and/or empty document
                    while retrieving \"$uri\" with access Token: \"$accessToken\": \n " . print_r($response, true);
            throw new \Exception($err);
            return null;
        }
        $document = json_decode($response['data']);
        return $document;
    }

    /**
     * Does a PUT operation on the given URI using the internal JSON objects
     * @param $uri
     *    the URI to use in the request
     * @param $accessToken
     *    the access token to use in the request
     * @return bool
     * @throws \Exception
     */
    private function putDocument($uri, $accessToken) {

        // Construct the document from the allowable properties in this object
        $document = $this->buildDocument();

        $request = new Request();

        // PUT request needs an authorization header with given access token and
        // the JSON-encoded body based on the document content
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->body(json_encode($document))
                            ->put($uri);

        // Response code must be 202 in order to be successful
        if ($response['code'] != 202) {
            $err = "Got unexpected non-HTTP-202 response
                    while sending \"$uri\" with access Token: \"$accessToken\": \n " . print_r($response, true);
            throw new \Exception($err);
            return false;
        }
        return true;
    }

    public function getAccessToken() {
        return $this->accessToken;
    }

    /**
     * Creates a new guid, either from the API, or by generating a compatible UUID
     * @param bool $useApi
     *     whether to go get the guid from the API first
     * @return string
     */
    public function createGuid($useApi=false) {
        if ($useApi) {
            try {
                $guid = $this->getGuid($this->getGuidsUri(), $this->getAccessToken());
                if ($guid) {
                    return $guid;
                }
            } catch (\Exception $e) {
                // do nothing - just generate a UUID instead
            }
        }
        return $this->generateUuid();
    }

    /**
     * Generates a guid using UUID v4 based on RFC 4122
     *
     * @see http://tools.ietf.org/html/rfc4122#section-4.4
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     *
     * @return string
     */
    private function generateUuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time-low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time-mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time-hi-and-version", four most significant bits are 0100 (so first hex digit is 4, for UUID version 4)
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clock-seq-hi-and-reserved", 8 bits for "clock_seq_low", two most significant bits are 10 (so first hex digit is 8, 9, A, or B)
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Does a POST operation on the given URI to get a new random guid
     * @param $uri
     *    the URI to use in the request
     * @param $accessToken
     *    the access token to use in the request
     * @return string
     * @throws \Exception
     */
    private function getGuid($uri, $accessToken) {

        $request = new Request();

        // POST request needs an authorization header with given access token
        $response = $request->header('Content-Type', 'application/json')
            ->header('Authorization', 'Bearer ' . $accessToken)
            ->body('{"count":1}')
            ->post($uri);

        // Response code must be 200 in order to be successful
        if ($response['code'] != 200) {
            $err = "Got unexpected non-HTTP-200 response
                    while POSTing to \"$uri\" with access Token: \"$accessToken\": \n " . print_r($response, true);
            throw new \Exception($err);
            return '';
        }

        $data = json_decode($response['data']);
        return $data->guids[0];
    }

    /**
     * Extracts important read-only links from the document
     * @param \stdClass $document
     * @return CollectionDocJson
     */
    private function extractReadOnlyLinks(\stdClass $document) {
        if (is_object($document)) {
            if (!empty($document->links->search)) {
                $this->readOnlyLinks->search = $document->links->search;
            }
            if (!empty($document->links->{"edit-form"})) {
                $this->readOnlyLinks->{"edit-form"} = $document->links->{"edit-form"};
            }
        }
        return $this;
    }

    /**
     * Clears the current document from the object
     * @return CollectionDocJson
     */
    public function clearDocument() {
        unset($this->version);
        unset($this->data);
        unset($this->links);
        unset($this->items);
        unset($this->error);

        return $this;
    }

    /**
     * Builds the current document from the writeable document properties of the object
     * @return \stdClass
     */
    public function buildDocument() {
        $document = new \stdClass();
        $document->version = (!empty($this->version)) ? $this->version : null;
        $document->data = (!empty($this->data)) ? $this->data : null;
        $document->links = (!empty($this->links)) ? $this->links : null;

        return $document;
    }

    /**
     * Sets the given document on the object
     * @param \stdClass $document
     * @return CollectionDocJson
     */
    public function setDocument(\stdClass $document) {
        $this->clearDocument();

        if (is_object($document)) {
            $properties = get_object_vars($document);
        } else {
            $properties = array();
        }

        foreach($properties as $name => $value) {
            $this->$name = $value;
        }

        return $this;
    }

    /**
     * Build the URI for saving the document
     * @return string
     */
    public function getSaveUri() {
        // Make sure there is a guid to save to
        if (empty($this->data->guid)) {
            $this->data->guid = $this->createGuid();
        }

        // Make sure there is an edit-form link to save to
        $editFormLinks = $this->links("edit-form");
        if (!empty($editFormLinks[0])) {
            if (!empty($this->data->guid)) {
                return $editFormLinks[0]->href . '/' . $this->data->guid;
            }
        }

        return '';
    }

    /**
     * Build the URI for retrieving guids
     * @return string
     * @todo needs to generate guids URI for correct domain
     */
    public function getGuidsUri() {
        return 'http://stage.pmp.io/guids';
    }
}