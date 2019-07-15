<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc;

/**
 * @method array  getCatalogProducts()
 * @method string getLocale()
 * @method CatalogDataInterface setCatalogProducts(array $catalogProducts)
 * @method CatalogDataInterface setLocale(string $locale)
 */
interface CatalogDataInterface
{
    /**
     * @param string     $key
     * @param string|int $index
     * @return mixed
     */
    public function getData($key = '', $index = null);
}
