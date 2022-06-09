<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Ui\Component\Listing\Column\Bvproductindex;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PageActions
 */
class PageActions extends Column
{
    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                $id = 'X';
                if (isset($item['entity_id'])) {
                    $id = $item['entity_id'];
                }
                $item[$name]['view'] = [
                    'href'  => $this->getContext()->getUrl(
                        'adminhtml/bv_product_index/viewlog',
                        ['id' => $id]
                    ),
                    'label' => __('Edit'),
                ];
            }
        }

        return $dataSource;
    }
}
