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
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Magento\Store\Model\Website;

class Product extends Feed\ProductFeed
{
    /** @var Collection $productCollection */
    protected $productCollection;

    /**
     * Product constructor.
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($logger, $helper, $objectManager);
    }

    /**
     * @param XMLWriter $writer
     * @param $store
     */
    public function processProductsForStore(XMLWriter $writer, Store $store)
    {
        $writer->startElement('Products');
        $productCollection = $this->getProductCollection();

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
        $productCollection = $this->getProductCollection();
        $productCollection->setStore($storeGroup->getDefaultStore());

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
        $productCollection = $this->getProductCollection();
        $productCollection->setStore($website->getDefaultStore());

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
        $productCollection = $this->getProductCollection();

        $this->logger->info($productCollection->count() . ' products found to export.');

        foreach($productCollection as $product) {
            try {
                $this->writeProduct($writer, $product);
            } Catch (\Exception $e) {
            	$this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            }
        }

        $writer->endElement(); // Products
    }

    /**
     * @param XMLWriter $writer
     * @param \Bazaarvoice\Connector\Model\Index
     */
    protected function writeProduct(XMLWriter $writer, \Bazaarvoice\Connector\Model\Index $product)
    {
        $this->logger->debug('Write product '.$product->getData('product_id'));

        foreach($product->getData() as $key => $value) {
            if(is_string($value) && (substr($value, 0, 1) == "[" || substr($value, 0, 1) == "{"))
                $product->setData($key, $this->helper->jsonDecode($value));
        }

        $writer->startElement('Product');

        $writer->writeElement('ExternalId', $product->getData('external_id'));
        $writer->writeElement('Name', $product->getData('name'), true);
        $localeName = $product->getData('locale_name');
        if(is_array($localeName) && count($localeName)){
            $writer->startElement('Names');
            foreach($localeName as $locale => $name) {
                $writer->startElement('Name');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($name, true);
                $writer->endElement(); // Name
            }
            $writer->endElement(); // Names
        }

        $writer->writeElement('Description', $product->getData('description'), true);
        $localeDescription = $product->getData('locale_description');
        if(is_array($localeDescription) && count($localeDescription)){
            $writer->startElement('Descriptions');
            foreach($localeDescription as $locale => $description) {
                $writer->startElement('Description');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($description, true);
                $writer->endElement(); // Description
            }
            $writer->endElement(); // Descriptions
        }

        $writer->writeElement('CategoryExternalId', $product->getData('category_external_id'));

        $writer->writeElement('ProductPageUrl', $product->getData('product_page_url'), true);
        $localeUrls = $product->getData('locale_product_page_url');
        if(is_array($localeUrls) && count($localeUrls)){
            $writer->startElement('ProductPageUrls');
            foreach($localeUrls as $locale => $url) {
                $writer->startElement('ProductPageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($url, true);
                $writer->endElement(); // ProductPageUrl
            }
            $writer->endElement(); // ProductPageUrls
        }

        $writer->writeElement('ImageUrl', $product->getData('image_url'), true);
        $localeImage = $product->getData('locale_image_url');
        if(is_array($localeImage) && count($localeImage)){
            $writer->startElement('ImageUrls');
            foreach($localeImage as $locale => $image) {
                $writer->startElement('ImageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($image, true);
                $writer->endElement(); // ImageUrl
            }
            $writer->endElement(); // ImageUrls
        }

        if($product->getData('brand_external_id'))
            $writer->writeElement('BrandExternalId', $product->getData('brand_external_id'));

        foreach($product->customAttributes as $code) {
            $values = $product->getData(strtolower($code) . 's');
            if(is_array($values) && !empty($values)) {
                $writer->startElement($code . 's');
                foreach ($values as $value)
                    $writer->writeElement($code, $value);
                $writer->endElement();
            }
        }

        if($product->getData('family') && count($product->getData('family'))) {
            $writer->startElement('Attributes');

            foreach($product->getData('family') as $familyId) {
                if($familyId) {
                    $writer->startElement('Attribute');
                    $writer->writeAttribute('id', 'BV_FE_FAMILY');
                    $writer->writeElement('Value', $familyId);
                    $writer->endElement(); // Attribute

                    if($this->helper->getConfig('feeds/bvfamilies_expand')) {
                        $writer->startElement('Attribute');
                        $writer->writeAttribute('id', 'BV_FE_EXPAND');
                        $writer->writeElement('Value', 'BV_FE_FAMILY:' . $familyId);
                        $writer->endElement(); // Attribute
                    }
                }
            }

            $writer->endElement(); // Attributes
        }

        $writer->endElement(); // Product
    }

    /**
     * @param bool $new Get new collection
     * @return Collection
     */
    protected function getProductCollection($new = false)
    {
        if($new || !$this->productCollection) {
            /** @var Collection\Factory $indexFactory */
            $indexFactory = $this->objectManager->get('\Bazaarvoice\Connector\Model\ResourceModel\Index\Collection\Factory');
            $collection = $indexFactory->create();
            $collection->addFieldToFilter('status', Status::STATUS_ENABLED);
            $this->productCollection = $collection;

        }
        return $this->productCollection;
    }



}