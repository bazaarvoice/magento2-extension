<?php

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use \Bazaarvoice\Connector\Model\XMLWriter;
use \Magento\Store\Api\Data\StoreInterface;
use \Magento\Store\Model\Group;
use \Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Store\Model\Website;
use \Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class Brand extends Generic
{
    protected $_attribute;

    /**
     * Brand constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param Attribute $attribute
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        StoreManagerInterface $storeManager,
        Attribute $attribute
    ) {
        $this->_attribute = $attribute;
        parent::__construct( $logger, $helper, $storeManager );
    }


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

        $storesList = $this->_storeManager->getStores();
        $stores = [];
        /** @var StoreInterface $store */
        foreach ($storesList as $store) {
        	if($this->_helper->getConfig('general/enable_bv', $store->getId()))
	            $stores[] = $store->getId();
        }
        $brandsByLocale = $this->getOptionsByLocale($attributeCode, $stores);

        /** Using admin store for now */
        $store = $this->_storeManager->getStore(0);
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
        $attribute = $this->_attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $code);
        $attribute->setStoreId($storeId);
        $attributeOptions = $attribute->getSource()->getAllOptions();
        /** Reformat array */
        $processedOptions = array();
        foreach ($attributeOptions as $attributeOption) {
            if (!empty($attributeOption['value']))
                $processedOptions[$attributeOption['value']] = $attributeOption['label'];
        }
        $this->_attribute->clearInstance();
        return $processedOptions;
    }

}