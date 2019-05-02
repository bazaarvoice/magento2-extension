<?php

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
        try {
            $tblName = $this->_objectManager->get('\Magento\Framework\App\ResourceConnection')->getTableName('catalog_product_flat');
            $columnResults = $read->query(sprintf('DESCRIBE `%s_%s`;', $tblName, $defaultStore->getId()));
            $flatColumns = array();
            while ($row = $columnResults->fetch()) {
                $flatColumns[] = $row['Field'];
            }
        } Catch (\Exception $e) {
            $flatColumns = array();
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
