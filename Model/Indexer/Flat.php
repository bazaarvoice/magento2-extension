<?php

namespace Bazaarvoice\Connector\Model\Indexer;

use Bazaarvoice\Connector\Api\Data\IndexInterfaceFactory;
use Bazaarvoice\Connector\Api\IndexRepositoryInterface;
use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\Product\Generic;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Bazaarvoice\Connector\Model\Index;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class Flat implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var \Bazaarvoice\Connector\Helper\Data
     */
    private $helper;
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $logger;
    /**
     * @var
     */
    private $indexer;
    /**
     * @var
     */
    private $generationScope;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection\Factory
     */
    private $bvIndexCollectionFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var
     */
    private $storeLocales;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var string
     */
    private $productIdField;
    /**
     * @var
     */
    private $mageVersion;
    /**
     * @var \Bazaarvoice\Connector\Api\Data\IndexInterfaceFactory
     */
    private $bvIndexFactory;
    /**
     * @var array
     */
    private $productIndexes;
    /**
     * @var \Bazaarvoice\Connector\Api\IndexRepositoryInterface
     */
    private $indexRepository;

    /**
     * Indexer constructor.
     *
     * @param Logger                                                $logger
     * @param Data                                                  $helper
     * @param IndexerInterface                                      $indexerInterface
     * @param ObjectManagerInterface                                $objectManager
     * @param StoreManagerInterface                                 $storeManager
     * @param Collection\Factory                                    $collectionFactory
     * @param ResourceConnection                                    $resourceConnection
     * @param ScopeConfigInterface                                  $scopeConfig
     * @param Generic                                               $feed
     * @param ProductMetadata                                       $productMetadata
     *
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterfaceFactory $bvIndexFactory
     *
     * @param \Bazaarvoice\Connector\Api\IndexRepositoryInterface   $indexRepository
     *
     * @throws \Exception
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        IndexerInterface $indexerInterface,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Collection\Factory $collectionFactory,
        ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig,
        Generic $feed,
        ProductMetadata $productMetadata,
        IndexInterfaceFactory $bvIndexFactory,
        IndexRepositoryInterface $indexRepository
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->indexer = $indexerInterface->load('bazaarvoice_product');
        $this->bvIndexCollectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
        $this->productIdField = $this->getProductIdFieldName();
        $this->mageVersion = $productMetadata->getVersion();
        $this->generationScope = $helper->getConfig('feeds/generation_scope');
        $this->storeLocales = $feed->getLocales();
        $this->bvIndexFactory = $bvIndexFactory;
        $this->indexRepository = $indexRepository;
    }

    /**
     * Check if flat tables are enabled
     *
     * @return bool
     */
    public function canIndex()
    {
        if ($this->scopeConfig->getValue('catalog/frontend/flat_catalog_product') == false
            || $this->scopeConfig->getValue('catalog/frontend/flat_catalog_category') == false) {
            $this->logger->error('Bazaarvoice Product feed requires Catalog Flat Tables to be enabled. Please check your Store Config.');
        }

        return true;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function executeFull()
    {
        /** @var Collection $incompleteIndex */

        $this->canIndex();
        $this->logger->debug('Full Product Feed Index');
        try {
            $incompleteIndex = $this->bvIndexCollectionFactory->create()->addFieldToFilter('version_id', 0);
            $indexHasBadScope = $this->hasBadScopeIndex();
            if ($incompleteIndex->count() == 0 || $indexHasBadScope) {
                if ($indexHasBadScope) {
                    $this->logger->debug('Index entries found with wrong scope. This usually means scope has changed in admin. Flagging entire index for rebuild.');
                }
                $this->flushIndex();
                $this->logger->debug(__('Bazaarvoice Product Feed Index has been flushed for rebuild.'));
            }
            $this->execute();
            $this->logger->debug(__('Bazaarvoice Product Feed Index is being rebuilt.'));
        } catch (\Exception $e) {
            $this->logger->err($e->getMessage()."\n".$e->getTraceAsString());
        }

        return true;
    }

    /**
     * Update a batch of index rows
     *
     * @param \int[] $ids
     *
     * @return mixed
     * @throws \Exception
     */
    public function execute($ids = array())
    {
        /** @var $idCollection \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection */

        $this->canIndex();
        try {
            $this->logger->debug('Partial Product Feed Index');

            if (empty($ids)) {
                $idCollection = $this->bvIndexCollectionFactory->create()->addFieldToFilter('version_id', 0);
                $idCollection->getSelect()->group('product_id');
                $idCollection->addFieldToSelect('product_id');
                $ids = $idCollection->getColumnValues('product_id');
            }

            $this->logger->debug('Found '.count($ids).' products to update.');

            /** Break ids into pages */
            $productIdSets = array_chunk($ids, 50);

            /** Time throttling */
            $limit = ($this->helper->getConfig('feeds/limit') * 60) - 10;
            $stop = time() + $limit;
            $counter = 0;
            do {
                if (time() > $stop) {
                    break;
                }

                $productIds = array_pop($productIdSets);
                if (
                    !is_array($productIds)
                    || count($productIds) == 0
                ) {
                    break;
                }

                $this->logger->debug('Updating product ids '.implode(',', $productIds));

                $this->reindexProducts($productIds);
                $counter += count($productIds);
            } while (1);

            if ($counter) {
                if ($counter < count($ids)) {
                    $changelogTable = $this->resourceConnection->getTableName('bazaarvoice_product_cl');
                    $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');
                    $this->resourceConnection->getConnection('core_write')
                        ->query("INSERT INTO `$changelogTable` (`entity_id`) SELECT `product_id` FROM `$indexTable` WHERE `version_id` = 0;");
                }
                $this->logStats();
            }
        } Catch (\Exception $e) {
            $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
        }

        return true;
    }

    /**
     * Get index data using flat tables
     *
     * @param array $productIds
     *
     * @throws \Exception
     */
    protected function reindexProducts($productIds)
    {
        switch ($this->generationScope) {
            case Scope::SCOPE_GLOBAL:
                $stores = $this->storeManager->getStores();
                ksort($stores);
                /** @var \Magento\Store\Model\Store $store */
                foreach ($stores as $store) {
                    if ($this->helper->canSendFeed($store->getId())) {
                        $this->reindexProductsForStore($productIds, $store);
                        break;
                    }
                }
                break;
            case Scope::WEBSITE:
                $websites = $this->storeManager->getWebsites();
                /** @var \Magento\Store\Model\Website $website */
                foreach ($websites as $website) {
                    $defaultStore = $website->getDefaultStore();
                    if ($defaultStore->getId()) {
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    } else {
                        throw new \Exception('Website %s has no default store!', $website->getCode());
                    }
                    if ($this->generationScope == Scope::SCOPE_GLOBAL) {
                        break;
                    }
                }
                break;
            case Scope::STORE_GROUP:
                $groups = $this->storeManager->getGroups();
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
                $stores = $this->storeManager->getStores();
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
     *
     * @throws \Exception
     * @throws \Zend_Db_Statement_Exception
     */
    protected function flushIndex()
    {
        $this->canIndex();
        /** Set indexer to use mview */
        $this->indexer->setScheduled(true);

        $writeAdapter = $this->resourceConnection->getConnection('core_write');

        /** Flush all old data */
        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');
        $writeAdapter->truncateTable($indexTable);
        $changelogTable = $this->resourceConnection->getTableName('bazaarvoice_product_cl');
        $writeAdapter->truncateTable($changelogTable);

        /** Setup dummy rows */
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $writeAdapter->query("INSERT INTO `$indexTable` (`product_id`, `version_id`) SELECT DISTINCT `entity_id`, '0' FROM `$productTable`;");
        $writeAdapter->query("INSERT INTO `$changelogTable` (`entity_id`) SELECT DISTINCT `entity_id` FROM `$productTable`;");

        /** Reset mview version */
        $mviewTable = $this->resourceConnection->getTableName('mview_state');
        $writeAdapter->query("UPDATE `$mviewTable` SET `version_id` = NULL, `status` = 'idle' WHERE `view_id` = 'bazaarvoice_product';");
        $indexCheck = $writeAdapter
            ->query("SELECT COUNT(1) indexIsThere FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE table_schema=DATABASE() AND table_name='{$changelogTable}' AND index_name='entity_id';");
        $indexCheck = $indexCheck->fetchObject();
        if ($indexCheck->indexIsThere == 0) {
            $writeAdapter->query("ALTER TABLE `{$changelogTable}` ADD INDEX (`entity_id`);");
        }
    }

    /**
     * Mass update process, uses flat tables.
     *
     * @param array     $productIds
     * @param int|Store $store
     *
     * @return bool
     * @throws \Exception
     */
    public function reindexProductsForStore($productIds, $store)
    {
        $this->productIndexes = [];
        $this->populateIndexStoreData($productIds, $store);
        $this->populateIndexLocaleData($productIds, $store);
        $this->saveProductIndexes();

        return true;
    }

    /**
     * @param $productIds
     * @param $store
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    private function populateIndexStoreData($productIds, $store)
    {
        if (is_int($store)) {
            $store = $this->objectManager->get('\Magento\Store\Model\Store')->load($store);
        }
        $storeId = $store->getId();

        /** Database Resources */
        $res = $this->resourceConnection;
        $read = $res->getConnection('core_read');
        $select = $this->getBaseSelect($read, $storeId, $res);
        $this->joinParent($select, $storeId, $res);

        if ($this->generationScope == Scope::SCOPE_GLOBAL) {
            $cppTable = $res->getTableName('catalog_category_product');
        } else {
            $cppTable = $res->getTableName("catalog_category_product_index_store{$storeId}");
        }

        if (version_compare($this->mageVersion, '2.2.5', '<')) {
            $select
                ->joinLeft(
                    array('cp' => $res->getTableName('catalog_category_product_index')),
                    "cp.product_id = p.entity_id AND cp.store_id = {$storeId}",
                    'category_id'
                )
                ->joinLeft(
                    array('cpp' => $res->getTableName('catalog_category_product_index')),
                    "cpp.product_id = parent.entity_id AND cpp.store_id = {$storeId}",
                    'category_id'
                );
        } else {
            $select
                ->joinLeft(
                    array('cp' => $cppTable),
                    "cp.product_id = p.entity_id",
                    "category_id"
                )
                ->joinLeft(
                    array('cpp' => $cppTable),
                    "cpp.product_id = parent.entity_id",
                    "category_id"
                );
        }
        $this->joinUrlRewrite($select, $storeId, $res);

        /** category */
        if ($this->helper->getConfig('feeds/category_id_use_url_path', $storeId)) {
            $select->joinLeft(
                array('cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'cat.entity_id = cp.category_id AND cat.level > 1',
                array('category_external_id' => 'max(cat.url_path)'));
            $select->joinLeft(
                array('parent_cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'parent_cat.entity_id = cpp.category_id AND parent_cat.level > 1',
                array('parent_category_external_id' => 'max(parent_cat.url_path)'));
            $select->joinLeft(
                array('bv_cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'bv_cat.entity_id = p.bv_category_id',
                array('bv_category_external_id' => 'bv_cat.url_path'));
            $select->joinLeft(
                array('bv_parent_cat' => $res->getTableName('catalog_category_flat').'_store_'.$storeId),
                'bv_parent_cat.entity_id = parent.bv_category_id',
                array('bv_parent_category_external_id' => 'bv_parent_cat.url_path'));
        } else {
            $select->joinLeft(
                array('cat' => $res->getTableName('catalog_category_entity')),
                'cat.entity_id = cp.category_id AND cat.level > 1',
                array('category_external_id' => 'max(cat.entity_id)'));
            $select->joinLeft(
                array('parent_cat' => $res->getTableName('catalog_category_entity')),
                'parent_cat.entity_id = cpp.category_id AND parent_cat.level > 1',
                array('parent_category_external_id' => 'max(parent_cat.entity_id)'));
            $select->joinLeft(
                array('bv_cat' => $res->getTableName('catalog_category_entity')),
                'bv_cat.entity_id = p.bv_category_id',
                array('bv_category_external_id' => 'bv_cat.entity_id'));
            $select->joinLeft(
                array('bv_parent_cat' => $res->getTableName('catalog_category_entity')),
                'bv_parent_cat.entity_id = parent.bv_category_id',
                array('bv_parent_category_external_id' => 'bv_parent_cat.entity_id'));
        }

        /** Brands and other Attributes */
        $columnResults = $read->query('DESCRIBE `'.$res->getTableName('catalog_product_flat').'_'.$storeId.'`;');
        $flatColumns = array();
        while ($row = $columnResults->fetch()) {
            $flatColumns[] = $row['Field'];
        }
        $brandAttr = $this->helper->getConfig('feeds/brand_code', $storeId);
        if ($brandAttr) {
            if (in_array($brandAttr, $flatColumns)) {
                $select->columns(array('brand_external_id' => $brandAttr));
            }
        }
        foreach (Index::CUSTOM_ATTRIBUTES as $label) {
            $code = strtolower($label);
            $attr = $this->helper->getConfig("feeds/{$code}_code", $storeId);
            if ($attr) {
                if (in_array("{$attr}_value", $flatColumns)) {
                    $this->logger->debug("using {$attr}_value for {$code}");
                    $select->columns(array("{$code}s" => "{$attr}_value"));
                } else {
                    if (in_array($attr, $flatColumns)) {
                        $this->logger->debug("using {$attr} for {$code}");
                        $select->columns(array("{$code}s" => $attr));
                    }
                }
            }
        }

        $bvFamiliesAttributeConfig = $this->getBvFamiliesAttributeConfig($storeId);
        if ($bvFamiliesAttributeConfig) {
            $this->logger->debug("using $bvFamiliesAttributeConfig for configurable product family data");
            $select->columns(array($bvFamiliesAttributeConfig));
        }

        /** Version */
        $select->joinLeft(
            array('cl' => $res->getTableName('bazaarvoice_product_cl')),
            'cl.entity_id = p.entity_id',
            array('version_id' => 'MAX(cl.version_id)'));

        $this->filterByProducts($select, $productIds);

        //$this->_logger->debug($select->__toString());

        try {
            $rows = $select->query();
        } catch (\Exception $e) {
            $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            throw new \Exception($e);
        }

        while (($indexData = $rows->fetch()) !== false) {
            $this->logger->debug('Processing product '.$indexData['product_id']);
            foreach ($indexData as $key => $value) {
                if (strpos($value, '||') !== false) {
                    $indexData[$key] = explode('||', $value);
                }
            }

            $indexData['status'] = $indexData[ProductFeed::INCLUDE_IN_FEED_FLAG] ? Status::STATUS_ENABLED
                : Status::STATUS_DISABLED;

            if ($indexData['product_type'] == Configurable::TYPE_CODE) {
                if ($bvFamiliesAttributeConfig && isset($indexData[$bvFamiliesAttributeConfig])) {
                    $indexData['family'] = array($indexData[$bvFamiliesAttributeConfig]);
                } else {
                    $indexData['family'] = array($indexData['external_id']);
                }
            } else {
                if (!empty($indexData['family'])) {
                    if (!is_array($indexData['family'])) {
                        $indexData['family'] = array($indexData['family']);
                    }
                }
            }

            $this->logger->debug('Family Info');
            $this->logger->debug($indexData['family']);

            /** categories */
            if ($indexData['bv_category_external_id']) {
                $indexData['category_external_id'] = $indexData['bv_category_external_id'];
            }
            if ($indexData['bv_parent_category_external_id']) {
                $indexData['parent_category_external_id'] = $indexData['bv_parent_category_external_id'];
            }

            /** Use parent URLs/categories if appropriate */
            if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                $this->logger->debug('Not visible');
                if (!empty($indexData['parent_url'])) {
                    $indexData['product_page_url'] = $indexData['parent_url'];
                    $this->logger->debug('Using Parent URL');
                } else {
                    $this->logger->debug('Product marked disabled because no parent URL found');
                    $indexData['status'] = Status::STATUS_DISABLED;
                }
                if (!empty($indexData['parent_category_external_id'])) {
                    $indexData['category_external_id'] = $indexData['parent_category_external_id'];
                    $this->logger->debug('Using Parent Category');
                }
            }

            $indexData['category_external_id'] = str_replace('/', '-', $indexData['category_external_id']);
            $indexData['category_external_id'] = str_replace('.html', '', $indexData['category_external_id']);
            $indexData['category_external_id']
                = $this->helper->replaceIllegalCharacters($indexData['category_external_id']);

            if ($indexData['category_external_id'] == '') {
                $indexData['status'] = Status::STATUS_DISABLED;
                $this->logger->debug('Product marked disabled because no category found.');
            } else {
                $this->logger->debug("Category '{$indexData['category_external_id']}'");
            }
            $standardUrl = static::getStandardUrl($indexData['product_id']);

            /** Add Store base to URLs */
            $indexData['product_page_url'] = $this->getStoreUrl($store->getBaseUrl(),
                $indexData['product_page_url'] == '' ? $standardUrl : $indexData['product_page_url']);
            $this->logger->debug("URL {$indexData['product_page_url']}");
            $indexData['image_url'] = $this->getImageUrl($store, $indexData);

            $this->logger->debug("Image {$indexData['image_url']}");

            $indexData['external_id'] = $this->helper->getProductId($indexData['external_id']);
            $indexData['scope'] = $this->generationScope;
            $indexData['store_id'] = $storeId;

            foreach ($indexData as $key => $value) {
                if (is_array($value)) {
                    $indexData[$key] = $this->helper->jsonEncode($value);
                }
            }

            /** @var \Bazaarvoice\Connector\Model\Index $index */
            $index = $this->bvIndexFactory->create();
            $index->setData($indexData);
            $this->productIndexes[] = $index;
        }
    }

    /**
     * @param $productIds
     * @param $store
     *
     * @return $this
     */
    private function populateIndexLocaleData($productIds, $store)
    {
        $storeId = $store->getId();
        if (isset($this->storeLocales[$storeId])) {
            /** Locale Data */
            $localeColumns = array(
                'entity_id'   => 'entity_id',
                'name'        => 'name',
                'description' => 'short_description',
                'image_url'   => 'small_image',
            );

            $res = $this->resourceConnection;
            $read = $res->getConnection('core_read');

            /** @var Store $localeStore */
            foreach ($this->storeLocales[$storeId] as $locale => $localeStore) {
                /** Core Data  */
                $select = $this->getBaseSelect($read, $localeStore->getId(), $res);

                $this->joinParent($select, $localeStore->getId(), $res);
                $this->joinUrlRewrite($select, $localeStore->getId(), $res);
                $this->filterByProducts($select, $productIds);

                $columns = array();
                foreach ($localeColumns as $dest => $source) {
                    $columns["{$dest}"] = 'p.'.$source;
                }
                $columns["product_page_url"] = 'url.request_path';
                $columns["parent_url"] = 'max(parent_url.request_path)';
                $columns["parent_image"] = 'parent.small_image';
                $select->columns($columns);

                try {
                    $rows = $select->query();
                    while (($indexData = $rows->fetch()) !== false) {
                        /** @var Index $productIndex */

                        foreach ($this->productIndexes as $productIndex) {
                            if ($productIndex->getProductId() == $indexData['product_id'] && $productIndex->getStoreId() == $storeId) {
                                break;
                            }
                        }

                        /** @var Store $localeStore */
                        $urlPath = isset($indexData['product_page_url'])
                            ? $indexData['product_page_url']
                            : $this->getStandardUrl($indexData['product_id']);
                        $localeUrl = $this->getStoreUrl(
                            $localeStore->getBaseUrl(),
                            $urlPath,
                            $localeStore->getCode());
                        $indexData['product_page_url'] = $localeUrl;

                        if (isset($indexData['product_page_url'])) {
                            $this->logger->debug('Locale URL');
                            $this->logger->debug($indexData['product_page_url']);
                        }

                        $indexData['image_url'] = $this->getImageUrl($localeStore, $indexData);

                        /** Use parent URLs/categories if appropriate */
                        if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                            $this->logger->debug('Locale not visible');
                            if (!empty($indexData['parent_url'])) {
                                $indexData['product_page_url'] = $this->getStoreUrl(
                                    $localeStore->getBaseUrl(),
                                    $indexData['parent_url'],
                                    $localeStore->getCode());
                                $this->logger->debug('Locale using Parent URL');
                            }
                        }

                        if (isset($indexData['description'])) {
                            $productIndex->addLocaleDescription([$locale => $indexData['description']]);
                        }

                        if (isset($indexData['image_url'])) {
                            $productIndex->addLocaleImageUrl([$locale => $indexData['image_url']]);
                        }

                        if (isset($indexData['name'])) {
                            $productIndex->addLocaleName([$locale => $indexData['name']]);
                        }

                        if (isset($indexData['product_page_url'])) {
                            $productIndex->addLocaleProductPageUrl([$locale => $indexData['product_page_url']]);
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
                }
            }
        }

        return $this;
    }

    /**
     * @param                                           $read
     * @param                                           $storeId
     *
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return mixed
     */
    private function getBaseSelect($read, $storeId, ResourceConnection $res)
    {
        $select = $read->select()
            ->from(array('p' => $res->getTableName('catalog_product_flat').'_'.$storeId), array(
                'name'            => 'p.name',
                'product_type'    => 'p.type_id',
                'product_id'      => 'p.entity_id',
                'description'     => 'p.short_description',
                'external_id'     => 'p.sku',
                'image_url'       => 'p.small_image',
                'visibility'      => 'p.visibility',
                'bv_feed_exclude' => 'bv_feed_exclude',
                'bv_category_id'  => 'p.bv_category_id',
            ));

        return $select;
    }

    /**
     * @param \Magento\Framework\DB\Select              $select
     * @param                                           $storeId
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return mixed
     */
    private function joinParent(Select $select, $storeId, ResourceConnection $res)
    {
        /** parents */
        $bvFamiliesAttributeConfig = $this->getBvFamiliesAttributeConfig($storeId);
        $bvFamiliesAttribute = $bvFamiliesAttributeConfig ? 'parent.'.$bvFamiliesAttributeConfig : 'null';

        return $select
            ->joinLeft(
                array('pp' => $res->getTableName('catalog_product_super_link')),
                'pp.product_id = p.entity_id', '')
            ->joinLeft(
                array('parent' => $res->getTableName('catalog_product_flat').'_'.$storeId),
                'pp.parent_id = parent.'.$this->productIdField,
                array(
                    'family'       => "GROUP_CONCAT(DISTINCT coalesce($bvFamiliesAttribute,parent.sku) SEPARATOR '||')",
                    'parent_image' => 'small_image',
                ));
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    private function getBvFamiliesAttributeConfig($storeId)
    {
        $bvFamiliesAttributeConfig = $this->helper->getConfig('feeds/bvfamilies_code', $storeId);

        return $bvFamiliesAttributeConfig;
    }

    /**
     * @param \Magento\Framework\DB\Select              $select
     * @param                                           $storeId
     *
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return \Magento\Framework\DB\Select
     */
    private function joinUrlRewrite(Select $select, $storeId, ResourceConnection $res)
    {
        /** urls */
        return $select
            ->joinLeft(
                array('url' => $res->getTableName('url_rewrite')),
                "url.entity_type = 'product'
                AND url.metadata IS NULL
                AND url.entity_id = p.entity_id
                AND url.store_id = {$storeId}",
                array('product_page_url' => 'url.request_path'))
            ->joinLeft(
                array('parent_url' => $res->getTableName('url_rewrite')),
                "parent_url.entity_type = 'product'
                AND parent_url.metadata IS NULL
                AND parent_url.entity_id = parent.entity_id
                AND parent_url.store_id = {$storeId}",
                array('parent_url' => 'max(parent_url.request_path)'));
    }

    /**
     * @param $select
     * @param $productIds
     */
    private function filterByProducts($select, $productIds)
    {
        $select->where("p.entity_id IN(?)", $productIds)->group('p.entity_id');
    }

    /**
     * @param $productId
     *
     * @return string
     */
    private static function getStandardUrl($productId)
    {
        /** Handle missing rewrites */
        $standardUrl = 'catalog/product/view/id/'.$productId;

        return $standardUrl;
    }

    /**
     * @param string      $storeUrl
     * @param string      $urlPath
     * @param string|null $storeCode
     *
     * @return string string
     */
    protected function getStoreUrl($storeUrl, $urlPath, $storeCode = null)
    {
        $url = $storeUrl.$urlPath;
        if ($storeCode) {
            $url .= '?___store='.$storeCode;
        }

        return $url;
    }

    /**
     * @param array $productIds
     */
    protected function _purgeUnversioned($productIds)
    {
        /** Database Resources */
        $write = $this->resourceConnection->getConnection('core_write');

        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');

        $delete = $write->deleteFromSelect($write->select()->from($indexTable)->where('product_id IN(?)', $productIds)
            ->where('store_id = 0'), $indexTable);
        $write->query($delete);
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     */
    protected function logStats()
    {
        /** @var Select $select */
        $select = $this->resourceConnection->getConnection('core_read')
            ->select()
            ->from(array(
                'source' => $this->resourceConnection->getTableName('bazaarvoice_index_product'),
            ));

        $select->columns(array('store_id', 'total' => 'count(*)'));
        $select->group('store_id');
        $result = $select->query();

        while ($row = $result->fetch()) {
            if ($row['store_id'] == 0) {
                $this->logger->debug("{$row['total']} Products left to Index");
            } else {
                $this->logger->debug("{$row['total']} Products Indexed for Store {$row['store_id']}");
            }
        }
    }

    /**
     * Required by interface but never called as far as I can tell
     *
     * @param array|\int[] $ids
     *
     * @return mixed
     */
    public function executeList(array $ids)
    {
        return true;
    }

    /**
     * Required by interface but never called as far as I can tell
     *
     * @param int $id
     *
     * @return mixed
     */
    public function executeRow($id)
    {
        return true;
    }

    /**
     * @param $store
     * @param $indexData
     *
     * @return mixed
     */
    private function getImageUrl($store, $indexData)
    {
        /** Use parent image if appropriate */
        if ($indexData['image_url'] == '' || $indexData['image_url'] == 'no_selection') {
            if (!empty($indexData['parent_image'])) {
                $indexData['image_url'] = $indexData['parent_image'];
                $this->logger->debug('Using Parent image');
            } else {
                $this->logger->debug('Product has no parent and no image');
                $indexData['image_url'] = $this->getPlaceholderUrl($store);
            }
        }

        if ($indexData['image_url'] == '' || $indexData['image_url'] == 'no_selection') {
            return '';
        } elseif (substr($indexData['image_url'], 0, 4) != 'http') {
            return $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA).'catalog/product'
                .$indexData['image_url'];
        }

        return $indexData['image_url'];
    }

    /**
     * @return string|false
     */
    public function getPlaceholderUrl($store)
    {
        /** @var Image $imageHelper */
        /** @var \Magento\Framework\View\Asset\Repository $assetRepo */
        /** @var \Magento\Framework\View\DesignInterface $design */
        /** @var Store $localeStore */
        /** @var string $locale */
        /** @var \Magento\Theme\Model\Theme $theme */

        $imageHelper = $this->objectManager->get('\Magento\Catalog\Helper\Image');
        $assetRepo = $this->objectManager->get('\Magento\Framework\View\Asset\Repository');
        $design = $this->objectManager->create('\Magento\Framework\View\DesignInterface');
        $localeCode = $this->helper->getConfig( 'general/locale', $store->getId() );
        $themeId = $design->getConfigurationDesignTheme('frontend', ['store' => $store->getId()]);
        $theme = $this->objectManager->create('\Magento\Theme\Model\Theme')->load($themeId);
        $assetParams = array(
            'area'   => 'frontend',
            'theme'  => $theme->getThemePath(),
            'locale' => $localeCode,
        );

        return $assetRepo->createAsset($imageHelper->getPlaceholder('image'), $assetParams)
            ->getUrl();
    }

    /**
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    protected function hasBadScopeIndex()
    {
        /** @var Select $select */
        $select = $this->resourceConnection->getConnection('core_read')
            ->select()
            ->from(array(
                'source' => $this->resourceConnection->getTableName('bazaarvoice_index_product'),
            ));

        $select->columns(array('total' => 'count(*)'));
        $select->where("scope IS NOT NULL AND scope != '{$this->generationScope}'");
        $result = $select->query();

        while ($row = $result->fetch()) {
            return $row['total'] > 0;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getProductIdFieldName()
    {
        $connection = $this->resourceConnection->getConnection('core_read');
        $table = $this->resourceConnection->getTableName('catalog_product_entity');
        $indexList = $connection->getIndexList($table);

        return $indexList[$connection->getPrimaryKeyName($table)]['COLUMNS_LIST'][0];
    }

    private function saveProductIndexes()
    {
        /** @var \Bazaarvoice\Connector\Model\Index $bvIndex */
        foreach ($this->productIndexes as $bvIndex) {
            try {
                $this->indexRepository->save($bvIndex);
            } catch (CouldNotSaveException $e) {
                try {
                    //possibly the table is in a bad state and an entry already exists somehow
                    $existingIndex = $this->indexRepository->getByProductIdStoreIdScope(
                        $bvIndex->getData('product_id'),
                        $bvIndex->getData('store_id'),
                        $bvIndex->getData('scope')
                    );
                    $existingIndex->addData($bvIndex->getData());
                    $this->indexRepository->save($existingIndex);
                } catch (\Exception $e) {
                    $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
                }
            } catch (\Exception $e) {
                $this->logger->crit($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }

}
