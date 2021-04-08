<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Setup;

use Bazaarvoice\Connector;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UninstallInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;

class Uninstall implements UninstallInterface
{
    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    private $categorySetupFactory;
    /**
     * @var \Magento\Sales\Setup\SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * Uninstall constructor.
     *
     * @param \Magento\Sales\Setup\SalesSetupFactory      $salesSetupFactory
     * @param \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        SalesSetupFactory $salesSetupFactory,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->salesSetupFactory = $salesSetupFactory;
    }

    /**
     * Module uninstall code
     *
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     *
     * @return void
     */
    public function uninstall(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $setup->startSetup();

        $salesSetup = $this->salesSetupFactory->create(['setup' => $setup]);
        $salesSetup->removeAttribute(
            Order::ENTITY,
            Connector\Model\Feed\PurchaseFeed::ALREADY_SENT_IN_FEED_FLAG
        );

        $catalogSetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $entityTypeId = $catalogSetup->getEntityTypeId(Product::ENTITY);

        if ($catalogSetup->getAttribute($entityTypeId, 'bv_feed_exclude')) {
            $catalogSetup->removeAttribute($entityTypeId, 'bv_feed_exclude');
        }

        if ($catalogSetup->getAttribute($entityTypeId, ProductFeed::INCLUDE_IN_FEED_FLAG)) {
            $catalogSetup->removeAttribute($entityTypeId, ProductFeed::INCLUDE_IN_FEED_FLAG);
        }

        if ($catalogSetup->getAttribute($entityTypeId, ProductFeed::CATEGORY_EXTERNAL_ID)) {
            $catalogSetup->removeAttribute($entityTypeId, ProductFeed::CATEGORY_EXTERNAL_ID);
        }

        $setup->endSetup();
    }
}
