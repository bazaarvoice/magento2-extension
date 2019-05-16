<?php

namespace Bazaarvoice\Connector\Api;

/**
 * Interface StringFormatterInterface
 *
 * @package Bazaarvoice\Connector\Api
 */
interface StringFormatterInterface
{
    /**
     * This unique ID can only contain alphanumeric characters (letters and numbers
     * only) and also the asterisk, hyphen, and underscore characters. If your
     * product IDs contain invalid characters, simply replace them with an alternate
     * character like an underscore. This will only be used in the feed and not for
     * any customer facing purpose.
     *
     * @static
     *
     * @param string $rawId
     *
     * @return mixed
     */
    public function replaceIllegalCharacters($rawId);

    /**
     * Get the uniquely identifying category ID for a catalog category.
     *
     * This is the unique, category or subcategory ID (duplicates are unacceptable).
     * This ID should be stable: it should not change for the same logical category even
     * if the category's name changes.
     *
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     * @param int                                                                         $storeId
     *
     * @return string The unique category ID to be used with Bazaarvoice
     */
    public function getFormattedCategoryId($category, $storeId = null);

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     *
     * @return mixed
     */
    public function getFormattedCategoryPath($category);

    /**
     * Get the uniquely identifying product ID for a catalog product.
     *
     * This is the unique, product family-level id (duplicates are unacceptable).
     * If a product has its own page, this is its product ID. It is not necessarily
     * the SKU ID, as we do not collect separate Ratings & Reviews for different
     * styles of product - i.e. the 'Blue' vs. 'Red Widget'.
     *
     * @param mixed $product a reference to a catalog product object
     *
     * @return string The unique product ID to be used with Bazaarvoice
     */
    public function getFormattedProductSku($product);

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function jsonEncode($value);

    /**
     * json decode, sends original data if error
     *
     * @param $value
     *
     * @return mixed
     */
    public function jsonDecode($value);

    /**
     * @param string $delimeter
     * @param string $string
     *
     * @return array
     */
    public function explodeAndTrim($delimeter, $string);

    /**
     * @param array $data
     *
     * @return array
     */
    public function stripEmptyValues($data);
}
