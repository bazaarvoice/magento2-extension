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

use \Bazaarvoice\Connector\Model\Feed;
use \Bazaarvoice\Connector\Model\XMLWriter;
use \Magento\Store\Api\Data\StoreInterface;
use \Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use \Magento\Store\Model\StoreManagerInterface;
use \Magento\Store\Model\Website;

class Brand extends Generic
{
	/**
	 * @param XMLWriter $writer
	 * @param $store
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function processBrandsForStore(XMLWriter $writer, Store $store)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->getAttributeCode('brand', $store);
        /** If there is no attribute code for store, then bail */
        if (!strlen(trim($attributeCode))) {
            return;
        }
        
        $writer->startElement('Brands');

            $brands = $this->getOptionsForStore($attributeCode, $store);

            foreach ($brands as $brandId => $brandValue) {
                $writer->startElement('Brand');

                $writer->writeElement('ExternalId', $brandId);
                $writer->writeElement('Name', $brandValue, true);

                $writer->endElement(); /** Brand */
            }

        $writer->endElement(); /** Brands */
    }

	/**
	 * @param XMLWriter $writer
	 * @param Group $storeGroup
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function processBrandsForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->getAttributeCode('brand', $storeGroup->getDefaultStore());
        /** If there is no attribute code for store, then bail */
        if (!strlen(trim($attributeCode))) {
            return;
        }

        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $storeGroup->getStoreIds());

        $defaultBrands = $this->getOptionsForStore($attributeCode, $storeGroup->getDefaultStore());

        $writer->startElement('Brands');

        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);

        $writer->endElement(); /** Brands */
    }

	/**
	 * @param XMLWriter $writer
	 * @param Website $website
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function processBrandsForWebsite(XMLWriter $writer, Website $website)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->getAttributeCode('brand', $website->getDefaultStore());
        /** If there is no attribute code for store, then bail */
        if (!strlen(trim($attributeCode))) {
            return;
        }

        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $website->getStoreIds());

        $defaultBrands = $this->getOptionsForStore($attributeCode, $website->getDefaultStore());

        $writer->startElement('Brands');

        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);

        $writer->endElement(); /** Brands */
    }

	/**
	 * @param XMLWriter $writer
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    public function processBrandsForGlobal(XMLWriter $writer)
    {
        /** Lookup the configured attribute code for "Brand" */
        $attributeCode = $this->getAttributeCode('brand');
        /** If there is no attribute code for store, then bail */
        if (!strlen(trim($attributeCode))) {
            return;
        }

        $storesList = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        $stores = [];
        /** @var StoreInterface $store */
        foreach ($storesList as $store) {
            $stores[] = $store->getId();
        }
        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $stores);

        /** Using admin store for now */
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore(0);
        $defaultBrands = $this->getOptionsForStore($attributeCode, $store);

        $writer->startElement('Brands');

        $this->writeBrandsByLocale($writer, $defaultBrands, $brandsByLocale);

        $writer->endElement(); /** Brands */
    }    

    /**
     * @param XMLWriter $writer
     * @param array $defaultBrands
     * @param array $brandsByLocale
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
                    $writer->endElement(); /** Name */
                }
            }

            $writer->endElement(); /** Names */

            $writer->endElement(); /** Brand */

        }
    }

	/**
	 * @param string $code
	 * @param array $storeIds
	 *
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    protected function getOptionsByLocale($code, $storeIds)
    {
        $brandsByLocale = array();
        foreach ($storeIds as $storeId) {
            $locale = $this->_helper->getConfig('general/locale', $storeId);
            $brandsByLocale[$locale] = $this->getOptionsForStore($code, $storeId);
        }
        return $brandsByLocale;
    }


	/**
	 * @param string $code
	 * @param mixed $store
	 *
	 * @return array
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    protected function getOptionsForStore($code, $store)
    {
        $storeId = $store instanceof Store ? $store->getId() : $store;
        /** Lookup the attribute options for this store */
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        $attribute = $this->_objectManager->get('\Magento\Catalog\Model\ResourceModel\Eav\Attribute');
        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $code);
        $attribute->setStoreId($storeId);
        $attributeOptions = $attribute->getSource()->getAllOptions();
        /** Reformat array */
        $processedOptions = array();
        foreach ($attributeOptions as $attributeOption) {
            if (!empty($attributeOption['value']))
                $processedOptions[$attributeOption['value']] = $attributeOption['label'];
        }
        return $processedOptions;
    }

}