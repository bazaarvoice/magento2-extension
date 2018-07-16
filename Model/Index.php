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

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;

class Index
    extends \Magento\Framework\Model\AbstractModel
    implements \Bazaarvoice\Connector\Model\IndexInterface, \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'bazaarvoice_product_index';

    /** Custom Attributes */
    public $customAttributes = array('UPC', 'ManufacturerPartNumber', 'EAN', 'ISBN', 'ModelNumber');
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
	 * @param \Magento\Catalog\Model\Product|int $productId
	 * @param \Magento\Store\Model\Store|int $storeId
	 * @param $scope
	 *
	 * @return Index
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
}