<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;

/**
 * Class StringFormatter
 *
 * @package Bazaarvoice\Connector\Model
 */
class StringFormatter implements StringFormatterInterface
{
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * StringFormatter constructor.
     *
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(ConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * This unique ID can only contain alphanumeric characters (letters and numbers
     * only) and also the asterisk, hyphen, and underscore characters. If your
     * product IDs contain invalid characters, simply replace them with an alternate
     * character like an underscore. This will only be used in the feed and not for
     * any customer facing purpose.
     *
     * @param string $rawId
     *
     * @return mixed
     */
    public function replaceIllegalCharacters($rawId)
    {
        /** Customizations go here */
        $rawProductId = preg_replace_callback('/\./s', function ($match) {
            return "_bv".ord($match[0])."_";
        }, $rawId);
        /** No further customizations after this */

        /**
         * We need to use a reversible replacement so that we can reconstruct the original ID later.
         * Example rawId = qwerty$%@#asdf
         * Example encoded = qwerty_bv36__bv37__bv64__bv35_asdf
         */
        return preg_replace_callback('/[^\w\d\*\-_]/s', function ($match) {
            return "_bv".ord($match[0])."_";
        }, $rawProductId);
    }

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
    public function getFormattedCategoryId($category, $storeId = null)
    {
        if ($this->configProvider->isCategoryIdUseUrlPathEnabled($storeId)) {
            $rawCategoryId = $category->getUrlPath();
            $rawCategoryId = str_replace('/', '-', $rawCategoryId);
            $rawCategoryId = str_replace('.html', '', $rawCategoryId);
            return $this->replaceIllegalCharacters($rawCategoryId);
        } else {
            return $this->configProvider->getCategoryPrefix($storeId) . $category->getId();
        }
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface|\Magento\Catalog\Model\Category $category
     *
     * @return mixed
     */
    public function getFormattedCategoryPath($category)
    {
        return str_replace('/', '-', $category->getPath());
    }

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
    public function getFormattedProductSku($product)
    {
        if (is_object($product)) {
            $rawProductId = $product->getSku();
        } else {
            $rawProductId = $product;
        }

        return $this->replaceIllegalCharacters($rawProductId);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function jsonEncode($value)
    {
        return json_encode($value, $options = JSON_UNESCAPED_UNICODE);
    }

    /**
     * json decode, sends original data if error
     *
     * @param $value
     *
     * @return mixed
     */
    public function jsonDecode($value)
    {
        $result = json_decode($value, $assoc = true);
        if (json_last_error() != JSON_ERROR_NONE) {
            return $value;
        }

        return $result;
    }

    /**
     * @param string $delimeter
     * @param string $string
     *
     * @return array
     */
    public function explodeAndTrim($delimeter, $string)
    {
        $data = explode($delimeter, $string);
        foreach ($data as $k => $v) {
            $data[$k] = trim($v);
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function stripEmptyValues($data)
    {
        return array_filter($data, function ($a) {
            return !empty($a) || $a === false; //send false values
        });
    }
}
