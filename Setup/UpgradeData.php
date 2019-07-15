<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Setup;

use Bazaarvoice\Connector;
use Bazaarvoice\Connector\Model\Source\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;

/**
 * Class UpgradeData
 *
 * @package Bazaarvoice\Connector\Setup
 */
class UpgradeData implements Setup\UpgradeDataInterface
{

    /** @var CategorySetupFactory */
    protected $categorySetupFactory;

    /**
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '7.1.4') < 0) {

            /** @var CategorySetup $eavSetup */
            $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $eavSetup->addAttribute(
                $entityTypeId,
                Connector\Model\Feed\ProductFeed::CATEGORY_EXTERNAL_ID,
                [
                    'group'                   => 'Product Details',
                    'type'                    => 'int',
                    'frontend'                => '',
                    'label'                   => 'Bazaarvoice Category',
                    'input'                   => 'select',
                    'class'                   => '',
                    'source'                  => Category::class,
                    'global'                  => ScopedAttributeInterface::SCOPE_STORE,
                    'visible'                 => true,
                    'required'                => false,
                    'user_defined'            => false,
                    'default'                 => '',
                    'apply_to'                => '',
                    'visible_on_front'        => false,
                    'is_used_in_grid'         => true,
                    'is_visible_in_grid'      => false,
                    'is_filterable_in_grid'   => false,
                    'used_in_product_listing' => true
                ]
            );

            $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'Default');

            $attributeId = $eavSetup->getAttributeId(
                $entityTypeId,
                Connector\Model\Feed\ProductFeed::CATEGORY_EXTERNAL_ID
            );
            if ($attributeId) {
                $eavSetup->addAttributeToGroup('catalog_product', 'Default', 'General', $attributeId, 75);
            }

            if (!$eavSetup->getAttributesNumberInGroup($entityTypeId, $attributeSetId, 'Product Details')) {
                $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Product Details');
            }
        }
    }
}
