<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Plugin\ProductList;

use Bazaarvoice\Connector\Model\Source\ProductList;

/**
 * Class Upsell
 *
 * @package Bazaarvoice\Connector\Plugin\ProductList
 */
class Upsell extends Item
{
    /**
     * @var string
     */
    protected $type = ProductList::UPSELL;
}
