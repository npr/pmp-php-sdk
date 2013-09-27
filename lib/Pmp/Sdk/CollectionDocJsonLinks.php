<?php
namespace Pmp\Sdk;

require_once('CollectionDocJsonLink.php');

class CollectionDocJsonLinks implements \ArrayAccess
{
    private $links;

    /**
     * @param array $links
     *    the raw links array
     * @param AuthClient $auth
     *    authentication client for the API
     */
    public function __construct(array $links, $auth) {
        // Create link objects for each raw link
        $this->links = array();
        foreach($links as $link) {
            $this->links[] = new CollectionDocJsonLink($link, $auth);
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
     *    the URNs of the desired links
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

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->links[$offset]);
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->links[$offset];
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset , $value) {
        $this->links[$offset] = $value;
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->links[$offset]);
    }
}