<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Dcc\CatalogData\CatalogProduct;

use Bazaarvoice\Connector\Api\Data\Dcc\CatalogData\CatalogProduct\FamilyInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

/**
 * @method string getId()
 * @method bool getExpand()
 * @method array getMembers()
 * @method Family setId(string $id)
 * @method Family setExpand(bool $expand)
 * @method Family setMembers(array $members)
 */
class Family extends DataObject implements FamilyInterface
{
}
