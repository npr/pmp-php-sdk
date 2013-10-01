<?php
namespace Pmp\Sdk;

require_once('PageIterator.php');

class CollectionDocJsonItems
{
    public $_document;
    private $items;

    /**
     * @param array $items
     *    the raw items array
     * @param CollectionDocJson $document
     *    the document object that contains this items object
     */
    public function __construct(array $items, $document) {
        $this->_document = $document;
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

    /**
     * Return array of items
     */
    public function toArray() {
        return $this->items;
    }
}