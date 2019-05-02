<?php

namespace Bazaarvoice\Connector\Model\Feed\Product;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Logger\Logger;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Store\Model\Group;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Generic
{

    protected $_logger;
    protected $_helper;
	protected $_generationScope;
	protected $_storeManager;

    /**
     * Generic constructor.
     *
     * @param Logger $logger
     * @param Data $helper
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
    	Logger $logger,
	    Data $helper,
	    StoreManagerInterface $storeManager
    ) {
        $this->_logger = $logger;
        $this->_helper = $helper;
	    $this->_storeManager = $storeManager;

	    $this->_generationScope = $helper->getConfig('feeds/generation_scope');
    }

	/**
	 * Get custom configured attributes
	 *
	 * @param string $type
	 * @param null $store
	 * @param string $scope
	 *
	 * @return string
	 */
    public function getAttributeCode($type, $store = null, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->_helper->getConfig('feeds/' . $type . '_code', $store, $scope);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLocales()
    {
        $locales = [];
	    switch ($this->_generationScope) {
		    case Scope::STORE_VIEW:
			    $stores = $this->_storeManager->getStores();
			    $defaultStore = null;
			    /** @var Store $store */
			    foreach ($stores as $store) {
				    if($this->_helper->canSendFeed($store->getId())) {
					    $localeCode = $this->_helper->getConfig( 'general/locale', $store->getId() );
					    if(!empty($localeCode))
						    $locales[ $store->getId() ] = [ $localeCode => $store ];
				    }
			    }
			    break;
		    case Scope::WEBSITE:
			    $websites = $this->_storeManager->getWebsites();
			    /** @var Website $website */
			    foreach ($websites as $website) {
				    $defaultStore = $website->getDefaultStore();
				    $locales[$defaultStore->getId()] = array();
				    /** @var Store $localeStore */
				    foreach ($website->getStores() as $localeStore) {
					    if($this->_helper->canSendFeed($localeStore->getId())) {
						    $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );
						    if(!empty($localeCode))
							    $locales[ $defaultStore->getId() ][ $localeCode ] = $localeStore;
					    }
				    }
				    $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
				    $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
			    }
			    break;
		    case Scope::SCOPE_GLOBAL:
			    $stores = $this->_storeManager->getStores();
			    ksort($stores);
			    /** @var Store $store */
			    $globalLocales = [];
			    $defaultStore = null;
			    foreach ($stores as $store) {
				    if($this->_helper->canSendFeed($store->getId())) {
					    $localeCode = $this->_helper->getConfig( 'general/locale', $store->getId() );
					    if(!empty($localeCode)) {
						    $globalLocales[ $localeCode ] = $store;
						    if($defaultStore == null)
						    	$defaultStore = $store;
					    }
				    }
			    }
			    if($defaultStore == null)
			        throw new \Exception(__('No valid store found for feed generation'));
			    $locales[$defaultStore->getId()] = $globalLocales;
			    unset($globalLocales);
			    $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
			    $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
			    break;
		    case Scope::STORE_GROUP:
			    $groups = $this->_storeManager->getGroups();
			    /** @var Group $group */
			    foreach ($groups as $group) {
				    $defaultStore = $group->getDefaultStore();
				    $locales[$defaultStore->getId()] = [];
				    /** @var Store $localeStore */
				    foreach ($group->getStores() as $localeStore) {
					    if($this->_helper->canSendFeed($localeStore->getId())) {
						    $localeCode = $this->_helper->getConfig( 'general/locale', $localeStore->getId() );
						    if(!empty($localeCode))
							    $locales[ $defaultStore->getId() ][ $localeCode ] = $localeStore;
					    }
				    }
				    $defaultLocale = $this->_helper->getConfig('general/locale', $defaultStore);
				    $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
			    }
			    break;
	    }
        return $locales;
    }

    /**
     * Get the uniquely identifying category ID for a catalog category.
     *
     * This is the unique, category or subcategory ID (duplicates are unacceptable).
     * This ID should be stable: it should not change for the same logical category even
     * if the category's name changes.
     *
     * @param  \Magento\Catalog\Model\Category $category a reference to a catalog category object
     * @param int $storeId
     * @return string The unique category ID to be used with Bazaarvoice
     */
    protected function getCategoryId($category, $storeId = null)
    {
        if ($this->_helper->getConfig('feeds/category_id_use_url_path', $storeId) == false) {
            return $category->getId();
        }
        else {
            $rawCategoryId = $category->getUrlPath();

            $rawCategoryId = str_replace('/', '-', $rawCategoryId);
            $rawCategoryId = str_replace('.html', '', $rawCategoryId);
            return $this->_helper->replaceIllegalCharacters($rawCategoryId);
        }
    }

}
