<?php

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Serialize\SerializerInterface;

class Index extends AbstractModel implements IndexInterface, IdentityInterface
{
    const CACHE_TAG = 'bazaarvoice_product_index';

    const CUSTOM_ATTRIBUTES = array('UPC', 'ManufacturerPartNumber', 'EAN', 'ISBN', 'ModelNumber');
    protected $_generationScope;
    protected $_helper;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index $resource
     * @param Collection $resourceCollection
     * @param Data $helper
     */
    // @codingStandardsIgnoreStart
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Bazaarvoice\Connector\Model\ResourceModel\Index $resource = null,
        Collection $resourceCollection = null,
        Data $helper
    )
    {
        // @codingStandardsIgnoreEnd
        $this->_init('Bazaarvoice\Connector\Model\ResourceModel\Index');
        $this->_generationScope = $helper->getConfig('feeds/generation_scope');
        $this->_helper = $helper;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @param      $productId
     * @param      $storeId
     * @param null $scope
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByStore($productId, $storeId, $scope = null)
    {
        if (is_object($productId))
            $productId = $productId->getId();

        if (is_object($storeId))
            $storeId = $storeId->getId();

        $scope = $scope ? $scope : $this->_generationScope;

        /** @var ResourceModel\Index $resource */
        $resource = $this->getResource();
        $index = $resource->loadBy(array(
            'product_id' => $productId,
            'scope' => $scope,
            'store_id' => $storeId));

        if ($index)
            $this->setData($index);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent() {
        if($this->_helper->getConfig('feeds/families')) {
            if(!empty($this->getData('family')))
                return true;
        }
        return false;
    }

    /**
     * @return array|mixed
     */
    public function getParents() {
        if($this->hasParent()) {
            return $this->_helper->jsonDecode($this->getData('family'));
        }
        return [];
    }

    public function setLocaleDescription($localeDescription)
    {
        return $this->setJsonField('locale_description', $localeDescription);
    }

    public function setLocaleImageUrl($localeImageUrl)
    {
        return $this->setJsonField('locale_image_url', $localeImageUrl);
    }

    public function setLocaleName($localeName)
    {
        return $this->setJsonField('locale_name', $localeName);
    }

    public function setLocaleProductPageUrl($localeProductPageUrl)
    {
        return $this->setJsonField('locale_product_page_url', $localeProductPageUrl);
    }

    public function getLocaleDescription()
    {
        return $this->getJsonField('locale_description');
    }

    public function getLocaleImageUrl()
    {
        return $this->getJsonField('locale_image_url');
    }

    public function getLocaleProductPageUrl()
    {
        return $this->getJsonField('locale_product_page_url');
    }

    public function getLocaleName()
    {
        return $this->getJsonField('locale_name');
    }

    public function addLocaleDescription($value)
    {
        return $this->addJsonField('locale_description', $value);
    }
    
    public function addLocaleImageUrl($value)
    {
        return $this->addJsonField('locale_image_url', $value);
    }

    public function addLocaleProductPageUrl($value)
    {
        return $this->addJsonField('locale_product_page_url', $value);
    }

    public function addLocaleName($value)
    {
        return $this->addJsonField('locale_name', $value);
    }
    
    public function addJsonField($field, $value)
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

    private function setJsonField($field, $value)
    {
        if (is_array($value)) {
            $this->setData($field, $this->_helper->jsonEncode($value));
        } else {
            $this->setData($field, $value);
        }

        return $this;
    }

    private function getJsonField($field)
    {
        $value = $this->getData($field);
        if (!is_array($value)) {
            return $this->_helper->jsonDecode($value);
        }
        return $value;
    }
}
