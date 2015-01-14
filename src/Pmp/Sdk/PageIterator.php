<?php
namespace Pmp\Sdk;

/**
 * CollectionDoc Page Iterator
 *
 * An iterator for ALL the items attached to a doc
 *
 */
class PageIterator implements \Iterator
{
    private $_initialDoc;
    private $_lastPageNumber;
    private $_currentPageDoc;

    /**
     * Constructor
     *
     * @param CollectionDocJson $doc the parent document to iterate over
     * @param int $pageLimit the maximum number of pages to load
     */
    public function __construct(CollectionDocJson $doc, $pageLimit = null) {
        $this->_initialDoc = $doc;
        $this->_currentPageDoc = $doc;

        // stop loading at the initial-doc-pagenum + limit
        if ($pageLimit) {
            $this->_lastPageNumber = $this->key() + ($pageLimit - 1);
        }
    }

    /**
     * Back to the first page (already loaded)
     */
    function rewind() {
        $this->_currentPageDoc = $this->_initialDoc;
    }

    /**
     * Get the current page
     *
     * @return CollectionDocJsonItems the current items
     */
    function current() {
        return $this->_currentPageDoc->items();
    }

    /**
     * Get the current page number
     *
     * @return int the page number
     */
    function key() {
        return $this->_currentPageDoc->items()->pageNum();
    }

    /**
     * Move forward a page
     */
    function next() {
        $link = $this->_currentPageDoc->navigation('next');
        if ($link && isset($link->pagenum) && $link->pagenum <= $this->_lastPageNumber) {
            $this->_currentPageDoc = $link->follow();
        }
        else {
            $this->_currentPageDoc = null;
        }
    }

    /**
     * Is the current page valid?
     *
     * @return bool whether the current page exists
     */
    function valid() {
        return $this->_currentPageDoc ? true : false;
    }

}
