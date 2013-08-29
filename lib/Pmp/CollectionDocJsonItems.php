<?php
namespace Pmp;

require_once('PageIterator.php');

class CollectionDocJsonItems
{
    public function __construct(array $items, $parent) {
        $this->_document = $parent;
        $this->items = $items;
    }

    /**
     * Total number of pages
     * @return int
     */
    public function numPages() {

    }

    /**
     * Gets the page iterator
     * @return PageIterator
     */
    public function getIterator() {
        return new PageIterator($this);
    }
}