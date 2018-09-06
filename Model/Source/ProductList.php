<?php
/**
 * StoreFront Bazaarvoice Extension for Magento
 *
 * PHP Version 5
 *
 * LICENSE: This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @category  SFC
 * @package   Bazaarvoice_Ext
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2016 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Model\Source;

class ProductList
{
    const CATEGORY = 'category';
    const SEARCH = 'search';
    const UPSELL = 'upsell';
    const RELATED = 'related';
    const CROSSSELL = 'crosssell';
    const WIDGET = 'widget';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => '',
                'label' => __('None')
            ),
            array(
                'value' => self::CATEGORY,
                'label' => __('Category and Search Result Pages')
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
	        array(
		        'value' => self::WIDGET,
		        'label' => __('Product List Widget')
	        ),
        );
    }
}