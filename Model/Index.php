<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use Bazaarvoice\Connector\Api\Data\IndexInterface;
use Bazaarvoice\Connector\Api\StringFormatterInterface;
use Bazaarvoice\Connector\Model\ResourceModel\Index as IndexResourceModel;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;

/**
 * Class Index
 *
 * @package Bazaarvoice\Connector\Model
 */
class Index extends AbstractModel implements IndexInterface
{
    const CUSTOM_ATTRIBUTES = ['UPC', 'ManufacturerPartNumber', 'EAN', 'ISBN', 'ModelNumber'];
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;
    /**
     * @var StringFormatterInterface
     */
    private $stringFormatter;

    /**
     * @param \Magento\Framework\Model\Context                                 $context
     * @param \Magento\Framework\Registry                                      $registry
     * @param ConfigProviderInterface                                          $configProvider
     * @param StringFormatterInterface                                         $stringFormatter
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index|null            $resource
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection|null $resourceCollection
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ConfigProviderInterface $configProvider,
        StringFormatterInterface $stringFormatter,
        ResourceModel\Index $resource = null,
        Collection $resourceCollection = null
    ) {
        $this->_init(IndexResourceModel::class);
        $this->configProvider = $configProvider;
        $this->stringFormatter = $stringFormatter;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    /**
     * @param $productId
     * @param $storeId
     * @param null $scope
     *
     * @return $this|\Bazaarvoice\Connector\Api\Data\IndexInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByStore($productId, $storeId, $scope = null)
    {
        if (is_object($productId)) {
            $productId = $productId->getId();
        }

        if (is_object($storeId)) {
            $storeId = $storeId->getId();
        }

        $scope = $scope ?: $this->configProvider->getFeedGenerationScope();

        /**
         * @var ResourceModel\Index $resource 
         */
        $resource = $this->getResource();
        $index = $resource->loadBy(
            [
            'product_id' => $productId,
            'scope'      => $scope,
            'store_id'   => $storeId,
            ]
        );

        if ($index) {
            $this->setData($index);
        }

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function getParents()
    {
        if ($this->hasParent()) {
            return $this->stringFormatter->jsonDecode($this->getData('family'));
        }

        return [];
    }

    /**
     * @return bool
     */
    public function hasParent(): bool
    {
        if ($this->configProvider->isFamiliesEnabled()) {
            if (!empty($this->getData('family'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $localeDescription
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function setLocaleDescription($localeDescription): Index
    {
        return $this->setJsonField('locale_description', $localeDescription);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     */
    private function setJsonField($field, $value): Index
    {
        if (is_array($value)) {
            $this->setData($field, $this->stringFormatter->jsonEncode($value));
        } else {
            $this->setData($field, $value);
        }

        return $this;
    }

    /**
     * @param $localeImageUrl
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function setLocaleImageUrl($localeImageUrl): Index
    {
        return $this->setJsonField('locale_image_url', $localeImageUrl);
    }

    /**
     * @param $localeName
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function setLocaleName($localeName): Index
    {
        return $this->setJsonField('locale_name', $localeName);
    }

    /**
     * @param $localeProductPageUrl
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function setLocaleProductPageUrl($localeProductPageUrl): Index
    {
        return $this->setJsonField('locale_product_page_url', $localeProductPageUrl);
    }

    /**
     * @return mixed
     */
    public function getLocaleDescription()
    {
        return $this->getJsonField('locale_description');
    }

    /**
     * @param $field
     *
     * @return mixed
     */
    private function getJsonField($field)
    {
        $value = $this->getData($field);
        if (is_string($value)) {
            return $this->stringFormatter->jsonDecode($value);
        }

        return $value;
    }

    /**
     * @return mixed
     */
    public function getLocaleImageUrl()
    {
        return $this->getJsonField('locale_image_url');
    }

    /**
     * @return mixed
     */
    public function getLocaleProductPageUrl()
    {
        return $this->getJsonField('locale_product_page_url');
    }

    /**
     * @return mixed
     */
    public function getLocaleName()
    {
        return $this->getJsonField('locale_name');
    }

    /**
     * @param $value
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function addLocaleDescription($value): Index
    {
        return $this->addJsonField('locale_description', $value);
    }

    /**
     * @param $field
     * @param $value
     *
     * @return $this
     */
    public function addJsonField($field, $value): Index
    {
        $fieldData = $this->getJsonField($field);
        if (isset($value)) {
            if (!$fieldData) {
                $fieldData = [];
            }
            $fieldData = array_merge($fieldData, $value);
            $this->setJsonField($field, $fieldData);
        }

        return $this;
    }

    /**
     * @param $value
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function addLocaleImageUrl($value): Index
    {
        return $this->addJsonField('locale_image_url', $value);
    }

    /**
     * @param $value
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function addLocaleProductPageUrl($value): Index
    {
        return $this->addJsonField('locale_product_page_url', $value);
    }

    /**
     * @param $value
     *
     * @return \Bazaarvoice\Connector\Model\Index
     */
    public function addLocaleName($value): Index
    {
        return $this->addJsonField('locale_name', $value);
    }
}
