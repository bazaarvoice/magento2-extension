<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

/**
 * @noinspection DuplicatedCode
 */
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Indexer;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\IndexInterfaceFactory;
use Bazaarvoice\Connector\Api\IndexRepositoryInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Bazaarvoice\Connector\Model\Index;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory;
use Bazaarvoice\Connector\Model\Source\Scope;
use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Expr;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\View\DesignInterface;
use Magento\Theme\Model\Theme;

class Eav implements IndexerActionInterface, MviewActionInterface
{
    /**
     * @var \Bazaarvoice\Connector\Logger\Logger
     */
    private $logger;
    /**
     * @var
     */
    private $indexer;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory
     */
    private $bvIndexCollectionFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var string
     */
    private $productIdField;
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
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;
    /**
     * @var \Magento\Eav\Model\Config
     */
    private $eavConfig;

    /**
     * @param \Bazaarvoice\Connector\Logger\Logger                               $logger
     * @param \Bazaarvoice\Connector\Api\ConfigProviderInterface                 $configProvider
     * @param \Bazaarvoice\Connector\Api\StringFormatterInterface                $stringFormatter
     * @param \Magento\Framework\Indexer\IndexerInterface                        $indexerInterface
     * @param \Magento\Framework\ObjectManagerInterface                          $objectManager
     * @param \Magento\Store\Model\StoreManagerInterface                         $storeManager
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\ResourceConnection                          $resourceConnection
     * @param \Bazaarvoice\Connector\Api\Data\IndexInterfaceFactory              $bvIndexFactory
     * @param \Bazaarvoice\Connector\Api\IndexRepositoryInterface                $indexRepository
     * @param \Magento\Eav\Model\Config                                          $eavConfig
     */
    public function __construct(
        Logger $logger,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        IndexerInterface $indexerInterface,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        IndexInterfaceFactory $bvIndexFactory,
        IndexRepositoryInterface $indexRepository,
        Config $eavConfig,
        Image $imageHelper,
        AssetRepository $assetRepository,
        DesignInterface $design,
        Theme $theme
    ) {
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->indexer = $indexerInterface->load('bazaarvoice_product');
        $this->bvIndexCollectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->productIdField = $this->getProductIdFieldName();
        $this->bvIndexFactory = $bvIndexFactory;
        $this->indexRepository = $indexRepository;
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        $this->eavConfig = $eavConfig;
        $this->imageHelper = $imageHelper;
        $this->assetRepository = $assetRepository;
        $this->design = $design;
        $this->theme = $theme;
    }

    /**
     * @return bool
     */
    public function canIndex(): bool
    {
        return $this->configProvider->canSendProductFeedInAnyScope();
    }

