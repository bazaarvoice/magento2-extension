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
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
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
        'ManufacturerPartNumber' => 'mpn',
        'ISBN' => 'isbn'
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
        $productCollection = $this->getProductCollection();

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
        $productCollection = $this->getProductCollection();
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
        $productCollection = $this->getProductCollection();
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
        $productCollection = $this->getProductCollection();

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
        $families = false;
        if($this->helper->getConfig('general/families')) {
            $families = $this->getProductFamilies($product);
        }

        // Parent values
        $masterProduct = false;
        if($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
            $this->logger->info('Product ' . $product->getSku() . ' not visible');
            if($product->getTypeId() == Configurable::TYPE_CODE) {
                $this->logger->info('Skipping not visible configurable product ' . $product->getSku());
                return;
            }
            if($families && count($families)) {
                $masterProduct = $this->getProductCollection()->getItemById($families[0]);
                if($masterProduct instanceof \Magento\Catalog\Model\Product) {
                    $this->logger->info('Using parent data for not visible child product ' . $product->getSku());
                } else {
                    $this->logger->info('Skipping not visible child product ' . $product->getSku() . ' no parent found.');
                    return;
                }
            }
        }

        // Localization
        $localeProducts = [];
        $localeMasterProducts = [];
        $localeStores = [];
        if(count($localeData)) {
            /** @var Collection $collection */
            foreach($localeData as $localeCode => $locale) {
                if(isset($locale['collection']) && $locale['collection'] instanceof Collection) {
                    $localeProduct = $locale['collection']->getItemById($product->getId());
                    if($localeProduct instanceof \Magento\Catalog\Model\Product && $localeProduct->getId())
                        $localeProducts[$localeCode] = $localeProduct;
                    if($masterProduct) {
                        $localeMasterProduct = $locale['collection']->getItemById($masterProduct->getId());
                        if($localeMasterProduct instanceof \Magento\Catalog\Model\Product && $localeMasterProduct->getId())
                            $localeMasterProducts[$localeCode] = $localeMasterProduct;
                    }
                }

                if(isset($locale['store']) && $locale['store'] instanceof Store)
                    $localeStores[$localeCode] = $locale['store'];
            }
        }
        if(!$masterProduct) {
            $masterProduct = $product;
            $localeMasterProducts = $localeProducts;
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

        $writer->writeElement('ProductPageUrl', $this->productHelper->getProductUrl($masterProduct), true);
        if(count($localeMasterProducts)){
            $writer->startElement('ProductPageUrls');
            foreach($localeMasterProducts as $locale => $localeProduct) {
                if(!isset($localeStores[$locale]) || empty($localeStores[$locale])) continue;
                $writer->startElement('ProductPageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw($this->getStoreUrl($localeProduct, $localeStores[$locale]->getId()), true);
                $writer->endElement(); // ProductPageUrl
            }
            $writer->endElement(); // ProductPageUrls
        }

        $imageUrl = $this->productHelper->getImageUrl($product);
        if(preg_match("#no_selection#", $imageUrl)) {
            $imageUrl = $this->productHelper->getImageUrl($masterProduct);
        }
        $writer->writeElement('ImageUrl', $imageUrl, true);
        if(count($localeProducts)){
            $writer->startElement('ImageUrls');
            foreach($localeProducts as $locale => $localeProduct) {
                $writer->startElement('ImageUrl');
                $writer->writeAttribute('locale', $locale);
                $localizedImage = $this->productHelper->getImageUrl($localeProduct);
                if(preg_match("#no_selection#", $localizedImage) && isset($localeMasterProducts[$locale]) && $localeMasterProducts[$locale] instanceof \Magento\Catalog\Model\Product) {
                    $localizedImage = $this->productHelper->getImageUrl($localeMasterProducts[$locale]);
                }
                $writer->writeRaw($localizedImage, true);
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

        if($families && count($families)) {
            $writer->startElement('Attributes');

            foreach($families as $familyId) {
                $family = $this->getProductCollection()->getItemById($familyId);
                $familyExternalId = $this->helper->getProductId($family);
                if($familyExternalId) {
                    $writer->startElement('Attribute');
                    $writer->writeAttribute('id', 'BV_FE_FAMILY');
                    $writer->writeElement('Value', $familyExternalId);
                    $writer->endElement(); // Attribute

                    if($this->helper->getConfig('feeds/bvfamilies_expand')) {
                        $writer->startElement('Attribute');
                        $writer->writeAttribute('id', 'BV_FE_EXPAND');
                        $writer->writeElement('Value', 'BV_FE_FAMILY:' . $familyExternalId);
                        $writer->endElement(); // Attribute
                    }
                }
            }

            $writer->endElement(); // Attributes
        }

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
            $collection = $this->getProductCollection(true);
            $collection->setStore($store)->load();
            $localeData[$locale] = [
                'store' => $store,
                'collection' => $collection
            ];
        }

        return $localeData;
    }

    protected function getProductFamilies(\Magento\Catalog\Model\Product $product)
    {
        $families = array();
        if($product->getTypeId() == Configurable::TYPE_CODE){
            $families[] = $product->getId();
        } else {
            /** @var Configurable $resource */
            $resource = $this->objectManager->get('\Magento\ConfigurableProduct\Model\Product\Type\Configurable');
            $parentIds = $resource->getParentIdsByChild($product->getId());
            foreach($parentIds as $parentId){
                /** @var \Magento\Catalog\Model\Product $parent */
                $parent = $this->getProductCollection()->getItemById($parentId);
                if($parent->getId())
                    $families[] = $parentId;
            }
        }
        return $families;
    }    

    /**
     * @param bool $new Get new collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected function getProductCollection($new = false)
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
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect(Feed\ProductFeed::INCLUDE_IN_FEED_FLAG)
                ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);

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