<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Model\Source\Environment;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\ModuleResource;
use Magento\Store\Model\Group;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

/**
 * Class ConfigProvider
 *
 * @package Bazaarvoice\Connector\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Magento\Framework\Encryption\EncryptorInterface   $encryptor
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool
     */
    public function isCloudSeoEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && $this->getConfig('general/enable_cloud_seo', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool
     */
    public function isBvEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('general/enable_bv', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool
     */
    public function isQaEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && (bool) $this->getConfig('qa/enable_qa', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool
     */
    public function isRrEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && (bool) $this->getConfig('rr/enable_rr', $storeId, $scope);
    }

    /**
     * @param string   $type
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function canSendFeed($type, $storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        if ($type == 'purchase') {
            return $this->canSendPurchaseFeed($storeId, $scope);
        }

        return $this->canSendProductFeed($storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function canSendProductFeed($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && $this->isProductFeedEnabled($storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    private function isProductFeedEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/enable_product_feed', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function canSendPurchaseFeed($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && $this->isPurchaseFeedEnabled($storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    private function isPurchaseFeedEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/enable_purchase_feed', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('general/families', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getSftpUsername($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/sftp_username', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getSftpPassword($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->encryptor->decrypt($this->getConfig('feeds/sftp_password', $storeId, $scope));
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductFilename($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/product_filename', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductPath($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/product_path', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getNumDaysLookback($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/lookback', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getFamilyAttributes($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/bvfamilies_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return array|null
     */
    public function getFamilyAttributesArray($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $families = $this->getFamilyAttributes($storeId, $scope);
        if ($families) {
            if (strpos($families, ',') !== false) {
                $families = explode(',', $families);
            } else {
                $families = [$families];
            }
        }

        return $families;
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getTriggeringEvent($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/triggering_event', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryIdUseUrlPathEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/category_id_use_url_path', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesInheritEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/bvfamilies_inherit', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesExpandEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/bvfamilies_expand', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getInlineRatings($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('rr/inline_ratings', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getCronjobDurationLimit($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/limit', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isRrChildrenEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('rr/children', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isDccEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && $this->getConfig('feeds/enable_dcc', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isBvPixelEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->isBvEnabled($storeId, $scope) && $this->getConfig('general/enable_bvpixel', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getRrDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('rr/do_show_content_js', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getQaDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('qa/do_show_content_js', $storeId, $scope);
    }

    /**
     * Get custom configured attributes
     *
     * @param string   $type
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getAttributeCode($type, $storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('feeds/'.$type.'_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isDebugEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('general/debug', $storeId, $scope);
    }

    /**
     * If sftp host is set in config, use that.
     * Else use preset hosts based on staging or production mode.
     *
     * @param int|null $storeId
     * @param mixed    $scope
     *
     * @return string
     */
    public function getSftpHost($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $environment = $this->getEnvironment($storeId, $scope);
        $hostSelection = trim($this->getConfig('feeds/sftp_host_name', $storeId, $scope));

        if ($environment == Environment::STAGING) {
            $sftpHost = $hostSelection.'-stg.bazaarvoice.com';
        } else {
            $sftpHost = $hostSelection.'.bazaarvoice.com';
        }

        return $sftpHost;
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getEnvironment($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('general/environment', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getCloudSeoKey($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('general/cloud_seo_key', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getClientName($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('general/client_name', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLegacyDisplayCode($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('bv_config/display_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getDeploymentZone($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('general/deployment_zone', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLocale($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getConfig('general/locale', $storeId, $scope);
    }

    /**
     * @return string|null
     */
    public function getFeedGenerationScope()
    {
        return $this->getConfig('feeds/generation_scope');
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isProductPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/enable_product_prefix', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return (bool) $this->getConfig('feeds/enable_category_prefix', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getProductPrefix($storeId = null)
    {
        $prefix = '';
        if ($this->isProductPrefixEnabled($storeId)) {
            $prefix = $this->getPrefix($storeId);
        }

        return $prefix;
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCategoryPrefix($storeId = null)
    {
        $prefix = '';
        if ($this->isCategoryPrefixEnabled($storeId)) {
            $prefix = $this->getPrefix($storeId);
        }

        return $prefix;
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getPrefix($storeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $prefix = '';
        try {
            $feedGenerationScope = $this->getFeedGenerationScope();
            if ($feedGenerationScope != Scope::SCOPE_GLOBAL) {
                /** @var \Magento\Store\Model\Store $store */
                $store = $this->storeManager->getStore($storeId);
                if ($feedGenerationScope == Scope::WEBSITE) {
                    $prefix = $store->getWebsite()->getCode();
                } elseif ($feedGenerationScope == Scope::STORE_GROUP) {
                    $prefix = $store->getGroup()->getCode();
                } elseif ($feedGenerationScope == Scope::STORE_VIEW) {
                    $prefix = $store->getCode();
                }
                $prefix = $prefix . '-';
            }
            //phpcs:ignore
        } catch (LocalizedException $exception) {
            //no-op
        }

        return $prefix;
    }

    /**
     * @return mixed
     */
    public function getExtensionVersion()
    {
        /** @var \Magento\Framework\Module\ModuleResource $module */

        $objectManager = ObjectManager::getInstance();
        $module = $objectManager->get(ModuleResource::class);
        $data = $module->getDataVersion('Bazaarvoice_Connector');

        return print_r($data, $return = true);
    }

    /**
     * @return string
     */
    public function getExtensionInjectionMessage()
    {
        return __('BV | Magento Extension %1', $this->getExtensionVersion());
    }

    /**
     * Get url to bv.js javascript API file
     *
     * C2013 staging call:
     * ----------------------
     * <code>
     *   src="//apps.bazaarvoice.com/deployments/{{ClientName}}/{{DeploymentZoneName}}/{{Environment}}/{{Locale}}/bv.js"
     * </code>
     *
     * @return string
     */
    public function getBvApiHostUrl(): string
    {
        $url = '//apps.bazaarvoice.com/deployments/'
            .$this->getClientName()
            .'/'.strtolower(str_replace(' ', '_', $this->getDeploymentZone()))
            .'/'.$this->getEnvironment()
            .'/'.$this->getLocale()
            .'/bv.js';

        return $url;
    }

    /**
     * @param string   $configPath
     * @param int|null $store
     * @param mixed    $scope
     *
     * @return mixed
     */
    private function getConfig($configPath, $store = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->getDefaultConfig('bazaarvoice/'.$configPath, $store, $scope);
    }

    /**
     * @param string[] $configPaths
     * @param string $scope
     *
     * @return mixed
     */
    private function areAllConfigsEnabledInAnyScopeId(array $configPaths, $scope)
    {
        /** @var \Magento\Store\Model\Website $website */
        /** @var \Magento\Store\Model\Group $group */
        /** @var \Magento\Store\Model\Store $store */

        switch ($scope) {
            case ScopeConfigInterface::SCOPE_TYPE_DEFAULT:
                $allConfigPathsEnabledInScope = true;
                foreach ($configPaths as $configPath) {
                    if (!$this->getConfig($configPath, 0)) {
                        $allConfigPathsEnabledInScope = false;
                    }
                }

                if ($allConfigPathsEnabledInScope) {
                    return true;
                }
                break;
            case ScopeInterface::SCOPE_WEBSITE:
                $websites = $this->storeManager->getWebsites();
                foreach ($websites as $website) {
                    $allConfigPathsEnabledInScope = true;
                    foreach ($configPaths as $configPath) {
                        if (!$this->getConfig($configPath, $website->getId(), ScopeInterface::SCOPE_WEBSITE)) {
                            $allConfigPathsEnabledInScope = false;
                        }
                    }

                    if ($allConfigPathsEnabledInScope) {
                        return true;
                    }
                }
                break;
            case ScopeInterface::SCOPE_GROUP:
                $groups = $this->storeManager->getGroups();
                foreach ($groups as $group) {
                    $allConfigPathsEnabledInScope = true;
                    foreach ($configPaths as $configPath) {
                        if (!$this->getConfig($configPath, $group->getId(), ScopeInterface::SCOPE_GROUP)) {
                            $allConfigPathsEnabledInScope = false;
                        }
                    }

                    if ($allConfigPathsEnabledInScope) {
                        return true;
                    }
                }
                break;
            case ScopeInterface::SCOPE_STORE:
                $stores = $this->storeManager->getStores();
                foreach ($stores as $store) {
                    $allConfigPathsEnabledInScope = true;
                    foreach ($configPaths as $configPath) {
                        if (!$this->getConfig($configPath, $store->getId(), ScopeInterface::SCOPE_STORE)) {
                            $allConfigPathsEnabledInScope = false;
                        }
                    }

                    if ($allConfigPathsEnabledInScope) {
                        return true;
                    }
                }
        }

        return false;
    }

    /**
     * @param string $configPath
     * @param mixed  $store
     * @param string $scope
     *
     * @return mixed
     */
    private function getDefaultConfig(
        $configPath,
        $store = null,
        $scope = ScopeInterface::SCOPE_STORE
    ) {
        $value = $this->scopeConfig->getValue($configPath, $scope, $store);

        return $value;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getLocales(): array
    {
        $locales = [];
        switch ($this->getFeedGenerationScope()) {
            case Scope::STORE_VIEW:
                $stores = $this->storeManager->getStores();
                /** @var Store $store */
                foreach ($stores as $store) {
                    if ($this->canSendProductFeed($store->getId())) {
                        $localeCode = $this->getLocale($store->getId());
                        if (!empty($localeCode)) {
                            $locales[$store->getId()] = [$localeCode => $store];
                        }
                    }
                }
                break;
            case Scope::WEBSITE:
                $websites = $this->storeManager->getWebsites();
                /** @var Website $website */
                foreach ($websites as $website) {
                    $defaultStore = $website->getDefaultStore();
                    $locales[$defaultStore->getId()] = [];
                    /** @var Store $localeStore */
                    foreach ($website->getStores() as $localeStore) {
                        if ($this->canSendProductFeed($localeStore->getId())) {
                            $localeCode = $this->getLocale($localeStore->getId());
                            if (!empty($localeCode)) {
                                $locales[$defaultStore->getId()][$localeCode] = $localeStore;
                            }
                        }
                    }
                    $defaultLocale = $this->getLocale($defaultStore->getId());
                    $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
                }
                break;
            case Scope::SCOPE_GLOBAL:
                $stores = $this->storeManager->getStores();
                ksort($stores);
                /** @var Store $store */
                $globalLocales = [];
                foreach ($stores as $store) {
                    if ($this->canSendProductFeed($store->getId())) {
                        $localeCode = $this->getLocale($store->getId());
                        if (!empty($localeCode)) {
                            $globalLocales[$localeCode] = $store;
                            if (!isset($defaultStore)) {
                                $defaultStore = $store;
                            }
                        }
                    }
                }
                if (!isset($defaultStore)) {
                    throw new NoSuchEntityException(__('No valid store found for feed generation'));
                }
                $locales[$defaultStore->getId()] = $globalLocales;
                $defaultLocale = $this->getLocale($defaultStore->getId());
                $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
                break;
            case Scope::STORE_GROUP:
                $groups = $this->storeManager->getGroups();
                /** @var Group $group */
                foreach ($groups as $group) {
                    $defaultStore = $group->getDefaultStore();
                    $locales[$defaultStore->getId()] = [];
                    /** @var Store $localeStore */
                    foreach ($group->getStores() as $localeStore) {
                        if ($this->canSendProductFeed($localeStore->getId())) {
                            $localeCode = $this->getLocale($localeStore->getId());
                            if (!empty($localeCode)) {
                                $locales[$defaultStore->getId()][$localeCode] = $localeStore;
                            }
                        }
                    }
                    $defaultLocale = $this->getLocale($defaultStore->getId());
                    $locales[$defaultStore->getId()][$defaultLocale] = $defaultStore;
                }
                break;
        }

        return $locales;
    }

    /**
     * @return bool|null
     */
    public function canSendProductFeedInAnyScope()
    {
        $configScope = $this->getConfigScope($this->getFeedGenerationScope());

        return $this->areAllConfigsEnabledInAnyScopeId(['general/enable_bv', 'feeds/enable_product_feed'], $configScope);
    }

    /**
     * @param string $feedGenerationScope
     *
     * @return string
     */
    private function getConfigScope($feedGenerationScope)
    {
        switch ($feedGenerationScope) {
            case Scope::WEBSITE:
                return ScopeInterface::SCOPE_WEBSITE;
            case Scope::SCOPE_GLOBAL:
                return ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            case Scope::STORE_GROUP:
                return ScopeInterface::SCOPE_GROUP;
            case Scope::STORE_VIEW:
            default:
                return ScopeInterface::SCOPE_STORE;
        }
    }
}
