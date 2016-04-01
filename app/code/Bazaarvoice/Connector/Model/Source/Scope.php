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

class Scope
{
    const WEBSITE = 'website';
    const STORE_GROUP = 'group';
    const STORE_VIEW = 'view';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::WEBSITE,
                'label' => __('Magento Website')
            ),
            array(
                'value' => self::STORE_GROUP,
                'label' => __('Magento Store / Store Group')
            ),
            array(
                'value' => self::STORE_VIEW,
                'label' => __('Magento Store View')
            ),
        );
    }
}