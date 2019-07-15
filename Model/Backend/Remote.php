<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\Backend;

use Magento\Framework\App\Config\Value;

/**
 * Class Remote
 *
 * @package Bazaarvoice\Connector\Model\Backend
 */
class Remote extends Value
{
    /**
     * @param string $value
     *
     * @return string
     */
    public function setValue($value)
    {
        if ($value == '') {
            if ($this->getPath() == 'bazaarvoice/feeds/product_filename') {
                $value = 'productfeed.xml';
            } elseif ($this->getPath() == 'bazaarvoice/feeds/product_path') {
                $value = '/import-inbox';
            }
        }

        return parent::setValue($value);
    }
}
