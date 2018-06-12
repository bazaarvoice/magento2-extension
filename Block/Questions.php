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

namespace Bazaarvoice\Connector\Block;

use \Bazaarvoice\Connector\Helper\Seosdk;

class Questions extends Reviews
{

    /**
     * Get complete BV SEO Content
     * @return string
     * @throws \Exception
     */
    public function getSEOContent()
    {
        $this->bvLogger->debug( __CLASS__ . ' getSEOContent');
        if ($this->getIsEnabled()) {
            $params = $this->_getParams();
            $bv = new Seosdk($params);
            $seoContent = $bv->questions->getContent();
            if($this->getConfig('general/environment') == 'staging')
                $seoContent .= '<!-- BV Reviews SEO Parameters: ' . json_encode($params) . '-->';
            return $seoContent;
        }
        return '';
    }

}