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

namespace Bazaarvoice\Connector\Model\Indexer;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Setup\Exception;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\Store\Model\Group;

/**
 * Class Flat
 * @package Bazaarvoice\Connector\Model\Indexer
 */
class Flat implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    protected $_helper;
    protected $_logger;
    protected $_indexer;
    protected $_generationScope;
    protected $_storeManager;
    protected $_objectManager;
    protected $_collectionFactory;
    protected $_resourceConnection;
    protected $_storeLocales;
    protected $_scopeConfig;

    /**
     * Indexer constructor.
     * @param Logger $logger
     * @param Data $helper
     * @param IndexerInterface $indexerInterface
     * @param ObjectManagerInterface $objectManager
     * @param Collection\Factory $collectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        IndexerInterface $indexerInterface,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Collection\Factory $collectionFactory,
        ResourceConnection $resourceConnection,
	    ScopeConfigInterface $scopeConfig
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->_indexer = $indexerInterface->load('bazaarvoice_product');
        $this->_collectionFactory = $collectionFactory;
        $this->_resourceConnection = $resourceConnection;
        $this->_scopeConfig = $scopeConfig;

        $this->_generationScope = $helper->getConfig('feeds/generation_scope');

        /** @var Store $store */
        switch ($this->_generationScope) {
            case Scope::STORE_VIEW:
                $stores = $this->_storeManager->getStores();
                $defaultStore = null;
                /** @var Store $store */
                foreach ($stores as $store) {
	                if($this->_helper->canSendFeed($store->getId())) {
		                $localeCode = $this->_helper->getConfig( 'general/locale', $store->getId() );
		                $this->_storeLocales[ $store->getId() ][ $localeCode ] = $store;
	                }
                }
                break;
            case Scope::WEBSITE:
	            $websites = $this->_storeManager->getWebsites();
	            /** @var Website $website */
	            foreach ($websites as $website) {
		            $defaultStore = $website->getDefaultStore();
		            $this->_storeLocales[$defaultStore->getId()] = array();
		            /** @var Store $localeStore */
		            foreach ($website->getStores() as $localeStore) {
			            if($this->_helper->canSendFeed($localeStore->getId())) {
				            $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );
				            $this->_storeLocales[ $defaultStore->getId() ][ $localeCode ] = $localeStore;
			            }
		            }
		            $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
		            $this->_storeLocales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
	            }
	            break;
            case Scope::SCOPE_GLOBAL:
                $websites = $this->_storeManager->getWebsites();
	            $defaultStore = null;
                /** @var Website $website */
                foreach ($websites as $website) {
                	if(!$defaultStore) {
		                $defaultStore = $website->getDefaultStore();
		                $this->_storeLocales[ $defaultStore->getId() ] = array();
	                }
                    /** @var Store $localeStore */
                    foreach ($website->getStores() as $localeStore) {
                    	if($this->_helper->canSendFeed($localeStore->getId())) {
		                    $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );
		                    $this->_storeLocales[ $defaultStore->getId() ][ $localeCode ] = $localeStore;
	                    }
                    }
                    $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
                    $this->_storeLocales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
                }
                break;
            case Scope::STORE_GROUP:
                $groups = $this->_storeManager->getGroups();
                /** @var Group $group */
                foreach ($groups as $group) {
                    $defaultStore = $group->getDefaultStore();
                    $this->_storeLocales[$defaultStore->getId()] = array();
                    /** @var Store $localeStore */
                    foreach ($group->getStores() as $localeStore) {
	                    if($this->_helper->canSendFeed($localeStore->getId())) {
		                    $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );
		                    $this->_storeLocales[ $defaultStore->getId() ][ $localeCode ] = $localeStore;
	                    }
                    }
                    $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
                    $this->_storeLocales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
                }
                break;
        }
    }

	/**
	 * Check if flat tables are enabled
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function canIndex() {
	    if ($this->_scopeConfig->getValue('catalog/frontend/flat_catalog_product') == false
	        || $this->_scopeConfig->getValue('catalog/frontend/flat_catalog_category') == false)
		    throw new Exception('Bazaarvoice Product feed requires Catalog Flat Tables to be enabled. Please check your Store Config.');
	    return true;
    }

	/**
	 * @return mixed
	 * @throws Exception
	 */
    public function executeFull()
    {
	    $this->canIndex();
        $this->_logger->info('Full Product Feed Index');
        try {
            /** @var Collection $incompleteIndex */
            $incompleteIndex = $this->_collectionFactory->create()->addFieldToFilter('version_id', 0);

            if ($incompleteIndex->count() == 0) {
                $msg = __('Bazaarvoice Product Feed Index has been flushed for rebuild.');
                $this->flushIndex();
            } else {
                $msg = $this->execute(array());
            }

            $this->_logger->info($msg);

        } Catch (\Exception $e) {
            $this->_logger->err($e->getMessage()."\n".$e->getTraceAsString());
        }
        return true;
    }

	/**
	 * Update a batch of index rows
	 *
	 * @param \int[] $ids
	 *
	 * @return mixed
	 * @throws Exception
	 */
    public function execute($ids = array())
    {
	    $this->canIndex();
        try {
            $this->_logger->info('Partial Product Feed Index');

            if (empty($ids))
                $ids = $this->_collectionFactory->create()->addFieldToFilter('version_id', 0)->getColumnValues('product_id');

            $this->_logger->info('Found '.count($ids).' products to update.');

            /** Break ids into pages */
            $productIdSets = array_chunk($ids, 50);

            /** Time throttling */
            $limit = ($this->_helper->getConfig('feeds/limit') * 60) - 10;
            $stop = time() + $limit;
            $counter = 0;
            do {
                if (time() > $stop) break;

                $productIds = array_pop($productIdSets);
                if (count($productIds) == 0) break;

                $this->_logger->debug('Updating product ids '.implode(',', $productIds));

                $this->reindexProducts($productIds);
                $counter += count($productIds);
            } while (1);

            if ($counter) {
                if ($counter < count($ids)) {
                    $changelogTable = $this->_resourceConnection->getTableName('bazaarvoice_product_cl');
                    $indexTable = $this->_resourceConnection->getTableName('bazaarvoice_index_product');
                    $this->_resourceConnection->getConnection('core_write')
                        ->query("INSERT INTO `$changelogTable` (`entity_id`) SELECT `product_id` FROM `$indexTable` WHERE `version_id` = 0;");
                }
                $this->logStats();
            }
        } Catch (\Exception $e) {
            $this->_logger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }
        return true;
    }

    /**
     * Get index data using flat tables
     * @param array $productIds
     * @throws \Exception
     */
    protected function reindexProducts($productIds)
    {

        switch ($this->_generationScope) {
            case Scope::SCOPE_GLOBAL:
            case Scope::WEBSITE:
                $websites = $this->_storeManager->getWebsites();
                /** @var \Magento\Store\Model\Website $website */
                foreach ($websites as $website) {
                    $defaultStore = $website->getDefaultStore();
                    if ($defaultStore->getId()) {
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    } else {
                        throw new \Exception('Website %s has no default store!', $website->getCode());
                    }
                    if($this->_generationScope == Scope::SCOPE_GLOBAL) break;
                }
                break;
            case Scope::STORE_GROUP:
                $groups = $this->_storeManager->getGroups();
                /** @var \Magento\Store\Model\Group $group */
                foreach ($groups as $group) {
                    $defaultStore = $group->getDefaultStore();
                    if ($defaultStore->getId()) {
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    } else {
                        throw new \Exception('Store Group %s has no default store!', $group->getName());
                    }
                }
                break;
            case Scope::STORE_VIEW:
                $stores = $this->_storeManager->getStores();
                /** @var \Magento\Store\Model\Store $store */
                foreach ($stores as $store) {
                    if ($store->getId()) {
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
	 * @throws Exception
	 * @throws \Zend_Db_Statement_Exception
	 */
    protected function flushIndex()
    {
	    $this->canIndex();
        /** Set indexer to use mview */
        $this->_indexer->setScheduled(true);

        $writeAdapter = $this->_resourceConnection->getConnection('core_write');

        /** Flush all old data */
        $indexTable = $this->_resourceConnection->getTableName('bazaarvoice_index_product');
        $writeAdapter->truncateTable($indexTable);
        $changelogTable = $this->_resourceConnection->getTableName('bazaarvoice_product_cl');
        $writeAdapter->truncateTable($changelogTable);

        /** Setup dummy rows */
        $productTable = $this->_resourceConnection->getTableName('catalog_product_entity');
        $writeAdapter->query("INSERT INTO `$indexTable` (`product_id`, `version_id`) SELECT `entity_id`, '0' FROM `$productTable`;");
        $writeAdapter->query("INSERT INTO `$changelogTable` (`entity_id`) SELECT `entity_id` FROM `$productTable`;");

        /** Reset mview version */
        $mviewTable = $this->_resourceConnection->getTableName('mview_state');
        $writeAdapter->query("UPDATE `$mviewTable` SET `version_id` = NULL, `status` = 'idle' WHERE `view_id` = 'bazaarvoice_product';");
        $indexCheck = $writeAdapter
            ->query("SELECT COUNT(1) indexIsThere FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE table_schema=DATABASE() AND table_name='{$changelogTable}' AND index_name='entity_id';");
        $indexCheck = $indexCheck->fetchObject();
        if ($indexCheck->indexIsThere == 0)
            $writeAdapter->query("ALTER TABLE `{$changelogTable}` ADD INDEX (`entity_id`);");

    }


    /**
     * Mass update process, uses flat tables.
     *
     * @param array $productIds
     * @param int|Store $store
     * @return bool
     * @throws \Exception
     */
    public function reindexProductsForStore($productIds, $store)
    {
        /** Check for scope change */
        if ($this->hasBadScopeIndex()) {
            $this->_logger->info('Index entries found with wrong scope. This usually means scope has changed in admin. Flagging entire index for rebuild.');
            $this->executeFull();
            return false;
        }

        /** @var \Bazaarvoice\Connector\Model\Index $index */
        $index = $this->_objectManager->get('\Bazaarvoice\Connector\Model\Index');
        if (is_int($store))
            $store = $this->_objectManager->get('\Magento\Store\Model\Store')->load($store);
        $storeId = $store->getId();

        /** Database Resources */
        $res = $this->_resourceConnection;
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
                'visibility' => 'p.visibility',
                'bv_feed_exclude' => 'bv_feed_exclude'
            ));

        /** parents */
        $select
            ->joinLeft(
                array('pp' => $res->getTableName('catalog_product_super_link')),
                'pp.product_id = p.entity_id', '')
            ->joinLeft(
                array('parent' => $res->getTableName('catalog_product_flat') . '_' . $storeId),
                'pp.parent_id = parent.entity_id',
                array(
                    'family' => 'GROUP_CONCAT(DISTINCT parent.sku SEPARATOR "|")',
                    'parent_image' => 'small_image'
                ))
            ->joinLeft(
                array('cp' => $res->getTableName('catalog_category_product_index')),
                "cp.product_id = p.entity_id AND cp.store_id = {$storeId}", '')
            ->joinLeft(
                array('cpp' => $res->getTableName('catalog_category_product_index')),
                "cpp.product_id = parent.entity_id AND cpp.store_id = {$storeId}", '');

        /** urls */
        $select
            ->joinLeft(
                array('url' => $res->getTableName('url_rewrite')),
                "url.entity_type = 'product'
                AND url.metadata IS NULL
                AND url.entity_id = p.entity_id
                AND url.store_id = {$storeId}",
                array('product_page_url' => 'request_path'))
            ->joinLeft(
                array('parent_url' => $res->getTableName('url_rewrite')),
                "parent_url.entity_type = 'product'
                AND parent_url.metadata IS NULL
                AND parent_url.entity_id = parent.entity_id
                AND parent_url.store_id = {$storeId}",
                array('parent_url' => 'request_path'));

        /** category */
        if ($this->_helper->getConfig('feeds/category_id_use_url_path', $storeId)) {
            $select->joinLeft(
                array('cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'cat.entity_id = cp.category_id AND cat.level > 1',
                array('category_external_id' => 'max(cat.url_path)'));
            $select->joinLeft(
                array('parent_cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'parent_cat.entity_id = cpp.category_id AND parent_cat.level > 1',
                array('parent_category_external_id' => 'max(parent_cat.url_path)'));
        } else {
            $select->joinLeft(
                array('cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'cat.entity_id = cp.category_id AND cat.level > 1',
                array('category_external_id' => 'max(cat.entity_id)'));
            $select->joinLeft(
                array('parent_cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'parent_cat.entity_id = cpp.category_id AND parent_cat.level > 1',
                array('parent_category_external_id' => 'max(parent_cat.entity_id)'));
        }

        /** Locale Data */
        $localeColumns = array('name' => 'name', 'description' => 'short_description', 'image_url' => 'small_image');
        if (isset($this->_storeLocales[$storeId])) {
            /** @var Store $localeStore */
            foreach ($this->_storeLocales[$storeId] as $locale => $localeStore) {
                if ($localeStore->getId() == $storeId) {
                    $columns = array();
                    foreach ($localeColumns as $dest => $source)
                        $columns["{$locale}|{$dest}"] = 'p.' . $source;
                    $columns["{$locale}|product_page_url"] = 'url.request_path';
                    $columns["{$locale}|parent_url"] = 'parent_url.request_path';
                    $columns["{$locale}|parent_image"] = 'parent.small_image';
                    $select->columns($columns);
                } else {
                    $columns = array();
                    foreach ($localeColumns as $dest => $source)
                        $columns["{$locale}|{$dest}"] = "{$locale}.{$source}";

                    $select
                        ->joinLeft(
                            array($locale => $res->getTableName('catalog_product_flat') . '_' . $localeStore->getId()),
                            $locale . '.entity_id = p.entity_id',
                            $columns)
                        ->joinLeft(
                            array("{$locale}_parent" => $res->getTableName('catalog_product_flat') . '_' . $localeStore->getId()),
                            "pp.parent_id = {$locale}_parent.entity_id",
                            array("{$locale}|parent_image" => 'small_image'))
                        ->joinLeft(
                            array("{$locale}_url" => $res->getTableName('url_rewrite')),
                            "{$locale}_url.entity_type = 'product'
                            AND {$locale}_url.metadata IS NULL
                            AND {$locale}_url.entity_id = p.entity_id
                            AND {$locale}_url.store_id = {$localeStore->getId()}",
                            array("{$locale}|product_page_url" => 'request_path'))
                        ->joinLeft(
                            array("{$locale}_parent_url" => $res->getTableName('url_rewrite')),
                            "{$locale}_parent_url.entity_type = 'product'
                            AND {$locale}_parent_url.metadata IS NULL
                            AND {$locale}_parent_url.entity_id = {$locale}_parent.entity_id
                            AND {$locale}_parent_url.store_id = {$localeStore->getId()}",
                            array("{$locale}|parent_url" => 'request_path'));
                }
            }
        }

        /** Brands and other Attributes */
        $columnResults = $read->query('DESCRIBE `' . $res->getTableName('catalog_product_flat') . '_' . $storeId . '`;');
        $flatColumns = array();
        while ($row = $columnResults->fetch()) {
            $flatColumns[] = $row['Field'];
        }
        $brandAttr = $this->_helper->getConfig('feeds/brand_code', $storeId);
        if ($brandAttr) {
            if (in_array($brandAttr, $flatColumns)) {
                $select->columns(array('brand_external_id' => $brandAttr));
            }
        }
        foreach ($index->customAttributes as $label) {
            $code = strtolower($label);
            $attr = $this->_helper->getConfig("feeds/{$code}_code", $storeId);
            if ($attr) {
                if (in_array("{$attr}_value", $flatColumns)) {
                    $this->_logger->debug("using {$attr}_value for {$code}");
                    $select->columns(array("{$code}s" => "{$attr}_value"));
                } else if (in_array($attr, $flatColumns)) {
                    $this->_logger->debug("using {$attr} for {$code}");
                    $select->columns(array("{$code}s" => $attr));
                }
            }
        }

        /** Version */
        $select->joinLeft(
            array('cl' => $res->getTableName('bazaarvoice_product_cl')),
            'cl.entity_id = p.entity_id',
            array('version_id' => 'MAX(cl.version_id)'));

        $select->where('p.entity_id IN(?)', $productIds)->group('p.entity_id');

        /** $this->_logger->debug($select->__toString()); */

        try {
            $rows = $select->query();
        } Catch (\Exception $e) {
            $this->_logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            return true;
        }

        /** Get placeholders */
        $placeholders = $this->getPlaceholderUrls($storeId);

        /** Iterate through results, clean up values, and write index. */
        while (($indexData = $rows->fetch()) !== false) {

            $this->_logger->debug('Processing product '.$indexData['product_id']);

            foreach ($indexData as $key => $value) {
                if (strpos($key, '|') !== false) {
                    $newKey = explode('|', $key);
                    if (strlen($value))
                        $indexData['locale_' . $newKey[1]][$newKey[0]] = $value;
                    unset($indexData[$key]);
                }
                if (strpos($value, '|') !== false) {
                    $indexData[$key] = $this->_helper->jsonEncode(explode('|', $value));
                }
            }

            $indexData['status'] = $indexData[ProductFeed::INCLUDE_IN_FEED_FLAG] ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;

	        if($indexData['product_type'] == Configurable::TYPE_CODE)
	        	$indexData['family'] = array($indexData['external_id']);
	        else if (!empty($indexData['family']) && !is_array($indexData['family']))
                $indexData['family'] = array($indexData['family']);

            if ($this->_helper->getConfig('feeds/category_id_use_url_path', $storeId)) {
                $indexData['category_external_id'] = str_replace('/', '-', $indexData['category_external_id']);
                $indexData['category_external_id'] = str_replace('.html', '', $indexData['category_external_id']);
                $indexData['category_external_id'] = $this->_helper->replaceIllegalCharacters($indexData['category_external_id']);
            }

            /** Use parent URLs/categories if appropriate */
            if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                $this->_logger->debug('Not visible');
                if (!empty($indexData['parent_url'])) {
                    $indexData['product_page_url'] = $indexData['parent_url'];
                    $this->_logger->debug('Using Parent URL');
                    if (isset($indexData['locale_product_page_url'])
                        && is_array($indexData['locale_product_page_url'])) {
                        foreach ($indexData['locale_product_page_url'] as $locale => $localeUrl) {
                            if (!empty($indexData['locale_parent_url'][$locale])) {
                                $indexData['locale_product_page_url'][$locale] = $indexData['locale_parent_url'][$locale];
                            }
                        }
                    }
                } else {
                    $this->_logger->debug('Product marked disabled because no parent URL found');
                    $indexData['status'] = Status::STATUS_DISABLED;
                }
                if(!empty($indexData['parent_category_external_id'])) {
                    $indexData['category_external_id'] = $indexData['parent_category_external_id'];
                    $this->_logger->debug('Using Parent Category');
                }
            }

            /** Use parent image if appropriate */
            if ($indexData['image_url'] == '' || $indexData['image_url'] == 'no_selection') {
                if (!empty($indexData['parent_image'])) {
                    $indexData['image_url'] = $indexData['parent_image'];
                    $this->_logger->debug('Using Parent image');
                    if (isset($indexData['locale_image_url'])
                        && is_array($indexData['locale_image_url'])) {
                        foreach ($indexData['locale_image_url'] as $locale => $localeUrl) {
                            if (!empty($indexData['locale_parent_image'][$locale]))
                                $indexData['locale_image_url'][$locale] = $indexData['locale_parent_image'][$locale];
                            else
                                unset($indexData['locale_image_url'][$locale]);
                        }
                    }
                } else {
                    $this->_logger->debug('Product has no parent and no image');
                    if (isset($placeholders['default']))
                        $indexData['image_url'] = $placeholders['default'];
                    foreach ($this->_storeLocales[$storeId] as $locale => $storeLocale) {
                        if (isset($placeholders[$locale]))
                            $indexData['locale_image_url'][$locale] = $placeholders[$locale];
                    }
                }
            }

            if ($indexData['category_external_id'] == '') {
                $indexData['status'] = Status::STATUS_DISABLED;
                $this->_logger->info('Product marked disabled because no category found.');
            } else {
                $this->_logger->debug("Category '{$indexData['category_external_id']}'");
            }

            /** Add Store base to URLs */
            $indexData['product_page_url'] = $this->getStoreUrl($store->getBaseUrl(), $indexData['product_page_url']);
            if (isset($indexData['locale_product_page_url']) && is_array($indexData['locale_product_page_url'])) {
                /** @var Store $storeLocale */
                foreach ($this->_storeLocales[$storeId] as $locale => $storeLocale) {
                    if (isset($indexData['locale_product_page_url'][$locale]))
                        $indexData['locale_product_page_url'][$locale] = $this->getStoreUrl(
                            $storeLocale->getBaseUrl(),
                            $indexData['locale_product_page_url'][$locale],
                            $storeLocale->getCode(),
                            $store->getBaseUrl());
                }
            }
            $this->_logger->debug("URL {$indexData['product_page_url']}");
            if(isset($indexData['locale_product_page_url']))
                $this->_logger->debug($indexData['locale_product_page_url']);

            /** Add Store base to images */
            if (substr($indexData['image_url'], 0, 4) != 'http') {
                $indexData['image_url'] = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $indexData['image_url'];
                if (isset($indexData['locale_image_url']) && is_array($indexData['locale_image_url'])) {
                    /** @var Store $storeLocale */
                    foreach ($this->_storeLocales[$storeId] as $locale => $storeLocale) {
                        if (isset($indexData['locale_image_url'][$locale]))
                            $indexData['locale_image_url'][$locale] =
                                $storeLocale->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                                . 'catalog/product'
                                . $indexData['locale_image_url'][$locale];
                    }
                }
            }
            $this->_logger->debug("Image {$indexData['image_url']}");

            $indexData['external_id'] = $this->_helper->getProductId($indexData['external_id']);
            $indexData['scope'] = $this->_generationScope;
            $indexData['store_id'] = $storeId;

            foreach ($indexData as $key => $value) {
                if (is_array($value))
                    $indexData[$key] = $this->_helper->jsonEncode($value);
            }

            $index = $this->_objectManager->create('\Bazaarvoice\Connector\Model\Index')->loadByStore($indexData['product_id'], $indexData['store_id']);

            if ($index->getId())
                $indexData['entity_id'] = $index->getId();
            else
                $indexData['entity_id'] = null;

            if (count(array_diff($indexData, $index->getData()))) {
                $index->setData($indexData);
                $index->save();
            }
            $this->_logger->debug('Product Indexed');

            $index->clearInstance();
            unset($indexData);
        }
        return true;
    }

    /**
     * @param string $storeUrl
     * @param string $urlPath
     * @param string|null $storeCode
     * @param string|null $defaultUrl
     * @return string string
     */
    protected function getStoreUrl($storeUrl, $urlPath, $storeCode = null, $defaultUrl = null)
    {
        $url = $storeUrl . $urlPath;

        if ($defaultUrl && $storeUrl == $defaultUrl) {
            $url .= '?___store=' . $storeCode;
        }

        return $url;
    }

    /**
     * @param array $productIds
     */
    protected function _purgeUnversioned($productIds)
    {
        /** Database Resources */
        $write = $this->_resourceConnection->getConnection('core_write');

        $indexTable = $this->_resourceConnection->getTableName('bazaarvoice_index_product');

        $delete = $write->deleteFromSelect($write->select()->from($indexTable)->where('product_id IN(?)', $productIds)->where('store_id = 0'), $indexTable);
        $write->query($delete);
    }

	/**
	 * @throws \Zend_Db_Statement_Exception
	 */
	protected function logStats()
    {
        /** @var Select $select */
        $select = $this->_resourceConnection->getConnection('core_read')
            ->select()
            ->from(array(
                'source' => $this->_resourceConnection->getTableName('bazaarvoice_index_product')));

        $select->columns(array('store_id', 'total' => 'count(*)'));
        $select->group('store_id');
        $result = $select->query();

        while ($row = $result->fetch()) {
            if ($row['store_id'] == 0)
                $this->_logger->debug("{$row['total']} Products left to Index");
            else
                $this->_logger->debug("{$row['total']} Products Indexed for Store {$row['store_id']}");
        }
    }

    /**
     * Required by interface but never called as far as I can tell
     * @param array|\int[] $ids
     * @return mixed
     */
    public function executeList(array $ids)
    {
        return true;
    }

    /**
     * Required by interface but never called as far as I can tell
     * @param int $id
     * @return mixed
     */
    public function executeRow($id)
    {
        return true;
    }

    /**
     * @param int $storeId
     * @return array
     */
    public function getPlaceholderUrls($storeId)
    {
        /** @var Image $imageHelper */
        $imageHelper = $this->_objectManager->get('\Magento\Catalog\Helper\Image');
        /** @var \Magento\Framework\View\Asset\Repository $assetRepo */
        $assetRepo = $this->_objectManager->get('\Magento\Framework\View\Asset\Repository');
        /** @var \Magento\Framework\View\DesignInterface $design */
        $design = $this->_objectManager->create('\Magento\Framework\View\DesignInterface');

        $placeholders = array();
        /**
         * @var string $locale
         * @var Store $localeStore
         */
        foreach ($this->_storeLocales[$storeId] as $locale => $localeStore) {
            $themeId = $design->getConfigurationDesignTheme('frontend', ['store' => $localeStore->getId()]);
            /** @var \Magento\Theme\Model\Theme $theme */
            $theme = $this->_objectManager->create('\Magento\Theme\Model\Theme')->load($themeId);
            $assetParams = array(
                'area' => 'frontend',
                'theme' => $theme->getThemePath(),
                'locale' => $locale
            );
            if ($localeStore->getId() == $storeId) {
                $placeholders['default'] = $assetRepo->createAsset($imageHelper->getPlaceholder('image'), $assetParams)->getUrl();
            } else {
                $placeholders[$locale] = $assetRepo->createAsset($imageHelper->getPlaceholder('image'), $assetParams)->getUrl();
            }

        }
        return $placeholders;
    }

	/**
	 * @return bool
	 * @throws \Zend_Db_Statement_Exception
	 */
	protected function hasBadScopeIndex()
    {
        /** @var Select $select */
        $select = $this->_resourceConnection->getConnection('core_read')
            ->select()
            ->from(array(
                'source' => $this->_resourceConnection->getTableName('bazaarvoice_index_product')));

        $select->columns(array('total' => 'count(*)'));
        $select->where("scope IS NOT NULL AND scope != '{$this->_generationScope}'");
        $result = $select->query();

        while ($row = $result->fetch()) {
            return $row['total'] > 0;
        }
        return true;
    }

}