    /**
     * @return mixed
     * @throws \Zend_Db_Statement_Exception
     */
    public function executeFull()
    {
        /**
         * @var Collection $incompleteIndex
         */

        if (!$this->canIndex()) {
            return false;
        }
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
            $this->logger->debug(__('Bazaarvoice Product Feed Index is being rebuilt via cron.'));
            $this->execute();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage()."\n".$e->getTraceAsString());
        }

        return true;
    }

    /**
     * Update a batch of index rows
     *
     * @param  \int[] $ids
     * @return mixed
     * @throws \Exception
     */
    public function execute($ids = [])
    {
        /**
         * @var $idCollection \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection
         */

        if (!$this->canIndex()) {
            return false;
        }
        try {
            $this->logger->debug('Partial Product Feed Index');

            if (empty($ids)) {
                $idCollection = $this->bvIndexCollectionFactory->create()->addFieldToFilter('version_id', 0);
                $idCollection->getSelect()->group('product_id');
                $idCollection->addFieldToSelect('product_id');
                $ids = $idCollection->getColumnValues('product_id');
            }

            $this->logger->debug('Found '.count($ids).' products to update.');

            /**
             * Break ids into pages
             */
            $productIdSets = array_chunk($ids, 50);

            /**
             * Time throttling
             */
            $limit = ($this->configProvider->getCronjobDurationLimit() * 60) - 10;
            $stop = time() + $limit;
            $counter = 0;
            do {
                if (time() > $stop) {
                    break;
                }

                $productIds = array_pop($productIdSets);
                if (!is_array($productIds)
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
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage()."\n".$e->getTraceAsString());
        }

        return true;
    }

    /**
     * @param array $productIds
     *
     * @throws \Exception
     */
    protected function reindexProducts(array $productIds)
    {
        switch ($this->configProvider->getFeedGenerationScope()) {
        case Scope::SCOPE_GLOBAL:
            $store = $this->storeManager->getStore(Store::DEFAULT_STORE_ID);
            if ($this->configProvider->canSendProductFeed($store->getId())) {
                $this->reindexProductsForStore($productIds, $store);
                break;
            }
            break;
        case Scope::WEBSITE:
            $websites = $this->storeManager->getWebsites();
            /**
             * @var \Magento\Store\Model\Website $website
             */
            foreach ($websites as $website) {
                $defaultStore = $website->getDefaultStore();
                if ($defaultStore->getId()) {
                    if ($this->configProvider->canSendProductFeed($defaultStore->getId())) {
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    }
                } else {
                    throw new NoSuchEntityException(__('Website %s has no default store!', $website->getCode()));
                }
            }
            break;
        case Scope::STORE_GROUP:
            $groups = $this->storeManager->getGroups();
            /**
             * @var \Magento\Store\Model\Group $group
             */
            foreach ($groups as $group) {
                $defaultStore = $group->getDefaultStore();
                if ($defaultStore->getId()) {
                    if ($this->configProvider->canSendProductFeed($defaultStore->getId())) {
                        $this->reindexProductsForStore($productIds, $defaultStore);
                    }
                } else {
                    throw new NoSuchEntityException(__('Store Group %s has no default store!', $group->getName()));
                }
            }
            break;
        case Scope::STORE_VIEW:
            $stores = $this->storeManager->getStores();
            /**
             * @var \Magento\Store\Model\Store $store
             */
            foreach ($stores as $store) {
                if ($store->getId()) {
                    if ($this->configProvider->canSendProductFeed($store->getId())) {
                        $this->reindexProductsForStore($productIds, $store);
                    }
                } else {
                    throw new NoSuchEntityException(__('Store %s not found!', $store->getCode()));
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
        /**
         * Set indexer to use mview
         */
        $this->indexer->setScheduled(true);

        $writeAdapter = $this->resourceConnection->getConnection('core_write');

        /**
         * Flush all old data
         */
        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');
        $writeAdapter->truncateTable($indexTable);
        $changelogTable = $this->resourceConnection->getTableName('bazaarvoice_product_cl');
        $writeAdapter->truncateTable($changelogTable);

        /**
         * Setup dummy rows
         */
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $writeAdapter->query("INSERT INTO `$indexTable` (`product_id`, `version_id`) SELECT DISTINCT `entity_id`, '0' FROM `$productTable`;");
        $writeAdapter->query("INSERT INTO `$changelogTable` (`entity_id`) SELECT DISTINCT `entity_id` FROM `$productTable`;");

        /**
         * Reset mview version
         */
        $mviewTable = $this->resourceConnection->getTableName('mview_state');
        $writeAdapter->query("UPDATE `$mviewTable` SET `version_id` = NULL, `status` = 'idle' WHERE `view_id` = 'bazaarvoice_product';");
        $indexCheck = $writeAdapter
            ->query(
                "SELECT COUNT(1) indexIsThere FROM INFORMATION_SCHEMA.STATISTICS
                    WHERE table_schema=DATABASE() AND table_name='$changelogTable' AND index_name='entity_id';"
            );
        $indexCheck = $indexCheck->fetchObject();
        if ($indexCheck->indexIsThere == 0) {
            $writeAdapter->query("ALTER TABLE `$changelogTable` ADD INDEX (`entity_id`);");
        }
    }

    /**
     * @param array     $productIds
     * @param int|Store $store
     *
     * @return bool
     * @throws \Exception
     */
    public function reindexProductsForStore(array $productIds, $store): bool
    {
        $this->productIndexes = [];
        $this->populateIndexStoreData($productIds, $store);
        $this->populateIndexLocaleData($productIds, $store);
        $this->saveProductIndexes();

        return true;
    }

    /**
     * @param $productIds
     * @param Store $store
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Exception
     * @throws \Exception
     */
    private function populateIndexStoreData($productIds, Store $store)
    {
        $storeId = $store->getId();
        $res = $this->resourceConnection;
        $read = $res->getConnection('core_read');
        $select = $this->getBaseSelect($read, $store, $res);
        $this->joinParent($select, $storeId, $res);

        if ($this->configProvider->getFeedGenerationScope() == Scope::SCOPE_GLOBAL) {
            $cppTable = $res->getTableName('catalog_category_product');
        } else {
            $cppTable = $res->getTableName("catalog_category_product_index_store$storeId");
        }
        $select->joinLeft(['cp' => $cppTable], 'cp.product_id = p.entity_id', 'category_id')
            ->joinLeft(['cpp' => $cppTable], 'cpp.product_id = parent.entity_id', 'category_id');
        $this->joinUrlRewrite($select, $storeId, $res);
        $this->addFieldToJoin($select, 'bv_category_id', $storeId);
        $bvCategoryId = $this->addFieldToSelect($select, 'bv_category_id', 'bv_category_id');
        $this->addFieldToJoin($select, 'bv_category_id', $storeId, 'parent');
        $parentBvCategoryId = $this->addFieldToSelect($select, 'bv_category_id', 'bv_category_id', 'parent');
        $categoryTable = $res->getTableName('catalog_category_entity');

        if ($this->configProvider->isCategoryIdUseUrlPathEnabled($storeId)) {
            $select->joinLeft(['c' => $categoryTable], 'c.entity_id = cp.category_id AND c.level > 1', []);
            $attribute = $this->eavConfig->getAttribute('catalog_category', 'url_path');
            $aliasTableName = 'ct'.$attribute->getId();
            $storeAliasTableName = 'sct'.$attribute->getId();
            $this->addFieldToJoin($select, 'url_path', $storeId, 'c', 'catalog_category');
            $columnValue = $this->resourceConnection->getConnection()->getIfNullSql(
                $storeAliasTableName . '.value',
                $aliasTableName . '.value'
            );
            $select->columns(['category_external_id' => "max($columnValue)"]);

            $select->joinLeft(['pc' => $categoryTable], 'pc.entity_id = cpp.category_id AND pc.level > 1', []);
            $aliasTableName = 'pct'.$attribute->getId();
            $storeAliasTableName = 'spct'.$attribute->getId();
            $this->addFieldToJoin($select, 'url_path', $storeId, 'pc', 'catalog_category');
            $columnValue = $this->resourceConnection->getConnection()->getIfNullSql(
                $storeAliasTableName . '.value',
                $aliasTableName . '.value'
            );
            $select->columns(['parent_category_external_id' => "max($columnValue)"]);

            $select->joinLeft(['bvc' => $categoryTable], "bvc.entity_id = $bvCategoryId", []);
            $this->addFieldToJoin($select, 'url_path', $storeId, 'bvc', 'catalog_category');
            $this->addFieldToSelect($select, 'bv_category_external_id', 'url_path', 'bvc', 'catalog_category');

            $select->joinLeft(['bvpc' => $categoryTable], "bvpc.entity_id = $parentBvCategoryId", []);
            $this->addFieldToJoin($select, 'url_path', $storeId, 'bvpc', 'catalog_category');
            $this->addFieldToSelect($select, 'bv_parent_category_external_id', 'url_path', 'bvpc', 'catalog_category');
        } else {
            $select->joinLeft(
                ['c' => $categoryTable],
                'c.entity_id = cp.category_id AND c.level > 1',
                ['category_external_id' => 'max(c.entity_id)']
            );
            $select->joinLeft(
                ['pc' => $categoryTable],
                'pc.entity_id = cpp.category_id AND pc.level > 1',
                ['parent_category_external_id' => 'max(pc.entity_id)']
            );
            $select->joinLeft(
                ['bvc' => $categoryTable],
                "bvc.entity_id = $bvCategoryId",
                ['bv_category_external_id' => 'bvc.entity_id']
            );
            $select->joinLeft(
                ['bvpc' => $categoryTable],
                "bvpc.entity_id = $parentBvCategoryId",
                ['bv_parent_category_external_id' => 'bvpc.entity_id']
            );
        }

        $brandAttr = $this->configProvider->getAttributeCode('brand', $storeId);
        if ($brandAttr) {
            $this->addFieldToJoin($select, $brandAttr, $storeId);
            $this->addFieldToSelect($select, 'brand_external_id', $brandAttr);
        }
        foreach (Index::CUSTOM_ATTRIBUTES as $label) {
            $code = strtolower($label);
            $attr = $this->configProvider->getAttributeCode($code, $storeId);
            if ($attr) {
                $this->addFieldToJoin($select, $attr, $storeId);
                $this->addFieldToSelect($select, "{$code}s", $attr);
                $this->logger->debug("using $attr for $code");
            }
        }

        /**
         * Version
         */
        $select->joinLeft(
            ['cl' => $res->getTableName('bazaarvoice_product_cl')],
            'cl.entity_id = p.entity_id',
            ['version_id' => 'MAX(cl.version_id)']
        );

        $this->filterByProducts($select, $productIds);

        //$this->_logger->debug($select->__toString());

        $rows = $select->query();

        while (($indexData = $rows->fetch()) !== false) {
            $this->logger->debug('Processing product '.$indexData['product_id']);
            foreach ($indexData as $key => $value) {
                if ($value && strpos($value, '||') !== false) {
                    $indexData[$key] = explode('||', $value);
                }
                if (in_array($key, ['family', 'parent_bvfamily']) && $value && strpos($value, ',') !== false) {
                    $indexData[$key] = explode(',', $value);
                }
            }

            $indexData['status'] = ($indexData[ProductFeed::INCLUDE_IN_FEED_FLAG]
                || $indexData[ProductFeed::INCLUDE_IN_FEED_FLAG] === null)
                ? Status::STATUS_ENABLED
                : Status::STATUS_DISABLED;

            if ($this->configProvider->isFamiliesEnabled($storeId)) {
                if ($indexData['product_type'] != Configurable::TYPE_CODE) {
                    if (!empty($indexData['parent_bvfamily'])) {
                        $indexData['family'] = $indexData['parent_bvfamily'];
                    }
                }
                $this->logger->debug('Family Info');
                $this->logger->debug($indexData['family']);
            }

            if ($indexData['bv_category_external_id']) {
                $indexData['category_external_id'] = $indexData['bv_category_external_id'];
            }
            if ($indexData['bv_parent_category_external_id']) {
                $indexData['parent_category_external_id'] = $indexData['bv_parent_category_external_id'];
            }

            /**
             * Use parent URLs/categories if appropriate
             */
            if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                $this->logger->debug('Not visible');
                if (!empty($indexData['parent_url'])) {
                    $indexData['product_page_url'] = $indexData['parent_url'];
                    if ($storeId == Store::DEFAULT_STORE_ID) {
                        $indexData['url_store_id'] = $indexData['parent_url_store_id'];
                    }
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

            if (!empty($indexData['parent_category_external_id']) && !empty($indexData['category_external_id']))
            {
                $indexData['category_external_id'] = str_replace('/', '-', $indexData['category_external_id']);
                $indexData['category_external_id'] = str_replace('.html', '', $indexData['category_external_id']);
                $indexData['category_external_id'] = $this->stringFormatter->replaceIllegalCharacters($indexData['category_external_id']);
            }

            if ($indexData['category_external_id'] == '') {
                $this->logger->debug('No category (or parent product category) found for product.');
            } else {
                $this->logger->debug("Category '{$indexData['category_external_id']}'");
            }
            $standardUrl = $this->getStandardUrl($indexData['product_id']);

            /**
             * Add Store base to URLs
             */
            if ($storeId == Store::DEFAULT_STORE_ID) {
                $urlStore = $this->storeManager->getStore($indexData['url_store_id']);
                $indexData['product_page_url'] = $this->getStoreUrl(
                    $urlStore->getBaseUrl(),
                    $indexData['product_page_url'] == '' ? $standardUrl : $indexData['product_page_url']
                );
            } else {
                $indexData['product_page_url'] = $this->getStoreUrl(
                    $store->getBaseUrl(),
                    $indexData['product_page_url'] == '' ? $standardUrl : $indexData['product_page_url']
                );
            }

            $this->logger->debug("URL {$indexData['product_page_url']}");
            $indexData['image_url'] = $this->getImageUrl($store, $indexData);

            $this->logger->debug("Image {$indexData['image_url']}");

            $indexData['external_id'] = $this->configProvider->getProductPrefix($storeId)
                .  $this->stringFormatter->getFormattedProductSku($indexData['external_id']);
            $indexData['scope'] = $this->configProvider->getFeedGenerationScope();
            $indexData['store_id'] = $storeId;

            foreach ($indexData as $key => $value) {
                if (is_array($value)) {
                    $indexData[$key] = $this->stringFormatter->jsonEncode($value);
                }
            }

            /**
             * @var \Bazaarvoice\Connector\Model\Index $index
             */
            $index = $this->bvIndexFactory->create();
            $index->setData($indexData);
            $this->productIndexes[] = $index;
        }
    }

    /**
     * @param $productIds
     * @param Store $store
     *
     * @return void
     * @throws \Exception
     */
    private function populateIndexLocaleData($productIds, Store $store): void
    {
        $storeId = $store->getId();
        $locales = $this->configProvider->getLocales();
        if (isset($locales[$storeId])) {
            $res = $this->resourceConnection;
            $read = $res->getConnection('core_read');

            /**
             * @var Store $localeStore
             */
            foreach ($locales[$storeId] as $locale => $localeStore) {
                /**
                 * Core Data
                 */
                $select = $this->getBaseSelect($read, $localeStore, $res);

                $this->joinParent($select, $localeStore->getId(), $res);
                $this->joinUrlRewrite($select, $localeStore->getId(), $res);
                $this->filterByProducts($select, $productIds);

                $columns = [];
                $columns["product_page_url"] = 'url.request_path';
                $columns["parent_url"] = 'max(parent_url.request_path)';
                $select->columns($columns);

                try {
                    $rows = $select->query();
                    while (($indexData = $rows->fetch()) !== false) {
                        /**
                         * @var Index $productIndex
                         */

                        foreach ($this->productIndexes as $productIndex) {
                            if ($productIndex->getData('product_id') == $indexData['product_id']
                                && $productIndex->getData('store_id') == $storeId
                            ) {
                                break;
                            }
                        }

                        /**
                         * Use parent URLs/categories if appropriate
                         */
                        if ($indexData['visibility'] == Visibility::VISIBILITY_NOT_VISIBLE) {
                            $this->logger->debug('Locale not visible');
                            if (!empty($indexData['parent_url'])) {
                                $indexData['product_page_url'] = $indexData['parent_url'];
                                if ($storeId == Store::DEFAULT_STORE_ID && $localeStore->getId() == Store::DEFAULT_STORE_ID) {
                                    $indexData['url_store_id'] = $indexData['parent_url_store_id'];
                                }
                                $this->logger->debug('Locale using Parent URL');
                            }
                        }

                        /**
                         * @var Store $localeStore
                         */
                        if ($storeId == Store::DEFAULT_STORE_ID && $localeStore->getId() == Store::DEFAULT_STORE_ID) {
                            $urlStore = $this->storeManager->getStore($indexData['url_store_id']);
                            $urlPath = $indexData['product_page_url'] ?? $this->getStandardUrl($indexData['product_id']);
                            $localeUrl = $this->getStoreUrl(
                                $urlStore->getBaseUrl(),
                                $urlPath,
                                $urlStore->getCode()
                            );
                        } else {
                            $urlPath = $indexData['product_page_url'] ?? $this->getStandardUrl($indexData['product_id']);
                            $localeUrl = $this->getStoreUrl(
                                $localeStore->getBaseUrl(),
                                $urlPath,
                                $localeStore->getCode()
                            );
                        }
                        $indexData['product_page_url'] = $localeUrl;

                        if (isset($indexData['product_page_url'])) {
                            $this->logger->debug('Locale URL');
                            $this->logger->debug($indexData['product_page_url']);
                        }

                        $indexData['image_url'] = $this->getImageUrl($localeStore, $indexData);

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
                } catch (Exception $e) {
                    $this->logger->critical($e->getMessage()."\n".$e->getTraceAsString());
                }
            }
        }
    }

    /**
     * @param $read
     * @param $store
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    private function getBaseSelect($read, $store, ResourceConnection $res)
    {
        $storeId = $store->getId();
        $select = $read->select()->from(['p' => $res->getTableName('catalog_product_entity')], []);
        $select->columns(['product_type' => 'p.type_id', 'product_id' => 'p.entity_id', 'external_id' => 'p.sku']);
        $this->addFieldToJoin($select, 'name', $storeId);
        $this->addFieldToJoin($select, 'short_description', $storeId);
        $this->addFieldToJoin($select, 'small_image', $storeId);
        $this->addFieldToJoin($select, 'visibility', $storeId);
        $this->addFieldToJoin($select, ProductFeed::INCLUDE_IN_FEED_FLAG, $storeId);
        $this->addFieldToJoin($select, 'status', $storeId);

        $this->addFieldToSelect($select, 'name', 'name');
        $this->addFieldToSelect($select, 'description', 'short_description');
        $this->addFieldToSelect($select, 'image_url', 'small_image');
        $this->addFieldToSelect($select, 'visibility', 'visibility');
        $this->addFieldToSelect($select, ProductFeed::INCLUDE_IN_FEED_FLAG, ProductFeed::INCLUDE_IN_FEED_FLAG);

        $this->filterByStore($select, $store);

        return $select;
    }

    /**
     * @param \Magento\Framework\DB\Select              $select
     * @param $storeId
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    private function joinParent(Select $select, $storeId, ResourceConnection $res): void
    {
        $select->joinLeft(
            ['pp' => $res->getTableName('catalog_product_super_link')],
            'pp.product_id = p.entity_id',
            ''
        )->joinLeft(
            ['parent' => $res->getTableName('catalog_product_entity')],
            'pp.parent_id = parent.'.$this->productIdField,
            ''
        );

        $families = $parentFamilies = '';
        if ($this->configProvider->isFamiliesEnabled($storeId)) {
            $familyAttributes = $this->configProvider->getFamilyAttributesArray($storeId);
            if ($familyAttributes) {
                foreach ($familyAttributes as $familyAttribute) {
                    $this->addFieldToJoin($select, $attributeCode = $familyAttribute, $storeId, 'parent');
                    $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
                    $aliasTableName = 'parent'.'t'.$attribute->getId();
                    $parentFamilies .= $this->resourceConnection->getConnection()->getIfNullSql(
                        's' . $aliasTableName . '.value',
                        $aliasTableName . '.value'
                    ) . ',';

                    $this->addFieldToJoin($select, $familyAttribute, $storeId);
                    $aliasTableName = 'pt'.$attribute->getId();
                    $families .= $this->resourceConnection->getConnection()->getIfNullSql(
                        's' . $aliasTableName . '.value',
                        $aliasTableName . '.value'
                    ) . ',';
                }
            }
        }

        $select->columns(['family' => "GROUP_CONCAT(DISTINCT CONCAT_WS(',', {$families}p.sku))"]);
        $select->columns(['parent_bvfamily' => "GROUP_CONCAT(DISTINCT CONCAT_WS(',', {$parentFamilies}parent.sku))"]);

        $this->addFieldToJoin($select, 'small_image', $storeId, 'parent');
        $this->addFieldToSelect($select, 'parent_image', 'small_image', 'parent');
    }

    /**
     * @param \Magento\Framework\DB\Select              $select
     * @param $storeId
     * @param \Magento\Framework\App\ResourceConnection $res
     *
     * @return void
     */
    private function joinUrlRewrite(Select $select, $storeId, ResourceConnection $res): void
    {
        /**
         * urls
         */
        if ($storeId == Store::DEFAULT_STORE_ID) {
            $select
                ->joinLeft(
                    ['url' => new Zend_Db_Expr(
                        "(select min(store_id) as url_store_id, entity_id, request_path
                    from `url_rewrite` where metadata is null and entity_type = 'product' group by entity_id)"
                    )],
                    "url.entity_id = p.entity_id",
                    ['product_page_url' => 'url.request_path', 'url_store_id' => 'url_store_id']
                )
                ->joinLeft(
                    ['parent_url' => new Zend_Db_Expr(
                        "(select min(store_id) as parent_url_store_id, entity_id, request_path
                    from `url_rewrite` where metadata is null and entity_type = 'product' group by entity_id)"
                    )],
                    "parent_url.entity_id = parent.entity_id",
                    ['parent_url' => 'parent_url.request_path', 'parent_url_store_id' => 'parent_url_store_id']
                );
        } else {
            $select
                ->joinLeft(
                    ['url' => $res->getTableName('url_rewrite')],
                    "url.entity_type = 'product'
                    and url.metadata is null
                    and url.entity_id = p.entity_id
                    and url.store_id = $storeId",
                    ['product_page_url' => 'url.request_path']
                )
                ->joinLeft(
                    ['parent_url' => $res->getTableName('url_rewrite')],
                    "parent_url.entity_type = 'product'
                    and parent_url.metadata is null
                    and parent_url.entity_id = parent.entity_id
                    and parent_url.store_id = $storeId",
                    ['parent_url' => 'max(parent_url.request_path)']
                );
        }
    }

    /**
     * @param \Magento\Framework\DB\Select $select
     * @param array                        $productIds
     */
    private function filterByProducts(Select $select, array $productIds)
    {
        $select->where("p.entity_id in (?)", $productIds)->group('p.entity_id');
    }

    /**
     * @param $productId
     *
     * @return string
     */
    private function getStandardUrl($productId): string
    {
        /**
         * Handle missing rewrites
         */
        return 'catalog/product/view/id/'.$productId;
    }

    /**
     * @param string      $storeUrl
     * @param string      $urlPath
     * @param string|null $storeCode
     *
     * @return string string
     */
    private function getStoreUrl(string $storeUrl, string $urlPath, $storeCode = null): string
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
    private function _purgeUnversioned(array $productIds)
    {
        /**
         * Database Resources
         */
        $write = $this->resourceConnection->getConnection('core_write');

        $indexTable = $this->resourceConnection->getTableName('bazaarvoice_index_product');

        $delete = $write->deleteFromSelect(
            $write->select()->from($indexTable)->where('product_id IN(?)', $productIds)
                ->where('version_id = 0'), $indexTable
        );
        $write->query($delete);
    }

    /**
     * @throws \Zend_Db_Statement_Exception
     */
    private function logStats()
    {
        $select = $this->resourceConnection->getConnection('core_read')
            ->select()
            ->from(
                [
                'source' => $this->resourceConnection->getTableName('bazaarvoice_index_product'),
                ]
            );

        $select->columns(['store_id', 'total' => 'count(*)']);
        $select->group('store_id');
        $result = $select->query();

        while ($row = $result->fetch()) {
            if ($row['version_id'] == 0) {
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
     * @return bool
     */
    public function executeList(array $ids): bool
    {
        return true;
    }

    /**
     * Required by interface but never called as far as I can tell
     *
     * @param int $id
     *
     * @return bool
     */
    public function executeRow($id): bool
    {
        return true;
    }

    /**
     * @param $store
     * @param $indexData
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getImageUrl($store, $indexData)
    {
        /**
         * Use parent image if appropriate
         */
        if ($indexData['image_url'] == '' || $indexData['image_url'] == 'no_selection') {
            if (!empty($indexData['parent_image'])) {
                $indexData['image_url'] = $indexData['parent_image'];
                $this->logger->debug('Using Parent image');
            } else {
                $this->logger->debug('Product has no parent and no image');
                $indexData['image_url'] = $this->getPlaceholderUrl($store);
                $this->logger->debug('default product url:');
                $this->logger->debug($indexData['image_url']);
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
     * @param $store
     *
     * @return string|false
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPlaceholderUrl($store)
    {
        /**
         * @var Store $localeStore
         */
        /**
         * @var string $locale
         */


        try {
            $localeCode = $this->configProvider->getLocale($store->getId());
            $themeId  = $this->design->getConfigurationDesignTheme('frontend', [$store->getId()]);

            $theme = $this->theme->load($themeId);
            $assetParams = [
                'area'   => 'frontend',
                'theme'  => $theme->getThemePath(),
                'locale' => $localeCode,
            ];

            return $this->assetRepository->createAsset($this->imageHelper->getPlaceholder('image'), $assetParams)
                ->getUrl();
        } catch (LocalizedException $exception) {
            throw new LocalizedException($exception->getMessage());
        }

    }

    /**
     * @return bool
     * @throws \Zend_Db_Statement_Exception
     */
    protected function hasBadScopeIndex(): bool
    {
        $select = $this->resourceConnection->getConnection('core_read')
            ->select()
            ->from(
                [
                'source' => $this->resourceConnection->getTableName('bazaarvoice_index_product'),
                ]
            );

        $select->columns(['total' => 'count(*)']);
        $select->where("scope IS NOT NULL AND scope != '{$this->configProvider->getFeedGenerationScope()}'");
        $result = $select->query();

        if ($row = $result->fetch()) {
            return $row['total'] > 0;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getProductIdFieldName(): string
    {
        $connection = $this->resourceConnection->getConnection('core_read');
        $table = $this->resourceConnection->getTableName('catalog_product_entity');
        $indexList = $connection->getIndexList($table);

        return $indexList[$connection->getPrimaryKeyName($table)]['COLUMNS_LIST'][0];
    }

    private function saveProductIndexes()
    {
        /**
         * @var \Bazaarvoice\Connector\Model\Index $bvIndex
         */
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
                } catch (Exception $e) {
                    $this->logger->critical($e->getMessage()."\n".$e->getTraceAsString());
                }
            } catch (Exception $e) {
                $this->logger->critical($e->getMessage()."\n".$e->getTraceAsString());
            }
        }
    }

    /**
     * @throws \Zend_Db_Select_Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addFieldToJoin(
        Select $select,
        string $attributeCode,
        $storeId,
        string $mainTableAlias = 'p',
        string $entityType = 'catalog_product'
    ) {
        $attribute = $this->eavConfig->getAttribute($entityType, $attributeCode);

        $aliasTableName = $mainTableAlias.'t'.$attribute->getId();
        $storeAliasTableName = 's' . $mainTableAlias.'t'.$attribute->getId();
        $joinCondition = '%1$s.%4$s = %2$s.%4$s AND %2$s.attribute_id = %3$d AND %2$s.store_id = %5$d';
        $tableName = "{$entityType}_entity_{$attribute->getBackendType()}";
        $linkField = $this->getProductIdFieldName();
        $defaultStoreId = Store::DEFAULT_STORE_ID;

        if (!in_array($aliasTableName, array_keys($select->getPart('from')))) {
            $select->joinLeft(
                [$aliasTableName => $tableName],
                sprintf(
                    $joinCondition, $mainTableAlias, $aliasTableName, $attribute->getId(), $linkField,
                    $defaultStoreId
                ),
                []
            );
        }
        if (!in_array($storeAliasTableName, array_keys($select->getPart('from')))) {
            $select->joinLeft(
                [$storeAliasTableName => $tableName],
                sprintf($joinCondition, $mainTableAlias, 's' . $aliasTableName, $attribute->getId(), $linkField, $storeId),
                []
            );
        }
    }

    /**
     * @param \Magento\Framework\DB\Select $select
     * @param string                       $fieldAlias
     * @param string                       $attributeCode
     * @param string                       $mainTableAlias
     * @param string                       $entityType
     *
     * @return \Zend_Db_Expr
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function addFieldToSelect(
        Select $select,
        string $fieldAlias,
        string $attributeCode,
        string $mainTableAlias = 'p',
        string $entityType = 'catalog_product'
    ): Zend_Db_Expr {
        $attribute = $this->eavConfig->getAttribute($entityType, $attributeCode);
        $aliasTableName = $mainTableAlias.'t'.$attribute->getId();
        $columnValue = $this->resourceConnection->getConnection()->getIfNullSql(
            's' . $aliasTableName . '.value',
            $aliasTableName . '.value'
        );

        $select->columns([$fieldAlias => $columnValue]);

        return $columnValue;
    }

    /**
     * @param \Magento\Framework\DB\Select $select
     * @param $store
     * @param string                       $mainTableAlias
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function filterByStore(
        Select $select,
        $store,
        string $mainTableAlias = 'p'
    ) {
        if ($store->getId() != Store::DEFAULT_STORE_ID) {
            $websiteId = $store->getWebsite()->getId();
            $select->join(
                ['cpw' => 'catalog_product_website'],
                "p.entity_id = cpw.product_id and cpw.website_id = $websiteId",
                []
            );
        }

        $attribute = $this->eavConfig->getAttribute('catalog_product', 'status');
        $aliasTableName = $mainTableAlias.'t'.$attribute->getId();
        $columnValue = $this->resourceConnection->getConnection()->getIfNullSql(
            's' . $aliasTableName . '.value',
            $aliasTableName . '.value'
        );

        $select->columns(['status' => $columnValue]);
        $select->where("$columnValue = ?", Status::STATUS_ENABLED);
    }
}
