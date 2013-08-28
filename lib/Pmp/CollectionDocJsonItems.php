<?php
namespace Pmp;

class CollectionDocJsonItems
{
    public function __construct(array $items) {
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

    }
}