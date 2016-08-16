<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to commercial source code license
 * of StoreFront Consulting, Inc.
 *
 * @copyright   (C)Copyright 2016 StoreFront Consulting, Inc (http://www.StoreFrontConsulting.com/)
 * @package     Bazaarvoice_Connector
 * @author      Dennis Rogers <dennis@storefrontconsulting.com>
 */

namespace Bazaarvoice\Connector\Model\ResourceModel;


class Index extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('bazaarvoice_index_product', 'entity_id');
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function loadBy($attributes)
    {
        $adapter = $this->_resources->getConnection('core_read');
        $where   = array();
        foreach ($attributes as $attributeCode=> $value) {
            $where[] = sprintf('%s=:%s', $attributeCode, $attributeCode);
        }
        $select = $adapter->select()
            ->from($this->getMainTable())
            ->where(implode(' AND ', $where));

        $binds = $attributes;

        return $adapter->fetchRow($select, $binds);
    }
}