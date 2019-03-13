<?php

namespace Bazaarvoice\Connector\Model;

interface IndexInterface
{
    /**
     * @param      $productId
     * @param      $storeId
     * @param null $scope
     *
     * @return mixed
     */
    public function loadByStore($productId, $storeId, $scope = null);
}
