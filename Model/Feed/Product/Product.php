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

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Model\Feed;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Store\Model\Store;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;

class Product extends Generic
{
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
    )
    {
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
        $this->_logger->info($productCollection->count() . ' products found to export.');

        /** @var \Magento\Framework\Model\ResourceModel\Iterator $iterator */
        $iterator = $this->_objectManager->create('\Magento\Framework\Model\ResourceModel\Iterator');
        $iterator
            ->walk($productCollection->getSelect(), array(array($this, 'writeProduct')));
        
        $this->_writer->endElement(); /** Products */
    }
    
    /**
     * @param array $args
     */
    public function writeProduct($args)
    {
        /** @var \Bazaarvoice\Connector\Model\Index $product */
        $product = $this->_objectManager->create('\Bazaarvoice\Connector\Model\Index');
        $product->setData($args['row']);
        
        $this->_logger->debug('Write product '.$product->getData('product_id'));

        foreach ($product->getData() as $key => $value) {
            if (is_string($value)
                && (substr($value, 0, 1) == '[' || substr($value, 0, 1) == '{'))
                $product->setData($key, $this->_helper->jsonDecode($value));
        }

        $this->_writer->startElement('Product');

        $this->_writer->writeElement('ExternalId', $product->getData('external_id'));
        $this->_writer->writeElement('Name', $product->getData('name'), true);
        $localeName = $product->getData('locale_name');
        if (is_array($localeName) && count($localeName)) {
            $this->_writer->startElement('Names');
            foreach ($localeName as $locale => $name) {
                $this->_writer->startElement('Name');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($name, true);
                $this->_writer->endElement(); /** Name */
            }
            $this->_writer->endElement(); /** Names */
        }

        $this->_writer->writeElement('Description', $product->getData('description'), true);
        $localeDescription = $product->getData('locale_description');
        if (is_array($localeDescription) && count($localeDescription)) {
            $this->_writer->startElement('Descriptions');
            foreach ($localeDescription as $locale => $description) {
                $this->_writer->startElement('Description');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($description, true);
                $this->_writer->endElement(); /** Description */
            }
            $this->_writer->endElement(); /** Descriptions */
        }

        $this->_writer->writeElement('CategoryExternalId', $product->getData('category_external_id'));

        $this->_writer->writeElement('ProductPageUrl', $product->getData('product_page_url'), true);
        $localeUrls = $product->getData('locale_product_page_url');
        if (is_array($localeUrls) && count($localeUrls)) {
            $this->_writer->startElement('ProductPageUrls');
            foreach ($localeUrls as $locale => $url) {
                $this->_writer->startElement('ProductPageUrl');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($url, true);
                $this->_writer->endElement(); /** ProductPageUrl */
            }
            $this->_writer->endElement(); /** ProductPageUrls */
        }

        $this->_writer->writeElement('ImageUrl', $product->getData('image_url'), true);
        $localeImage = $product->getData('locale_image_url');
        if (is_array($localeImage) && count($localeImage)) {
            $this->_writer->startElement('ImageUrls');
            foreach ($localeImage as $locale => $image) {
                $this->_writer->startElement('ImageUrl');
                $this->_writer->writeAttribute('locale', $locale);
                $this->_writer->writeRaw($image, true);
                $this->_writer->endElement(); /** ImageUrl */
            }
            $this->_writer->endElement(); /** ImageUrls */
        }

        if ($product->getData('brand_external_id'))
            $this->_writer->writeElement('BrandExternalId', $product->getData('brand_external_id'));

        foreach ($product->customAttributes as $label) {
            $code = strtolower($label) . 's';
            $values = $product->getData($code);
            if (!empty($values)) {
                $this->_writer->startElement($label . 's');
                if(is_string($values) && strpos($values, ','))
                	$values = explode(',', $values);
                if (is_array($values)) {
                    foreach ($values as $value) {
                        $this->_writer->writeElement($label, $value, true);
                    }
                } else {
                    $this->_writer->writeElement($label, $values, true);
                }
                $this->_writer->endElement();
            }
        }

        if ($this->_helper->getConfig('feeds/families')) {
            if ($product->getData('family') && count($product->getData('family'))) {
                $this->_writer->startElement('Attributes');

                foreach ($product->getData('family') as $familyId) {
                    if ($familyId) {
                        $this->_writer->startElement('Attribute');
                        $this->_writer->writeAttribute('id', 'BV_FE_FAMILY');
                        $this->_writer->writeElement('Value', $familyId);
                        $this->_writer->endElement(); /** Attribute */

                        if ($this->_helper->getConfig('feeds/bvfamilies_expand')) {
                            $this->_writer->startElement('Attribute');
                            $this->_writer->writeAttribute('id', 'BV_FE_EXPAND');
                            $this->_writer->writeElement('Value', 'BV_FE_FAMILY:' . $familyId);
                            $this->_writer->endElement(); /** Attribute */
                        }
                    }
                }
                $this->_writer->endElement(); /** Attributes */
            }
        }

        $this->_writer->endElement(); /** Product */
    }

    /**
     * @return Collection
     */
    protected function getProductCollection()
    {
        /** @var Collection\Factory $indexFactory */
        $indexFactory = $this->_objectManager->get('\Bazaarvoice\Connector\Model\ResourceModel\Index\Collection\Factory');
        $collection = $indexFactory->create();
        $collection->addFieldToFilter('status', Status::STATUS_ENABLED);

        return $collection;
    }



}