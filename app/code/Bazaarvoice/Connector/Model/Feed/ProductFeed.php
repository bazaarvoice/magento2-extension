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



