<?php
declare(strict_types=1);

namespace Bazaarvoice\Connector\Model\ResourceModel\Index;

use Bazaarvoice\Connector\Model\Index as IndexModel;
use Bazaarvoice\Connector\Model\ResourceModel\Index as IndexResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @package Bazaarvoice\Connector\Model\ResourceModel\Index
 */
class Collection extends AbstractCollection
{
    /**
     * @param \Magento\Store\Api\Data\StoreInterface $store
     *
     * @return $this
     */
    public function setStore($store)
    {
        $this->addFieldToFilter('store_id', $store->getId());

        return $this;
    }

    protected function _construct()
    {
        $this->_init(IndexModel::class, IndexResourceModel::class);
    }
}
