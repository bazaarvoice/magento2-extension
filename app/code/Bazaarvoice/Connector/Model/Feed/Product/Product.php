<?php
namespace Bazaarvoice\Connector\Model\Feed\Product;
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

use Bazaarvoice\Connector\Model\Feed;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\Website;

class Product extends Feed\ProductFeed
{
    /** @var \Magento\Catalog\Helper\Product $productHelper */
    protected $productHelper;
    /** @var Collection $productCollection */
    protected $productCollection;

    protected $attributeCodes = array(
        'brand',
        'upc',
        'ean',
        'mpn'
    );
    
    /**
     * Constructor
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Helper\Product $catalogProductHelper
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Product $catalogProductHelper
    ) {
        parent::__construct($logger, $helper, $objectManager);
        $this->productHelper = $catalogProductHelper;
    }

    /**
     * @param XMLWriter $writer
     * @param $store
     */
    public function processProductsForStore(XMLWriter $writer, Store $store)
    {
        $writer->startElement('Products');
        $productCollection = $this->_getProductCollection();

        $productCollection->setStore($store);
        $this->logger->info($productCollection->count() . ' products found to export.');

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product);
        }
        
        $writer->endElement(); // Products
    }

    /**
     * @param XMLWriter $writer
     * @param Group $storeGroup
     */
    public function processProductsForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        $writer->startElement('Products');
        $productCollection = $this->_getProductCollection();

        $this->logger->info($productCollection->count() . ' products found to export.');

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product);
        }

        $writer->endElement(); // Products
    }

    /**
     * @param XMLWriter $writer
     * @param Website $website
     */
    public function processProductsForWebsite(XMLWriter $writer, Website $website)
    {
        $writer->startElement('Products');
        $productCollection = $this->_getProductCollection();

        $this->logger->info($productCollection->count() . ' products found to export.');

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product);
        }

        $writer->endElement(); // Products
    }
    
    /**
     * @param XMLWriter $writer
     */
    public function processProductsForGlobal(XMLWriter $writer)
    {
        $writer->startElement('Products');
        $productCollection = $this->_getProductCollection();

        $this->logger->info($productCollection->count() . ' products found to export.');

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product);
        }

        $writer->endElement(); // Products
    }

    /**
     * @param XMLWriter $writer
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function writeProduct(XMLWriter $writer, \Magento\Catalog\Model\Product $product)
    {
        $writer->startElement('Product');

        $writer->writeElement('ExternalId', $this->helper->getProductId($product));
        $writer->writeElement('Name', $product->getName(), true);
        $writer->writeElement('Description', $product->getData('description'), true);

        $writer->writeElement('BrandExternalId', $product->getData('brand'));

        // !TODO Categories
        $writer->writeElement('CategoryExternalId', $product->getData('category_ids'));

        $writer->writeElement('ProductPageUrl', $this->productHelper->getProductUrl($product), true);

        // !TODO Localized Image Url
        $writer->writeElement('ImageUrl', $product->getImageUrl());

        // !TODO extra attributes
        foreach(array('ManufacturerPartNumber', 'EAN', 'UPC') as $code) {
            $writer->startElement($code.'s');
            $writer->writeElement($code, $product->getData($code));
            $writer->endElement();
        }

        // !TODO Families

        $writer->endElement(); // Product
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollection()
    {
        if(!$this->productCollection) {
            $productFactory = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');

            /* @var Collection $productCollection */
            $productCollection = $productFactory->create()->getCollection();

            $productCollection
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('brand')
                ->addAttributeToSelect('category_ids')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect(Feed\ProductFeed::INCLUDE_IN_FEED_FLAG);

            $productCollection->addAttributeToFilter(Feed\ProductFeed::INCLUDE_IN_FEED_FLAG, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            $this->productCollection = $productCollection;
        }
        return $this->productCollection;
    }



}