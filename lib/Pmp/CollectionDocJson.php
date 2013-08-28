<?php
namespace Pmp;

require_once(dirname(__FILE__).'/../restagent/restagent.lib.php');
use restagent\Request as Request;

class CollectionDocJson
{
    public function __construct($url, $accessToken) {
        $this->url = $url;
        $this->accessToken = $accessToken;

        $document = $this->getDocument($url, $accessToken);
        $this->version = (!empty($document->version)) ? $document->version : null;
        $this->data = (!empty($document->data)) ? $document->data : null;
        $this->links = (!empty($document->links)) ? $document->links : null;
        $this->items = (!empty($document->items)) ? $document->items : null;
        $this->error = (!empty($document->error)) ? $document->error : null;
    }

    /**
     * Gets the set of links from the document that are associated with the given link relation
     * @param string $relType
     *     link relation of the set of links to get from the document
     * @return CollectionDocJsonLinks
     */
    public function links($relType) {
        $links = (!empty($this->links->$relType)) ? $this->links->$relType : array();
        return new CollectionDocJsonLinks($links);
    }

    /**
     * Saves the current document
     * @return CollectionDocJson
     */
    public function save() {
        $this->putDocument($this->url, $this->accessToken);
    }

    /**
     * Gets the set of items from the document
     * @return CollectionDocJsonItems
     */
    public function items() {
        $items = (!empty($this->items)) ? $this->items : array();
        return new CollectionDocJsonItems($items);
    }

    /**
     * Gets a specific "search" relation link with the given URN
     * @param string $urn
     * @return CollectionDocJsonLink
     */
    public function search($urn) {
        $searchLinks = $this->links('search');
        $urnSearchLinks = $searchLinks->rels(array($urn));
        return $urnSearchLinks[0];
    }

    /**
     * Does a GET operation on the given URL and returns a JSON object
     * @param $url
     * @param $accessToken
     * @return stdClass
     */
    private function getDocument($url, $accessToken) {
        $request = new Request();
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->get($url);

        $document = json_decode($response['data']);
        return $document;
    }

    /**
     * Does a PUT operation on the given URL using the internal JSON objects
     * @param $url
     * @param $accessToken
     */
    private function putDocument($url, $accessToken) {
        $document = new \stdClass();
        $document->version = $this->version;
        $document->data = $this->data;
        $document->links = $this->links;

        $request = new Request();
        $response = $request->header('Content-Type', 'application/json')
            ->header('Authorization', 'Bearer ' . $accessToken)
            ->body(json_encode($document))
            ->put($url);
    }
}