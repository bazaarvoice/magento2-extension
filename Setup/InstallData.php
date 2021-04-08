<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Setup;

use Bazaarvoice\Connector;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * Class InstallData
 *
 * @package Bazaarvoice\Connector\Setup
 */
class InstallData implements Setup\InstallDataInterface
{
    /** @var SalesSetupFactory */
    protected $salesSetupFactory;

    /** @var CategorySetupFactory */
    protected $categorySetupFactory;

    /**
     * @param SalesSetupFactory    $salesSetupFactory
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    public function install(Setup\ModuleDataSetupInterface $setup, Setup\ModuleContextInterface $context)
    {
        /** @var SalesSetup $eavSetup */
        $eavSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Order::ENTITY,
            Connector\Model\Feed\PurchaseFeed::ALREADY_SENT_IN_FEED_FLAG,
            [
                'type'     => Table::TYPE_INTEGER,
                'visible'  => false,
                'required' => false,
                'default'  => 0,
            ]
        );

        /** @var CategorySetup $eavSetup */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            Connector\Model\Feed\ProductFeed::INCLUDE_IN_FEED_FLAG,
            [
                'group'                   => 'Product Details',
                'type'                    => 'int',
                'frontend'                => '',
                'label'                   => 'Send in Bazaarvoice Product Feed',
                'input'                   => 'boolean',
                'class'                   => '',
                'source'                  => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => false,
                'default'                 => Boolean::VALUE_YES,
                'apply_to'                => '',
                'visible_on_front'        => false,
                'is_used_in_grid'         => true,
                'is_visible_in_grid'      => false,
                'is_filterable_in_grid'   => false,
                'used_in_product_listing' => true
            ]
        );

        $groupName = 'Autosettings';
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'Default');

        $attribute = $eavSetup->getAttribute($entityTypeId, Connector\Model\Feed\ProductFeed::INCLUDE_IN_FEED_FLAG);
        if ($attribute && $attributeSetId) {
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupName,
                $attribute['attribute_id'],
                60
            );
        }

        if ($attributeSetId && !$eavSetup->getAttributesNumberInGroup($entityTypeId, $attributeSetId, 'Product Details')) {
            $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Product Details');
        }
    }
}
