<?php

namespace Pmp\Sdk;

/**
 * Array of Collection.Doc+JSON item docs
 */
class CollectionDocJsonItems extends \ArrayObject
{
    /**
     * The parent doc
     *
     * @var CollectionDocJson
     */
    private $_doc;

    /**
     * Constructor
     *
     * @param \StdClass[] $items the raw items
     * @param CollectionDocJson $doc the parent doc
     */
    public function __construct(array $items, CollectionDocJson $doc)
    {
        $this->_doc = $doc;

        // init docs
        $itemDocs = [];
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
    public function totalItems()
    {
        $link = $this->_doc->navigation('self');
        return ($link && isset($link->totalitems)) ? $link->totalitems : 0;
    }

    /**
     * Total pages in the parent doc
     *
     * @return int the total number of pages
     */
    public function totalPages()
    {
        $link = $this->_doc->navigation('self');
        return ($link && isset($link->totalpages)) ? $link->totalpages : 1;
    }

    /**
     * Current page of these items in the parent doc
     *
     * @return int the page number
     */
    public function pageNum()
    {
        $link = $this->_doc->navigation('self');
        return ($link && isset($link->pagenum)) ? $link->pagenum : 1;
    }

    /**
     * Get the first item doc
     *
     * @return CollectionDocJson the first item
     */
    public function first()
    {
        return count($this) > 0 ? $this[0] : null;
    }
}
