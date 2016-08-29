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
use Magento\Store\Model\Store;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;

class Product extends Generic
{
    /** @var Collection $productCollection */
    protected $productCollection;
    /** @var  XMLWriter $_writer */
    protected $_writer;

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
    public function processProducts(XMLWriter $writer, Store $store)
    {
        $this->_writer = $writer;
        $this->_writer->startElement('Products');
        $productCollection = $this->getProductCollection();

        $productCollection->setStore($store);
        $this->logger->info($productCollection->count() . ' products found to export.');

        /** @var \Magento\Framework\Model\ResourceModel\Iterator $iterator */
        $iterator = $this->objectManager->create('\Magento\Framework\Model\ResourceModel\Iterator');
        $iterator
            ->walk($productCollection->getSelect(), array(array($this, 'writeProduct')));
        
        $this->_writer->endElement(); // Products
    }
    
    /**
     * @param array $args
     */
    public function writeProduct($args)
    {
        /** @var \Bazaarvoice\Connector\Model\Index $product */
        $product = $this->objectManager->create('\Bazaarvoice\Connector\Model\Index');
        $product->setData($args['row']);
        
        $this->logger->debug('Write product '.$product->getData('product_id'));

        foreach($product->getData() as $key => $value) {
            if(is_string($value) && (substr($value, 0, 1) == "[" || substr($value, 0, 1) == "{"))
                $product->setData($key, $this->helper->jsonDecode($value));
        }

        $this->_writer->startElement('Product');

        $this->_writer->writeElement('ExternalId', $product->getData('external_id'));
        $this->_writer->writeElement('Name', $product->getData('name'), true);
        $localeName = $product->getData('locale_name');
        if(is_array($localeName) && count($localeName)){
            $this->_writer->startElement('Names');
            foreach($localeName as $locale => $name) {
                $this->_writer->startElement('Name');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($name, true);
                $this->_writer->endElement(); // Name
            }
            $this->_writer->endElement(); // Names
        }

        $this->_writer->writeElement('Description', $product->getData('description'), true);
        $localeDescription = $product->getData('locale_description');
        if(is_array($localeDescription) && count($localeDescription)){
            $this->_writer->startElement('Descriptions');
            foreach($localeDescription as $locale => $description) {
                $this->_writer->startElement('Description');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($description, true);
                $this->_writer->endElement(); // Description
            }
            $this->_writer->endElement(); // Descriptions
        }

        $this->_writer->writeElement('CategoryExternalId', $product->getData('category_external_id'));

        $this->_writer->writeElement('ProductPageUrl', $product->getData('product_page_url'), true);
        $localeUrls = $product->getData('locale_product_page_url');
        if(is_array($localeUrls) && count($localeUrls)){
            $this->_writer->startElement('ProductPageUrls');
            foreach($localeUrls as $locale => $url) {
                $this->_writer->startElement('ProductPageUrl');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($url, true);
                $this->_writer->endElement(); // ProductPageUrl
            }
            $this->_writer->endElement(); // ProductPageUrls
        }

        $this->_writer->writeElement('ImageUrl', $product->getData('image_url'), true);
        $localeImage = $product->getData('locale_image_url');
        if(is_array($localeImage) && count($localeImage)){
            $this->_writer->startElement('ImageUrls');
            foreach($localeImage as $locale => $image) {
                $this->_writer->startElement('ImageUrl');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($image, true);
                $this->_writer->endElement(); // ImageUrl
            }
            $this->_writer->endElement(); // ImageUrls
        }

        if($product->getData('brand_external_id'))
            $this->_writer->writeElement('BrandExternalId', $product->getData('brand_external_id'));

        foreach($product->customAttributes as $label) {
            $code = strtolower($label) . 's';
            $values = $product->getData($code);
            if(!empty($values)) {
                $this->_writer->startElement($label . 's');
                if(is_array($values)) {
                    foreach ($values as $value) {
                        $this->_writer->writeElement($label, $value);
                    }
                } else {
                    $this->_writer->writeElement($label, $values);
                }
                $this->_writer->endElement();
            }
        }

        if($this->helper->getConfig('feeds/families')) {
            if($product->getData('family') && count($product->getData('family'))) {
                $this->_writer->startElement('Attributes');

                foreach ($product->getData('family') as $familyId) {
                    if ($familyId) {
                        $this->_writer->startElement('Attribute');
                        $this->_writer->writeAttribute('id', 'BV_FE_FAMILY');
                        $this->_writer->writeElement('Value', $familyId);
                        $this->_writer->endElement(); // Attribute

                        if ($this->helper->getConfig('feeds/bvfamilies_expand')) {
                            $this->_writer->startElement('Attribute');
                            $this->_writer->writeAttribute('id', 'BV_FE_EXPAND');
                            $this->_writer->writeElement('Value', 'BV_FE_FAMILY:' . $familyId);
                            $this->_writer->endElement(); // Attribute
                        }
                    }
                }
                $this->_writer->endElement(); // Attributes
            }
        }

        $this->_writer->endElement(); // Product
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