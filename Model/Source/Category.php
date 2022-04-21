<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Bazaarvoice\Connector\Model\Feed\ProductFeed;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Category
 */
class Category extends AbstractSource
{
    /**
     * @var array
     */
    private $categories = [];
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    private $categoryCollection;

    /**
     * Category constructor.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $categoryCollection
     */
    public function __construct(
        Collection $categoryCollection
    ) {
        $this->categoryCollection = $categoryCollection;
    }

    /**
     * @param int|string $value
     *
     * @return bool|string
     */
    public function getOptionText($value)
    {
        return $value;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array_merge([['value' => '', 'label' => __('-- Please Select --')]], $this->getCategories());
    }

    /**
     * @return array
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }

    /**
     * @return array
     */
    public function getFlatColumns()
    {
        return [
            ProductFeed::CATEGORY_EXTERNAL_ID => [
                'unsigned' => false,
                'default'  => null,
                'extra'    => null,
                'type'     => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment'  => 'Bazaarvoice Category ID',
            ],
        ];
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getCategories()
    {
        if (!$this->categories) {
            $this->categories = [];
            $this->categoryCollection->addAttributeToSelect('name');
            $this->categoryCollection->addAttributeToFilter('level', ['gt' => 1]);
            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($this->categoryCollection as $category) {
                $names = [];
                foreach ($category->getParentCategories() as $parent) {
                    $names[$parent->getId()] = $parent->getName();
                }
                $names[$category->getId()] = $category->getName();
                $name = implode('/', $names);
                $this->categories[] = [
                    'value' => $category->getId(),
                    'label' => $name,
                ];
            }
        }

        return $this->categories;
    }
}
