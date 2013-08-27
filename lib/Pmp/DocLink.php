<?php
namespace Pmp;

interface DocLink
{
    /**
     * Follows the link href to retrieve a document
     * @param string $index
     *    if omitted, defaults to link[0]; otherwise, must be a valid index or guid of the link to follow
     * @return CollectionDocJsonInterface
     */
    public function follow($index);

    /**
     * Follows the link href-template to retrieve a document
     * @param string $index
     *    if omitted, defaults to link[0]; otherwise, must be a valid index or guid of the link to follow
     * @param
     * @return CollectionDocJsonInterface
     */
    public function submit($index, $arguments);
}