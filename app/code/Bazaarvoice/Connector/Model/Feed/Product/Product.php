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
use Magento\Framework\UrlFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Product extends Feed\ProductFeed
{
    /** @var \Magento\Catalog\Helper\Product $productHelper */
    protected $productHelper;
    /** @var Collection $productCollection */
    protected $productCollection;
    /** @var  UrlFactory $urlFactory */
    protected $urlFactory;

    protected $attributeCodes = array(
        'BrandExternalId' => 'brand',
        'UPC' => 'upc',
        'EAN' => 'ean',
        'ManufacturerPartNumber' => 'mpn'
    );

    protected $customAttributes;

    /**
     * Product constructor.
     * @param \Bazaarvoice\Connector\Logger\Logger $logger
     * @param \Bazaarvoice\Connector\Helper\Data $helper
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Helper\Product $catalogProductHelper
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        \Bazaarvoice\Connector\Logger\Logger $logger,
        \Bazaarvoice\Connector\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        UrlFactory $urlFactory
    ) {
        parent::__construct($logger, $helper, $objectManager);
        $this->productHelper = $catalogProductHelper;
        $this->urlFactory = $urlFactory;
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
            $this->writeProduct($writer, $product, $store);
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
        $productCollection->setStore($storeGroup->getDefaultStore())->load();

        $this->logger->info($productCollection->count() . ' products found to export.');

        $localeData = $this->getLocaleData($storeGroup->getStoreIds());

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product, $storeGroup->getDefaultStore(), $localeData);
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
        $productCollection->setStore($website->getDefaultStore())->load();

        $this->logger->info($productCollection->count() . ' products found to export.');

        $localeData = $this->getLocaleData($website->getStoreIds());

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product, $website->getDefaultStore(), $localeData);
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

        $storesList = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        $stores = [];
        /** @var StoreInterface $store */
        foreach($storesList as $store) {
            $stores[] = $store->getId();
        }
        $localeData = $this->getLocaleData($stores);

        // Using admin store for now
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $store = $storeManager->getStore(0);

        foreach($productCollection as $product) {
            $this->writeProduct($writer, $product, $store, $localeData);
        }

        $writer->endElement(); // Products
    }

    /**
     * @param XMLWriter $writer
     * @param \Magento\Catalog\Model\Product $product
     * @param Store $store
     * @param null|array $localeData
     */
    protected function writeProduct(XMLWriter $writer, \Magento\Catalog\Model\Product $product, Store $store, &$localeData = null)
    {
        // Families
        $masterProduct = $product;
        if($this->helper->getConfig('general/families')) {

        }

        // Localization
        $localeProducts = [];
        $localeStores = [];
        if(count($localeData)) {
            /** @var Collection $collection */
            foreach($localeData as $localeCode => $locale) {
                if(isset($locale['collection']) && $locale['collection'] instanceof Collection) {
                    $localeProduct = $locale['collection']->getItemById($product->getId());
                    if($localeProduct instanceof \Magento\Catalog\Model\Product && $localeProduct->getId())
                        $localeProducts[$localeCode] = $localeProduct;
                }

                if(isset($locale['store']) && $locale['store'] instanceof Store)
                    $localeStores[$localeCode] = $locale['store'];
            }
        }

        $writer->startElement('Product');

        $writer->writeElement('ExternalId', $this->helper->getProductId($product));
        $writer->writeElement('Name', $product->getName(), true);
        if(count($localeProducts)){
            $writer->startElement('Names');
            foreach($localeProducts as $locale => $localeProduct) {
                $writer->startElement('Name');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($localeProduct->getName(), true);
                $writer->endElement(); // Name
            }
            $writer->endElement(); // Names
        }

        $writer->writeElement('Description', $product->getData('description'), true);
        if(count($localeProducts)){
            $writer->startElement('Descriptions');
            foreach($localeProducts as $locale => $localeProduct) {
                $writer->startElement('Description');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($localeProduct->getDescription(), true);
                $writer->endElement(); // Description
            }
            $writer->endElement(); // Descriptions
        }

        $categories = $masterProduct->getCategoryCollection()->addAttributeToSelect('url_path')->setOrder('level', 'decs');
        /** @var \Magento\Catalog\Model\Category\Interceptor $category */
        $category = $categories->getFirstItem();
        $writer->writeElement('CategoryExternalId', $this->getCategoryId($category));

        $writer->writeElement('ProductPageUrl', $this->productHelper->getProductUrl($product), true);
        if(count($localeProducts)){
            $writer->startElement('ProductPageUrls');
            foreach($localeProducts as $locale => $localeProduct) {
                if(!isset($localeStores[$locale]) || empty($localeStores[$locale])) continue;
                $writer->startElement('ProductPageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($this->getStoreUrl($localeProduct, $localeStores[$locale]->getId()), true);
                $writer->endElement(); // ProductPageUrl
            }
            $writer->endElement(); // ProductPageUrls
        }

        $writer->writeElement('ImageUrl', $this->productHelper->getImageUrl($masterProduct), true);
        if(count($localeProducts)){
            $writer->startElement('ImageUrls');
            foreach($localeProducts as $locale => $localeProduct) {
                $writer->startElement('ImageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($this->productHelper->getImageUrl($localeProduct), true);
                $writer->endElement(); // ImageUrl
            }
            $writer->endElement(); // ImageUrls
        }

        foreach($this->getCustomAttributes() as $code => $attributeCode) {
            if($code == 'brand') {
                $writer->writeElement('BrandExternalId', $product->getData($attributeCode));
            } else if($product->getData($attributeCode)) {
                $writer->startElement($code . 's');
                $writer->writeElement($code, $product->getData($attributeCode));
                $writer->endElement();
            }
        }

        // !TODO Families

        $writer->endElement(); // Product
    }

    /**
     * @return array
     */
    protected function getCustomAttributes()
    {
        if(!$this->customAttributes) {
            $customAttributes = [];
            foreach ($this->attributeCodes as $label => $code) {
                $attributeCode = $this->helper->getConfig('feeds/' . $code . '_code');
                if ($attributeCode)
                    $customAttributes[$label] = $attributeCode;
            }
            $this->customAttributes = $customAttributes;
        }
        return $this->customAttributes;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @param mixed $storeId
     * @return string
     */
    protected function getStoreUrl(\Magento\Catalog\Model\Product $product, $storeId)
    {
        $storeCode = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore($storeId)->getCode();

        $urlInstance = $this->urlFactory->create();

        $originalUrl = $product->getProductUrl();
        $originalUrl = str_replace($urlInstance->getBaseUrl(), '', $originalUrl);

        $url = $urlInstance
            ->setScope($storeId)
            ->addQueryParams(['___store' => $storeCode])
            ->getUrl($originalUrl);

        return $url;
    }

    /**
     * Get localized data for relevant products
     * @param array $storeIds
     * @return array
     */
    protected function getLocaleData($storeIds)
    {
        /** @var \Magento\Store\Model\StoreManagerInterface $storeInterface */
        $storeInterface = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
        $localeData = [];
        $locales = $this->getLocales($storeIds);
        foreach($locales as $locale => $storeId)
        {
            $store = $storeInterface->getStore($storeId);
            $collection = $this->_getProductCollection(true);
            $collection->setStore($store)->load();
            $localeData[$locale] = [
                'store' => $store,
                'collection' => $collection
            ];
        }

        return $localeData;
    }

    /**
     * @param bool $new Get new collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function _getProductCollection($new = false)
    {
        if($new || !$this->productCollection) {
            $productFactory = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');

            /* @var Collection $productCollection */
            $productCollection = $productFactory->create()->getCollection();

            foreach($this->getCustomAttributes() as $code => $attributeCode) {
                $productCollection->addAttributeToSelect($attributeCode);
            }

            $productCollection
                ->addAttributeToSelect('name')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('url_path')
                ->addAttributeToSelect(Feed\ProductFeed::INCLUDE_IN_FEED_FLAG);

            $productCollection->addAttributeToFilter(Feed\ProductFeed::INCLUDE_IN_FEED_FLAG, \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

            if(!$new) {
                $productCollection->load();
                $this->productCollection = $productCollection;
            } else {
                return $productCollection;
            }
        }
        return $this->productCollection;
    }



}