<?php
namespace Pmp\Sdk;

class CollectionDocJsonItems extends \ArrayObject
{
    public $_document;

    /**
     * @param array $items
     *    the raw items array
     * @param CollectionDocJson $document
     *    the document object that contains this items object
     */
    public function __construct(array $items, CollectionDocJson $document) {
        $this->_document = $document;

        $itemDocs = array();
        foreach ($items as $item) {
            $itemDoc = clone $document;
            $itemDoc->setDocument($item);
            $itemDocs[] = $itemDoc;
        }

        parent::__construct($items);
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
    public function getPageIterator() {
        return new PageIterator($this);
    }
}