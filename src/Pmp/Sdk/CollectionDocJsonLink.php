<?php

namespace Pmp\Sdk;

use GuzzleHttp\UriTemplate;
use Pmp\Sdk\Exception\LinkException;
use Pmp\Sdk\Exception\NotFoundException;

/**
 * Collection.Doc+JSON link
 */
class CollectionDocJsonLink
{
    // Query string operators
    const PMP_AND = ',';
    const PMP_OR = ';';

    /**
     * @var string
     */
    public $href;

    /**
     * Link object
     *
     * @var \StdClass
     */
    private $_link;

    /**
     * Auth client
     *
     * @var AuthClient
     */
    private $_auth;

    /**
     * Constructor
     *
     * @param \StdClass $link the raw link data
     * @param AuthClient $auth authentication client for the API
     */
    public function __construct(\StdClass $link, AuthClient $auth = null)
    {
        $this->_link = $link;
        $this->_auth = $auth;

        // set properties
        $props = get_object_vars($link);
        foreach ($props as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Custom string representation
     *
     * @return string
     */
    public function __toString()
    {
        if (!empty($this->href)) {
            return $this->href;
        } else if (!empty($this->{'href-template'})) {
            return $this->{'href-template'};
        } else {
            return '';
        }
    }

    /**
     * Resolve this link's URL from its href or href-template
     *
     * @param array $options array of href-template params
     * @return string the resolved URL
     */
    public function expand(array $options = [])
    {
        if (!empty($this->href)) {
            return $this->href;
        } else if (!empty($this->{'href-template'})) {
            $parser = new UriTemplate();
            return $parser->expand($this->{'href-template'}, $this->_convertOptions($options));
        } else {
            throw new LinkException('Cannot expand link because no href or href-template defined');
        }
    }

    /**
     * Follow the link to retrieve a doc
     *
     * @param array $options array of href-template params
     * @return CollectionDocJson a loaded doc
     */
    public function follow(array $options = [])
    {
        $url = $this->expand($options);
        try {
            return new CollectionDocJson($url, $this->_auth);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Submit the link with params to retrieve a doc
     *
     * @param array $options array of href-template params
     * @return CollectionDocJson a loaded doc
     */
    public function submit(array $options)
    {
        return $this->follow($options);
    }

    /**
     * Get the available options for the href-template if exists
     *
     * @return \StdClass options object
     */
    public function options()
    {
        if (empty($this->{'href-template'}) || empty($this->{'href-vars'})) {
            throw new LinkException('Cannot give link options because link is not a properly defined href template');
        } else {
            return $this->{'href-vars'};
        }
    }

    /**
     * Converts the set of options into a compatible query string structure
     *
     * Use to convert:
     *     array('profile' => array('AND' => array('foo', 'bar')))
     * into:
     *     array('profile' => 'foo,bar')
     *
     * @param array $options
     * @return array
     */
    private function _convertOptions(array $options = [])
    {
        $converted = [];
        if (!empty($options)) {
            foreach ($options as $name => $value) {
                if (is_array($value)) {
                    if (!empty($value['AND'])) {
                        $converted[$name] = implode(self::PMP_AND, $value['AND']);
                    } else if (!empty($value['OR'])) {
                        $converted[$name] = implode(self::PMP_OR, $value['OR']);
                    } else {
                        $converted[$name] = ''; // bad params
                    }
                } elseif (is_bool($value)) {
                    $converted[$name] = $value ? 'true' : 'false';
                } else {
                    $converted[$name] = $value;
                }
            }
        }
        return $converted;
    }
}
