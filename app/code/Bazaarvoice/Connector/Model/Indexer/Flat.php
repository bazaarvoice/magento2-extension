<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright   (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package     Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\Indexer;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;
use Magento\Store\Model\Group;

class Flat implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $helper;
    protected $logger;
    protected $indexer;
    protected $generationScope;
    protected $objectManger;
    protected $collectionFactory;
    protected $resourceConnection;
    protected $storeLocales;
    
    /**
     * Indexer constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param IndexerInterface $indexerInterface
     * @param ObjectManagerInterface $objectManager
     * @param Collection\Factory $collectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(Logger $logger, Data $helper, IndexerInterface $indexerInterface, ObjectManagerInterface $objectManager, Collection\Factory $collectionFactory, ResourceConnection $resourceConnection)
    {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->objectManger = $objectManager;
        $this->indexer = $indexerInterface->load('bazaarvoice_product');
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;

        $this->generationScope = $helper->getConfig('feeds/generation_scope');

        /** @var Store $store */
        switch ($this->generationScope) {
            case Scope::WEBSITE:
                $websites = $this->objectManger->get('Magento\Store\Model\StoreManagerInterface')->getWebsites();
                /** @var Website $website */
                foreach($websites as $website) {
                    $defaultStore = $website->getDefaultStore()->getId();
                    $this->storeLocales[$defaultStore] = array();
                    /** @var Store $localeStore */
                    foreach($website->getStores() as $localeStore) {
                        $localeCode = $this->helper->getConfig('general/locale', $localeStore->getId());
                        $this->storeLocales[$defaultStore][$localeCode] = $localeStore;
                    }
                }
                break;
            case Scope::STORE_GROUP:
                $groups = $this->objectManger->get('Magento\Store\Model\StoreManagerInterface')->getGroups();
                /** @var Group $group */
                foreach($groups as $group) {
                    $defaultStore = $group->getDefaultStore()->getId();
                    $this->storeLocales[$defaultStore] = array();
                    /** @var Store $localeStore */
                    foreach($group->getStores() as $localeStore) {
                        $localeCode = $this->helper->getConfig('general/locale', $localeStore->getId());
                        $this->storeLocales[$defaultStore][$localeCode] = $localeStore;
                    }
                }
                break;
        }        
    }

    /**
     * @return mixed
     */
    public function executeFull()
    {
        $this->logger->info('Full Product Feed Index');
        try {
            /** @var Collection $incompleteIndex */
            $incompleteIndex = $this->collectionFactory->create()->addFieldToFilter('version_id', 0);

            if($incompleteIndex->count() == 0)
                $this->flushIndex();

            $this->execute(array());

        } Catch (\Exception $e) {
        	$this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }
            
    }

    /**
     * Update a batch of index rows
     *
     * @param \int[] $ids
     * @return mixed
     */
    public function execute($ids = array())
    {
        $this->logger->info('Partial Product Feed Index');

        if(empty($ids))
            $ids = $this->collectionFactory->create()->addFieldToFilter('version_id', 0)->getColumnValues('product_id');

        $this->logger->info('Found '.count($ids).' products to update.');

        // Break ids into pages
        $productIdSets = array_chunk($ids, 50);

        // Time throttling
        $limit = ($this->helper->getConfig('feeds/limit') * 60) - 10;
        $stop = time() + $limit;
        $counter = 0;
        do {
            if (time() > $stop) break;

            $productIds = array_pop($productIdSets);
            if(count($productIds) == 0) break;

            $this->logger->debug('Updating product ids '.implode(',', $productIds));

            $this->reindexProducts($productIds);
            $counter += count($productIds);
        } while(1);

        if($counter)
            $this->logger->debug("Processed $counter products");

    }

    /**
     * Get index data using flat tables
     * @param array $productIds
     * @throws \Exception
     */
    protected function reindexProducts($productIds)
    {

        switch ($this->generationScope) {
            case Scope::SCOPE_GLOBAL:
                /** @var Store $store */
                $store = $this->objectManger->get('Magento\Store\Model\Store')->load(0);
                // !TODO Figure out Global
                $this->reindexProductsForStore($productIds, $store);
                break;
            case Scope::WEBSITE:
                $websites = $this->objectManger->get('Magento\Store\Model\StoreManagerInterface')->getWebsites();
                /** @var \Magento\Store\Model\Website $website */
                foreach($websites as $website) {
                    $defaultStore = $website->getDefaultStore();
                    if($defaultStore->getId()){
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    } else {
                        throw new \Exception('Website %s has no default store!', $website->getCode());
                    }
                }
                break;
            case Scope::STORE_GROUP:
                $groups = $this->objectManger->get('Magento\Store\Model\StoreManagerInterface')->getGroups();
                /** @var \Magento\Store\Model\Group $group */
                foreach($groups as $group) {
                    $defaultStore = $group->getDefaultStore();
                    if($defaultStore->getId()){
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    } else {
                        throw new \Exception('Store Group %s has no default store!', $group->getName());
                    }
                }
                break;
            case Scope::STORE_VIEW:
                $stores = $this->objectManger->get('Magento\Store\Model\StoreManagerInterface')->getStores();
                /** @var \Magento\Store\Model\Store $store */
                foreach($stores as $store) {
                    if($store->getId()){
                        $this->reindexProductsForStore($productIds, $store);
                    } else {
                        throw new \Exception('Store %s not found!', $store->getCode());
                    }
                }
                break;
        }
        $this->_purgeUnversioned($productIds);
    }

    /**
     * Prepare for full reindex
     */
    protected function flushIndex()
    {
        /** Set indexer to use mview */
        $this->indexer->setScheduled(true);

        /** Flush all old data */
        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');
        $writeAdapter = $this->resourceConnection->getConnection('core_write');
        $writeAdapter->truncateTable($indexTable);

        /** Setup dummy rows */
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $writeAdapter->query("INSERT INTO `$indexTable` (`product_id`, `version_id`) SELECT `entity_id`, '0' FROM `$productTable`;");

    }


    /**
     * Mass update process, uses flat tables.
     *
     * @param array $productIds
     * @param int|Store $store
     * @throws \Exception
     */
    public function reindexProductsForStore($productIds, $store)
    {
        /** @var \Bazaarvoice\Connector\Model\Index $index */
        $index = $this->objectManger->get('\Bazaarvoice\Connector\Model\Index');
        if(is_int($store))
            $store = $this->objectManger->get('\Magento\Store\Model\Store')->load($store);
        $storeId = $store->getId();

        /** Database Resources */
        $res = $this->resourceConnection;
        $read = $res->getConnection('core_read');
        /** Core Data  */
        $select = $read->select()
            ->from(array('p' => $res->getTableName('catalog_product_flat') . '_' . $storeId), array(
                'name' => 'p.name',
                'product_type' => 'p.type_id',
                'product_id' => 'p.entity_id',
                'description' => 'p.short_description',
                'external_id' => 'p.sku',
                'image_url' => 'p.small_image',
                'visibility' => 'p.visibility'
            ))->joinLeft(array('pp' => $res->getTableName('catalog_product_super_link')), 'pp.product_id = p.entity_id', '')
            ->joinLeft(array('parent' => $res->getTableName('catalog_product_flat') . '_' . $storeId), 'pp.parent_id = parent.entity_id', array(
                'family' => 'GROUP_CONCAT(DISTINCT parent.sku SEPARATOR "|")',
                'parent_image' => 'small_image'
            ))
            ->joinLeft(array('cp' => $res->getTableName('catalog_category_product')), 'cp.product_id = p.entity_id OR cp.product_id = parent.entity_id', '');

        if($this->helper->getConfig('feeds/category_id_use_url_path', $storeId)){
            $select->columns(array('category_external_id' => 'cp.category_id'));
        } else {
            $select->joinLeft(array('cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId), 'cat.entity_id = cp.category_id', array(
                'category_external_id' => 'max(cat.url_path)'
            ));
        }

        /** Locale Data */
        $localeColumns = array('name' => 'name', 'description' => 'short_description', 'image_url' => 'small_image');
        if(isset($this->storeLocales[$storeId])) {
            /** @var Store $localeStore */
            foreach ($this->storeLocales[$storeId] as $locale => $localeStore) {
                if ($localeStore->getId() == $storeId) {
                    $columns = array();
                    foreach($localeColumns as $dest => $source)
                        $columns["{$locale}|{$dest}"] = 'p.' . $source;
//                    $columns["{$locale}|product_page_url"] = "url.request_path";
//                    $columns["{$locale}|parent_url"] = "parent_url.request_path";
                    $columns["{$locale}|parent_image"] = "parent.small_image";
                    $select->columns($columns);
                } else {
                    $columns = array();
                    foreach($localeColumns as $dest => $source)
                        $columns["{$locale}|{$dest}"] = "{$locale}.{$source}";

                    $select
                        ->joinLeft(array($locale => $res->getTableName('catalog_product_flat') . '_' . $localeStore->getId()), $locale . '.entity_id = p.entity_id', $columns)
                        ->joinLeft(array("{$locale}_parent" => $res->getTableName('catalog_product_flat') . '_' . $localeStore->getId()), "pp.parent_id = {$locale}_parent.entity_id", array(
                            "{$locale}|parent_image" => 'small_image'
                        ));
                }
            }
        }

        /** Brands and other Attributes */
        $columnResults = $read->query('DESCRIBE `' . $res->getTableName('catalog_product_flat') . '_' . $storeId . '`;');
        $flatColumns = array();
        while($row = $columnResults->fetch()) {
            $flatColumns[] = $row['Field'];
        }
        $brandAttr = $this->helper->getConfig("/bv_config/product_feed_brand_attribute_code", $storeId);
        if($brandAttr) {
            if(in_array($brandAttr, $flatColumns)) {
                $select->columns(array('brand_external_id' => 'brand'));
            }
        }
        foreach($index->customAttributes as $label) {
            $code = strtolower($label);
            $attr = $this->helper->getConfig("/bv_config/product_feed_{$code}_attribute_code", $storeId);
            if($attr) {
                if(in_array("{$attr}_value", $flatColumns)) {
                    $select->columns(array("{$code}s" => "{$attr}_value"));
                } else if(in_array($attr, $flatColumns)) {
                    $select->columns(array("{$code}s" => $attr));
                }
            }
        }

        /** Version */
        $select->joinLeft(array('cl' => $res->getTableName('bazaarvoice_product_cl')), 'cl.entity_id = p.entity_id', array('version_id' => 'MAX(cl.version_id)'));

        $select->where('p.entity_id IN(?)', $productIds)->group('p.entity_id');

        //$this->logger->debug($select->__toString());

        $rows = $select->query();

        $urlModel = $this->objectManger->get('\Magento\Framework\Url');
        /** @var \Magento\Catalog\Helper\Data $eHelper */
        $eHelper = $this->objectManger->get('\Magento\Catalog\Helper\Data');
        /** Iterate through results, clean up values, and write index. */
        while(($indexData = $rows->fetch()) !== false) {

            foreach ($indexData as $key => $value) {
                if (strpos($key, '|') !== false) {
                    $newKey = explode('|', $key);
                    $indexData['locale_' . $newKey[1]][$newKey[0]] = $value;
                    unset($indexData[$key]);
                }
                if (strpos($value, '|') !== false) {
                    $indexData[$key] = $this->helper->jsonEncode(explode('|', $value));
                }
            }

            if ($this->helper->getConfig('/feeds/category_id_use_url_path', $storeId) == false) {
                $indexData['category_external_id'] = str_replace('/', '-', $indexData['category_external_id']);
                $indexData['category_external_id'] = $this->helper->replaceIllegalCharacters($indexData['category_external_id']);
            }

            /** Use parent URLs if appropriate */
            if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                if (!empty($indexData['parent_url'])) {
                    $indexData['product_page_url'] = $indexData['parent_url'];
                    $this->logger->debug("Product Using Parent URL");
                    if (is_array($indexData['locale_product_page_url']) && count($indexData['locale_product_page_url'])) {
                        foreach ($indexData['locale_product_page_url'] as $locale => $localeUrl) {
                            if (!empty($indexData["locale_parent_url"][$locale])) {
                                $indexData['locale_product_page_url'][$locale] = $indexData["locale_parent_url"][$locale];
                            }
                        }
                    }
                } else {
                    $this->logger->debug("Not visible Product marked disabled because no parent found");
                    $indexData['status'] = Status::STATUS_DISABLED;
                }
            }

            /** Use parent image if appropriate */
            if ($indexData['image_url'] == '' || $indexData['image_url'] == 'no_selection') {
                if (!empty($indexData['parent_image'])) {
                    $indexData['image_url'] = $indexData['parent_image'];
                    $this->logger->debug("Product Using Parent image");
                    if (is_array($indexData['locale_image_url']) && count($indexData['locale_image_url'])) {
                        foreach ($indexData['locale_image_url'] as $locale => $localeUrl) {
                            if (!empty($indexData["locale_parent_image"][$locale]))
                                $indexData['locale_image_url'][$locale] = $indexData["locale_parent_image"][$locale];
                            else
                                unset($indexData['locale_image_url'][$locale]);
                        }
                    }
                } else {
                    $this->logger->debug("Product has no parent and no image");
                }
            }

            /** Add Store base to URLs */
//            $indexData['product_page_url'] = $urlModel->getDirectUrl($eHelper->getProductRequestPath($indexData['product_page_url'], $storeId), array('_store' => $storeId));
//            if (is_array($indexData['locale_product_page_url']) && count($indexData['locale_product_page_url'])) {
//                /** @var Store $storeLocale */
//                foreach ($this->storeLocales[$storeId] as $locale => $storeLocale) {
//                    if (isset($indexData['locale_product_page_url'][$locale]))
//                        $indexData['locale_product_page_url'][$locale] = $urlModel->getDirectUrl($eHelper->getProductRequestPath($indexData['locale_product_page_url'][$locale], $storeLocale->getId()), array('_store' => $storeLocale->getId()));
//                }
//            }

            /** Add Store base to images */
            $indexData['image_url'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog_product' . $indexData['image_url'];
            if (is_array($indexData['locale_image_url']) && count($indexData['locale_image_url'])) {
                /** @var Store $storeLocale */
                foreach ($this->storeLocales[$storeId] as $locale => $storeLocale) {
                    if (isset($indexData['locale_image_url'][$locale]))
                        $indexData['locale_image_url'][$locale] = $storeLocale->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog_product' . $indexData['locale_image_url'][$locale];
                }
            }

            $indexData['external_id'] = $this->helper->getProductId($indexData['external_id']);
            $indexData['scope'] = $this->generationScope;
            $indexData['store_id'] = $storeId;

            foreach ($indexData as $key => $value) {
                if (is_array($value))
                    $indexData[$key] = $this->helper->jsonEncode($value);
            }

            $index = $this->objectManger->create('\Bazaarvoice\Connector\Model\Index')->loadByStore($indexData['product_id'], $indexData['store_id']);

            if ($index->getId())
                $indexData['entity_id'] = $index->getId();
            else
                $indexData['entity_id'] = null;

            if (count(array_diff($indexData, $index->getData()))) {
                $index->setData($indexData);
                $index->save();
            }

            $index->clearInstance();
            unset($indexData);
        }
    }

    /**
     * @param array $productIds
     */
    protected function _purgeUnversioned($productIds)
    {
        /** Database Resources */
        $write = $this->resourceConnection->getConnection('core_write');

        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');

        $delete = $write->deleteFromSelect($write->select()->from($indexTable)->where('product_id IN(?)', $productIds)->where('store_id = 0'), $indexTable);
        $write->query($delete);
    }

    /**
     * Required by interface but never called as far as I can tell
     * @param array|\int[] $ids
     * @return mixed
     */
    public function executeList(array $ids){}

    /**
     * Required by interface but never called as far as I can tell
     * @param int $id
     * @return mixed
     */
    public function executeRow($id){}


}