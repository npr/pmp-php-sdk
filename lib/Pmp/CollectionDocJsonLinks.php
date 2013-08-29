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
        $count = count($urns);
        $links = array();

        foreach($this->links as $link) {

            // array_diff gives elements of $urns that are not present in $link->rels,
            // so if the result is not the same length as $urns, then we have a match
            $result = array_diff($urns, $link->rels);
            if (count($result) !== $count) {
                $links[] = $link;
            }
        }

        return $links;
    }
}