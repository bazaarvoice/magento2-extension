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

class SftpHost
{
    const BASE = 'sftp';
    const EUROPE = 'sftp7';

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::BASE,
                'label' => __('Non-European Clients (C1-C6)')
            ),
            array(
                'value' => self::EUROPE,
                'label' => __('European Clients (C7)')
            ),
        );
    }
}
