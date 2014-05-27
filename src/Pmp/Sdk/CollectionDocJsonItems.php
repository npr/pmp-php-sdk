<?php
namespace Pmp\Sdk;

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
    public function __construct(array $items, CollectionDocJson $document) {
        $this->_document = $document;
        $this->_items = $items;
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