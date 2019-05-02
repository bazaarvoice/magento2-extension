<?php

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