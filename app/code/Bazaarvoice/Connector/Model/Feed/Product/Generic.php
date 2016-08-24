<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @package   Bazaarvoice_Connector
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 */

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Magento\Framework\ObjectManagerInterface;

class Generic
{

    protected $logger;
    protected $helper;
    protected $objectManager;

    /**
     * Generic constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(Logger $logger, Data $helper, ObjectManagerInterface $objectManager)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->objectManager = $objectManager;
    }

    /**
     * Get custom configured attributes
     * @param string $type
     * @return string
     */
    public function getAttributeCode($type)
    {
        return $this->helper->getConfig('feeds/' . $type . '_code');
    }

    /**
     * @param $storeIds
     * @return array
     */
    protected function getLocales($storeIds)
    {
        $locales = array();
        foreach ($storeIds as $storeId) {
            $localeCode = $this->helper->getConfig('general/locale', $storeId);
            $locales[$localeCode] = $storeId;
        }
        return $locales;
    }

    /**
     * Get the uniquely identifying category ID for a catalog category.
     *
     * This is the unique, category or subcategory ID (duplicates are unacceptable).
     * This ID should be stable: it should not change for the same logical category even
     * if the category's name changes.
     *
     * @param  \Magento\Catalog\Model\Category $category a reference to a catalog category object
     * @param int $storeId
     * @return string The unique category ID to be used with Bazaarvoice
     */
    protected function getCategoryId($category, $storeId = null)
    {
        if($this->helper->getConfig('feeds/category_id_use_url_path', $storeId) == false) {
            return $category->getId();
        }
        else {
            $rawCategoryId = $category->getUrlPath();

            $rawCategoryId = str_replace('/', '-', $rawCategoryId);
            return $this->helper->replaceIllegalCharacters($rawCategoryId);
        }
    }

}