<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\Product\Brand;
use Bazaarvoice\Connector\Model\Feed\Product\Category;
use Bazaarvoice\Connector\Model\Feed\Product\Product;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class ProductFeed
 *
 * @package Bazaarvoice\Connector\Model\Feed
 */
class ProductFeed extends Feed
{
    const INCLUDE_IN_FEED_FLAG = 'bv_feed_exclude';
    const FEED_FILE_XSD = 'http://www.bazaarvoice.com/xs/PRR/ProductFeed/14.7';
    const CATEGORY_EXTERNAL_ID = 'bv_category_id';

    /**
     * @var string
     */
    protected $typeId = 'product';
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\Product\Brand
     */
    private $brand;
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\Product\Category
     */
    private $category;
    /**
     * @var \Bazaarvoice\Connector\Model\Feed\Product\Product
     */
    private $product;

    /**
     * Constructor
     *
     * @param \Bazaarvoice\Connector\Logger\Logger               $logger
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param ConfigProviderInterface                            $configProvider
     * @param StringFormatterInterface                           $stringFormatter
     * @param \Bazaarvoice\Connector\Model\XMLWriter             $xmlWriter
     * @param \Magento\Framework\Filesystem\Io\File              $filesystem
     * @param \Bazaarvoice\Connector\Model\Feed\Product\Brand    $brand
     * @param \Bazaarvoice\Connector\Model\Feed\Product\Category $category
     * @param \Bazaarvoice\Connector\Model\Feed\Product\Product  $product
     */
    public function __construct(
        Logger $logger,
        StoreManagerInterface $storeManager,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        XMLWriter $xmlWriter,
        File $filesystem,
        Brand $brand,
        Category $category,
        Product $product
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->xmlWriter = $xmlWriter;
        $this->filesystem = $filesystem;
        $this->brand = $brand;
        $this->category = $category;
        $this->product = $product;
    }

    /**
     * @param Store $store
     *
     * @throws \Exception
     */
    public function exportFeedForStore(Store $store)
    {
        $writer = $this->openProductFile($store);

        $this->brand->processBrandsForStore($writer, $store);
        $this->category->processCategoriesForStore($writer, $store);
        $this->product->processProducts($writer, $store);

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

        $this->brand->processBrandsForStoreGroup($writer, $storeGroup);
        $this->category->processCategoriesForStoreGroup($writer, $storeGroup);
        $this->product->processProducts($writer, $store);

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

        $this->brand->processBrandsForWebsite($writer, $website);
        $this->category->processCategoriesForWebsite($writer, $website);
        $this->product->processProducts($writer, $store);

        $this->closeAndUploadFile($writer, $website->getId(), $store);
    }

    /**
     * @throws \Exception
     */
    public function exportFeedForGlobal()
    {
        /** Using admin store for now */
        /** @var Store $store */
        $store = $this->storeManager->getStore(0);

        $writer = $this->openProductFile($store);

        $this->brand->processBrandsForGlobal($writer);
        $this->category->processCategoriesForGlobal($writer);
        $this->product->processProducts($writer, $store);

        $this->closeAndUploadFile($writer, 0, $store);
    }

    /**
     * Get custom configured attributes
     * @param string $type
     * @return string
     */
    public function getAttributeCode($type)
    {
        return $this->configProvider->getAttributeCode($type);
    }

    /**
     * @param Store $store
     * @return XMLWriter
     */
    protected function openProductFile($store)
    {

        /** Get client name for the scope */
        $clientName = $this->configProvider->getClientName($store->getId());

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
        $scope = $this->configProvider->getFeedGenerationScope();
        $date = date('U');

        $productFeedFileName = "{$productFeedFilePath}/productFeed-{$scope}-{$scopeId}-{$date}.xml";
        $this->logger->debug("Creating file $productFeedFileName");

        parent::closeFile($writer, $productFeedFileName);

        /** Upload feed */
        $destinationFile = $this->configProvider->getProductPath($store->getId()) . '/' .
                           $this->configProvider->getProductFilename($store->getId());
        $this->uploadFeed($productFeedFileName, $destinationFile, $store);
    }
}
