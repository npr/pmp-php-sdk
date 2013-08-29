<?php
namespace Pmp;

class PageIterator
{
    public function __construct($parent) {
        $this->_items = $parent;
    }

    /**
     * Ordinal of the current page
     * @return int
     */
    public function currentPageNo() {

    }

    /**
     * Array of items on the current page
     * @return array
     */
    public function current() {

    }

    /**
     * Determine if there is a next page
     * @return bool
     */
    public function hasNext() {

    }

    /**
     * Determine if there is a prev page
     * @return bool
     */
    public function hasPrev() {

    }

    /**
     * Array of items on the next page
     * @return array
     */
    public function next() {

    }

    /**
     * Array of items on the previous page
     * @return array
     */
    public function prev() {

    }

    /**
     * Array of items on the first page
     * @return array
     */
    public function first() {

    }

    /**
     * Array of items on the last page
     * @return array
     */
    public function last() {

    }
}