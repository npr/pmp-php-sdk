<?php
namespace Pmp;

interface Doc
{
    /**
     * Gets the set of links from the document that are associated with the given link relation
     * @param $linkRelationType
     *     link relation of the set of links to get from the document
     * @return DocLinks
     */
    public function links($linkRelationType);

    /**
     * Saves the current document
     * @return Doc
     */
    public function save();

    /**
     * Gets the set of items from the document
     * @return DocItems
     */
    public function items();
}