<?php

namespace Bazaarvoice\Connector\Api\Data;

interface IndexInterface
{
    /**
     * @param      $productId
     * @param      $storeId
     * @param null $scope
     *
     * @return \Bazaarvoice\Connector\Api\Data\IndexInterface
     */
    public function loadByStore($productId, $storeId, $scope = null);
}
