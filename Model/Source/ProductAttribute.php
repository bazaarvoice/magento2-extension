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

namespace Bazaarvoice\Connector\Model\Source;

use \Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;

class ProductAttribute
{
    /** @var ObjectManagerInterface $_objectManager */
    protected $_objectManager;

    /**
     * ProductAttribute constructor.
     * @param ObjectManagerInterface $interface
     */
    public function __construct(
        ObjectManagerInterface $interface
    )
    {
        $this->_objectManager = $interface;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $factory */
        $factory = $this->_objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory');

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes */
        $attributes = $factory->create();


        $stores = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStores();
        $defaultStore = null;
        /** @var Store $store */
        foreach ($stores as $store) {
            if (isset($defaultStore) == false) {
                $defaultStore = $store;
                break;
            }
        }

        $attributeOptions = array(array(
            'label' => __('-- Please Select --'),
            'value' => ''
        ));

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $read */
        $read = $attributes->getConnection();
        $columnResults = $read->query('DESCRIBE `' . $read->getTableName('catalog_product_flat') . '_' . $defaultStore->getId() . '`;');
        $flatColumns = array();
        while ($row = $columnResults->fetch()) {
            $flatColumns[] = $row['Field'];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if (
                $attribute->getIsUserDefined() == 0
                || $attribute->getUsedInProductListing() == 0
                || in_array($attribute->getAttributeCode(), $flatColumns) == false
            )
                continue;
            $attributeOptions[] = array(
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            );
        }

        return $attributeOptions;
    }

}