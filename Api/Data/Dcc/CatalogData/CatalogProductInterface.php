<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Api\Data\Dcc\CatalogData;

/**
 * @method string getProductId()
 * @method string getProductName()
 * @method string getProductDescription()
 * @method string getProductImageUrl()
 * @method string getProductPageUrl()
 * @method string getBrandId()
 * @method string getBrandName()
 * @method array  getCategoryPath()
 * @method array  getUpcs()
 * @method array  getManufacturerPartNumbers()
 * @method array  getEans()
 * @method array  getIsbns()
 * @method array  getModelNumbers()
 * @method array  getFamilies()
 * @method string getFamily()
 * @method bool   getInactive()
 * @method CatalogProductInterface setProductId(string $productId)
 * @method CatalogProductInterface setProductName(string $productName)
 * @method CatalogProductInterface setProductDescription(string $productDescription)
 * @method CatalogProductInterface setProductImageUrl(string $productImageUrl)
 * @method CatalogProductInterface setProductPageUrl(string $productPageUrl)
 * @method CatalogProductInterface setBrandId(string $brandId)
 * @method CatalogProductInterface setBrandName(string $brandName)
 * @method CatalogProductInterface setCategoryPath(array $categoryPath)
 * @method CatalogProductInterface setUpcs(array $upcs)
 * @method CatalogProductInterface setManufacturerPartNumbers(array $manufacturerPartNumbers)
 * @method CatalogProductInterface setEans(array $eans)
 * @method CatalogProductInterface setIsbns(array $isbns)
 * @method CatalogProductInterface setModelNumbers(array $modelNumbers)
 * @method CatalogProductInterface setFamilies(array $families)
 * @method CatalogProductInterface setFamily(string $family)
 * @method CatalogProductInterface setInactive(bool $inactive)
 */
interface CatalogProductInterface
{
    /**
     * @param  string     $key
     * @param  string|int $index
     * @return mixed
     */
    public function getData($key = '', $index = null);
}
