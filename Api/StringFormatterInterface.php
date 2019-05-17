<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Api;

/**
 * Interface StringFormatterInterface
 *
 * @package Bazaarvoice\Connector\Api
 */
interface StringFormatterInterface
{
    /**
     * @param string $rawId
     *
     * @return mixed
     */
    public function replaceIllegalCharacters($rawId);

    /**
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
