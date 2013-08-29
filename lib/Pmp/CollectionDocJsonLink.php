<?php
namespace Pmp;

class CollectionDocJsonLink
{
    public function __construct($link) {
        $properties = get_object_vars($link);
        foreach($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Follows the link href to retrieve a document
     * @return CollectionDocJson
     */
    public function follow(){

    }

    /**
     * Follows the link href-template to retrieve a document
     * @param array $options
     * @return CollectionDocJson
     */
    public function submit(array $options) {

    }
}