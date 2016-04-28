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
use Bazaarvoice\Connector;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\Product;


class InstallData implements Setup\InstallDataInterface
{
    /** @var SalesSetupFactory */
    protected $salesSetupFactory;

    /** @var EavSetup */
    protected $eavSetupFactory;

    /**
     * Init
     *
     * @param SalesSetupFactory $salesSetupFactory
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->salesSetupFactory = $salesSetupFactory;
        $this->eavSetupFactory = $eavSetupFactory;
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

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

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
                'user_defined' => true,
                'default' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED,
                'apply_to' => '',
                'visible_on_front' => false,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false,
            ]
        );

    }

}