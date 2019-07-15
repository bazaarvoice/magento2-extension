<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Ui\Component\Listing\DataProviders\Bv\Product;

use Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

/**
 * Class Index
 *
 * @package Bazaarvoice\Connector\Ui\Component\Listing\DataProviders\Bv\Product
 */
class Index extends AbstractDataProvider
{
    /**
     * @param string                                                              $name
     * @param string                                                              $primaryFieldName
     * @param string                                                              $requestFieldName
     * @param \Bazaarvoice\Connector\Model\ResourceModel\Index\CollectionFactory $collectionFactory
     * @param array                                                               $meta
     * @param array                                                               $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }
}
