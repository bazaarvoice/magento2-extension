<?php
namespace Bazaarvoice\Connector\Model\Feed;

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license 
 * of StoreFront Consulting, Inc.
 *
 * @copyright	(C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package		Bazaarvoice_Connector
 * @author		Dennis Rogers <dennis@storefrontconsulting.com>
 */
 
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use \Magento\Framework\Exception;
use Magento\Store\Model\Website;

class ProductFeed extends Feed
{
    
    const INCLUDE_IN_FEED_FLAG = 'bv_feed_exclude';
    const FEED_FILE_XSD = 'http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.6';

    protected $type_id = 'product';

    /**
     * @param Store $store
     */
    public function exportFeedForStore(Store $store)
    {
        $writer = $this->openProductFile($store);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForStore($writer, $store);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForStore($writer, $store);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForStore($writer, $store);

        $this->closeAndUploadFile($writer, $store->getId(), $store);
    }

    /**
     * @param Group $storeGroup
     */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
        $store = $storeGroup->getDefaultStore();
        // Create varien io object and write local feed file
        $writer = $this->openProductFile($store);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForStoreGroup($writer, $storeGroup);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForStoreGroup($writer, $storeGroup);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForStoreGroup($writer, $storeGroup);

        $this->closeAndUploadFile($writer, $storeGroup->getId(), $store);
    }

    /**
     * @param Website $website
     */
    public function exportFeedForWebsite(Website $website)
    {
        $store = $website->getDefaultStore();
        // Create varien io object and write local feed file
        $writer = $this->openProductFile($store);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForWebsite($writer, $website);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForWebsite($writer, $website);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForWebsite($writer, $website);

        $this->closeAndUploadFile($writer, $website->getId(), $store);
    }

    /**
     */
    public function exportFeedForGlobal()
    {
        // Using admin store for now
        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        /** @var Store $store */
        $store = $storeManager->getStore(0);

        // Create varien io object and write local feed file
        $writer = $this->openProductFile($store);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForGlobal($writer);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForGlobal($writer);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForGlobal($writer);


        $this->closeAndUploadFile($writer, 0, $store);
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
     * @static
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
     * @param $store
     * @return XMLWriter
     */
    protected function openProductFile($store)
    {

        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store->getId());

        // Create varien io object and write local feed file
        $writer = parent::openFile(self::FEED_FILE_XSD, $clientName);
        return $writer;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param String $scopeId ID of current scope, store website or group
     * @param Store $store Config store for destination paths
     */
    protected function closeAndUploadFile($writer, $scopeId, $store)
    {
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $scope = $this->helper->getConfig('feeds/generation_scope');
        $date = date('U');

        $productFeedFileName = "{$productFeedFilePath}/productFeed-{$scope}-{$scopeId}-{$date}.xml";
        $this->log("Creating file $productFeedFileName");

        parent::closeFile($writer, $productFeedFileName);

        // Upload feed
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store->getId()) . '/' .
            $this->helper->getConfig('feeds/product_filename', $store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }


}



