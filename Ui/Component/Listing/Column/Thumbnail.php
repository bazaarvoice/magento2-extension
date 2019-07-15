<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Thumbnail
 *
 * @package Bazaarvoice\Connector\Ui\Component\Listing\Column
 */
class Thumbnail extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $value = $item[$fieldName];
                if ($value) {
                    $item[$fieldName.'_src'] = $value;
                    $item[$fieldName.'_alt'] = $fieldName;
                    $item[$fieldName.'_link'] = $value;
                    $item[$fieldName.'_orig_src'] = $value;
                }
            }
        }

        return $dataSource;
    }
}
