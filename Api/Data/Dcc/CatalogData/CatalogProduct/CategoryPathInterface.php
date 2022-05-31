<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct;

/**
 * @method string getId()
 * @method string getName()
 * @method CategoryPathInterface setId(string $id)
 * @method CategoryPathInterface setName(string $name)
 */
interface CategoryPathInterface
{
    /**
     * @param  string     $key
     * @param  string|int $index
     * @return mixed
     */
    public function getData($key = '', $index = null);
}
