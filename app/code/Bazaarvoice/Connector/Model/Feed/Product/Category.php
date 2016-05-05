<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package        Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Model\Feed;
use Bazaarvoice\Connector\Model\XMLWriter;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\UrlFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Framework\ObjectManagerInterface;

class Category extends Feed\ProductFeed
{
    /** @var CategoryFactory $categoryFactory */
    protected $categoryFactory;

    /** @var  UrlFactory $urlFactory */
    protected $urlFactory;

    /**
     * Category constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param ObjectManagerInterface $objectManager
     * @param CategoryFactory $categoryFactory
     * @param UrlFactory $urlFactory
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        ObjectManagerInterface $objectManager,
        CategoryFactory $categoryFactory,
        UrlFactory $urlFactory
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->urlFactory = $urlFactory;
        parent::__construct($logger, $helper, $objectManager);
    }

    public function processCategoriesForStore(XMLWriter $writer, Store $store)
    {
        $writer->startElement('Categories');

        $categories = $this->getCategoryCollection();
        $categories->setStore($store);

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories as $category) {
            $this->writeCategory($writer, $category);
        }

        $writer->endElement(); // Categories
    }

    /** 
     * @param XMLWriter $writer
     * @param Group $storeGroup
     */
    public function processCategoriesForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        $writer->startElement('Categories');

        $categories = $this->getCategoryCollection();

        $locales = $this->getLocales($storeGroup->getStoreIds());

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories as $category) {
            $this->writeCategory($writer, $category, $storeGroup->getDefaultStoreId(), $locales);
        }

        $writer->endElement(); // Categories        
    }

    /** 
     * @param XMLWriter $writer
     * @param Website $website
     */
    public function processCategoriesForWebsite(XMLWriter $writer, Website $website)
    {
        $writer->startElement('Categories');

        $categories = $this->getCategoryCollection();

        $locales = $this->getLocales($website->getStoreIds());

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories as $category) {
            $this->writeCategory($writer, $category, $website->getDefaultStore()->getId(), $locales);
        }

        $writer->endElement(); // Categories
    }

    /** 
     * @param XMLWriter $writer
     */
    public function processCategoriesForGlobal(XMLWriter $writer)
    {
        $writer->startElement('Categories');

        $categories = $this->getCategoryCollection();

        $storesList = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        $stores = [];
        /** @var StoreInterface $store */
        foreach($storesList as $store) {
            $stores[] = $store->getId();
        }
        $locales = $this->getLocales($stores);

        /** @var \Magento\Catalog\Model\Category $category */
        foreach($categories as $category) {
            $this->writeCategory($writer, $category, 0, $locales);
        }

        $writer->endElement(); // Categories
    }

    /**
     * @param XMLWriter $writer
     * @param \Magento\Catalog\Model\Category $category
     * @param int $storeId
     * @param mixed $locales
     */
    protected function writeCategory(XMLWriter $writer, \Magento\Catalog\Model\Category $category, $storeId = 0, $locales = false)
    {
        $localizedData = [];
        if(is_array($locales) && count($locales)) {
            foreach($locales as $locale => $localeStoreId) {
                $category->setStoreId($localeStoreId)->load($category->getId());
                $url = $this->getStoreUrl($category, $localeStoreId);

                $localizedData[$locale] = [
                    'Name' => $category->getName(),
                    'CategoryPageUrl' => $url,
                    'ImageUrl' => $category->getImageUrl()
                ];
            }
        }

        $category->setStoreId($storeId)->load($category->getId());
        /** If not using entity_id for categories, return if no valid url path found */
        $categoryId = $this->getCategoryId($category, $storeId);
        if($categoryId == '') return;

        $writer->startElement('Category');

        $writer->writeElement('ExternalId', $categoryId);
        $writer->writeElement('Name', $category->getName(), true);
        if(count($localizedData)) {
            $writer->startElement('Names');
            foreach($localizedData as $locale => $data) {
                if(isset($data['Name']) && !empty($data['Name'])) {
                    $writer->startElement('Name');
                    $writer->writeAttribute('locale', $locale);
                    $writer->writeRaw($data['Name'], true);
                    $writer->endElement();
                }
            }
            $writer->endElement(); // Names
        }

        $writer->writeElement('CategoryPageUrl', $category->getUrl());
        if(count($localizedData)) {
            $writer->startElement('CategoryPageUrls');
            foreach($localizedData as $locale => $data) {
                if(isset($data['CategoryPageUrl']) && !empty($data['CategoryPageUrl'])) {
                    $writer->startElement('CategoryPageUrl');
                    $writer->writeAttribute('locale', $locale);
                    $writer->writeRaw($data['CategoryPageUrl']);
                    $writer->endElement();
                }
            }
            $writer->endElement(); // CategoryPageUrls
        }

        $writer->writeElement('ImageUrl', $category->getImageUrl());
        if(count($localizedData)) {
            $writer->startElement('ImageUrls');
            foreach($localizedData as $locale => $data) {
                if(isset($data['ImageUrl']) && !empty($data['ImageUrl'])) {
                    $writer->startElement('ImageUrl');
                    $writer->writeAttribute('locale', $locale);
                    $writer->writeRaw($data['ImageUrl']);
                    $writer->endElement();
                }
            }
            $writer->endElement(); // ImageUrls
        }


        $writer->endElement(); // Category

    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @param mixed $storeId
     * @return string
     */
    protected function getStoreUrl(\Magento\Catalog\Model\Category $category, $storeId)
    {
        $storeCode = $this->objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore($storeId)->getCode();

        $urlInstance = $this->urlFactory->create();

        $originalUrl = $category->getUrlPath();

        $url = $urlInstance
            ->setScope($storeId)
            ->addQueryParams(['___store' => $storeCode])
            ->getUrl($originalUrl);

        return $url;
    }

    /**
     * Get the uniquely identifying category ID for a catalog category.
     *
     * This is the unique, category or subcategory ID (duplicates are unacceptable).
     * This ID should be stable: it should not change for the same logical category even
     * if the category's name changes.
     *
     * @static
     * @param  \Magento\Catalog\Model\Category $category a reference to a catalog category object
     * @param int $storeId
     * @return string The unique category ID to be used with Bazaarvoice
     */
    protected function getCategoryId($category, $storeId = null)
    {
        // Check config setting to see if we should use Magento category id
        $useUrlPath = $this->helper->getConfig('feeds/category_id_use_url_path', $storeId);
        $useUrlPath = (strtoupper($useUrlPath) == 'TRUE' || $useUrlPath == '1');
        if(!$useUrlPath) {
            return $category->getId();
        }
        else {
            // Generate a unique id based on category path
            // Start with url path
            $rawCategoryId = $category->getUrlPath();
            // Replace slashes with dashes in url path
            $rawCategoryId = str_replace('/', '-', $rawCategoryId);
            // Replace any illegal characters
            return $this->helper->replaceIllegalCharacters($rawCategoryId);
        }
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    protected function getCategoryCollection()
    {
        $factory = $this->categoryFactory->create();

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
        $categories = $factory->getCollection();
        $categories
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image');

        return $categories;
    }

}