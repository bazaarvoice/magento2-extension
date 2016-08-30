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
namespace Bazaarvoice\Connector\Model\Backend;

class Remote extends \Magento\Framework\App\Config\Value
{
    /**
     * @param string $value
     * @return string
     */
    public function setValue($value)
    {
        if ($value == '') {
            if ($this->getPath() == 'bazaarvoice/feeds/product_filename')
                $value = 'productfeed.xml';
            elseif ($this->getPath() == 'bazaarvoice/feeds/product_path')
                $value = '/import-inbox';
        }
        return parent::setValue($value);
    }

}