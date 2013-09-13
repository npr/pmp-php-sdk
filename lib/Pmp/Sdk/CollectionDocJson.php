<?php
namespace Pmp\Sdk;

require_once('CollectionDocJsonLinks.php');
require_once(dirname(__FILE__) . '/../../restagent/restagent.lib.php');
use restagent\Request as Request;

class CollectionDocJson
{
    private $url;
    private $accessToken;

    /**
     * @param string $url
     *    URL for a Collection.doc+json document
     * @param string $accessToken
     *    access token retrieved from the authentication client
     */
    public function __construct($url, $accessToken) {
        $this->url = $url;
        $this->accessToken = $accessToken;

        // Retrieve the document from the given URL. Document is never empty. It will throw exception if it is empty.
        $document = $this->getDocument($url, $accessToken);

        // Map the document properties to this object's properties
        $properties = get_object_vars($document);
        foreach($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Gets the set of links from the document that are associated with the given link relation
     * @param string $relType
     *     link relation of the set of links to get from the document
     * @return CollectionDocJsonLinks
     */
    public function links($relType) {
        $links = array();
        if (!empty($this->links->$relType)) {
            $links = $this->links->$relType;
        }
        return new CollectionDocJsonLinks($links, $this->accessToken);
    }

    /**
     * Saves the current document
     * @return CollectionDocJson
     */
    public function save() {
        // Determine where to save the document
        $editFormLinks = $this->links("edit-form");
        if (!empty($editFormLinks[0])) {

            // Make sure there is a guid to save to
            if (empty($this->data->guid)) {
                $this->data->guid = $this->generateGuid();
            }

            // Build the URL for saving the document
            $saveUrl = $editFormLinks[0]->href . '/' . $this->data->guid;

            // Save the document
            $this->putDocument($saveUrl, $this->accessToken);
        }

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
     * Does a GET operation on the given URL and returns a JSON object
     * @param $url
     *    the URL to use in the request
     * @param $accessToken
     *    the access token to use in the request
     * @return stdClass
     */
    private function getDocument($url, $accessToken) {
        $request = new Request();

        // GET request needs an authorization header with given access token
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->get($url);

        // Response code must be 200 and data must be found in response in order to continue
        if ($response['code'] != 200 || empty($response['data'])) {
            $err = "Got unexpected non-HTTP-200 response and/or empty document
                    while retrieving \"$url\" with access Token: \"$accessToken\": \n " . print_r($response, true);
            throw new \Exception($err);
            return;
        }
        $document = json_decode($response['data']);
        return $document;
    }

    /**
     * Does a PUT operation on the given URL using the internal JSON objects
     * @param $url
     *    the URL to use in the request
     * @param $accessToken
     *    the access token to use in the request
     * @return bool
     */
    private function putDocument($url, $accessToken) {

        // Construct the document from the allowable properties in this object
        $document = new \stdClass();
        $document->version = (!empty($this->version)) ? $this->version : null;
        $document->data = (!empty($this->data)) ? $this->data : null;
        $document->links = (!empty($this->links)) ? $this->links : null;

        $request = new Request();

        // PUT request needs an authorization header with given access token and
        // the JSON-encoded body based on the document content
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->body(json_encode($document))
                            ->put($url);

        // Response code must be 202 in order to be successful
        if ($response['code'] != 202) {
            return false;
        }
        return true;
    }

    public function getAccessToken() {
        return $this->accessToken;
    }

    /**
     * Generates a guid using UUID v4 based on RFC 4122
     *
     * @see http://tools.ietf.org/html/rfc4122#section-4.4
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     *
     * @return string
     */
    public function generateGuid() {
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
}