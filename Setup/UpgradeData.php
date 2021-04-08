<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Setup;

use Bazaarvoice\Connector;
use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Bazaarvoice\Connector\Model\Source\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Config\App\Config\Source\RuntimeConfigSource;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class UpgradeData
 *
 * @package Bazaarvoice\Connector\Setup
 */
class UpgradeData implements Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    protected $categorySetupFactory;
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    private $configWriter;
    /**
     * @var \Magento\Config\App\Config\Source\RuntimeConfigSource
     */
    private $runtimeConfigSource;

    /**
     * @param CategorySetupFactory                                  $categorySetupFactory
     * @param \Magento\Framework\Encryption\EncryptorInterface      $encryptor
     * @param \Magento\Store\Model\StoreManagerInterface            $storeManager
     * @param \Magento\Framework\App\Config\Storage\WriterInterface $configWriter
     * @param \Magento\Config\App\Config\Source\RuntimeConfigSource $runtimeConfigSource
     */
    public function __construct(
        CategorySetupFactory $categorySetupFactory,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        RuntimeConfigSource $runtimeConfigSource
    ) {
        $this->categorySetupFactory = $categorySetupFactory;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->runtimeConfigSource = $runtimeConfigSource;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Validate_Exception
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '7.1.4') < 0) {
            /** @var CategorySetup $eavSetup */
            $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $eavSetup->addAttribute(
                $entityTypeId,
                ProductFeed::CATEGORY_EXTERNAL_ID,
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
                    'used_in_product_listing' => true,
                ]
            );

            $attributeSetId = $eavSetup->getAttributeSetId($entityTypeId, 'Default');

            $attributeId = $eavSetup->getAttributeId(
                $entityTypeId,
                ProductFeed::CATEGORY_EXTERNAL_ID
            );
            if ($attributeId) {
                $eavSetup->addAttributeToGroup('catalog_product', 'Default', 'General', $attributeId, 75);
            }

            if (!$eavSetup->getAttributesNumberInGroup($entityTypeId, $attributeSetId, 'Product Details')) {
                $eavSetup->removeAttributeGroup($entityTypeId, $attributeSetId, 'Product Details');
            }
        }

        if (version_compare($context->getVersion(), '8.1.11') < 0) {
            /** @var CategorySetup $eavSetup */
            $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $eavSetup->updateAttribute(
                $entityTypeId,
                ProductFeed::INCLUDE_IN_FEED_FLAG,
                'note',
                'Not applicable to DCC'
            );
        }

        if (version_compare($context->getVersion(), '8.3.0') < 0) {
            $this->encryptPasswordConfig(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

            foreach ($this->storeManager->getWebsites() as $website) {
                $this->encryptPasswordConfig(ScopeInterface::SCOPE_WEBSITES, $website);
            }

            foreach ($this->storeManager->getStores() as $store) {
                $this->encryptPasswordConfig(ScopeInterface::SCOPE_STORES, $store);
            }
        }


        if (version_compare($context->getVersion(), '9.0.0') < 0) {
            /** @var CategorySetup $eavSetup */
            $eavSetup = $this->categorySetupFactory->create(['setup' => $setup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            if ($eavSetup->getAttribute($entityTypeId, 'bv_feed_exclude')) {
                $eavSetup->updateAttribute(
                    $entityTypeId,
                    'bv_feed_exclude',
                    'attribute_code',
                    'bv_feed_include' //just fixes the code to reflect that it's been used as an include flag for years
                );
            }
        }
    }

    /**
     * @param $scope
     * @param $store
     */
    private function encryptPasswordConfig($scope, $store = null)
    {
        $formattedScopeId = $store ? "/{$store->getCode()}" : '';
        /**
         * Not using \Magento\Framework\App\Config\ScopeConfigInterface specifically to prevent retrieval of
         * inherited configuration values, otherwise those inherited values will be written back to core_config_data.
         */
         if (($password = $this->runtimeConfigSource->get("$scope$formattedScopeId/bazaarvoice/feeds/sftp_password"))) {
            $decryptedPassword = $this->encryptor->decrypt($password);
            $passwordIsEncrypted = mb_check_encoding($decryptedPassword, 'UTF-8');
            if (!$passwordIsEncrypted) {
                $encryptedPassword = $this->encryptor->encrypt($password);
                $this->configWriter->save('bazaarvoice/feeds/sftp_password', $encryptedPassword, $scope, $store ? $store->getId() : 0);
            }
        }
    }
}
