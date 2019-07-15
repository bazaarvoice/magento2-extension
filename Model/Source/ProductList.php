<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ProductList
 *
 * @package Bazaarvoice\Connector\Model\Source
 */
class ProductList implements OptionSourceInterface
{
    const CATEGORY = 'category';
    const SEARCH = 'search';
    const UPSELL = 'upsell';
    const RELATED = 'related';
    const CROSSSELL = 'crosssell';
    const WIDGET = 'widget';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => __('None'),
            ],
            [
                'value' => self::CATEGORY,
                'label' => __('Category and Search Result Pages'),
            ],
            [
                'value' => self::UPSELL,
                'label' => __('Up-Sell Products on Product Pages'),
            ],
            [
                'value' => self::RELATED,
                'label' => __('Related Products on Product Pages'),
            ],
            [
                'value' => self::CROSSSELL,
                'label' => __('Cross-Sell Products on Cart Page'),
            ],
            [
                'value' => self::WIDGET,
                'label' => __('Product List Widget'),
            ],
        ];
    }
}
