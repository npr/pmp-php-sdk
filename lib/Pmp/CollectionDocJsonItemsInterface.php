<?php
namespace Pmp;

interface CollectionDocJsonItemsInterface extends Iterator
{
    /**
     * Total number of pages
     * @return int
     */
    public function numPages();

    /**
     * Ordinal of the current page
     * @return int
     */
    public function currentPage();

    /**
     * Array of items on the current page
     * @return array
     */
    public function currentPageItems();

    /**
     * Ordinal of the next page
     * @return int
     */
    public function nextPage();

    /**
     * Array of items on the next page
     * @return array
     */
    public function nextPageItems();

    /**
     * Ordinal of the previous page
     * @return int
     */
    public function prevPage();

    /**
     * Array of items on the previous page
     * @return array
     */
    public function prevPageItems();

    /**
     * Ordinal of the first page
     * @return int
     */
    public function firstPage();

    /**
     * Array of items on the first page
     * @return array
     */
    public function firstPageItems();

    /**
     * Ordinal of the last page
     * @return int
     */
    public function lastPage();

    /**
     * Array of items on the last page
     * @return array
     */
    public function lastPageItems();
}