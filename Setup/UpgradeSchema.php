<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Setup;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 *
 * @package Bazaarvoice\Connector\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @param \Magento\Framework\Setup\SchemaSetupInterface   $setup
     * @param \Magento\Framework\Setup\ModuleContextInterface $context
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        /** handle all possible upgrade versions */

//        if (!$context->getVersion()) {
//
//        }

        if (version_compare($context->getVersion(), '7.0.1.1') < 0) {

            $table = $setup->getTable('bazaarvoice_index_product');

            $setup->run("DROP TABLE IF EXISTS $table;");

            $setup->run("
                CREATE TABLE $table (
                  `entity_id` int(11) unsigned NOT NULL auto_increment,
                  
                  `product_id` int(10) unsigned NOT NULL DEFAULT '0',
                  `product_type` varchar(32) NOT NULL DEFAULT '',
                  `external_id` varchar(255) NOT NULL default '',
                  `category_external_id` varchar(255) NULL,
                  `brand_external_id` varchar(255) NULL,
                  
                  `family` varchar(255) NULL,
                  
                  `name` TEXT NOT NULL default '',
                  `locale_name` text NULL,
                  `description` TEXT NOT NULL default '',
                  `locale_description` text NULL,
                  `product_page_url` TEXT NOT NULL default '',
                  `locale_product_page_url` text NULL,
                  `image_url` TEXT NULL,
                  `locale_image_url` text NULL,
                    
                  `upcs` text NULL,
                  `eans` text NULL,
                  `isbns` text NULL,
                  `manufacturerpartnumbers` text NULL,  
                  `modelnumbers` text NULL,
                  
                  `scope` varchar(255) NULL,
                  `store_id` int(11) NOT NULL default '0',  
                  `status` int(2) unsigned NOT NULL DEFAULT '".Status::STATUS_ENABLED."',
                  `version_id` bigint(20) unsigned default '0',
                    
                  PRIMARY KEY (`entity_id`),
                  UNIQUE KEY `product_id` (`product_id`,`scope`,`store_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        }

        $setup->endSetup();
    }
}
