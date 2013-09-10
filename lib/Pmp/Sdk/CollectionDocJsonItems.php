<?php
namespace Pmp\Sdk;

require_once('PageIterator.php');

class CollectionDocJsonItems
{
    /**
     * @param array $items
     *    the raw items array
     * @param CollectionDocJson $parent
     *    the document object that contains this items object
     */
    public function __construct(array $items, $parent) {
        $this->_document = $parent;
        $this->items = $items;
    }

    /**
     * Total number of pages
     * @return int
     */
    public function numPages() {
        $links = $this->_document->links('self');
        return $links[0]->totalpages;
    }

    /**
     * Gets the page iterator
     * @return PageIterator
     */
    public function getIterator() {
        return new PageIterator($this);
    }
}