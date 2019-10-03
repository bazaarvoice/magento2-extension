<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Exception;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ProductAttribute
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class ProductAttribute implements OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $productAttributeCollectionFactory;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * ProductAttribute constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection                                $resourceConnection
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->productAttributeCollectionFactory = $attributeCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param bool $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes */
        $attributes = $this->productAttributeCollectionFactory->create();

        $stores = $this->storeManager->getStores();
        $defaultStore = null;
        /** @var Store $store */
        foreach ($stores as $store) {
            if (isset($defaultStore) == false) {
                $defaultStore = $store;
                break;
            }
        }

        if (!$isMultiselect) {
            $attributeOptions = [
                [
                    'label' => __('-- Please Select --'),
                    'value' => '',
                ],
            ];
        } else {
            $attributeOptions = [];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getIsUserDefined() == 0
                || $attribute->getUsedInProductListing() == 0
            ) {
                continue;
            }
            $attributeOptions[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return $attributeOptions;
    }
}
