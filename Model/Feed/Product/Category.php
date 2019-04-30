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

use Bazaarvoice\Connector\Model\Source\Scope;
use Bazaarvoice\Connector\Model\XMLWriter;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Category extends Generic
{
    protected $_categoryFactory;
    protected $_urlFactory;
    protected $_resourceConnection;
    protected $_rootCategoryPath;

    /**
     * Category constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        ResourceConnection $resourceConnection
    )
    {
        $this->_categoryFactory = $categoryFactory;
        $this->_resourceConnection = $resourceConnection;
        parent::__construct($logger, $helper, $storeManager);
    }

	/**
	 * @param XMLWriter $writer
	 * @param Store $store
	 *
	 * @throws \Exception
	 */
	public function processCategoriesForStore(XMLWriter $writer, Store $store)
    {
        $locales = $this->getLocales();
        $this->processCategories($writer, $store, $locales);
    }

	/**
	 * @param XMLWriter $writer
	 * @param Group $storeGroup
	 *
	 * @throws \Exception
	 */
    public function processCategoriesForStoreGroup(XMLWriter $writer, Group $storeGroup)
    {
        $locales = $this->getLocales();
        $this->processCategories($writer, $storeGroup->getDefaultStore(), $locales);
    }

	/**
	 * @param XMLWriter $writer
	 * @param Website $website
	 *
	 * @throws \Exception
	 */
    public function processCategoriesForWebsite(XMLWriter $writer, Website $website)
    {
        $locales = $this->getLocales();

        $this->processCategories($writer, $website->getDefaultStore(), $locales);
    }

	/**
	 * @param XMLWriter $writer
	 *
	 * @throws \Exception
	 */
    public function processCategoriesForGlobal(XMLWriter $writer)
    {
        $storesList = $this->_storeManager->getStores();
        ksort($storesList);
        $stores = [];
        $defaultStore = null;
        /** @var StoreInterface $store */
        foreach ($storesList as $store) {
        	if($this->_helper->canSendFeed($store->getId())) {
		        $stores[] = $store->getId();
		        if ( $defaultStore == null ) {
			        $defaultStore = $store;
		        }
	        }
        }
        $locales = $this->getLocales();

        $this->processCategories($writer, $defaultStore, $locales);
    }

    /**
     * @param XMLWriter $writer
     * @param Store|StoreInterface $defaultStore
     * @param array $localeStores
     * @throws \Exception
     */
    protected function processCategories(XMLWriter $writer, $defaultStore, $localeStores = array())
    {
        $defaultCollection = $this->getProductCollection($defaultStore);

        $baseUrl = $defaultStore->getBaseUrl();
        $categories = array();
        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($defaultCollection as $category) {
            $categories[$category->getId()] = array(
                'url' => $this->getStoreUrl($baseUrl, $category->getUrlPath()),
                'name' =>  $category->getName(),
                'externalId' => $this->getCategoryId($category),
                'parent_id' => $category->getParentId(),
                'names' => array(),
                'urls' => array()
            );
        }
        unset($defaultCollection);

        if(is_array($localeStores) && !empty($localeStores[$defaultStore->getId()])) {
	        /** get localized data */
	        foreach ( $localeStores[ $defaultStore->getId() ] as $localeCode => $localeStore ) {
		        /** @var Store $localeStore */
		        $localeBaseUrl   = $localeStore->getBaseUrl();
		        $localeStoreCode = $localeStore->getCode();

		        $localeCollection = $this->getProductCollection( $localeStore );

		        /** Get store locale */
		        $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );

		        foreach ( $localeCollection as $category ) {
			        /** Skip categories not in main store */
			        if ( ! isset( $categories[ $category->getId() ] ) ) {
				        continue;
			        }
			        $categories[ $category->getId() ]['names'][ $localeCode ] = $category->getName();
			        $categories[ $category->getId() ]['urls'][ $localeCode ]  =
				        $this->getStoreUrl( $localeBaseUrl, $category->getUrlPath(), $localeStoreCode, $categories[ $category->getId() ]['urls'] );
		        }
		        unset( $localeCollection );
	        }
        }

        /** Check count of categories */
        if (count($categories) > 0) {
            $writer->startElement('Categories');
        }
        /** @var array $category */
        foreach ($categories as $category) {
            if (
                !empty($category['parent_id']) &&
                $category['parent_id'] != $defaultStore->getRootCategoryId() &&
                isset($categories[$category['parent_id']]) &&
                is_array($categories[$category['parent_id']]) &&
                !empty($categories[$category['parent_id']]['externalId'])
            ) {
                $category['parent'] = $categories[$category['parent_id']]['externalId'];
            }
            $this->writeCategory($writer, $category);
        }
        if (count($categories) > 0) {
            $writer->endElement(); /** Categories */
        }
    }

    /**
     * @param XMLWriter $writer
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
        if(is_array($category['names']) && !empty($category['names'])) {
            $writer->startElement( 'Names' );
            /* @var $curCategory \Magento\Catalog\Model\Category */
            foreach ( $category['names'] as $locale => $name ) {
                $writer->startElement( 'Name' );
                $writer->writeAttribute( 'locale', $locale );
                $writer->writeRaw( htmlspecialchars( $name, ENT_QUOTES, 'UTF-8', false ), true );
                $writer->endElement();
                /** Name */
            }
            $writer->endElement();
            /** Names */
        }

        if(is_array($category['urls']) && !empty($category['urls'])) {
            /** Write out localized <CategoryPageUrls> */
            $writer->startElement( 'CategoryPageUrls' );
            /* @var $curCategory \Magento\Catalog\Model\Category */
            foreach ( $category['urls'] as $locale => $url ) {
                $writer->startElement( 'CategoryPageUrl' );
                $writer->writeAttribute( 'locale', $locale );
                $writer->writeRaw( htmlspecialchars( $url, ENT_QUOTES, 'UTF-8', false ), true );
                $writer->endElement();
                /** CategoryPageUrl */
            }
            $writer->endElement();
            /** CategoryPageUrls */
        }

        $writer->endElement(); /** Category */
    }

	/**
	 * @param string $storeUrl
	 * @param string $urlPath
	 * @param string|null $storeCode
	 * @param null $currentUrls
	 *
	 * @return string string
	 */
	protected function getStoreUrl( $storeUrl, $urlPath, $storeCode = null, $currentUrls = null ) {
		$url = $storeUrl . $urlPath;

		if (
			is_array($currentUrls)
			&& in_array($url, $currentUrls)
		) {
			$url .= '?___store=' . $storeCode;
		}

		return $url;
	}

	/**
	 * @param Store $store
	 *
	 * @return \Magento\Catalog\Model\ResourceModel\Category\Collection
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
    protected function getProductCollection($store = null) {
	    if ( $store ) {
		    $rootCategoryId = $store->getRootCategoryId();
		    /* @var $rootCategory \Magento\Catalog\Model\Category */
		    $rootCategory     = $this->_categoryFactory->create()->load( $rootCategoryId );
		    $rootCategoryPath = $rootCategory->getData( 'path' );
	    }

        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->_categoryFactory->create()->getCollection();

        /**
         * Filter category collection based on Magento store
         * Do this by filtering on 'path' attribute, based on root category path found above
         * Include the root category itself in the feed
         */
        if($store) {
        	if($this->_generationScope != Scope::SCOPE_GLOBAL)
		        $collection->addAttributeToFilter( 'path', array( 'like' => $rootCategoryPath . '/%' ) );
	        $collection->setStore( $store );
        }

        $collection
            ->addAttributeToFilter('level', array('gt' => 1))
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('parent_id');

        $collection->getSelect()
	        ->distinct(true)
            ->joinLeft(array('url' => $this->_resourceConnection->getTableName('url_rewrite')),
                "entity_type = 'category' AND url.entity_id = e.entity_id "
	            . (($store) ? " AND url.store_id = {$store->getId()}" : '' ) . " AND metadata IS NULL AND redirect_type = 0"
	            . " AND is_autogenerated = 1",
                array('url_path' => 'request_path'));

        return $collection;
    }


}
