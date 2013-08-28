<?php
namespace Pmp;

class CollectionDocJsonLinks
{

    public function __construct(array $links) {
        $this->links = $links;
    }

    /**
     * Gets the set of links that are associated with this object
     * @return array
     */
    public function getLinks() {

    }

    /**
     * Gets the set of links that are associated with the given rel URNs
     * @param array $urns
     * @return array
     */
    public function rels(array $urns) {

    }
}