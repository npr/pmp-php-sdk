<?php
namespace Pmp;

require_once('CollectionDocJsonLinks.php');
require_once(dirname(__FILE__).'/../restagent/restagent.lib.php');
use restagent\Request as Request;

class CollectionDocJson
{
    public function __construct($url, $accessToken) {
        $this->url = $url;
        $this->accessToken = $accessToken;

        $document = $this->getDocument($url, $accessToken);
        if (empty($document)) {
            return;
        }

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
        return new CollectionDocJsonLinks($links, $this);
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
        $items = array();
        if (!empty($this->items)) {
            $items = $this->items;
        }
        return new CollectionDocJsonItems($items, $this);
    }

    /**
     * Gets a specific "search" relation link with the given URN
     * @param string $urn
     * @return CollectionDocJsonLink
     */
    public function search($urn) {
        $urnSearchLink = null;
        $searchLinks = $this->links('search');
        if (!empty($searchLinks)) {
            $urnSearchLinks = $searchLinks->rels(array($urn));
            if (!empty($urnSearchLinks[0])) {
                $urnSearchLink = $urnSearchLinks[0];
            }
        }
        return ($urnSearchLink) ? $urnSearchLink : new CollectionDocJsonLink(null, $searchLinks);
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
        if ($response['code'] != 200 || empty($response['data'])) {
            return null;
        }
        $document = json_decode($response['data']);
        return $document;
    }

    /**
     * Does a PUT operation on the given URL using the internal JSON objects
     * @param $url
     * @param $accessToken
     * @return bool
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

        if ($response['code'] != 202) {
            return false;
        } else {
            return true;
        }
    }
}