<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Exception;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ProductAttribute
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class ProductAttribute implements OptionSourceInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    private $productAttributeCollectionFactory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * ProductAttribute constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection                                $resourceConnection
     * @param \Magento\Store\Model\StoreManagerInterface                               $storeManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StoreManagerInterface $storeManager,
        CollectionFactory $attributeCollectionFactory
    ) {
        $this->productAttributeCollectionFactory = $attributeCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->storeManager = $storeManager;
    }

    /**
     * @param bool $isMultiselect
     *
     * @return array
     */
    public function toOptionArray($isMultiselect = false)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $attributes */
        $attributes = $this->productAttributeCollectionFactory->create();

        $stores = $this->storeManager->getStores();
        $defaultStore = null;
        /** @var Store $store */
        foreach ($stores as $store) {
            if (isset($defaultStore) == false) {
                $defaultStore = $store;
                break;
            }
        }

        if (!$isMultiselect) {
            $attributeOptions = [
                [
                    'label' => __('-- Please Select --'),
                    'value' => '',
                ],
            ];
        } else {
            $attributeOptions = [];
        }

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $read */
        $read = $attributes->getConnection();
        try {
            $tblName = $this->resourceConnection->getTableName('catalog_product_flat');
            $columnResults = $read->query(sprintf('DESCRIBE `%s_%s`;', $tblName, $defaultStore->getId()));
            $flatColumns = [];
            while ($row = $columnResults->fetch()) {
                $flatColumns[] = $row['Field'];
            }
        } catch (Exception $e) {
            $flatColumns = [];
        }

        /** @var \Magento\Catalog\Model\ResourceModel\Attribute $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute->getIsUserDefined() == 0
                || $attribute->getUsedInProductListing() == 0
                || in_array($attribute->getAttributeCode(), $flatColumns) == false
            ) {
                continue;
            }
            $attributeOptions[] = [
                'label' => $attribute->getFrontendLabel(),
                'value' => $attribute->getAttributeCode(),
            ];
        }

        return $attributeOptions;
    }
}
