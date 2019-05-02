<?php

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\Product\Brand;
use Bazaarvoice\Connector\Model\Feed\Product\Category;
use Bazaarvoice\Connector\Model\Feed\Product\Product;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use Magento\Store\Model\Website;

class ProductFeed extends Feed
{
    
    const INCLUDE_IN_FEED_FLAG = 'bv_feed_exclude';
    const FEED_FILE_XSD = 'http://www.bazaarvoice.com/xs/PRR/ProductFeed/14.4';
    const CATEGORY_EXTERNAL_ID = 'bv_category_id';

    protected $_typeId = 'product';

    protected $_brand;
    protected $_category;
    protected $_product;

    /**
     * ProductFeed constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     * @param Brand $brand
     * @param Category $category
     * @param Product $product
     */
    public function __construct(Logger $logger, Data $helper, ObjectManagerInterface $objectManager, Brand $brand, Category $category, Product $product)
    {
        $this->_brand = $brand;
        $this->_category = $category;
        $this->_product = $product;

        parent::__construct($logger, $helper, $objectManager);
    }


	/**
	 * @param Store $store
	 *
	 * @throws \Exception
	 */
    public function exportFeedForStore(Store $store)
    {
        $writer = $this->openProductFile($store);

        $this->_brand
            ->processBrandsForStore($writer, $store);
        $this->_category
            ->processCategoriesForStore($writer, $store);
        $this->_product
            ->processProducts($writer, $store);

        $this->closeAndUploadFile($writer, $store->getId(), $store);
    }

	/**
	 * @param Group $storeGroup
	 *
	 * @throws \Exception
	 */
    public function exportFeedForStoreGroup(Group $storeGroup)
    {
        $store = $storeGroup->getDefaultStore();
        /** Create varien io object and write local feed file */
        $writer = $this->openProductFile($store);

        $this->_brand
            ->processBrandsForStoreGroup($writer, $storeGroup);
        $this->_category
            ->processCategoriesForStoreGroup($writer, $storeGroup);
        $this->_product
            ->processProducts($writer, $store);

        $this->closeAndUploadFile($writer, $storeGroup->getId(), $store);
    }

	/**
	 * @param Website $website
	 *
	 * @throws \Exception
	 */
    public function exportFeedForWebsite(Website $website)
    {
        $store = $website->getDefaultStore();
        /** Create varien io object and write local feed file */
        $writer = $this->openProductFile($store);

        $this->_brand
            ->processBrandsForWebsite($writer, $website);
        $this->_category
            ->processCategoriesForWebsite($writer, $website);
        $this->_product
            ->processProducts($writer, $store);

        $this->closeAndUploadFile($writer, $website->getId(), $store);
    }

	/**
	 * @throws \Exception
	 */
    public function exportFeedForGlobal()
    {
        /** Using admin store for now */
        /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        /** @var Store $store */
        $store = $storeManager->getStore(0);

        /** Create varien io object and write local feed file */
        $writer = $this->openProductFile($store);

        $this->_brand
            ->processBrandsForGlobal($writer);
        $this->_category
            ->processCategoriesForGlobal($writer);
        $this->_product
            ->processProducts($writer, $store);


        $this->closeAndUploadFile($writer, 0, $store);
    }

    /**
     * Get custom configured attributes
     * @param string $type
     * @return string
     */
    public function getAttributeCode($type)
    {
        return $this->_helper->getConfig( 'feeds/' . $type . '_code');
    }

    /**
     * @param Store $store
     * @return XMLWriter
     */
    protected function openProductFile($store)
    {

        /** Get client name for the scope */
        $clientName = $this->_helper->getConfig('general/client_name', $store->getId());

        /** Create varien io object and write local feed file */
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
        /** Build local file name / path */
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $scope = $this->_helper->getConfig('feeds/generation_scope');
        $date = date('U');

        $productFeedFileName = "{$productFeedFilePath}/productFeed-{$scope}-{$scopeId}-{$date}.xml";
        $this->_logger->debug("Creating file $productFeedFileName");

        parent::closeFile($writer, $productFeedFileName);

        /** Upload feed */
        $destinationFile = $this->_helper->getConfig('feeds/product_path', $store->getId()) . '/' .
                           $this->_helper->getConfig('feeds/product_filename', $store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }
}
