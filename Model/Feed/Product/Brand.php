<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class Brand
 *
 * @package Bazaarvoice\Connector\Model\Feed\Product
 */
class Brand
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute
     */
    protected $attribute;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Brand constructor.
     *
     * @param Logger                                     $logger
     * @param ConfigProviderInterface                    $configProvider
     * @param Attribute                                  $attribute
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        Logger $logger,
        ConfigProviderInterface $configProvider,
        Attribute $attribute,
        StoreManagerInterface $storeManager
    ) {
        $this->attribute = $attribute;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
    }

    /**
     * @param XMLWriter $writer
     * @param           $store
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processBrandsForStore(XMLWriter $writer, Store $store)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->configProvider->getAttributeCode('brand', $store->getId());
        /** If there is no attribute code for store, then bail */
        if (!$attributeCode) {
            return;
        }
        $writer->startElement('Brands');
        $brands = $this->getOptionsForStore($attributeCode, $store);
        foreach ($brands as $brandId => $brandValue) {
            $writer->startElement('Brand');
            $writer->writeElement('ExternalId', $brandId);
            $writer->writeElement('Name', $brandValue, true);
            $writer->endElement(); //End Brand
        }
        $writer->endElement(); //End Brands
    }

    /**
     * @param XMLWriter $writer
     * @param Group     $storeGroup
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processBrandsForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->configProvider->getAttributeCode('brand', $storeGroup->getDefaultStore()->getId());
        /** If there is no attribute code for store, then bail */
        if (!$attributeCode) {
            return;
        }
        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $storeGroup->getStoreIds());
        $defaultBrands = $this->getOptionsForStore($attributeCode, $storeGroup->getDefaultStore());
        $writer->startElement('Brands');
        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);
        $writer->endElement(); //End Brands
    }

    /**
     * @param XMLWriter $writer
     * @param Website   $website
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processBrandsForWebsite(XMLWriter $writer, Website $website)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->configProvider->getAttributeCode('brand', $website->getDefaultStore()->getId());
        /** If there is no attribute code for store, then bail */
        if (!$attributeCode) {
            return;
        }
        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $website->getStoreIds());
        $defaultBrands = $this->getOptionsForStore($attributeCode, $website->getDefaultStore());
        $writer->startElement('Brands');
        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);
        $writer->endElement(); //End Brands
    }

    /**
     * @param XMLWriter $writer
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processBrandsForGlobal(XMLWriter $writer)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->configProvider->getAttributeCode('brand');
        /** If there is no attribute code for store, then bail */
        if (!$attributeCode) {
            return;
        }
        $storesList = $this->storeManager->getStores();
        $stores = [];
        /** @var StoreInterface $store */
        foreach ($storesList as $store) {
            if ($this->configProvider->isBvEnabled($store->getId())) {
                $stores[] = $store->getId();
            }
        }
        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $stores);
        /** Using admin store for now */
        $store = $this->storeManager->getStore(0);
        $defaultBrands = $this->getOptionsForStore($attributeCode, $store);
        $writer->startElement('Brands');
        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);
        $writer->endElement(); //End Brands
    }

    /**
     * @param XMLWriter $writer
     * @param array     $defaultBrands
     * @param array     $brandsByLocale
     */
    protected function writeBrandsByLocale(XMLWriter $writer, $defaultBrands, $brandsByLocale)
    {
        foreach ($defaultBrands as $brandId => $brandDefaultValue) {
            $writer->startElement('Brand');
            $writer->writeElement('ExternalId', $brandId);
            $writer->writeElement('Name', $brandDefaultValue, true);
            $writer->startElement('Names');
            foreach ($brandsByLocale as $locale => $brands) {
                if (isset($brands[$brandId])) {
                    $writer->startElement('Name');
                    $writer->writeAttribute('locale', $locale);
                    $writer->writeRaw($brands[$brandId], true);
                    $writer->endElement(); //End Name
                }
            }
            $writer->endElement(); //End Names
            $writer->endElement(); //End Brand
        }
    }

    /**
     * @param string $code
     * @param array  $storeIds
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getOptionsByLocale($code, $storeIds)
    {
        $brandsByLocale = [];
        foreach ($storeIds as $storeId) {
            $locale = $this->configProvider->getLocale($storeId);
            $brandsByLocale[$locale] = $this->getOptionsForStore($code, $storeId);
        }
        return $brandsByLocale;
    }

    /**
     * @param string $code
     * @param mixed  $store
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getOptionsForStore($code, $store)
    {
        $storeId = $store instanceof Store ? $store->getId() : $store;
        /** Lookup the attribute options for this store */
        $attribute = $this->attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $code);
        $attribute->setStoreId($storeId);
        $attributeOptions = $attribute->getSource()->getAllOptions();
        /** Reformat array */
        $processedOptions = [];
        foreach ($attributeOptions as $attributeOption) {
            if (!empty($attributeOption['value'])) {
                $processedOptions[$attributeOption['value']] = $attributeOption['label'];
            }
        }
        $this->attribute->clearInstance();
        return $processedOptions;
    }
}
