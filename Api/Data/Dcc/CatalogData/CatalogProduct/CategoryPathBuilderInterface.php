<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct;

/**
 * Interface CategoryPathBuilderInterface
 *
 * @package Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct
 */
interface CategoryPathBuilderInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterface
     */
    public function build($category): ?CategoryPathInterface;
}
