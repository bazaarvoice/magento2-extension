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

class Environment
{
    const STAGING = 'staging';
    const PRODUCTION = 'production';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::STAGING,
                'label' => __('Staging')
            ),
            array(
                'value' => self::PRODUCTION,
                'label' => __('Production')
            ),
        );
    }
}
