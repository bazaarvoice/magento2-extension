<?php

namespace Bazaarvoice\Connector\Model\ResourceModel\Index;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @param \Magento\Store\Model\Store $store
     * @return $this
     */
    public function setStore($store)
    {
        $this->addFieldToFilter('store_id', $store->getId());
        return $this;
    }

    protected function _construct()
    {
        $this->_init('Bazaarvoice\Connector\Model\Index', 'Bazaarvoice\Connector\Model\ResourceModel\Index');
    }


}
