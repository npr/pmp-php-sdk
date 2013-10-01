<?php
namespace Pmp\Sdk;

require_once('CollectionDocJsonItems.php');

class PageIterator
{
    private $_items;
    private $_navigationLinks;

    /**
     * @param CollectionDocJsonItems $items
     *    the items object that contains this iterator
     */
    public function __construct($items) {
        $this->_items = $items;
        $this->_navigationLinks = $this->_items->_document->links('navigation');
    }

    /**
     * Ordinal of the current page
     * @return int
     */
    public function currentPageNum() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:self'));
        if (!empty($links[0])) {
            return $links[0]->pagenum;
        } else {
            return 0;
        }
    }

    /**
     * Items on the current page
     * @return CollectionDocJsonItems
     */
    public function current() {
        return $this->_items;
    }

    /**
     * Determine if there is a next page
     * @return bool
     */
    public function hasNext() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:next'));
        return (!empty($links[0]));
    }

    /**
     * Determine if there is a previous page
     * @return bool
     */
    public function hasPrevious() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:prev'));
        return (!empty($links[0]));
    }

    /**
     * Items on the next page
     * @return CollectionDocJsonItems
     */
    public function next() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:next'));
        if (!empty($links[0])) {
            return $links[0]->follow()->items();
        } else {
            return new CollectionDocJsonItems(array(), null);
        }
    }

    /**
     * Items on the previous page
     * @return CollectionDocJsonItems
     */
    public function previous() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:prev'));
        if (!empty($links[0])) {
            return $links[0]->follow()->items();
        } else {
            return new CollectionDocJsonItems(array(), null);
        }
    }

    /**
     * Items on the first page
     * @return CollectionDocJsonItems
     */
    public function first() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:first'));
        if (!empty($links[0])) {
            return $links[0]->follow()->items();
        } else {
            return new CollectionDocJsonItems(array(), null);
        }
    }

    /**
     * Items on the last page
     * @return CollectionDocJsonItems
     */
    public function last() {
        $links = $this->_navigationLinks->rels(array('urn:pmp:navigation:last'));
        if (!empty($links[0])) {
            return $links[0]->follow()->items();
        } else {
            return new CollectionDocJsonItems(array(), null);
        }
    }
}