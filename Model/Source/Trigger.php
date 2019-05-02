<?php

namespace Bazaarvoice\Connector\Model\Source;

class Trigger
{
    const PURCHASE = 'purchase';
    const SHIPPING = 'shipping';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::SHIPPING,
                'label' => __('Shipping')
            ),
            array(
                'value' => self::PURCHASE,
                'label' => __('Purchase')
            )
        );
    }
}