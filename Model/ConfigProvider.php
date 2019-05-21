<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Model\Source\Environment;
use Bazaarvoice\Connector\Model\Source\Scope;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
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
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isCloudSeoEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && $this->getConfig('general/enable_cloud_seo', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isBvEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('general/enable_bv', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isQaEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && (bool) $this->getConfig('qa/enable_qa', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isRrEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && (bool) $this->getConfig('rr/enable_rr', $storeId, $scope);
    }

    /**
     * @param string $type
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool|null
     */
    public function canSendFeed($type, $storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        if ($type == 'purchase') {
            return $this->canSendPurchaseFeed($storeId, $scope);
        }

        return $this->canSendProductFeed($storeId, $scope);
    }

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool|null
     */
    public function canSendProductFeed($storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && $this->isProductFeedEnabled($storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool|null
     */
    private function isProductFeedEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/enable_product_feed', $storeId, $scope);
    }

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool
     */
    public function canSendPurchaseFeed($storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && $this->isPurchaseFeedEnabled($storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool|null
     */
    private function isPurchaseFeedEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/enable_purchase_feed', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isFamiliesEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('general/families', $storeId, $scope);
    }

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return string|null
     */
    public function getSftpUsername($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/sftp_username', $storeId, $scope);
    }

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return string|null
     */
    public function getSftpPassword($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/sftp_password', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductFilename($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/product_filename', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductPath($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/product_path', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getNumDaysLookback($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/lookback', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getFamilyAttributes($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/bvfamilies_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return array|null
     */
    public function getFamilyAttributesArray($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?array
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
    public function getTriggeringEvent($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/triggering_event', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryIdUseUrlPathEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/category_id_use_url_path', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesInheritEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/bvfamilies_inherit', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesExpandEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/bvfamilies_expand', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getInlineRatings($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('rr/inline_ratings', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getCronjobDurationLimit($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/limit', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isRrChildrenEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('rr/children', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function isDccEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && $this->getConfig('feeds/enable_dcc', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function isBvPixelEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return $this->isBvEnabled($storeId, $scope) && $this->getConfig('general/enable_bvpixel', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getRrDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('rr/do_show_content_js', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getQaDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
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
    public function getAttributeCode(string $type, $storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('feeds/'.$type.'_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isDebugEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
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
    public function getSftpHost($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
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
    public function getEnvironment($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('general/environment', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getCloudSeoKey($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('general/cloud_seo_key', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getClientName($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('general/client_name', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLegacyDisplayCode($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('bv_config/display_code', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getDeploymentZone($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('general/deployment_zone', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLocale($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
    {
        return $this->getConfig('general/locale', $storeId, $scope);
    }

    /**
     * @return string|null
     */
    public function getFeedGenerationScope(): ?string
    {
        return $this->getConfig('feeds/generation_scope');
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isProductPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/enable_product_prefix', $storeId, $scope);
    }

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool
    {
        return (bool) $this->getConfig('feeds/enable_category_prefix', $storeId, $scope);
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getProductPrefix($storeId)
    {
        $prefix = '';
        if ($this->isProductPrefixEnabled($storeId)) {
            $prefix = $this->getPrefix($storeId);
        }

        return $prefix;
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getCategoryPrefix($storeId)
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
    public function getPrefix($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string
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
    public function getExtensionVersion(): ?string
    {
        /** @var \Magento\Framework\Module\ModuleResource $module */

        $objectManager = ObjectManager::getInstance();
        $module = $objectManager->get(ModuleResource::class);
        $data = $module->getDataVersion('Bazaarvoice_Connector');

        return print_r($data, $return = true);
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
}
