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
 
use Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use \Magento\Framework\Exception;
use Magento\Store\Model\Website;

class ProductFeed extends Feed
{
    
    const INCLUDE_IN_FEED_FLAG = 'bv_feed_exclude';

    protected $type_id = 'product';

    /**
     * @param Store $store
     */
    public function exportFeedForStore(Store $store)
    {
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $productFeedFileName =
            $productFeedFilePath . '/productFeed-store-' . $store->getId() . /* '-' . date('U') . */ '.xml';
        $this->logger->info('Creating file ' . $productFeedFileName);
        
        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store->getId());

        // Create varien io object and write local feed file
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.2', $clientName);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForStore($writer, $store);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForStore($writer, $store);
        //$productModel->setCategoryIdList($categoryModel->getCategoryIdList());
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForStore($writer, $store);

        $this->closeFile($writer, $productFeedFileName);

        // Upload feed
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store->getId()) . '/' .
            $this->helper->getConfig('feeds/product_filename', $store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }

    /**
     * @param Group $storeGroup
     */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
        $store = $storeGroup->getDefaultStore();
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $productFeedFileName =
            $productFeedFilePath . '/productFeed-group-' . $storeGroup->getId() . /* '-' . date('U') . */ '.xml';
        $this->logger->info('Creating file ' . $productFeedFileName);

        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store);

        // Create varien io object and write local feed file
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.2', $clientName);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForStoreGroup($writer, $storeGroup);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForStoreGroup($writer, $storeGroup);
        //$productModel->setCategoryIdList($categoryModel->getCategoryIdList());
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForStoreGroup($writer, $storeGroup);

        $this->closeFile($writer, $productFeedFileName);

        // Upload feed
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store->getId()) . '/' .
            $this->helper->getConfig('feeds/product_filename', $store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }

    /**
     * @param Website $website
     */
    public function exportFeedForWebsite(Website $website)
    {
        $store = $website->getDefaultStore();
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $productFeedFileName =
            $productFeedFilePath . '/productFeed-website-' . $website->getId() . /* '-' . date('U') . */ '.xml';
        $this->logger->info('Creating file ' . $productFeedFileName);

        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store);

        // Create varien io object and write local feed file
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.2', $clientName);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForWebsite($writer, $website);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForWebsite($writer, $website);
        //$productModel->setCategoryIdList($categoryModel->getCategoryIdList());
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForWebsite($writer, $website);

        $this->closeFile($writer, $productFeedFileName);

        // Upload feed
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store) . '/' .
            $this->helper->getConfig('feeds/product_filename', $store);
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }

    /**
     */
    public function exportFeedForGlobal()
    {
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $productFeedFileName =
            $productFeedFilePath . '/productFeed-' . /* date('U') . */ '.xml';
        $this->logger->info('Creating file ' . $productFeedFileName);

        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name');

        // Create varien io object and write local feed file
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.2', $clientName);

        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForGlobal($writer);
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForGlobal($writer);
        //$productModel->setCategoryIdList($categoryModel->getCategoryIdList());
        $this->objectManager->get('\Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForGlobal($writer);

        $this->closeFile($writer, $productFeedFileName);

        // Using admin store for now
        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore(0);

        // Upload feed
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store) . '/' .
            $this->helper->getConfig('feeds/product_filename', $store);
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }

    /**
     * @param $storeIds
     * @return array
     */
    protected function getLocales($storeIds)
    {
        $locales = array();
        foreach($storeIds as $storeId) {
            $localeCode = $this->helper->getConfig('general/locale', $storeId);
            $locales[$localeCode] = $storeId;
        }
        return $locales;
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

}



