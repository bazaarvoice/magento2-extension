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

namespace Bazaarvoice\Connector\Model\Backend;

class Remote extends \Magento\Framework\App\Config\Value
{

    /**
     * @param string $value
     * @return string
     */
    public function setValue($value)
    {
        if($value == '') {
            if($this->getPath() == 'bazaarvoice/feeds/product_filename')
                $value = 'productfeed.xml';
            elseif($this->getPath() == 'bazaarvoice/feeds/product_path')
                $value = '/import-inbox';
        }
        return parent::setValue($value);
    }

}