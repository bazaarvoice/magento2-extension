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

namespace Bazaarvoice\Connector\Setup;

use Magento\Framework\Setup;
use Magento\Framework\ObjectManagerInterface;
use Bazaarvoice\Connector;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\Product;


class InstallData implements Setup\InstallDataInterface
{
    /** @var SalesSetupFactory */
    protected $salesSetupFactory;

    /** @var CategorySetupFactory */
    protected $categorySetupFactory;

    /** @var ObjectManagerInterface */
    protected $objectManager;

    /**
     * Init
     *
     * @param SalesSetupFactory $salesSetupFactory
     * @param CategorySetupFactory $categorySetupFactory
     * @param ObjectManagerInterface $objectManger
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        CategorySetupFactory $categorySetupFactory,
        ObjectManagerInterface $objectManger,
        \Magento\Framework\App\State $state
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->objectManager = $objectManger;
        $state->setAreaCode('frontend');
    }


    /**
     * {@inheritdoc}
     */
    public function install(Setup\ModuleDataSetupInterface $setup, Setup\ModuleContextInterface $context)
    {
        /** @var SalesSetup $eavSetup */
        $eavSetup = $this->salesSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Order::ENTITY,
            Connector\Model\Feed\PurchaseFeed::ALREADY_SENT_IN_FEED_FLAG,
            [
                'type' =>  \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'visible' => false,
                'required' => false,
                'default' => 0
            ]
        );

        /** @var CategorySetup $eavSetup */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            Product::ENTITY,
            Connector\Model\Feed\ProductFeed::INCLUDE_IN_FEED_FLAG,
            [
                'group' => 'Product Details',
                'type' =>  'int',
                'frontend' => '',
                'label' => 'Send in Bazaarvoice Product Feed',
                'input' => 'select',
                'class' => '',
                'source' => 'Magento\Catalog\Model\Product\Attribute\Source\Status',
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible' => true,
                'required' => false,
                'user_defined' => false,
                'default' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                'apply_to' => '',
                'visible_on_front' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
            ]
        );

        $groupName = 'Autosettings';
        $entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
        $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'Default');

        $attribute = $eavSetup->getAttribute($entityTypeId, Connector\Model\Feed\ProductFeed::INCLUDE_IN_FEED_FLAG);
        if ($attribute) {
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupName,
                $attribute['attribute_id'],
                60
            );
        }

        if (!$eavSetup->getAttributesNumberInGroup($entityTypeId, $attributeSetId, 'Product Details')) {
            $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Product Details');
        }

        $attrData = array(
            Connector\Model\Feed\ProductFeed::INCLUDE_IN_FEED_FLAG => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
        );

        $storeId = 0;

        /** @var \Magento\Catalog\Model\ProductFactory $productFactory */
        $productFactory = $this->objectManager->get('\Magento\Catalog\Model\ProductFactory');
        $productIds = $productFactory->create()->getCollection()->getAllIds();
        /** @var \Magento\Catalog\Model\Product\Action $action */
        $this->objectManager->get('\Magento\Catalog\Model\Product\Action')->updateAttributes(
            $productIds,
            $attrData,
            $storeId
        );

    }

}