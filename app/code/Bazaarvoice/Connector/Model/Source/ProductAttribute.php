<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\Source;

use \Magento\Framework\ObjectManagerInterface;

class ProductAttribute
{
    /** @var ObjectManagerInterface $objectManager */
    protected $objectManager;

    /**
     * ProductAttribute constructor.
     * @param ObjectManagerInterface $interface
     */
    public function __construct(
        ObjectManagerInterface $interface
    ) {
        $this->objectManager = $interface;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory */
        $factory = $this->objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory');
        $attributes = $factory->create();

        $attributeOptions = array(array(
            'label' => __('-- Please Select --'),
            'value' => ''
        ));

        foreach($attributes as $attribute){
            if($attribute->getIsUserDefined() == 0) continue;
            $attributeOptions[] = array(
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            );
        }

        return $attributeOptions;
    }

}