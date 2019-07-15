<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

use Bazaarvoice\Connector\Model\Source\ProductList;

/**
 * Class Related
 *
 * @package Bazaarvoice\Connector\Plugin\ProductList
 */
class Related extends Item
{
    protected $type = ProductList::RELATED;
}
