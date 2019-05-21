<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Source\Scope;
use Bazaarvoice\Connector\Model\XMLWriter;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class Category
 *
 * @package Bazaarvoice\Connector\Model\Feed\Product
 */
class Category
{
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @var
     */
    protected $urlFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var
     */
    protected $rootCategoryPath;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * Category constructor.
     *
     * @param Logger                                     $logger
     * @param ConfigProviderInterface                    $configProvider
     * @param CategoryFactory                            $categoryFactory
     * @param ResourceConnection                         $resourceConnection
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param StringFormatterInterface                   $stringFormatter
     */
    public function __construct(
        Logger $logger,
        ConfigProviderInterface $configProvider,
        CategoryFactory $categoryFactory,
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        StringFormatterInterface $stringFormatter
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->resourceConnection = $resourceConnection;
        $this->configProvider = $configProvider;
        $this->storeManager = $storeManager;
        $this->stringFormatter = $stringFormatter;
    }

    /**
     * @param XMLWriter $writer
     * @param Store     $store
     *
     * @throws \Exception
     */
    public function processCategoriesForStore(XMLWriter $writer, Store $store)
    {
        $this->processCategories($writer, $store);
    }

    /**
     * @param XMLWriter $writer
     * @param Group     $storeGroup
     *
     * @throws \Exception
     */
    public function processCategoriesForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        $this->processCategories($writer, $storeGroup->getDefaultStore());
    }

    /**
     * @param XMLWriter $writer
     * @param Website   $website
     *
     * @throws \Exception
     */
    public function processCategoriesForWebsite(XMLWriter $writer, Website $website)
    {
        $this->processCategories($writer, $website->getDefaultStore());
    }

    /**
     * @param XMLWriter $writer
     *
     * @throws \Exception
     */
    public function processCategoriesForGlobal(XMLWriter $writer)
    {
        $storesList = $this->storeManager->getStores();
        ksort($storesList);
        $stores = [];
        $defaultStore = null;
        /** @var StoreInterface $store */
        foreach ($storesList as $store) {
            if ($this->configProvider->canSendProductFeed($store->getId())) {
                $stores[] = $store->getId();
                if ($defaultStore == null) {
                    $defaultStore = $store;
                }
            }
        }
        $this->processCategories($writer, $defaultStore);
    }

    /**
     * @param XMLWriter            $writer
     * @param Store|StoreInterface $defaultStore
     *
     * @throws \Exception
     */
    protected function processCategories(XMLWriter $writer, $defaultStore)
    {
        $localeStores = $this->configProvider->getLocales();
        $defaultCollection = $this->getProductCollection($defaultStore);

        $baseUrl = $defaultStore->getBaseUrl();
        $categories = [];
        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($defaultCollection as $category) {
            $categories[$category->getId()] = [
                'url'        => $this->getStoreUrl($baseUrl, $category->getUrlPath()),
                'name'       => $this->configProvider->getCategoryPrefix($defaultStore->getId()) . $category->getName(),
                'externalId' => $this->stringFormatter->getFormattedCategoryId($category, $defaultStore),
                'parent_id'  => $category->getParentId(),
                'names'      => [],
                'urls'       => [],
            ];
        }
        unset($defaultCollection);

        if (is_array($localeStores) && !empty($localeStores[$defaultStore->getId()])) {
            /** get localized data */
            foreach ($localeStores[$defaultStore->getId()] as $localeCode => $localeStore) {
                /** @var Store $localeStore */
                $localeBaseUrl = $localeStore->getBaseUrl();
                $localeStoreCode = $localeStore->getCode();
                $localeCollection = $this->getProductCollection($localeStore);
                $localeCode = $this->configProvider->getLocale($localeStore->getId());
                foreach ($localeCollection as $category) {
                    /** Skip categories not in main store */
                    if (!isset($categories[$category->getId()])) {
                        continue;
                    }
                    $categories[$category->getId()]['names'][$localeCode] = $category->getName();
                    $categories[$category->getId()]['urls'][$localeCode]
                        = $this->getStoreUrl(
                            $localeBaseUrl,
                            $category->getUrlPath(),
                            $localeStoreCode,
                            $categories[$category->getId()]['urls']
                        );
                }
                unset($localeCollection);
            }
        }

        /** Check count of categories */
        if (count($categories) > 0) {
            $writer->startElement('Categories');
        }
        /** @var array $category */
        foreach ($categories as $category) {
            if (!empty($category['parent_id'])
                && $category['parent_id'] != $defaultStore->getRootCategoryId()
                && isset($categories[$category['parent_id']])
                && is_array($categories[$category['parent_id']])
                && !empty($categories[$category['parent_id']]['externalId'])
            ) {
                $category['parent'] = $categories[$category['parent_id']]['externalId'];
            }
            $this->writeCategory($writer, $category);
        }
        if (count($categories) > 0) {
            $writer->endElement(); //End Categories
        }
    }

    /**
     * @param XMLWriter                             $writer
     * @param array|\Magento\Catalog\Model\Category $category
     */
    protected function writeCategory(XMLWriter $writer, $category)
    {
        $writer->startElement('Category');
        $writer->writeElement('ExternalId', $category['externalId']);

        /** If parent category is the root category, then ignore it */
        if (isset($category['parent'])) {
            $writer->writeElement('ParentExternalId', $category['parent']);
        }

        $writer->writeElement('Name', htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8', false), true);
        $writer->writeElement('CategoryPageUrl', htmlspecialchars($category['url'], ENT_QUOTES, 'UTF-8', false), true);

        /** Write out localized <Names> */
        if (is_array($category['names']) && !empty($category['names'])) {
            $writer->startElement('Names');
            foreach ($category['names'] as $locale => $name) {
                $writer->startElement('Name');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw(htmlspecialchars($name, ENT_QUOTES, 'UTF-8', false), true);
                $writer->endElement(); //End Name
            }
            $writer->endElement(); //End Names
        }

        if (is_array($category['urls']) && !empty($category['urls'])) {
            /** Write out localized <CategoryPageUrls> */
            $writer->startElement('CategoryPageUrls');
            foreach ($category['urls'] as $locale => $url) {
                $writer->startElement('CategoryPageUrl');
                $writer->writeAttribute('locale', $locale);
                $writer->writeRaw(htmlspecialchars($url, ENT_QUOTES, 'UTF-8', false), true);
                $writer->endElement(); //End CategoryPageUrl
            }
            $writer->endElement(); //End CategoryPageUrls
        }

        $writer->endElement(); //End Category
    }

    /**
     * @param string      $storeUrl
     * @param string      $urlPath
     * @param string|null $storeCode
     * @param null        $currentUrls
     *
     * @return string string
     */
    protected function getStoreUrl($storeUrl, $urlPath, $storeCode = null, $currentUrls = null)
    {
        $url = $storeUrl.$urlPath;

        if (is_array($currentUrls)
            && in_array($url, $currentUrls)
        ) {
            $url .= '?___store='.$storeCode;
        }

        return $url;
    }

    /**
     * @param Store $store
     *
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getProductCollection($store = null)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->categoryFactory->create()->getCollection();

        /**
         * Filter category collection based on Magento store
         * Do this by filtering on 'path' attribute, based on root category path found above
         * Include the root category itself in the feed
         */
        if ($store) {
            $rootCategoryId = $store->getRootCategoryId();
            /* @var $rootCategory \Magento\Catalog\Model\Category */
            $rootCategory = $this->categoryFactory->create()->load($rootCategoryId);
            $rootCategoryPath = $rootCategory->getData('path');
            if ($this->configProvider->getFeedGenerationScope() != Scope::SCOPE_GLOBAL) {
                $collection->addAttributeToFilter('path', ['like' => $rootCategoryPath.'/%']);
            }
            $collection->setStore($store);
        }

        $collection
            ->addAttributeToFilter('level', ['gt' => 1])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('parent_id');

        $collection->getSelect()
            ->distinct(true)
            ->joinLeft(
                ['url' => $this->resourceConnection->getTableName('url_rewrite')],
                "entity_type = 'category' AND url.entity_id = e.entity_id "
                .(($store) ? " AND url.store_id = {$store->getId()}" : '')." AND metadata IS NULL AND redirect_type = 0"
                ." AND is_autogenerated = 1",
                ['url_path' => 'request_path']
            );

        return $collection;
    }
}
