<?php

namespace Pmp\Sdk;

/**
 * Iterator for items attached to a Collection.Doc+JSON doc
 */
class PageIterator implements \Iterator
{
    /**
     * The first page configured for the iterator
     *
     * @var CollectionDocJson
     */
    private $_initialDoc;

    /**
     * The last page number configured for the iterator
     *
     * @var int
     */
    private $_lastPageNumber;

    /**
     * The page currently loaded in the iterator
     *
     * @var CollectionDocJson
     */
    private $_currentPageDoc;

    /**
     * Constructor
     *
     * @param CollectionDocJson $doc the parent doc to iterate over
     * @param int $pageLimit the maximum number of pages to load
     */
    public function __construct(CollectionDocJson $doc, $pageLimit = null)
    {
        $this->_initialDoc = $doc;
        $this->_currentPageDoc = $doc;

        // stop loading at the initial-doc-pagenum + limit
        if ($pageLimit) {
            $this->_lastPageNumber = $this->key() + ($pageLimit - 1);
        }
    }

    /**
     * Go back to the first page (already loaded)
     */
    public function rewind()
    {
        $this->_currentPageDoc = $this->_initialDoc;
    }

    /**
     * Get the current page
     *
     * @return CollectionDocJsonItems the current items
     */
    public function current()
    {
        return $this->_currentPageDoc->items();
    }

    /**
     * Get the current page number
     *
     * @return int the page number
     */
    public function key()
    {
        return $this->_currentPageDoc->items()->pageNum();
    }

    /**
     * Go to the next page
     */
    public function next()
    {
        $link = $this->_currentPageDoc->navigation('next');
        if ($link && isset($link->pagenum) && $link->pagenum <= $this->_lastPageNumber) {
            $this->_currentPageDoc = $link->follow();
        } else {
            $this->_currentPageDoc = null;
        }
    }

    /**
     * Determine whether current page exists
     *
     * @return bool whether the current page exists
     */
    public function valid()
    {
        return !empty($this->_currentPageDoc);
    }
}
