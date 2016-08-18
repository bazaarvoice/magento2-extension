<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright   (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package     Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model;

use Bazaarvoice\Connector\Helper\Data;
use Bazaarvoice\Connector\Model\ResourceModel\Index\Collection;

class Index extends \Magento\Framework\Model\AbstractModel implements \Bazaarvoice\Connector\Model\IndexInterface, \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'bazaarvoice_product_index';

    /** Custom Attributes */
    public $customAttributes = array('UPC', 'ManufacturerPartNumber', 'EAN', 'ISBN', 'ModelNumber');
    protected $generationScope;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index $resource
     * @param Collection $resourceCollection
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Bazaarvoice\Connector\Model\ResourceModel\Index $resource = null,
        Collection $resourceCollection = null,
        Data $helper
    )
    {
        $this->_init('Bazaarvoice\Connector\Model\ResourceModel\Index');
        $this->generationScope = $helper->getConfig('feeds/generation_scope');
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
     * @return Index
     */
    public function loadByStore($productId, $storeId, $scope = null) {

        if(is_object($productId))
            $productId = $productId->getId();

        if(is_object($storeId))
            $storeId = $storeId->getId();

        $scope = $scope ? $scope : $this->generationScope;

        /** @var ResourceModel\Index $resource */
        $resource = $this->getResource();
        $index = $resource->loadBy(array(
            'product_id' => $productId,
            'scope' => $scope,
            'store_id' => $storeId));

        if($index)
            $this->setData($index);

        return $this;
    }
}