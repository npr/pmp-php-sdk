<?php
namespace Pmp;

class CollectionDocJson
{
    public function __construct($url, $accessToken) {
        $this->url = $url;
        $this->accessToken = $accessToken;
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
}