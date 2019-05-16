<?php

namespace Bazaarvoice\Connector\Api;

use Magento\Store\Model\ScopeInterface;

/**
 * Interface ConfigProviderInterface
 *
 * @package Bazaarvoice\Connector\Api
 */
interface ConfigProviderInterface
{
    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isCloudSeoEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param string $type
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool|null
     */
    public function canSendFeed($type, $storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool
     */
    public function canSendPurchaseFeed($storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isBvEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isQaEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     *
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isRrEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return bool|null
     */
    public function canSendProductFeed($storeId, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool|null
     */
    public function isFamiliesEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return string|null
     */
    public function getSftpUsername($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int    $storeId
     * @param string $scope
     *
     * @return string|null
     */
    public function getSftpPassword($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductFilename($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getProductPath($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getNumDaysLookback($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getFamilyAttributes($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return array|null
     */
    public function getFormattedFamilyAttributes($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?array;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getTriggeringEvent($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryIdUseUrlPathEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesInheritEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isFamiliesExpandEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getInlineRatings($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getCronjobDurationLimit($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isRrChildrenEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function isDccEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function isBvPixelEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getRrDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getQaDoShowContentJs($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * Get custom configured attributes
     *
     * @param string   $type
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string
     */
    public function getAttributeCode(string $type, $storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isDebugEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * If sftp host is set in config, use that.
     * Else use preset hosts based on staging or production mode.
     *
     * @param int|null $storeId
     * @param mixed    $scope
     *
     * @return string
     */
    public function getSftpHost($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getEnvironment($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getCloudSeoKey($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

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
    public function getBvApiHostUrl(): string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getClientName($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLegacyDisplayCode($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getDeploymentZone($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return mixed
     */
    public function getLocale($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;

    /**
     * @return mixed
     */
    public function getExtensionVersion(): ?string;

    /**
     * @return array
     * @throws \Exception
     */
    public function getLocales(): array;

    /**
     * @return string|null
     */
    public function getFeedGenerationScope(): ?string;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isProductPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return bool
     */
    public function isCategoryPrefixEnabled($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?bool;

    /**
     * @param int|null $storeId
     * @param string   $scope
     *
     * @return string|null
     */
    public function getPrefix($storeId = null, $scope = ScopeInterface::SCOPE_STORE): ?string;
}
