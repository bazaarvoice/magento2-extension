<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

use Bazaarvoice\Connector\Model\Source\ProductList;

/**
 * Class Crosssell
 *
 * @package Bazaarvoice\Connector\Plugin\ProductList
 */
class Crosssell extends Item
{
    protected $type = ProductList::CROSSSELL;
}
