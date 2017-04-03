<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

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

    protected $_typeId = 'product';

    protected $_brand;
    protected $_category;
    protected $_product;

    /**
     * ProductFeed constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     * @param Brand $brand
     * @param Category $category
     * @param Product $product
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem\Io\SftpFactory $sftpFactory,
        Brand $brand,
        Category $category,
        Product $product
    ) {
        $this->_brand = $brand;
        $this->_category = $category;
        $this->_product = $product;

        parent::__construct($logger, $helper, $objectManager, $sftpFactory);
    }

    /**
     * @param Store $store
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
     *
     * @param string $type
     *
     * @return string
     */
    public function getAttributeCode($type)
    {
        return $this->helper->getConfig('feeds/' . $type . '_code');
    }

    /**
     * @param Store $store
     *
     * @return XMLWriter
     */
    protected function openProductFile($store)
    {

        /** Get client name for the scope */
        $clientName = $this->helper->getConfig('general/client_name', $store->getId());

        /** Create varien io object and write local feed file */
        $writer = parent::openFile(self::FEED_FILE_XSD, $clientName);

        return $writer;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param String                                 $scopeId ID of current scope, store website or group
     * @param Store                                  $store   Config store for destination paths
     */
    protected function closeAndUploadFile($writer, $scopeId, $store)
    {
        /** Build local file name / path */
        $productFeedFilePath = BP . '/var/export/bvfeeds';
        $scope = $this->helper->getConfig('feeds/generation_scope');
        $date = date('U');

        $productFeedFileName = "{$productFeedFilePath}/productFeed-{$scope}-{$scopeId}-{$date}.xml";
        $this->log("Creating file $productFeedFileName");

        parent::closeFile($writer, $productFeedFileName);

        /** Upload feed */
        $destinationFile = $this->helper->getConfig('feeds/product_path', $store->getId()) . '/' .
                           $this->helper->getConfig('feeds/product_filename', $store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }

}



