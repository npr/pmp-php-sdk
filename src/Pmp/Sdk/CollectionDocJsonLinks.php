<?php

namespace Pmp\Sdk;

/**
 * Array of Collection.Doc+JSON links
 */
class CollectionDocJsonLinks extends \ArrayObject
{
    /**
     * Set of link objects
     *
     * @var \StdClass[]
     */
    private $_links;

    /**
     * Auth client
     *
     * @var AuthClient
     */
    private $_auth;

    /**
     * Constructor
     *
     * @param \StdClass[] $links the raw links
     * @param AuthClient $auth authentication client for the API
     */
    public function __construct(array $links, AuthClient $auth = null)
    {
        $this->_links = $links;
        $this->_auth = $auth;

        // init links
        $linkObjects = [];
        foreach ($links as $link) {
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
    public function __toString()
    {
        return 'CollectionDocJsonLinks[' . implode(',', iterator_to_array($this)) . ']';
    }

    /**
     * Get the set of links matching an array of URNs
     *
     * @param string[] $urns the names to match on
     * @return CollectionDocJsonLinks the matched links
     */
    public function rels(array $urns)
    {
        $rawLinks = [];
        foreach ($this as $i => $link) {
            if (!empty($link->rels)) {
                $match = array_diff($urns, $link->rels);
                if (count($match) != count($urns)) {
                    $rawLinks[] = $this->_links[$i];
                }
            }
        }
        return new CollectionDocJsonLinks($rawLinks, $this->_auth);
    }

    /**
     * Get the first link matching a URN
     *
     * @param string $urn the name to match on
     * @return CollectionDocJsonLink the matched link
     */
    public function rel($urn)
    {
        $match = $this->rels([$urn]);
        return count($match) > 0 ? $match[0] : null;
    }

    /**
     * Get the first link in the set
     *
     * @return CollectionDocJsonLink the first link
     */
    public function first()
    {
        return count($this) > 0 ? $this[0] : null;
    }
}
