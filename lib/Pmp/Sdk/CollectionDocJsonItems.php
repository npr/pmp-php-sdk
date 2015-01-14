<?php
namespace Pmp\Sdk;

require_once('PageIterator.php');

class CollectionDocJsonItems implements \ArrayAccess
{
    public $_document;
    private $_items;

    /**
     * @param array $items
     *    the raw items array
     * @param CollectionDocJson $document
     *    the document object that contains this items object
     */
    public function __construct(array $items, CollectionDocJson $document, AuthClient $auth = null) {
        $this->_document = $document;
        $this->_items = array();
        foreach ($items as $item) {
            $item_doc = new CollectionDocJson($item, $auth);
            array_push($this->_items, $item_doc);
        }
    }

    /**
     * Number of items in this page
     * @return int
     */
    public function count() {
        return count($this->_items);
    }

    /**
     * Total number of items
     * @return int
     */
    public function total() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->totalitems)) ? $link->totalitems : null;
    }

    /**
     * Total number of pages
     * @return int
     */
    public function numPages() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->totalpages)) ? $link->totalpages : null;
    }

    /**
     * Current page number
     * @return int
     */
    public function pageNum() {
        $link = $this->_document->navigation('self');
        return ($link && isset($link->pagenum)) ? $link->pagenum : null;
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
        return $this->_items;
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->_items[$offset]);
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->_items[$offset];
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset , $value) {
        $this->_items[$offset] = $value;
    }

    /**
     * Required by the ArrayAccess interface
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->_items[$offset]);
    }
}
