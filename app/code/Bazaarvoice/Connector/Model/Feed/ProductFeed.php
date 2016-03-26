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
 
use \Magento\Store\Model\Store;
use \Magento\Framework\Exception;


class ProductFeed extends Feed
{

    public function generateFeed()
    {
        $this->logger->info('Start Bazaarvoice Product Feed Generation');
        // TODO: Scopes
        $this->exportFeedByStore();  
        $this->logger->info('End Bazaarvoice Product Feed Generation');
    }

    public function exportFeedByStore()
    {
        $this->logger->info('Exporting product feed file for each store / store view');

        $stores = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        
        foreach ($stores as $store) {
            /* @var \Magento\Store\Model\Store $store */
            try {
                if ($this->helper->getConfig('feeds/enable_product_feed', $store->getId()) === '1'
                    && $this->helper->getConfig('general/enable_bv', $store->getId()) === '1'
                ) {
                    $this->logger->info('Exporting product feed for store: ' . $store->getCode());
                    $this->exportFeedForStore($store);
                }
                else {
                    $this->logger->info('Product feed disabled for store: ' . $store->getCode());
                }
            }
            catch (Exception $e) {
                $this->logger->error('Failed to export daily product feed for store: ' . $store->getCode());
                $this->logger->error('Error message: ' . $e->getMessage());
            }
        }
        
    }

    /**
     * @param Store $store
     */
    public function exportFeedForStore(Store $store)
    {
        // Build local file name / path
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $productFeedFileName =
            $productFeedFilePath . '/productFeed-store-' . $store->getId() . /* '-' . date('U') . */ '.xml';
        // Get client name for the scope
        $clientName = $this->helper->getConfig('general/client_name', $store->getId());

        // Create varien io object and write local feed file
        $writer = $this->openFile('http://www.bazaarvoice.com/xs/PRR/ProductFeed/5.2', $clientName);

        $this->objectManager->get('Bazaarvoice\Connector\Model\Feed\Product\Brand')
            ->processBrandsForStore($writer, $store);
        $this->objectManager->get('Bazaarvoice\Connector\Model\Feed\Product\Category')
            ->processCategoriesForStore($writer, $store);
        //$productModel->setCategoryIdList($categoryModel->getCategoryIdList());
        $this->objectManager->get('Bazaarvoice\Connector\Model\Feed\Product\Product')
            ->processProductsForStore($writer, $store);

        $this->closeFile($writer, $productFeedFileName);

        // Upload feed
        //$this->uploadFeed($productFeedFileName, $store);
    }

}



