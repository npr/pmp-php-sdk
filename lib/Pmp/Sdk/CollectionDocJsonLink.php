<?php
namespace Pmp\Sdk;

require_once('CollectionDocJson.php');

require_once(dirname(__FILE__) . '/../../guzzle.phar');

use Guzzle\Parser\UriTemplate\UriTemplate as UriTemplate;

class CollectionDocJsonLink
{
    private $_link;
    private $_auth;

    /**
     * @param \stdClass $link
     *    the raw link data
     * @param AuthClient $auth
     *    authentication client for the API
     *
     * @throws Exception
     */
    public function __construct(\stdClass $link, AuthClient $auth = null) {
        $this->_link = $link;
        $this->_auth = $auth;

        // Map the link properties to this object's properties
        if (is_object($link)) {
            $properties = get_object_vars($link);
        } else {
            $properties = array();
        }

        foreach($properties as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Follows the link href to retrieve a document
     * @return CollectionDocJson
     * @throws Exception
     */
    public function follow() {
        if (!empty($this->href)) {
            // Retrieve the document at the other end of this URL
            $document = new CollectionDocJson($this->href, $this->_auth);
            return $document;
        } else {
            $err = "Can't follow link because no href defined";
            $exception = new Exception($err);
            $exception->setDetails(array($this->_link));
            throw $exception;
        }
    }

    /**
     * Follows the link href-template to retrieve a document
     * @param array $options
     *    the mapping of template parameter values
     * @return CollectionDocJson
     * @throws Exception
     */
    public function submit(array $options) {
        if (!empty($this->{'href-template'})) {
            // Generate the URL from the template
            $parser = new UriTemplate();
            $url = $parser->expand($this->{'href-template'}, $this->convertOptions($options));

            // Retrieve the document at the other end of this constructed URL
            $document = new CollectionDocJson($url, $this->_auth);
            return $document;
        } else {
            $err = "Can't submit against link because no href-template defined";
            $exception = new Exception($err);
            $exception->setDetails(array($this->_link));
            throw $exception;
        }
    }

    /**
     * Return available options for a query type.
     * @return Object
     * @throws Exception
     */
    public function options() {
        if (!empty($this->{'href-template'}) && !empty($this->{'href-vars'})) {
            return $this->{'href-vars'};
        } else {
            $err = "Can't give link options because link is not a properly defined href template";
            $exception = new Exception($err);
            $exception->setDetails(array($this->_link));
            throw $exception;
        }
    }

    /**
     * Converts the given option set into API-compatible query string form
     * @param array $option
     *    the mapping of a template parameter to values
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

    /**
     * Converts the set of options into API-compatible query string forms
     * @param array $options
     * @return array
     */
    private function convertOptions(array $options) {
        $converted = array();
        foreach ($options as $name => $value) {
            if (is_array($value)) {
                $converted[$name] = $this->convertOption($value);
            } else {
                $converted[$name] = $value;
            }
        }
        return $converted;
    }
}
