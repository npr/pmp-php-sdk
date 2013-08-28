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

    }

    /**
     * Saves the current document
     * @return CollectionDocJson
     */
    public function save() {

    }

    /**
     * Gets the set of items from the document
     * @return CollectionDocJsonItems
     */
    public function items() {

    }

    /**
     * Gets a specific "search" relation link with the given URN
     * @param string $urn
     * @return CollectionDocJsonLink
     */
    public function search($urn) {

    }

    private function getDocument($url, $accessToken) {
        $request = new Request();
        $response = $request->header('Content-Type', 'application/json')
                            ->header('Authorization', 'Bearer ' . $accessToken)
                            ->get($url);

        $document = json_decode($response['data']);
        return $document;
    }
}