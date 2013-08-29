<?php
namespace Pmp;

require_once('CollectionDocJsonLink.php');

class CollectionDocJsonLinks implements \ArrayAccess
{
    public function __construct(array $links, $parent) {
        $this->_document = $parent;

        $this->links = array();
        foreach($links as $link) {
            $this->links[] = new CollectionDocJsonLink($link, $this);
        }
    }

    /**
     * Gets the set of links that are associated with this object
     * @return array
     */
    public function getLinks() {
        return $this->links;
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
            if (!empty($link->rels)) {
                $result = array_diff($urns, $link->rels);
                if (count($result) !== $count) {
                    $links[] = $link;
                }
            }
        }

        return $links;
    }

    public function offsetExists($offset) {
        return isset($this->links[$offset]);
    }

    public function offsetGet($offset) {
        return $this->links[$offset];
    }

    public function offsetSet($offset , $value) {
        $this->links[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->links[$offset]);
    }
}