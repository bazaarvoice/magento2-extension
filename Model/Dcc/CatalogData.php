<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

/**
 * @method string getLocale()
 * @method CatalogData setLocale(string $locale)
 */
class CatalogData extends DataObject implements CatalogDataInterface
{
    /**
     * @return array|mixed
     */
    public function getCatalogProducts()
    {
        return $this->getData('catalogProducts');
    }

    /**
     * @param array $catalogProducts
     *
     * @return \Bazaarvoice\Connector\Api\Data\Dcc\CatalogDataInterface|\Bazaarvoice\Connector\Model\Dcc\CatalogData
     */
    public function setCatalogProducts(array $catalogProducts)
    {
        return $this->setData('catalogProducts', $catalogProducts);
    }
}
