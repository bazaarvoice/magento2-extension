<?php 
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

namespace Bazaarvoice\Connector\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
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
use Magento\Framework\Module\ModuleListInterface;
use Magento\Sales\Setup\SalesSetupFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class UpgradeBvDataAtttribute implements DataPatchInterface, PatchRevertableInterface
{
    const MODULE_NAME = 'Bazaarvoice_Connector';
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
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

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        RuntimeConfigSource $runtimeConfigSource,
        ModuleListInterface $moduleList
    ) {
        
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->runtimeConfigSource = $runtimeConfigSource;
        $this->_moduleList = $moduleList;
    }

    /**
    * {@inheritdoc}
    */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        
        /** @var CategorySetup $eavSetup, */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
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

            
        /** @var CategorySetup $eavSetup */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        $eavSetup->updateAttribute(
            $entityTypeId,
            ProductFeed::INCLUDE_IN_FEED_FLAG,
            'note',
            'Not applicable to DCC'
        );
    
         $logger->info("function3");
        $this->encryptPasswordConfig(ScopeConfigInterface::SCOPE_TYPE_DEFAULT);

        foreach ($this->storeManager->getWebsites() as $website) {
            $this->encryptPasswordConfig(ScopeInterface::SCOPE_WEBSITES, $website);
        }

        foreach ($this->storeManager->getStores() as $store) {
            $this->encryptPasswordConfig(ScopeInterface::SCOPE_STORES, $store);
        }


        $logger->info("function4");
        /** @var CategorySetup $eavSetup */
        $eavSetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        if ($eavSetup->getAttribute($entityTypeId, 'bv_feed_exclude')) {
            $eavSetup->updateAttribute(
                $entityTypeId,
                'bv_feed_exclude',
                'attribute_code',
                'bv_feed_include' //just fixes the code to reflect that it's been used as an include flag for years
            );
        }

        $eavSetup->updateAttribute(
            $entityTypeId,
            ProductFeed::INCLUDE_IN_FEED_FLAG,
            'default',
            Boolean::VALUE_YES
        );
        foreach ($eavSetup->getAllAttributeSetIds() as $attributeSetId) {
            if (!$eavSetup->getAttributeGroup($entityTypeId, $attributeSetId, 'Bazaarvoice')) {
                $eavSetup->addAttributeGroup($entityTypeId, $attributeSetId, 'Bazaarvoice');
            }
            $attributeGroupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Bazaarvoice');
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $attributeGroupId,
                ProductFeed::INCLUDE_IN_FEED_FLAG
            );
            $eavSetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $attributeGroupId,
                ProductFeed::CATEGORY_EXTERNAL_ID
            );
        }


        $this->moduleDataSetup->getConnection()->endSetup();

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

    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $salesSetup = $this->salesSetupFactory->create();
        $salesSetup->removeAttribute(
            Order::ENTITY,
            Connector\Model\Feed\PurchaseFeed::ALREADY_SENT_IN_FEED_FLAG
        );

        $catalogSetup = $this->categorySetupFactory->create();
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

        $this->moduleDataSetup->getConnection()->endSetup();

    }

    public function getAliases()
    {
        return [];
    }	

    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
    * {@inheritdoc}
    */
    public static function getVersion()
    {
        return '9.0.0';
    }
}