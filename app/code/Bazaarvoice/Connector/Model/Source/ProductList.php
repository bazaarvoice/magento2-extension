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

class ProductList
{
    const CATEGORY = 'category';
    const SEARCH = 'search';
    const UPSELL = 'upsell';
    const RELATED = 'related';
    const CROSSSELL = 'crosssell';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::CATEGORY,
                'label' => __('Category Pages')
            ),
            array(
                'value' => self::SEARCH,
                'label' => __('Search Result Pages')
            ),
            array(
                'value' => self::UPSELL,
                'label' => __('Upsells on Product Pages')
            ),
            array(
                'value' => self::RELATED,
                'label' => __('Related Products on Product Pages')
            ),
            array(
                'value' => self::CROSSSELL,
                'label' => __('Cross Sells on Cart Page')
            ),
        );
    }
}