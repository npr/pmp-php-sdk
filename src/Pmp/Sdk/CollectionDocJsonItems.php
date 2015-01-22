<?php
namespace Pmp\Sdk;

/**
 * PMP CollectionDoc items
 *
 * An array-ish list of CollectionDoc items
 *
 */
class CollectionDocJsonItems extends \ArrayObject
{
    private $_document;

    /**
     * Constructor
     *
     * @param array(stdClass) $items the raw items
     * @param CollectionDocJson $doc the container document
     */
    public function __construct(array $items, CollectionDocJson $doc) {
        $this->_document = $doc;

        // init documents
        $itemDocs = array();
        foreach ($items as $item) {
            $itemDoc = clone $doc;
            $itemDoc->setDocument($item);
            $itemDocs[] = $itemDoc;
        }

        // impersonate array
        parent::__construct($itemDocs);
    }

    /**
     * Total items in the parent doc
     *
     * @return int the total
     */
    public function totalItems() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->totalitems)) ? $link->totalitems : 0;
    }

    /**
     * Total pages in the parent doc
     *
     * @return int the total number of pages
     */
    public function totalPages() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->totalpages)) ? $link->totalpages : 1;
    }

    /**
     * Current page of these items in the parent doc
     *
     * @return int the page number
     */
    public function pageNum() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->pagenum)) ? $link->pagenum : 1;
    }

}
