<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright    (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package      Bazaarvoice_Connector
 * @author       Dennis Rogers <dennis@storefrontconsulting.com>
 */

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