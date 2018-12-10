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
 * @package   bvmage2
 * @author    Dennis Rogers <dennis@storefrontconsulting.com>
 * @copyright 2018 StoreFront Consulting, Inc
 * @license   http://www.storefrontconsulting.com/media/downloads/ExtensionLicense.pdf StoreFront Consulting Commercial License
 * @link      http://www.StoreFrontConsulting.com/bazaarvoice-extension/
 */

namespace Bazaarvoice\Connector\Block;


use Bazaarvoice\Connector\Helper\Data;

class Disable {
    protected $_helper;

    /**
     * Disable constructor.
     *
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->_helper = $helper;
    }

    /**
     * @return false|string
     */
    public function afterToHtml( $subject, $result ) {
        if ( $this->_helper->getConfig( 'general/enable_bv' ) ) {
            return '';
        }
        return $result;
    }


}