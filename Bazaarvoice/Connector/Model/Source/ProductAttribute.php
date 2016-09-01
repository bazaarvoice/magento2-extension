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

namespace Bazaarvoice\Connector\Model\Source;

use \Magento\Framework\ObjectManagerInterface;

class ProductAttribute
{
    /** @var ObjectManagerInterface $_objectManager */
    protected $_objectManager;

    /**
     * ProductAttribute constructor.
     * @param ObjectManagerInterface $interface
     */
    public function __construct(
        ObjectManagerInterface $interface
    )
    {
        $this->_objectManager = $interface;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory */
        $factory = $this->_objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory');
        $attributes = $factory->create();

        $attributeOptions = array(array(
            'label' => __('-- Please Select --'),
            'value' => ''
        ));

        foreach ($attributes as $attribute) {
            if (
                $attribute->getIsUserDefined() == 0
                || $attribute->getUsedInProductListing() == 0
            )
                continue;
            $attributeOptions[] = array(
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            );
        }

        return $attributeOptions;
    }

}