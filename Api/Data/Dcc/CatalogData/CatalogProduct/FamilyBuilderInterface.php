<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct;

/**
 * Interface FamilyBuilderInterface
 *
 * @package Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct
 */
interface FamilyBuilderInterface
{
    /**
     * @param null|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product $product
     * @param string                                                                         $familyCode
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface
     */
    public function build($product, $familyCode);
}
