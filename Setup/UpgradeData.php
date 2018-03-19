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

namespace Bazaarvoice\Connector\Setup;

use Magento\Framework\App\State;
use Magento\Framework\Setup;
use Magento\Framework\ObjectManagerInterface;
use Bazaarvoice\Connector;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetup;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Sales\Model\Order;
use Magento\Catalog\Model\Product;


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
	 * @param ModuleContextInterface $context
	 *
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function upgrade( ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {


		if (version_compare($context->getVersion(), '7.1.4') < 0) {

			/** @var CategorySetup $eavSetup */
			$eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);
			$entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

			$eavSetup->addAttribute(
				$entityTypeId,
				Connector\Model\Feed\ProductFeed::CATEGORY_EXTERNAL_ID,
				[
					'group' => 'Product Details',
					'type' =>  'int',
					'frontend' => '',
					'label' => 'Bazaarvoice Category',
					'input' => 'select',
					'class' => '',
					'source' => 'Bazaarvoice\Connector\Model\Source\Category',
					'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
					'visible' => true,
					'required' => false,
					'user_defined' => false,
					'default' => '',
					'apply_to' => '',
					'visible_on_front' => false,
					'is_used_in_grid' => true,
					'is_visible_in_grid' => false,
					'is_filterable_in_grid' => false,
					'used_in_product_listing' => true
				]
			);

			$attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'Default');

			$attributeId = $eavSetup->getAttributeId($entityTypeId, Connector\Model\Feed\ProductFeed::CATEGORY_EXTERNAL_ID);
			if ($attributeId) {
				$eavSetup->addAttributeToGroup('catalog_product', 'Default', 'General', $attributeId, 75);
			}

			if (!$eavSetup->getAttributesNumberInGroup($entityTypeId, $attributeSetId, 'Product Details')) {
				$eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Product Details');
			}
		}

	}

}
