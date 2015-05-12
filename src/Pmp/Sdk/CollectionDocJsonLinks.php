<?php
namespace Pmp\Sdk;

/**
 * PMP CollectionDoc links
 *
 * An array-ish list of CollectionDoc links
 *
 */
class CollectionDocJsonLinks extends \ArrayObject
{
    private $_links;
    private $_auth;

    /**
     * Constructor
     *
     * @param array(stdClass) $links the raw links
     * @param AuthClient $auth authentication client for the API
     */
    public function __construct(array $links, AuthClient $auth = null) {
        $this->_links = $links;
        $this->_auth = $auth;

        // init links
        $linkObjects = array();
        foreach($links as $link) {
            $linkObjects[] = new CollectionDocJsonLink($link, $auth);
        }

        // impersonate array
        parent::__construct($linkObjects);
    }

    /**
     * Print as a string
     *
     * @return string the string form of these links
     */
    public function __toString() {
        return 'CollectionDocJsonLinks[' . implode(',', iterator_to_array($this)) .']';
    }

    /**
     * Get the set of links matching an array of urns
     *
     * @param array $urn the names to match on
     * @return CollectionDocJsonLinks the matched links
     */
    public function rels(array $urns) {
        $rawLinks = array();
        foreach ($this as $idx => $link) {
            if (!empty($link->rels)) {
                $match = array_diff($urns, $link->rels);
                if (count($match) != count($urns)) {
                    $rawLinks[] = $this->_links[$idx];
                }
            }
        }
        return new CollectionDocJsonLinks($rawLinks, $this->_auth);
    }

    /**
     * Gets the first link matching an urn
     *
     * @param string $urn the name to match on
     * @return CollectionDocJsonLink the matched link or null
     */
    public function rel($urn) {
        $match = $this->rels(array($urn));
        return count($match) > 0 ? $match[0] : null;
    }

    /**
     * Get the first link in this collection
     *
     * @return CollectionDocJsonLink the first link or null
     */
    public function first() {
        return count($this) > 0 ? $this[0] : null;
    }

}
