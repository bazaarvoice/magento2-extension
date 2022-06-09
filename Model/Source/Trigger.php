<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Trigger
 */
class Trigger implements OptionSourceInterface
{
    const PURCHASE = 'purchase';
    const SHIPPING = 'shipping';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHIPPING,
                'label' => __('Shipping'),
            ],
            [
                'value' => self::PURCHASE,
                'label' => __('Purchase'),
            ],
        ];
    }
}
