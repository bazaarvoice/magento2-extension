<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Index;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Class Product
 *
 * @package Bazaarvoice\Connector\Model\Feed\Product
 */
class Product
{
    /**
     * @var \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory
     */
    private $indexCollectionFactory;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $logger;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * Product constructor.
     *
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                 $configProvider
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory $factory
     * @param \Bazaarvoice\Connector\Logger\Logger                               $logger
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                $stringFormatter
     */
    public function __construct(
        ConfigProviderInterface $configProvider,
        CollectionFactory $factory,
        Logger $logger,
        StringFormatterInterface $stringFormatter
    ) {
        $this->indexCollectionFactory = $factory;
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->stringFormatter = $stringFormatter;
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param \Magento\Store\Api\Data\StoreInterface             $store
     */
    public function processProducts(XMLWriter $writer, StoreInterface $store)
    {
        $writer->startElement('Products');
        $indexCollection = $this->getIndexCollection();
        if ($store->getId()) {
            $indexCollection->setStore($store);
        }
        foreach ($indexCollection as $product) {
            $this->writeProduct($writer, $product);
        }
        $this->logger->info($indexCollection->count().' products found to export.');
        $writer->endElement(); //End Products
    }

    /**
     * @param \Bazaarvoice\Connector\Model\XMLWriter $writer
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     */
    public function writeProduct(XMLWriter $writer, $product)
    {
        $this->logger->debug('Write product '.$product->getData('product_id'));

        /** Load parent value if product value is blank */
        if ($product->getData('family') && $this->configProvider->isFamiliesInheritEnabled()) {
            $this->logger->debug('inherit family values');
            $children = $this->indexCollectionFactory->create();
            $children
                ->addFieldToFilter('family', $product->getData('family'))
                ->addFieldToFilter('store_id', $product->getData('store_id'));
            $childrenValues = [];
            foreach ($children as $child) {
                $this->logger->debug($child->getExternalId());
                foreach (Index::CUSTOM_ATTRIBUTES as $attribute) {
                    $this->logger->debug($attribute);
                    $attribute = strtolower($attribute).'s';
                    if ($child->getData($attribute)) {
                        $value = $child->getData($attribute);
                        if (is_string($value) && strpos($value, ',') !== false) {
                            $values = explode(',', $value);
                            if (empty($childrenValues[$attribute])) {
                                $childrenValues[$attribute] = $values;
                            } else {
                                $childrenValues[$attribute] = array_merge($childrenValues[$attribute], $values);
                            }
                        } else {
                            $childrenValues[$attribute][] = $value;
                        }
                    }
                }
            }
            $this->logger->debug($childrenValues);
            foreach ($childrenValues as $attribute => $values) {
                if (!is_array($values) || empty($values)) {
                    continue;
                }
                $product->setData($attribute, $values);
            }
        }

        foreach ($product->getData() as $key => $value) {
            if (is_string($value)
                && (substr($value, 0, 1) == '[' || substr($value, 0, 1) == '{')) {
                $product->setData($key, $this->stringFormatter->jsonDecode($value));
            }
        }

        $writer->startElement('Product');

        $writer->writeElement('ExternalId', $product->getData('external_id'));
        $writer->writeElement('Name', $product->getData('name'), true);
        $localeName = $product->getData('locale_name');
        if (is_array($localeName) && count($localeName)) {
            $writer->startElement('Names');
            foreach ($localeName as $locale => $name) {
                $writer->startElement('Name');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($name, true);
                $writer->endElement(); //End Name
            }
            $writer->endElement(); //End Names
        }

        if ($product->getData('description')) {
            $writer->writeElement('Description', $product->getData('description'), true);
        }
        $localeDescription = $product->getData('locale_description');
        if (is_array($localeDescription) && count($localeDescription)) {
            $writer->startElement('Descriptions');
            foreach ($localeDescription as $locale => $description) {
                $writer->startElement('Description');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($description, true);
                $writer->endElement(); //End Description
            }
            $writer->endElement(); //End Descriptions
        }

        if ($product->getData('category_external_id')) {
            $writer->writeElement('CategoryExternalId', $product->getData('category_external_id'));
        }

        $writer->writeElement('ProductPageUrl', $product->getData('product_page_url'), true);
        $localeUrls = $product->getData('locale_product_page_url');
        if (is_array($localeUrls) && count($localeUrls)) {
            $writer->startElement('ProductPageUrls');
            foreach ($localeUrls as $locale => $url) {
                $writer->startElement('ProductPageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($url, true);
                $writer->endElement(); //End ProductPageUrl
            }
            $writer->endElement(); //End ProductPageUrls
        }

        $writer->writeElement('ImageUrl', $product->getData('image_url'), true);
        $localeImage = $product->getData('locale_image_url');
        if (is_array($localeImage) && count($localeImage)) {
            $writer->startElement('ImageUrls');
            foreach ($localeImage as $locale => $image) {
                $writer->startElement('ImageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($image, true);
                $writer->endElement(); //End ImageUrl
            }
            $writer->endElement(); //End ImageUrls
        }

        if ($product->getData('brand_external_id')) {
            $writer->writeElement('BrandExternalId', $product->getData('brand_external_id'));
        }

        foreach (Index::CUSTOM_ATTRIBUTES as $label) {
            $code = strtolower($label).'s';
            $values = $product->getData($code);
            if (!empty($values)) {
                $writer->startElement($label.'s');
                if (is_string($values) && strpos($values, ',') !== false) {
                    $values = explode(',', $values);
                }
                if (is_array($values)) {
                    foreach ($values as $value) {
                        $writer->writeElement($label, $value, true);
                    }
                } else {
                    $writer->writeElement($label, $values, true);
                }
                $writer->endElement();
            }
        }

        if ($this->configProvider->isFamiliesEnabled()) {
            if ($product->getData('family')) {
                $writer->startElement('Attributes');
                $family = $product->getData('family');
                if (!is_array($family)) {
                    $family = [$family];
                }

                foreach ($family as $familyId) {
                    if ($familyId) {
                        $writer->startElement('Attribute');
                        $writer->writeAttribute('id', 'BV_FE_FAMILY');
                        $writer->writeElement('Value', $familyId);
                        $writer->endElement(); //End Attribute

                        if ($product->getData('product_type') != 'simple'
                            || $this->configProvider->isFamiliesExpandEnabled()
                        ) {
                            $writer->startElement('Attribute');
                            $writer->writeAttribute('id', 'BV_FE_EXPAND');
                            $writer->writeElement('Value', 'BV_FE_FAMILY:'.$familyId);
                            $writer->endElement(); //End Attribute
                        }
                    }
                }
                $writer->endElement(); //End Attributes
            }
        }

        $writer->endElement(); //End Product
    }

    /**
     * @return Collection
     */
    private function getIndexCollection()
    {
        $collection = $this->indexCollectionFactory->create();
        $collection->addFieldToFilter('status', Status::STATUS_ENABLED);

        return $collection;
    }
}
