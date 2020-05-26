<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Catalog\Model\ResourceModel\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as AttributeCollection;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ProductAttribute
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class ProductAttribute implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $productAttributeCollectionFactory;

    /**
     * ProductAttribute constructor.
     *
     * @param CollectionFactory $attributeCollectionFactory
     */
    public function __construct(CollectionFactory $attributeCollectionFactory) {
        $this->productAttributeCollectionFactory = $attributeCollectionFactory;
    }

    /**
     * @param bool $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        $attributeOptions = [];
        if (!$isMultiselect) {
            $attributeOptions[] = [
                'label' => __('-- Please Select --'),
                'value' => ''
            ];
        }

        /** @var AttributeCollection $attributes */
        $attributes = $this->productAttributeCollectionFactory->create();
        $attributes->addFieldToFilter('used_in_product_listing', '1');

        /** @var Attribute $attribute */
        foreach ($attributes as $attribute) {
            $attributeOptions[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return $attributeOptions;
    }
}
