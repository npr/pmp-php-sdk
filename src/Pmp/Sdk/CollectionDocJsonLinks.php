<?php
namespace Pmp\Sdk;

class CollectionDocJsonLinks implements \ArrayAccess
{
    private $_links;

    /**
     * @param array $links
     *    the raw links array
     * @param AuthClient $auth
     *    authentication client for the API
     * @throws Exception
     */
    public function __construct(array $links, AuthClient $auth) {

        // Create link objects for each raw link
        $this->_links = array();
        foreach($links as $link) {
            $this->_links[] = new CollectionDocJsonLink($link, $auth);
        }
    }

    /**
     * Gets the set of links that are associated with this object
     * @return array
     */
    public function getLinks() {
        return $this->_links;
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

        foreach($this->_links as $link) {

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
     * Convenience function. Gets the set of relationship types for query links.
     * @return array
     */
    public function queryRelTypes() {
        $relTypes = array();

        foreach($this->_links as $link) {
            if (!empty($link->rels)) {
                // Most query links only have one rel. Good enough for now.
                // @TODO improve
                $relTypes[$link->rels[0]] = $link->title;
            }
        }

        return $relTypes;
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->_links[$offset]);
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->_links[$offset];
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset , $value) {
        $this->_links[$offset] = $value;
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->_links[$offset]);
    }
}