<?php

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