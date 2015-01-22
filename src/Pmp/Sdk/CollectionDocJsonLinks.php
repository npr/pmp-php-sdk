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

    /**
     * Constructor
     *
     * @param array(stdClass) $links the raw links
     * @param AuthClient $auth authentication client for the API
     */
    public function __construct(array $links, AuthClient $auth = null) {
        $linkObjects = array();

        // init links
        foreach($links as $link) {
            $linkObjects[] = new CollectionDocJsonLink($link, $auth);
        }

        // impersonate array
        parent::__construct($linkObjects);
    }

    /**
     * Get the set of links matching an array of urns
     *
     * @param array $urn the names to match on
     * @return array the matched links
     */
    public function rels(array $urns) {
        $links = array();
        foreach ($this as $link) {
            if (!empty($link->rels)) {
                $match = array_diff($urns, $link->rels);
                if (count($match) != count($urns)) {
                    $links[] = $link;
                }
            }
        }
        return $links;
    }

    /**
     * Gets the first link matching an urn
     *
     * @param string $urn the name to match on
     * @return CollectionDocJsonLink the matched link or null
     */
    public function rel($urn) {
        $match = $this->rels(array($urn));
        return empty($match) ? null : $match[0];
    }

}
