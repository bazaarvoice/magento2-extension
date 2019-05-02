<?php

namespace Bazaarvoice\Connector\Ui\Component\Listing\DataProviders\Bv\Product;

class Index extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Bazaarvoice\Connector\Model\ResourceModel\Index\Collection\Factory $collectionFactory,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
    }
}
