<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc;

/**
 * Interface CatalogDataBuilderInterface
 *
 * @package Bazaarvoice\Connector\Api\Data\Dcc
 */
interface CatalogDataBuilderInterface
{
    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface|\Magento\Catalog\Model\Product      $product
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface
     */
    public function build($product);
}
