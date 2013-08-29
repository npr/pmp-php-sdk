<?php
namespace Pmp;

require_once('CollectionDocJson.php');

class CollectionDocJsonLink
{
    public function __construct($link, $parent) {
        $this->_links = $parent;

        $properties = get_object_vars($link);
        foreach($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Follows the link href to retrieve a document
     * @return CollectionDocJson
     */
    public function follow() {
        $url = $this->href;
        $accessToken = $this->_links->_document->accessToken;
        $document = new CollectionDocJson($url, $accessToken);
        return $document;
    }

    /**
     * Follows the link href-template to retrieve a document
     * @param array $options
     * @return CollectionDocJson
     */
    public function submit(array $options) {
        $template = $this->{'href-template'};
        $accessToken = $this->_links->_document->accessToken;
        $document = new CollectionDocJson($template, $accessToken);
        return $document;
    }

    /**
     * Converts the given option set into API-compatible query string form
     * @param array $option
     * @return string
     */
    private function convertOption(array $option) {
        if (!empty($option['AND'])) {
            return implode(',', $option['AND']);
        } else if (!empty($option['OR'])) {
            return implode(';', $option['OR']);
        } else {
            return '';
        }
    }
}