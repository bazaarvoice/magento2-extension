<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\CategoryPathInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

/**
 * @method string getId()
 * @method string getName()
 * @method CategoryPath setId(string $id)
 * @method CategoryPath setName(string $name)
 */
class CategoryPath extends DataObject implements CategoryPathInterface
{
}
